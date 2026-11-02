<?php

namespace App\Http\Requests;

use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\ContentReviewGate;
use Illuminate\Validation\Rule;

class UpdateAiContentDraftRequest extends StoreAiContentDraftRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        $brand = $this->route('brand');
        $draft = $this->route('aiContentDraft');

        return $workspace instanceof Workspace
            && $brand instanceof Brand
            && $draft instanceof AiContentDraft
            && $brand->workspace_id === $workspace->getKey()
            && $draft->workspace_id === $workspace->getKey()
            && $draft->brand_id === $brand->getKey()
            && ($this->user()?->can('update', $draft) ?? false);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'lock_version' => [
                Rule::requiredIf(app(ContentReviewGate::class)->enabled()),
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('lock_version', 0);
    }
}
