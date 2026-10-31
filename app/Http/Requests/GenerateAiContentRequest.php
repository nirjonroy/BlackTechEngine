<?php

namespace App\Http\Requests;

use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAiContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        $brand = $this->route('brand');

        return $workspace instanceof Workspace
            && $brand instanceof Brand
            && $brand->workspace_id === $workspace->getKey()
            && ($this->user()?->can('view', $brand) ?? false);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'content_type' => ['required', Rule::enum(AiContentType::class)],
            'topic' => ['required', 'string', 'max:240'],
            'primary_keyword' => ['required', 'string', 'max:160'],
            'secondary_keywords' => ['nullable', 'array', 'max:20'],
            'secondary_keywords.*' => ['required', 'string', 'max:160', 'distinct:ignore_case'],
            'target_audience' => ['required', 'string', 'max:2000'],
            'language' => ['required', 'string', 'max:64'],
            'region' => ['required', 'string', 'max:120'],
            'content_length' => ['required', Rule::enum(AiContentLength::class)],
            'tone' => ['required', 'string', 'max:500'],
        ];
    }

    /** @return array<string, mixed> */
    public function generationData(): array
    {
        return $this->safe()->only([
            'idempotency_key', 'content_type', 'topic', 'primary_keyword', 'secondary_keywords',
            'target_audience', 'language', 'region', 'content_length', 'tone',
        ]);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('secondary_keywords'))) {
            $this->merge([
                'secondary_keywords' => collect(preg_split('/[\r\n,]+/', $this->string('secondary_keywords')->toString()) ?: [])
                    ->map(fn (string $item): string => trim($item))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }

        foreach (['topic', 'primary_keyword', 'target_audience', 'language', 'region', 'tone'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->string($field)->toString())]);
            }
        }
    }

    protected function failedAuthorization(): void
    {
        abort(404);
    }
}
