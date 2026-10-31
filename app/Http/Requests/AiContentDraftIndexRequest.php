<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
