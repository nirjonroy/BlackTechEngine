<?php

namespace App\Http\Controllers;

use App\Enums\BillingInterval;
use App\Http\Requests\StoreSubscriptionCancellationRequest;
use App\Http\Requests\StoreSubscriptionChangeRequest;
use App\Models\PricingPlan;
use App\Models\Workspace;
use App\Services\Subscriptions\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class SubscriptionRequestController extends Controller
{
    public function __construct(private readonly SubscriptionManager $subscriptions) {}

    public function change(StoreSubscriptionChangeRequest $request, Workspace $workspace): RedirectResponse
    {
        $subscription = $workspace->subscription()->firstOrFail();
        Gate::authorize('requestChange', $subscription);
        $plan = PricingPlan::query()->findOrFail($request->integer('pricing_plan_id'));
        $this->subscriptions->requestPlanChange(
            $subscription,
            $plan,
            $request->user(),
            BillingInterval::from($request->string('billing_interval')->toString()),
        );

        return Redirect::route('subscriptions.index', ['workspace' => $workspace->getKey()])
            ->with('status', 'Your plan-change request was recorded for Admin review. No payment was collected.');
    }

    public function cancel(StoreSubscriptionCancellationRequest $request, Workspace $workspace): RedirectResponse
    {
        $subscription = $workspace->subscription()->firstOrFail();
        Gate::authorize('requestCancellation', $subscription);
        $this->subscriptions->requestCancellation($subscription, $request->user(), $request->string('reason')->toString());

        return Redirect::route('subscriptions.index', ['workspace' => $workspace->getKey()])
            ->with('status', 'Your cancellation request was recorded. The subscription has not been cancelled automatically.');
    }
}
