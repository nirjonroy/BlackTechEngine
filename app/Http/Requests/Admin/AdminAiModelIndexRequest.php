<?php

namespace App\Http\Requests\Admin;

use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelModality;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAiModelIndexRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public const SORT_OPTIONS = ['newest', 'oldest', 'name', 'identifier', 'status'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'integer', 'min:1', 'exists:ai_providers,id'],
            'status' => ['nullable', Rule::in(['all', ...array_column(AiModelAvailabilityStatus::cases(), 'value')])],
            'modality' => ['nullable', Rule::in(['all', ...array_column(AiModelModality::cases(), 'value')])],
            'sort' => ['nullable', Rule::in(self::SORT_OPTIONS)],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /** @return array{search: string, provider: int|null, status: string, modality: string, sort: string, per_page: int} */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'provider' => isset($validated['provider']) ? (int) $validated['provider'] : null,
            'status' => (string) ($validated['status'] ?? 'all'),
            'modality' => (string) ($validated['modality'] ?? 'all'),
            'sort' => (string) ($validated['sort'] ?? 'name'),
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];
    }
}
