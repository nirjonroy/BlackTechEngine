<?php

namespace App\Http\Requests\Admin;

use App\Models\Blog;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBlogRequest extends AdminFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('title'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique(Blog::class, 'slug')->ignore(Blog::findOrFail($this->route('id'))),
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:1000000'],
            'image' => $this->imageRules(),
            'seo_title' => ['nullable', 'string', 'max:1000'],
            'seo_description' => ['nullable', 'string', 'max:5000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:5000'],
            'meta_image' => $this->imageRules(),
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:5000'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
