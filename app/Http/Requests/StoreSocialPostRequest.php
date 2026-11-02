<?php

namespace App\Http\Requests;

use App\Enums\AiContentDraftStatus;
use App\Enums\SocialPlatform;
use App\Models\AiContentDraft;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        $brand = $this->route('brand');

        return $workspace?->isAccessibleBy($this->user()) && $brand?->workspace_id === $workspace?->getKey();
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid:4'],
            'title' => ['required', 'string', 'max:180'],
            'campaign' => ['nullable', 'string', 'max:120'],
            'source_ai_content_draft_id' => ['nullable', 'integer'],
            'variants' => ['required', 'array', 'min:1', 'max:5'],
            'variants.*.platform' => ['required', Rule::enum(SocialPlatform::class), 'distinct'],
            'variants.*.caption' => ['required', 'string', 'max:10000'],
            'variants.*.hashtags' => ['nullable', 'string', 'max:2000'],
            'variants.*.cta_text' => ['nullable', 'string', 'max:160'],
            'variants.*.target_url' => ['nullable', 'url:http,https', 'max:2048'],
            'variants.*.media_reference' => ['nullable', 'string', 'max:500', 'regex:/^(?![a-z]+:\/\/)(?!\/)(?!.*\.\.).+$/i'],
            'variants.*.image_alt_text' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function sourceDraft(): ?AiContentDraft
    {
        if (! $this->filled('source_ai_content_draft_id')) {
            return null;
        }

        return AiContentDraft::query()
            ->whereKey($this->integer('source_ai_content_draft_id'))
            ->where('workspace_id', $this->route('workspace')->getKey())
            ->where('brand_id', $this->route('brand')->getKey())
            ->whereIn('status', [AiContentDraftStatus::Approved->value, AiContentDraftStatus::ExportReady->value])
            ->firstOrFail();
    }

    public function postData(): array
    {
        return $this->safe()->only(['title', 'campaign']);
    }

    public function idempotencyKey(): string
    {
        return (string) $this->validated('idempotency_key');
    }

    public function variantsData(): array
    {
        return collect($this->validated('variants'))->map(function (array $variant): array {
            $variant['hashtags'] = collect(preg_split('/[\s,]+/', $variant['hashtags'] ?? '', -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn (string $tag): string => '#'.ltrim($tag, '#'))->unique()->values()->all();

            return $variant;
        })->all();
    }
}
