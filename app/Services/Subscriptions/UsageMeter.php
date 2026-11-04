<?php

namespace App\Services\Subscriptions;

use App\Enums\EntitlementKey;
use App\Enums\UsageEventStatus;
use App\Models\UsageCounter;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use App\Services\Subscriptions\Exceptions\EntitlementDeniedException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UsageMeter
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    /** @param array<string, mixed> $metadata */
    public function reserve(
        Workspace $workspace,
        EntitlementKey $key,
        int $quantity,
        string $idempotencyKey,
        ?User $actor = null,
        array $metadata = [],
    ): ?UsageEvent {
        if (! $this->entitlements->enforcementEnabled()) {
            return null;
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Usage quantity must be at least one.');
        }

        return DB::transaction(function () use ($actor, $idempotencyKey, $key, $metadata, $quantity, $workspace): UsageEvent {
            $subscription = WorkspaceSubscription::query()
                ->whereBelongsTo($workspace)
                ->lockForUpdate()
                ->first();

            if ($subscription === null || ! $subscription->isEntitled()) {
                throw new EntitlementDeniedException('no_active_subscription', 'An active workspace subscription is required.');
            }

            $existing = UsageEvent::query()
                ->whereBelongsTo($workspace)
                ->where('entitlement_key', $key->value)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->quantity !== $quantity) {
                    throw new EntitlementDeniedException('idempotency_conflict', 'This request identifier was already used for different usage.');
                }

                return $existing;
            }

            [$periodStartsAt, $periodEndsAt] = $this->periodBounds($subscription);
            $counter = UsageCounter::query()->firstOrCreate([
                'workspace_subscription_id' => $subscription->getKey(),
                'entitlement_key' => $key->value,
                'period_starts_at' => $periodStartsAt,
            ], [
                'workspace_id' => $workspace->getKey(),
                'period_ends_at' => $periodEndsAt,
            ]);
            $counter = UsageCounter::query()->lockForUpdate()->findOrFail($counter->getKey());
            $this->releaseExpiredReservations($counter);
            $counter->refresh();

            $decision = $this->entitlements->decision($workspace, $key, $counter->consumed());

            if (! $decision->allowed || ($decision->remaining !== null && $quantity > $decision->remaining)) {
                throw new EntitlementDeniedException('quota_exhausted', 'The usage allowance for this billing period has been reached.');
            }

            $counter->increment('reserved', $quantity, ['lock_version' => $counter->lock_version + 1]);

            return UsageEvent::query()->create([
                'usage_counter_id' => $counter->getKey(),
                'workspace_subscription_id' => $subscription->getKey(),
                'workspace_id' => $workspace->getKey(),
                'actor_user_id' => $actor?->getKey(),
                'entitlement_key' => $key->value,
                'idempotency_key' => $idempotencyKey,
                'quantity' => $quantity,
                'status' => UsageEventStatus::Reserved,
                'metadata' => $metadata,
                'expires_at' => now()->addMinutes((int) config('subscriptions.reservation_minutes', 15)),
            ]);
        }, 3);
    }

    public function commit(?UsageEvent $event, ?string $referenceType = null, ?int $referenceId = null, ?int $actualQuantity = null): void
    {
        if ($event === null) {
            return;
        }

        $this->commitMany([[
            'event' => $event,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'actual_quantity' => $actualQuantity,
        ]]);
    }

    /**
     * @param  list<array{event: UsageEvent|null, reference_type?: string|null, reference_id?: int|null, actual_quantity?: int|null}>  $commits
     */
    public function commitMany(array $commits): void
    {
        $commits = collect($commits)
            ->filter(fn (array $commit): bool => $commit['event'] instanceof UsageEvent)
            ->sortBy(fn (array $commit): int => $commit['event']->getKey())
            ->values();

        if ($commits->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($commits): void {
            foreach ($commits as $commit) {
                $locked = UsageEvent::query()->lockForUpdate()->findOrFail($commit['event']->getKey());

                if ($locked->status === UsageEventStatus::Committed) {
                    continue;
                }

                if ($locked->status === UsageEventStatus::Released) {
                    throw new EntitlementDeniedException('reservation_released', 'This usage reservation is no longer available.');
                }

                $committedQuantity = $commit['actual_quantity'] ?? $locked->quantity;

                if ($committedQuantity < 0 || $committedQuantity > $locked->quantity) {
                    throw new InvalidArgumentException('Committed usage cannot exceed its reservation.');
                }

                $counter = UsageCounter::query()->lockForUpdate()->findOrFail($locked->usage_counter_id);
                $counter->update([
                    'reserved' => max(0, $counter->reserved - $locked->quantity),
                    'used' => $counter->used + $committedQuantity,
                    'lock_version' => $counter->lock_version + 1,
                ]);
                $metadata = $locked->metadata ?? [];
                $metadata['reserved_quantity'] = $locked->quantity;
                $locked->update([
                    'quantity' => $committedQuantity,
                    'status' => UsageEventStatus::Committed,
                    'reference_type' => $commit['reference_type'] ?? null,
                    'reference_id' => $commit['reference_id'] ?? null,
                    'metadata' => $metadata,
                    'committed_at' => now(),
                    'expires_at' => null,
                ]);
            }
        }, 3);
    }

    public function release(?UsageEvent $event, string $reason = 'operation_failed'): void
    {
        if ($event === null) {
            return;
        }

        $this->releaseMany([$event], $reason);
    }

    public function commitAndRelease(
        ?UsageEvent $committedEvent,
        ?UsageEvent $releasedEvent,
        string $releaseReason,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): void {
        DB::transaction(function () use ($committedEvent, $referenceId, $referenceType, $releaseReason, $releasedEvent): void {
            $this->commit($committedEvent, $referenceType, $referenceId);
            $this->release($releasedEvent, $releaseReason);
        }, 3);
    }

    /** @param list<UsageEvent|null> $events */
    public function releaseMany(array $events, string $reason = 'operation_failed'): void
    {
        $eventIds = collect($events)
            ->filter(fn (?UsageEvent $event): bool => $event instanceof UsageEvent)
            ->map(fn (UsageEvent $event): int => $event->getKey())
            ->unique()
            ->sort()
            ->values();

        if ($eventIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($eventIds, $reason): void {
            foreach ($eventIds as $eventId) {
                $locked = UsageEvent::query()->lockForUpdate()->findOrFail($eventId);

                if ($locked->status !== UsageEventStatus::Reserved) {
                    continue;
                }

                $counter = UsageCounter::query()->lockForUpdate()->findOrFail($locked->usage_counter_id);
                $counter->update([
                    'reserved' => max(0, $counter->reserved - $locked->quantity),
                    'lock_version' => $counter->lock_version + 1,
                ]);
                $locked->update([
                    'status' => UsageEventStatus::Released,
                    'metadata' => [...($locked->metadata ?? []), 'release_reason' => $reason],
                    'released_at' => now(),
                    'expires_at' => null,
                ]);
            }
        }, 3);
    }

    public function remaining(Workspace $workspace, EntitlementKey $key): ?int
    {
        if (! $this->entitlements->enforcementEnabled()) {
            return null;
        }

        $subscription = $this->entitlements->currentSubscription($workspace);

        if ($subscription === null) {
            return 0;
        }

        [$periodStartsAt] = $this->periodBounds($subscription);
        $used = UsageCounter::query()
            ->whereBelongsTo($subscription, 'subscription')
            ->where('entitlement_key', $key->value)
            ->where('period_starts_at', $periodStartsAt)
            ->first()?->consumed() ?? 0;

        return $this->entitlements->decision($workspace, $key, $used)->remaining;
    }

    /** @return array{0: mixed, 1: mixed} */
    private function periodBounds(WorkspaceSubscription $subscription): array
    {
        return [$subscription->current_period_starts_at, $subscription->current_period_ends_at];
    }

    private function releaseExpiredReservations(UsageCounter $counter): void
    {
        $expired = UsageEvent::query()
            ->whereBelongsTo($counter, 'counter')
            ->where('status', UsageEventStatus::Reserved->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->lockForUpdate()
            ->get();

        if ($expired->isEmpty()) {
            return;
        }

        $released = $expired->sum('quantity');
        $counter->update([
            'reserved' => max(0, $counter->reserved - $released),
            'lock_version' => $counter->lock_version + 1,
        ]);

        foreach ($expired as $event) {
            $event->update([
                'status' => UsageEventStatus::Released,
                'metadata' => [...($event->metadata ?? []), 'release_reason' => 'reservation_expired'],
                'released_at' => now(),
                'expires_at' => null,
            ]);
        }
    }
}
