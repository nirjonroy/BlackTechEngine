<?php

namespace App\Http\Requests\Admin;

use App\Services\FrontendNavigation;
use Illuminate\Validation\Rule;

class UpdateFrontendNavigationRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $destination = ['required', 'string', Rule::in(array_keys(FrontendNavigation::DESTINATION_OPTIONS))];

        return [
            'header_navigation' => ['required', 'array', 'max:12'],
            'header_navigation.*.label' => ['required', 'string', 'max:80'],
            'header_navigation.*.route' => [...$destination, 'distinct:strict'],
            'header_navigation.*.order' => ['required', 'integer', 'between:0,1000'],
            'header_navigation.*.is_visible' => ['required', 'boolean'],
            'footer_description' => ['nullable', 'string', 'max:1000'],
            'footer_columns' => ['required', 'array', 'max:6'],
            'footer_columns.*.title' => ['required', 'string', 'max:80'],
            'footer_columns.*.order' => ['required', 'integer', 'between:0,1000'],
            'footer_columns.*.is_visible' => ['required', 'boolean'],
            'footer_columns.*.links' => ['required', 'array', 'max:12'],
            'footer_columns.*.links.*.label' => ['required', 'string', 'max:80'],
            'footer_columns.*.links.*.route' => $destination,
            'footer_columns.*.links.*.order' => ['required', 'integer', 'between:0,1000'],
            'footer_columns.*.links.*.is_visible' => ['required', 'boolean'],
            'footer_copyright' => ['nullable', 'string', 'max:500'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:2048'],
            'x_url' => ['nullable', 'url:http,https', 'max:2048'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:2048'],
            'github_url' => ['nullable', 'url:http,https', 'max:2048'],
            'youtube_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
