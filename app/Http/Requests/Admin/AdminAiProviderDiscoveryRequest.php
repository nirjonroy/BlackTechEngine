<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminAiProviderDiscoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('verify ai providers') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'confirm_discovery' => ['required', 'accepted'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_keys($this->except(['_token'])) as $field) {
                    if ($field !== 'confirm_discovery') {
                        $validator->errors()->add($field, "The {$field} field is not supported for AI model discovery.");
                    }
                }
            },
        ];
    }
}
