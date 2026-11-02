<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialPostWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'planned_at' => ['nullable', 'date'],
            'timezone' => ['nullable', 'timezone:all'],
            'published_at' => ['nullable', 'date'],
            'published_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
