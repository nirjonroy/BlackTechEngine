<?php

namespace App\Http\Requests;

use App\Enums\BillingInterval;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace && $workspace->isOwnedBy($this->user());
    }

    public function rules(): array
    {
        return [
            'pricing_plan_id' => [
                'required',
                'integer',
                Rule::exists('pricing_plans', 'id')->where('is_active', true)->where('is_billable', true),
            ],
            'billing_interval' => ['required', Rule::in([
                BillingInterval::Monthly->value,
                BillingInterval::Annual->value,
            ])],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function failedAuthorization(): void
    {
        abort(404);
    }
}
