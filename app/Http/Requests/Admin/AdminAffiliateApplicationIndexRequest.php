<?php

namespace App\Http\Requests\Admin;

use App\Enums\AffiliateApplicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAffiliateApplicationIndexRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public const SORT_OPTIONS = ['newest', 'oldest', 'status', 'submitted', 'id'];

    /**
     * Determine if the user is authorized to make this request.
     */
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
            'status' => ['nullable', Rule::in([
                'all',
                ...array_column(AffiliateApplicationStatus::cases(), 'value'),
            ])],
            'sort' => ['nullable', Rule::in(self::SORT_OPTIONS)],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    /**
     * @return array{search: string, status: string, sort: string, per_page: int}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'status' => (string) ($validated['status'] ?? 'all'),
            'sort' => (string) ($validated['sort'] ?? 'newest'),
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];
    }
}
