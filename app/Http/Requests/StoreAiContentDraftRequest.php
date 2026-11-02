<?php

namespace App\Http\Requests;

use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiContentDraftRequest extends FormRequest
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
            'seo_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'suggested_slug' => ['nullable', 'string', 'max:191', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D'],
            'outline' => ['nullable', 'array', 'max:40'],
            'outline.*' => ['required', 'string', 'max:240'],
            'content' => ['nullable', 'string', 'max:100000'],
            'faq_entries' => ['nullable', 'array', 'max:30'],
            'faq_entries.*.question' => ['required', 'string', 'max:500'],
            'faq_entries.*.answer' => ['required', 'string', 'max:2000'],
            'internal_link_opportunities' => ['nullable', 'array', 'max:30'],
            'internal_link_opportunities.*' => ['required', 'string', 'max:500'],
            'external_research_topics' => ['nullable', 'array', 'max:30'],
            'external_research_topics.*' => ['required', 'string', 'max:500'],
            'verification_notes' => ['nullable', 'array', 'max:30'],
            'verification_notes.*' => ['required', 'string', 'max:1000'],
            'status' => ['prohibited'],
            'workspace_id' => ['prohibited'],
            'brand_id' => ['prohibited'],
            'created_by_user_id' => ['prohibited'],
            'ai_provider_id' => ['prohibited'],
            'ai_model_id' => ['prohibited'],
            'approved_by_user_id' => ['prohibited'],
            'approved_at' => ['prohibited'],
            'review_submitted_at' => ['prohibited'],
            'changes_requested_at' => ['prohibited'],
        ];
    }

    /** @return array<string, mixed> */
    public function draftData(): array
    {
        return $this->safe()->only([
            'content_type', 'topic', 'primary_keyword', 'secondary_keywords', 'target_audience',
            'language', 'region', 'content_length', 'tone', 'seo_title', 'meta_description',
            'suggested_slug', 'outline', 'content', 'faq_entries', 'internal_link_opportunities',
            'external_research_topics', 'verification_notes',
        ]);
    }

    protected function prepareForValidation(): void
    {
        foreach (['secondary_keywords', 'outline', 'internal_link_opportunities', 'external_research_topics', 'verification_notes'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => $this->normalizedLines($this->string($field)->toString())]);
            }
        }

        foreach (['topic', 'primary_keyword', 'target_audience', 'language', 'region', 'tone', 'seo_title', 'meta_description', 'suggested_slug'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->string($field)->toString())]);
            }
        }
    }

    /** @return list<string> */
    private function normalizedLines(string $value): array
    {
        return collect(preg_split('/[\r\n,]+/', $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function failedAuthorization(): void
    {
        abort(404);
    }
}
