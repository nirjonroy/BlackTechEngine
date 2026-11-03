<?php

namespace App\Http\Requests\Admin;

use App\Enums\BillingInterval;
use Illuminate\Validation\Rule;

class StoreSubscriptionAdjustmentRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['grant', 'change_plan', 'cancel_immediately', 'cancel_period_end'])],
            'pricing_plan_id' => ['required_if:action,grant,change_plan', 'nullable', 'integer', Rule::exists('pricing_plans', 'id')->where('is_active', true)->where('is_billable', true)],
            'billing_interval' => ['required_if:action,grant,change_plan', 'nullable', Rule::enum(BillingInterval::class)],
            'trial' => ['nullable', 'boolean'],
            'lock_version' => ['required_unless:action,grant', 'nullable', 'integer', 'min:0'],
            'confirmation' => ['required', 'string', Rule::in(['CONFIRM SUBSCRIPTION CHANGE'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['trial' => $this->boolean('trial')]);
    }
}
