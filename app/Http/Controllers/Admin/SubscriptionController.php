<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
            'pricing_plan_id' => ['nullable', 'integer', Rule::exists('pricing_plans', 'id')],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $subscriptions = WorkspaceSubscription::query()
            ->with(['workspace.owner', 'plan'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('pricing_plan_id'), fn ($query) => $query->where('pricing_plan_id', $request->integer('pricing_plan_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->whereHas('workspace', fn ($workspace) => $workspace
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('owner', fn ($owner) => $owner->where('email', 'like', '%'.$search.'%')));
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'plans' => PricingPlan::query()->where('is_billable', true)->orderBy('name')->get(),
            'statuses' => SubscriptionStatus::cases(),
            'unsubscribedWorkspaces' => Workspace::query()
                ->doesntHave('subscription')
                ->with('owner')
                ->orderBy('name')
                ->limit(50)
                ->get(),
        ]);
    }

    public function show(WorkspaceSubscription $workspaceSubscription): View
    {
        $workspaceSubscription->load([
            'workspace.owner',
            'plan.entitlements',
            'histories.actorUser',
            'histories.actorAdmin',
            'usageCounters',
            'invoices.items',
            'invoices.payments',
        ]);

        return view('admin.subscriptions.show', [
            'subscription' => $workspaceSubscription,
            'plans' => PricingPlan::query()->where('is_active', true)->where('is_billable', true)->orderBy('name')->get(),
        ]);
    }
}
