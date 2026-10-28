<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminAiProviderVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('verify ai providers') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'confirm_verification' => ['required', 'accepted'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_keys($this->except(['_token'])) as $field) {
                    if (! in_array($field, ['confirm_verification', 'lock_version'], true)) {
                        $validator->errors()->add($field, "The {$field} field is not supported for AI provider verification.");
                    }
                }
            },
        ];
    }

    public function lockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }
}
