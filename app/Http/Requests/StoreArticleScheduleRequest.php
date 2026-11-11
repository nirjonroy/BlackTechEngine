<?php

namespace App\Http\Requests;

use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Enums\ArticleScheduleFrequency;
use App\Enums\ArticleScheduleTargetStatus;
use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleScheduleRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:240'],
            'primary_keyword' => ['required', 'string', 'max:160'],
            'secondary_keywords' => ['nullable'],
            'brand_keyword_id' => ['nullable', 'integer', 'exists:brand_keywords,id'],
            'brand_topic_cluster_id' => ['nullable', 'integer', 'exists:brand_topic_clusters,id'],
            'content_type' => ['required', Rule::enum(AiContentType::class)],
            'content_length' => ['required', Rule::enum(AiContentLength::class)],
            'tone' => ['nullable', 'string', 'max:240'],
            'target_audience' => ['nullable', 'string', 'max:2000'],
            'language' => ['nullable', 'string', 'max:64'],
            'region' => ['nullable', 'string', 'max:120'],
            'frequency' => ['required', Rule::enum(ArticleScheduleFrequency::class)],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'timezone' => ['required', 'string', 'timezone'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'target_status' => ['required', Rule::enum(ArticleScheduleTargetStatus::class)],
            'publish_as_status' => ['nullable', 'string', 'in:draft,publish'],
            'auto_publish_to_wordpress' => ['nullable', 'boolean'],
        ];
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

        if ($this->has('auto_publish_to_wordpress')) {
            $this->merge([
                'auto_publish_to_wordpress' => filter_var($this->input('auto_publish_to_wordpress'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    protected function failedAuthorization(): void
    {
        abort(404);
    }
}

