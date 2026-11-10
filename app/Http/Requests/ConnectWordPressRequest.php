<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConnectWordPressRequest extends FormRequest
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
            'site_url' => ['required', 'string', 'url', 'max:255'],
            'username' => ['required', 'string', 'max:100'],
            'application_password' => ['required', 'string', 'max:255'],
        ];
    }
}
