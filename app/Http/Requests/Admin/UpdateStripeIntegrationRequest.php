<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStripeIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $admin = $this->user('admin');

        return $admin instanceof Admin && $admin->hasRole('Super Admin', 'admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', 'string', Rule::in(['test', 'live'])],
            'publishable_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:4096'],
            'webhook_secret' => ['nullable', 'string', 'max:4096'],
            'enabled' => ['required', 'boolean'],
            'clear_secrets' => ['required', 'boolean'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->boolean('enabled') && $this->boolean('clear_secrets')) {
                $validator->errors()->add('enabled', 'Stripe cannot be enabled while its secrets are being cleared.');
            }

            foreach (array_keys($this->except(['_token', '_method'])) as $field) {
                if (! in_array($field, ['mode', 'publishable_key', 'secret_key', 'webhook_secret', 'enabled', 'clear_secrets', 'lock_version'], true)) {
                    $validator->errors()->add($field, "The {$field} field is not supported.");
                }
            }
        }];
    }
}
