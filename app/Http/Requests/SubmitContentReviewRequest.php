<?php

namespace App\Http\Requests;

use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class SubmitContentReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $draft = $this->route('aiContentDraft');

        return $this->hasValidRouteScope()
            && $draft instanceof AiContentDraft
            && ($this->user()?->can('requestReview', $draft) ?? false);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('lock_version');
    }

    private function hasValidRouteScope(): bool
    {
        $workspace = $this->route('workspace');
        $brand = $this->route('brand');
        $draft = $this->route('aiContentDraft');

        return $workspace instanceof Workspace
            && $brand instanceof Brand
            && $draft instanceof AiContentDraft
            && $brand->workspace_id === $workspace->getKey()
            && $draft->workspace_id === $workspace->getKey()
            && $draft->brand_id === $brand->getKey();
    }

    protected function failedAuthorization(): void
    {
        abort(404);
    }
}
