<?php

namespace App\Http\Requests;

use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
        ];
    }

    /** @return array{name: string, settings: array{timezone: string}} */
    public function workspaceData(): array
    {
        return [
            'name' => (string) $this->validated('name'),
            'settings' => ['timezone' => (string) $this->validated('timezone')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        $this->merge(['name' => is_string($name) ? trim($name) : $name]);
    }
}
