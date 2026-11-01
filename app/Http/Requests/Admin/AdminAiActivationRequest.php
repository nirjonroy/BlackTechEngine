<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminAiActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user('admin');

        return $admin instanceof Admin
            && $admin->can('manage ai models')
            && $admin->hasRole('Super Admin', 'admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'state' => ['required', 'string', Rule::in(['enabled', 'disabled'])],
            'lock_version' => ['required', 'integer', 'min:0'],
            'confirm' => ['accepted'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_keys($this->except(['_token', '_method'])) as $field) {
                    if (! in_array($field, ['state', 'lock_version', 'confirm'], true)) {
                        $validator->errors()->add($field, "The {$field} field is not supported for AI activation.");
                    }
                }
            },
        ];
    }

    public function enable(): bool
    {
        return $this->validated('state') === 'enabled';
    }

    public function lockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }

    public function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = $this->user('admin');

        return $admin;
    }
}
