<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __invoke(Request $request): View
    {
        $workspaces = $request->user()->accessibleWorkspaces()
            ->with(['subscription.plan', 'subscription.usageCounters', 'subscription.invoices'])
            ->orderBy('name')
            ->get();
        $workspace = $request->filled('workspace')
            ? $workspaces->firstWhere('id', $request->integer('workspace'))
            : ($workspaces->firstWhere('id', $request->user()->active_workspace_id) ?? $workspaces->first());

        abort_if($request->filled('workspace') && $workspace === null, 404);

        $subscription = $workspace?->subscription;
        $plans = PricingPlan::query()
            ->where('is_active', true)
            ->where('is_billable', true)
            ->with('entitlements')
            ->inDisplayOrder()
            ->get();

        return view('subscriptions.index', compact('workspaces', 'workspace', 'subscription', 'plans'));
    }
}
