<?php

namespace App\Http\Controllers;

use App\Enums\BillingInterval;
use App\Http\Requests\StorePaymentCheckoutRequest;
use App\Models\PricingPlan;
use App\Models\Workspace;
use App\Services\Billing\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PaymentCheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkouts) {}

    public function __invoke(StorePaymentCheckoutRequest $request, Workspace $workspace): RedirectResponse
    {
        $subscription = $workspace->subscription()->firstOrFail();
        Gate::authorize('requestChange', $subscription);
        $plan = PricingPlan::query()->findOrFail($request->integer('pricing_plan_id'));
        $session = $this->checkouts->initiate(
            $request->user(),
            $workspace,
            $plan,
            BillingInterval::from($request->string('billing_interval')->toString()),
            $request->string('idempotency_key')->toString(),
        );

        return redirect()->away($session->checkoutUrl);
    }
}
