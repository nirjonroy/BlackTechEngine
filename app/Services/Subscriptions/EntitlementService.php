<?php

namespace App\Services\Subscriptions;

use App\Enums\EntitlementKey;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Validation\ValidationException;

class EntitlementService
{
    public function enforcementEnabled(): bool
    {
        return (bool) config('subscriptions.enforcement_enabled', false);
    }

    public function currentSubscription(Workspace $workspace): ?WorkspaceSubscription
    {
        return WorkspaceSubscription::query()->whereBelongsTo($workspace)->first();
    }

    public function decision(Workspace $workspace, EntitlementKey|string $key, int $used = 0): EntitlementDecision
    {
        if (! $this->enforcementEnabled()) {
            return new EntitlementDecision(true, true, null, $used, null, 'enforcement_disabled');
        }

        $subscription = $this->currentSubscription($workspace);

        if ($subscription === null || ! $subscription->isEntitled()) {
            return new EntitlementDecision(false, false, 0, $used, 0, 'no_active_subscription');
        }

        $keyValue = $key instanceof EntitlementKey ? $key->value : $key;
        $entitlement = data_get($subscription->entitlement_snapshot, 'entitlements.'.$keyValue);

        if (! is_array($entitlement) || ! ($entitlement['enabled'] ?? false)) {
            return new EntitlementDecision(false, false, 0, $used, 0, 'feature_not_included');
        }

        if ($entitlement['unlimited'] ?? false) {
            return new EntitlementDecision(true, true, null, $used, null, 'unlimited');
        }

        $limit = isset($entitlement['limit']) ? (int) $entitlement['limit'] : null;

        if ($limit === null) {
            return new EntitlementDecision(true, true, null, $used, null, 'feature_enabled');
        }

        $remaining = max(0, $limit - $used);

        return new EntitlementDecision($used < $limit, true, $limit, $used, $remaining, $used < $limit ? 'available' : 'quota_exhausted');
    }

    public function assertFeature(Workspace $workspace, EntitlementKey $key): void
    {
        $decision = $this->decision($workspace, $key);

        if (! $decision->allowed) {
            throw ValidationException::withMessages(['subscription' => 'Your current subscription does not include this feature.']);
        }
    }

    public function assertBrandCreationAllowed(Workspace $workspace): void
    {
        if (! $this->enforcementEnabled()) {
            return;
        }

        $used = $workspace->brands()->where('is_active', true)->count();
        $decision = $this->decision($workspace, EntitlementKey::MaximumBrands, $used);

        if (! $decision->allowed) {
            throw ValidationException::withMessages(['subscription' => 'The active-brand limit for this workspace has been reached.']);
        }
    }

    public function assertWorkspaceCreationAllowed(User $user): void
    {
        if (! $this->enforcementEnabled()) {
            return;
        }

        $ownedCount = $user->ownedWorkspaces()->count();

        if ($ownedCount === 0) {
            return;
        }

        $subscriptions = WorkspaceSubscription::query()
            ->whereHas('workspace', fn ($query) => $query->where('owner_id', $user->getKey()))
            ->get();
        $limits = $subscriptions->map(function (WorkspaceSubscription $subscription): ?int {
            $entitlement = data_get($subscription->entitlement_snapshot, 'entitlements.'.EntitlementKey::MaximumWorkspaces->value);

            if (! is_array($entitlement) || ! ($entitlement['enabled'] ?? false)) {
                return 0;
            }

            return ($entitlement['unlimited'] ?? false) ? null : (int) ($entitlement['limit'] ?? 0);
        });

        if ($limits->containsStrict(null)) {
            return;
        }

        if ($ownedCount >= (int) ($limits->max() ?? 0)) {
            throw ValidationException::withMessages(['subscription' => 'The workspace limit for this account has been reached.']);
        }
    }
}
