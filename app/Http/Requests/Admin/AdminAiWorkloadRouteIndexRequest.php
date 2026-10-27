<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAiWorkloadRouteIndexRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public const SORT_OPTIONS = ['newest', 'oldest', 'key', 'state'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', Rule::in(['all', 'enabled', 'disabled', 'unassigned'])],
            'sort' => ['nullable', Rule::in(self::SORT_OPTIONS)],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /** @return array{search: string, state: string, sort: string, per_page: int} */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'state' => (string) ($validated['state'] ?? 'all'),
            'sort' => (string) ($validated['sort'] ?? 'key'),
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];
    }
}
