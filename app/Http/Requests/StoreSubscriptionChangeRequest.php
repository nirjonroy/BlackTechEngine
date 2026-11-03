<?php

namespace App\Http\Requests;

use App\Enums\BillingInterval;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace && $workspace->isOwnedBy($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'pricing_plan_id' => ['required', 'integer', Rule::exists('pricing_plans', 'id')->where('is_active', true)->where('is_billable', true)],
            'billing_interval' => ['required', Rule::enum(BillingInterval::class)],
        ];
    }

    protected function failedAuthorization(): void
    {
        abort(404);
    }
}
