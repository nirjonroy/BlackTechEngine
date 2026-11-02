<?php

namespace App\Http\Requests;

use App\Enums\AiContentDraftStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiContentDraftIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'workspace' => ['nullable', 'integer', 'min:1'],
            'brand' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(AiContentDraftStatus::class)],
        ];
    }
}
