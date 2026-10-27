<?php

namespace App\Http\Requests\Admin;

use App\Enums\AiProviderHealthStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAiProviderIndexRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public const SORT_OPTIONS = ['newest', 'oldest', 'name', 'key', 'health'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'health' => ['nullable', Rule::in(['all', ...array_column(AiProviderHealthStatus::cases(), 'value')])],
            'state' => ['nullable', Rule::in(['all', 'enabled', 'disabled'])],
            'sort' => ['nullable', Rule::in(self::SORT_OPTIONS)],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /** @return array{search: string, health: string, state: string, sort: string, per_page: int} */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'health' => (string) ($validated['health'] ?? 'all'),
            'state' => (string) ($validated['state'] ?? 'all'),
            'sort' => (string) ($validated['sort'] ?? 'name'),
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];
    }
}
