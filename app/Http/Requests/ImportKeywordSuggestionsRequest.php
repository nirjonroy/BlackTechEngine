<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportKeywordSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clusters' => ['required', 'array'],
            'clusters.*.cluster_name' => ['required', 'string', 'max:120'],
            'clusters.*.pillar_keyword' => ['nullable', 'string', 'max:160'],
            'clusters.*.description' => ['nullable', 'string', 'max:1000'],
            'clusters.*.keywords' => ['nullable', 'array'],
            'clusters.*.keywords.*.keyword' => ['required', 'string', 'max:160'],
            'clusters.*.keywords.*.search_intent' => ['required', 'string', 'in:informational,commercial,transactional,navigational'],
        ];
    }
}
