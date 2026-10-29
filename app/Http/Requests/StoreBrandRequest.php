<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace
            && ($this->user()?->can('update', $workspace) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'website_url' => ['required', 'url:http,https', 'max:2048'],
            'business_description' => ['required', 'string', 'max:5000'],
            'industry' => ['required', 'string', 'max:120'],
            'target_audience' => ['required', 'string', 'max:2000'],
            'primary_market' => ['required', 'string', 'max:120'],
            'preferred_language' => ['required', 'string', 'max:64'],
            'voice_tone' => ['required', 'string', 'max:1000'],
            'content_objectives' => ['required', 'array', 'min:1', 'max:12'],
            'content_objectives.*' => ['required', 'string', 'max:160', 'distinct:ignore_case'],
            'seo_topic_categories' => ['required', 'array', 'min:1', 'max:20'],
            'seo_topic_categories.*' => ['required', 'string', 'max:120', 'distinct:ignore_case'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function brandData(): array
    {
        return $this->safe()->only([
            'name',
            'website_url',
            'business_description',
            'industry',
            'target_audience',
            'primary_market',
            'preferred_language',
            'voice_tone',
            'content_objectives',
            'seo_topic_categories',
            'is_active',
        ]);
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'name',
            'website_url',
            'business_description',
            'industry',
            'target_audience',
            'primary_market',
            'preferred_language',
            'voice_tone',
        ] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => trim($value)]);
            }
        }

        $this->merge([
            'content_objectives' => $this->normalizedList('content_objectives'),
            'seo_topic_categories' => $this->normalizedList('seo_topic_categories'),
        ]);
    }

    private function normalizedList(string $field): mixed
    {
        $value = $this->input($field);

        if (! is_string($value)) {
            return $value;
        }

        return collect(preg_split('/[\r\n,]+/', $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
