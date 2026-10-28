<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminAiWorkloadDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage ai models') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $workloadRoute = $this->route('aiWorkloadRoute');

        return [
            'workload_key' => [
                'required',
                'string',
                'max:64',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('ai_workload_routes', 'workload_key')->ignore($workloadRoute),
            ],
            'primary_ai_model_id' => ['nullable', 'integer', 'exists:ai_models,id'],
            'fallback_ai_model_id' => ['nullable', 'integer', 'different:primary_ai_model_id', 'exists:ai_models,id'],
            'settings' => ['nullable', 'array', 'max:0'],
            'lock_version' => $this->isMethod('PATCH')
                ? ['required', 'integer', 'min:0']
                : ['prohibited'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allowedFields = ['workload_key', 'primary_ai_model_id', 'fallback_ai_model_id', 'settings'];

                if ($this->isMethod('PATCH')) {
                    $allowedFields[] = 'lock_version';
                }

                foreach (array_keys($this->except(['_token', '_method'])) as $field) {
                    if (! in_array($field, $allowedFields, true)) {
                        $validator->errors()->add($field, "The {$field} field is not supported for AI workload drafts.");
                    }
                }
            },
        ];
    }

    /** @return array{workload_key: string, primary_ai_model_id: int|null, fallback_ai_model_id: int|null, settings: array|null} */
    public function metadata(): array
    {
        $validated = $this->validated();

        return [
            'workload_key' => (string) $validated['workload_key'],
            'primary_ai_model_id' => isset($validated['primary_ai_model_id']) ? (int) $validated['primary_ai_model_id'] : null,
            'fallback_ai_model_id' => isset($validated['fallback_ai_model_id']) ? (int) $validated['fallback_ai_model_id'] : null,
            'settings' => $validated['settings'] ?? null,
        ];
    }

    public function lockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }

    protected function prepareForValidation(): void
    {
        $workloadKey = $this->input('workload_key');

        $this->merge([
            'workload_key' => is_string($workloadKey) ? Str::slug(Str::lower(trim($workloadKey))) : $workloadKey,
            'primary_ai_model_id' => $this->filled('primary_ai_model_id') ? $this->input('primary_ai_model_id') : null,
            'fallback_ai_model_id' => $this->filled('fallback_ai_model_id') ? $this->input('fallback_ai_model_id') : null,
        ]);
    }
}
