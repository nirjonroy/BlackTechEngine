<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMailIntegrationRequest extends FormRequest
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
            'host' => ['nullable', 'string', 'max:255', 'not_regex:/[\s\/]/'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'encryption' => ['nullable', 'string', Rule::in(['tls', 'ssl'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:4096'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_address' => ['nullable', 'email:rfc', 'max:255'],
            'reply_to' => ['nullable', 'email:rfc', 'max:255'],
            'enabled' => ['required', 'boolean'],
            'clear_password' => ['required', 'boolean'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_keys($this->except(['_token', '_method'])) as $field) {
                if (! in_array($field, ['host', 'port', 'encryption', 'username', 'password', 'from_name', 'from_address', 'reply_to', 'enabled', 'clear_password', 'lock_version'], true)) {
                    $validator->errors()->add($field, "The {$field} field is not supported.");
                }
            }
        }];
    }
}
