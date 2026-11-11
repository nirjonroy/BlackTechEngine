<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandKeywordRequest extends FormRequest
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
            'keyword' => ['required', 'string', 'max:160'],
            'search_intent' => ['required', 'string', 'in:informational,commercial,transactional,navigational'],
            'brand_topic_cluster_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
