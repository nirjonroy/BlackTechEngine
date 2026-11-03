<?php

namespace App\Http\Requests\Admin;

use App\Enums\EntitlementKey;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePlanEntitlementsRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required_if:is_billable,true', 'nullable', 'string', 'max:64', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', Rule::unique('pricing_plans', 'code')->ignore($this->route('pricingPlan'))],
            'billing_currency' => ['required', 'string', 'size:3', Rule::in(['USD', 'BDT', 'EUR', 'GBP'])],
            'monthly_price_minor' => ['nullable', 'integer', 'min:0'],
            'annual_price_minor' => ['nullable', 'integer', 'min:0'],
            'trial_days' => ['required', 'integer', 'between:0,365'],
            'is_billable' => ['required', 'boolean'],
            'entitlements' => ['required', 'array:'.implode(',', array_column(EntitlementKey::cases(), 'value'))],
            'entitlements.*' => ['required', 'array:enabled,unlimited,limit,unit'],
            'entitlements.*.enabled' => ['required', 'boolean'],
            'entitlements.*.unlimited' => ['required', 'boolean'],
            'entitlements.*.limit' => ['nullable', 'integer', 'min:0'],
            'entitlements.*.unit' => ['nullable', 'string', 'max:32'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (EntitlementKey::cases() as $key) {
                $value = $this->input('entitlements.'.$key->value, []);

                if (! $key->isFeature()
                    && ($value['enabled'] ?? false)
                    && ! ($value['unlimited'] ?? false)
                    && ! isset($value['limit'])) {
                    $validator->errors()->add('entitlements.'.$key->value.'.limit', 'Enter a limit or mark this entitlement as unlimited.');
                }
            }
        }];
    }

    /** @return array<string, array{enabled: bool, unlimited: bool, limit: int|null, unit: string|null}> */
    public function entitlements(): array
    {
        return collect(EntitlementKey::cases())->mapWithKeys(function (EntitlementKey $key): array {
            $value = $this->validated('entitlements.'.$key->value);

            return [$key->value => [
                'enabled' => (bool) $value['enabled'],
                'unlimited' => (bool) $value['unlimited'],
                'limit' => isset($value['limit']) ? (int) $value['limit'] : null,
                'unit' => $value['unit'] ?? null,
            ]];
        })->all();
    }

    protected function prepareForValidation(): void
    {
        $entitlements = collect($this->input('entitlements', []))->map(function (mixed $value): mixed {
            if (! is_array($value)) {
                return $value;
            }

            $value['enabled'] = filter_var($value['enabled'] ?? false, FILTER_VALIDATE_BOOL);
            $value['unlimited'] = filter_var($value['unlimited'] ?? false, FILTER_VALIDATE_BOOL);

            return $value;
        })->all();

        $this->merge([
            'code' => $this->filled('code') ? str($this->string('code')->toString())->trim()->lower()->toString() : null,
            'billing_currency' => strtoupper($this->string('billing_currency')->toString()),
            'is_billable' => $this->boolean('is_billable'),
            'entitlements' => $entitlements,
        ]);
    }
}
