<?php

namespace App\Http\Requests\Admin;

use App\Enums\AiModelCapability;
use App\Enums\AiModelModality;
use App\Models\AiModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminAiModelMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage ai models') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $model = $this->route('aiModel');

        return [
            'ai_provider_id' => ['required', 'integer', 'exists:ai_providers,id'],
            'provider_model_id' => [
                'required',
                'string',
                'max:191',
                Rule::unique('ai_models', 'provider_model_id')
                    ->where(fn ($query) => $query->where('ai_provider_id', $this->integer('ai_provider_id')))
                    ->ignore($model),
            ],
            'display_name' => ['required', 'string', 'max:120'],
            'modality' => ['required', Rule::in(array_column(AiModelModality::cases(), 'value'))],
            'capabilities' => ['present', 'array', 'max:'.count(AiModelCapability::cases())],
            'capabilities.*' => ['required', 'string', 'distinct', Rule::in(array_column(AiModelCapability::cases(), 'value'))],
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
                $allowedFields = ['ai_provider_id', 'provider_model_id', 'display_name', 'modality', 'capabilities'];

                if ($this->isMethod('PATCH')) {
                    $allowedFields[] = 'lock_version';
                }

                foreach (array_keys($this->except(['_token', '_method'])) as $field) {
                    if (! in_array($field, $allowedFields, true)) {
                        $validator->errors()->add($field, "The {$field} field is not supported for AI model metadata.");
                    }
                }

                $capabilities = $this->input('capabilities');

                if (is_array($capabilities) && ! array_is_list($capabilities)) {
                    $validator->errors()->add('capabilities', 'The capabilities field must be a list of supported values.');
                }

                if ($validator->errors()->hasAny(['ai_provider_id', 'provider_model_id'])) {
                    return;
                }

                $duplicateQuery = AiModel::query()
                    ->where('ai_provider_id', $this->integer('ai_provider_id'))
                    ->whereRaw('LOWER(provider_model_id) = ?', [Str::lower($this->string('provider_model_id')->toString())]);
                $model = $this->route('aiModel');

                if ($model instanceof AiModel) {
                    $duplicateQuery->whereKeyNot($model->getKey());
                }

                if ($duplicateQuery->exists()) {
                    $validator->errors()->add('provider_model_id', 'This provider already has a model with that identifier.');
                }
            },
        ];
    }

    /** @return array{ai_provider_id: int, provider_model_id: string, display_name: string, modality: string, capabilities: list<string>} */
    public function metadata(): array
    {
        $validated = $this->validated();

        return [
            'ai_provider_id' => (int) $validated['ai_provider_id'],
            'provider_model_id' => (string) $validated['provider_model_id'],
            'display_name' => (string) $validated['display_name'],
            'modality' => (string) $validated['modality'],
            'capabilities' => array_values($validated['capabilities']),
        ];
    }

    public function lockVersion(): int
    {
        return (int) $this->validated('lock_version');
    }

    protected function prepareForValidation(): void
    {
        $providerModelId = $this->input('provider_model_id');
        $displayName = $this->input('display_name');

        $this->merge([
            'provider_model_id' => is_string($providerModelId) ? trim($providerModelId) : $providerModelId,
            'display_name' => is_string($displayName) ? trim($displayName) : $displayName,
            'capabilities' => $this->has('capabilities') ? $this->input('capabilities') : [],
        ]);
    }
}
