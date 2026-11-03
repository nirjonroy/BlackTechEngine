<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingInterval;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionAdjustmentRequest;
use App\Models\PricingPlan;
use App\Models\Workspace;
use App\Services\Subscriptions\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class SubscriptionAdjustmentController extends Controller
{
    public function __construct(private readonly SubscriptionManager $subscriptions) {}

    public function __invoke(StoreSubscriptionAdjustmentRequest $request, Workspace $workspace): RedirectResponse
    {
        $action = $request->string('action')->toString();
        $subscription = $workspace->subscription()->first();

        if ($action === 'grant') {
            $plan = PricingPlan::query()->findOrFail($request->integer('pricing_plan_id'));
            $subscription = $this->subscriptions->grant(
                $workspace,
                $plan,
                $request->user('admin'),
                BillingInterval::from($request->string('billing_interval')->toString()),
                $request->boolean('trial'),
            );
        } elseif ($action === 'change_plan') {
            abort_if($subscription === null, 404);
            $plan = PricingPlan::query()->findOrFail($request->integer('pricing_plan_id'));
            $subscription = $this->subscriptions->changePlan(
                $subscription,
                $plan,
                $request->user('admin'),
                BillingInterval::from($request->string('billing_interval')->toString()),
                $request->integer('lock_version'),
            );
        } else {
            abort_if($subscription === null, 404);
            $subscription = $this->subscriptions->cancel(
                $subscription,
                $request->user('admin'),
                $request->integer('lock_version'),
                $action === 'cancel_period_end',
            );
        }

        return Redirect::route('admin.subscriptions.show', $subscription)
            ->with('success', 'The subscription adjustment was recorded with an audit history.');
    }
}
