<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPageSetting extends Model
{
    /** @var array<string, string> */
    public const DESTINATION_OPTIONS = [
        'register' => 'Create account',
        'login' => 'Client sign in',
        'pricing' => 'Pricing page',
        'features' => 'Features page',
        'about' => 'About page',
        'blog.index' => 'Blog listing',
        'home' => 'Homepage',
    ];

    protected $fillable = [
        'hero_badge',
        'hero_title',
        'hero_highlighted_title',
        'hero_description',
        'search_label',
        'search_placeholder',
        'filter_heading',
        'all_posts_label',
        'featured_label',
        'read_full_article_label',
        'read_article_label',
        'minutes_read_label',
        'empty_state_heading',
        'empty_state_description',
        'no_results_heading',
        'no_results_description',
        'back_to_blog_label',
        'published_label',
        'article_empty_state',
        'related_articles_heading',
        'sidebar_heading',
        'sidebar_description',
        'sidebar_cta_label',
        'sidebar_cta_destination',
        'sidebar_note',
    ];
}
