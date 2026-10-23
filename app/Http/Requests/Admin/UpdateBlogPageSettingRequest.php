<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogPageSetting;
use Illuminate\Validation\Rule;

class UpdateBlogPageSettingRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hero_badge' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_highlighted_title' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:5000'],
            'search_label' => ['nullable', 'string', 'max:255'],
            'search_placeholder' => ['nullable', 'string', 'max:255'],
            'filter_heading' => ['nullable', 'string', 'max:255'],
            'all_posts_label' => ['nullable', 'string', 'max:255'],
            'featured_label' => ['nullable', 'string', 'max:255'],
            'read_full_article_label' => ['nullable', 'string', 'max:255'],
            'read_article_label' => ['nullable', 'string', 'max:255'],
            'minutes_read_label' => ['nullable', 'string', 'max:100'],
            'empty_state_heading' => ['nullable', 'string', 'max:255'],
            'empty_state_description' => ['nullable', 'string', 'max:2000'],
            'no_results_heading' => ['nullable', 'string', 'max:255'],
            'no_results_description' => ['nullable', 'string', 'max:2000'],
            'back_to_blog_label' => ['nullable', 'string', 'max:255'],
            'published_label' => ['nullable', 'string', 'max:100'],
            'article_empty_state' => ['nullable', 'string', 'max:2000'],
            'related_articles_heading' => ['nullable', 'string', 'max:255'],
            'sidebar_heading' => ['nullable', 'string', 'max:255'],
            'sidebar_description' => ['nullable', 'string', 'max:2000'],
            'sidebar_cta_label' => ['nullable', 'string', 'max:255'],
            'sidebar_cta_destination' => ['nullable', 'string', Rule::in(array_keys(BlogPageSetting::DESTINATION_OPTIONS))],
            'sidebar_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
