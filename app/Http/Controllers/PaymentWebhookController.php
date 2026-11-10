<?php

namespace App\Http\Controllers;

use App\Services\Billing\Contracts\PaymentGateway;
use App\Services\Billing\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;
use UnexpectedValueException;
use ValueError;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentWebhookService $webhooks,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(config('subscriptions.checkout_enabled') && $this->gateway->isConfigured(), 404);

        try {
            $event = $this->gateway->verifyWebhook($request->getContent(), $request->headers->all());
        } catch (JsonException|UnexpectedValueException|ValueError) {
            abort(400, 'Invalid payment webhook.');
        }

        abort_unless($event->provider === $this->gateway->name(), 400);
        try {
            $webhook = $this->webhooks->handle($event);
        } catch (ValidationException) {
            abort(400, 'Invalid payment webhook.');
        }

        return response()->json(
            ['received' => true],
            in_array($webhook->processing_status, ['rejected', 'requires_review'], true) ? 202 : 200,
        );
    }
}
