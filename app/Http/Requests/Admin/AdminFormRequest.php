<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

abstract class AdminFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') instanceof Admin;
    }

    /**
     * @return array<int, mixed>
     */
    protected function imageRules(string $maxSize = '5mb', int $maxWidth = 6000, int $maxHeight = 6000): array
    {
        return [
            'nullable',
            File::image(allowSvg: false)
                ->types(['jpg', 'jpeg', 'png', 'webp'])
                ->extensions(['jpg', 'jpeg', 'png', 'webp'])
                ->max($maxSize)
                ->dimensions(
                    Rule::dimensions()
                        ->maxWidth($maxWidth)
                        ->maxHeight($maxHeight),
                ),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function faviconRules(): array
    {
        return [
            'nullable',
            File::types(['jpg', 'jpeg', 'png', 'webp', 'ico'])
                ->extensions(['jpg', 'jpeg', 'png', 'webp', 'ico'])
                ->max('1mb'),
        ];
    }
}
