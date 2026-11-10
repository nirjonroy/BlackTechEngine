<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateGeminiIntegrationRequest extends FormRequest
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
            'api_key' => ['nullable', 'string', 'max:4096'],
            'enabled' => ['required', 'boolean'],
            'clear_api_key' => ['required', 'boolean'],
            'ai_model_id' => ['nullable', 'integer', 'exists:ai_models,id'],
            'workload_lock_version' => ['nullable', 'required_with:ai_model_id', 'integer', 'min:0'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->boolean('enabled') && $this->boolean('clear_api_key')) {
                $validator->errors()->add('enabled', 'Gemini cannot be enabled while its API key is being cleared.');
            }

            $this->rejectUnexpectedFields($validator, [
                'api_key', 'enabled', 'clear_api_key', 'ai_model_id', 'workload_lock_version', 'lock_version',
            ]);
        }];
    }

    /** @param list<string> $allowed */
    private function rejectUnexpectedFields(Validator $validator, array $allowed): void
    {
        foreach (array_keys($this->except(['_token', '_method'])) as $field) {
            if (! in_array($field, $allowed, true)) {
                $validator->errors()->add($field, "The {$field} field is not supported.");
            }
        }
    }
}
