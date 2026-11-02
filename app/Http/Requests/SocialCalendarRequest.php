<?php

namespace App\Http\Requests;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'view' => ['nullable', Rule::in(['month', 'week', 'list'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'workspace' => ['nullable', 'integer'],
            'brand' => ['nullable', 'integer'],
            'platform' => ['nullable', Rule::enum(SocialPlatform::class)],
            'status' => ['nullable', Rule::enum(SocialPostStatus::class)],
        ];
    }
}
