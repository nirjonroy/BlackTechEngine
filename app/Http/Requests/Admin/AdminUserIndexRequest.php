<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserIndexRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public const SORT_OPTIONS = ['newest', 'oldest', 'name', 'email', 'id'];

    public const VERIFICATION_OPTIONS = ['all', 'verified', 'unverified'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'verification' => ['nullable', Rule::in(self::VERIFICATION_OPTIONS)],
            'sort' => ['nullable', Rule::in(self::SORT_OPTIONS)],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /**
     * @return array{search: string, verification: string, sort: string, per_page: int}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'verification' => (string) ($validated['verification'] ?? 'all'),
            'sort' => (string) ($validated['sort'] ?? 'newest'),
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];
    }
}
