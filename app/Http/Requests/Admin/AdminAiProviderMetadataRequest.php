<?php

namespace App\Http\Requests\Admin;

use App\Models\AiProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminAiProviderMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage ai models') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $provider = $this->route('aiProvider');

        return [
            'key' => ['required', 'string', 'max:64', Rule::unique('ai_providers', 'key')->ignore($provider)],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'request_timeout_seconds' => ['required', 'integer', 'min:1', 'max:'.AiProvider::MAX_REQUEST_TIMEOUT_SECONDS],
            'retry_limit' => ['required', 'integer', 'min:0', 'max:'.AiProvider::MAX_RETRY_LIMIT],
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
                $allowedFields = ['key', 'display_name', 'description', 'request_timeout_seconds', 'retry_limit'];

                if ($this->isMethod('PATCH')) {
                    $allowedFields[] = 'lock_version';
                }

                foreach (array_keys($this->except(['_token', '_method'])) as $field) {
                    if (! in_array($field, $allowedFields, true)) {
                        $validator->errors()->add($field, "The {$field} field is not supported for AI provider metadata.");
                    }
                }
            },
        ];
    }

    /** @return array{key: string, display_name: string, description: string|null, request_timeout_seconds: int, retry_limit: int} */
    public function metadata(): array
    {
        $validated = $this->validated();

        return [
            'key' => (string) $validated['key'],
            'display_name' => (string) $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'request_timeout_seconds' => (int) $validated['request_timeout_seconds'],
            'retry_limit' => (int) $validated['retry_limit'],
        ];
    }

    public function lockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }

    protected function prepareForValidation(): void
    {
        $key = $this->input('key');
        $displayName = $this->input('display_name');
        $description = $this->input('description');

        $this->merge([
            'key' => is_string($key) ? Str::slug(Str::lower(trim($key))) : $key,
            'display_name' => is_string($displayName) ? trim($displayName) : $displayName,
            'description' => is_string($description) && filled(trim($description)) ? trim($description) : null,
        ]);
    }
}
