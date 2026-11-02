<?php

namespace App\Http\Requests;

use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportContentDraftRequest extends FormRequest
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
            && ($this->user()?->can('export', $draft) ?? false);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'format' => ['required', Rule::in(['text', 'markdown', 'html'])],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('lock_version');
    }

    public function exportFormat(): string
    {
        return (string) $this->validated('format');
    }

    protected function failedAuthorization(): void
    {
        abort(404);
    }
}
