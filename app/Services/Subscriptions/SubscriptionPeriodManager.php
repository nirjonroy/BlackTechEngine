<?php

namespace App\Services\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\WorkspaceSubscription;
use Carbon\CarbonImmutable;

class SubscriptionPeriodManager
{
    public function __construct(private readonly SubscriptionManager $subscriptions) {}

    /**
     * @return array{inspected: int, advanced: int, past_due: int, cancelled: int, expired: int}
     */
    public function processDue(int $limit = 100, ?CarbonImmutable $asOf = null): array
    {
        $asOf ??= CarbonImmutable::now('UTC')->startOfSecond();
        $subscriptionIds = WorkspaceSubscription::query()
            ->whereIn('status', [
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Trialing->value,
                SubscriptionStatus::PastDue->value,
            ])
            ->where(function ($query) use ($asOf): void {
                $query->where('current_period_ends_at', '<=', $asOf)
                    ->orWhere(function ($query) use ($asOf): void {
                        $query->where('status', SubscriptionStatus::Trialing->value)
                            ->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '<=', $asOf);
                    });
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $result = [
            'inspected' => $subscriptionIds->count(),
            'advanced' => 0,
            'past_due' => 0,
            'cancelled' => 0,
            'expired' => 0,
        ];

        foreach ($subscriptionIds as $subscriptionId) {
            $action = $this->subscriptions->processDuePeriod($subscriptionId, $asOf);

            if ($action !== null) {
                $result[$action]++;
            }
        }

        return $result;
    }
}
