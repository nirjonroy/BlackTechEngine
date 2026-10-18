<?php

use App\Models\FeaturePageSetting;
use App\Models\SeoSetting;
use App\Models\SiteSetting;

test('the public features page renders configured content in order with testimonials and SEO', function () {
    SiteSetting::create(['site_name' => 'Fallback Brand']);
    SeoSetting::create(['page_name' => 'Global', 'meta_title' => 'Global Title']);
    FeaturePageSetting::create([
        'hero_badge' => 'Database Capabilities',
        'hero_title_normal' => 'Build Better Content',
        'hero_title_highlighted' => 'With Six Engines',
        'hero_description' => 'A database-backed feature overview.',
        'features_json' => [
            ['icon' => 'shield-check', 'title' => 'First Configured Feature', 'description' => 'First configured description.'],
            ['icon' => 'calendar-days', 'title' => 'Second Configured Feature', 'description' => 'Second configured description.'],
        ],
        'calc_title' => 'Workflow Calculator',
        'calc_description' => 'Model your content workflow.',
        'testimonial_badge' => 'Customer Stories',
        'testimonial_title' => 'Teams Building Better',
        'testimonials_json' => [
            ['quote' => 'First review.', 'author_initials' => 'FR', 'author_name' => 'First Reviewer', 'author_role' => 'Founder'],
            ['quote' => 'Second review.', 'author_initials' => 'SR', 'author_name' => 'Second Reviewer', 'author_role' => 'Editor'],
        ],
        'meta_title' => 'Platform Features | BlackTechEngine',
        'meta_description' => 'Explore BlackTechEngine platform features.',
        'meta_image' => 'seo/features.png',
        'site_name' => 'BlackTechEngine',
    ]);

    $response = $this->get(route('features'));

    $response->assertOk()
        ->assertSee('Database Capabilities')
        ->assertSee('Build Better Content')
        ->assertSee('With Six Engines')
        ->assertSeeInOrder(['First Configured Feature', 'Second Configured Feature'])
        ->assertSee('data-lucide="shield-check"', false)
        ->assertSee('Workflow Calculator')
        ->assertSee('Model your content workflow.')
        ->assertSee('Customer Stories')
        ->assertSee('Teams Building Better')
        ->assertSeeInOrder(['First review.', 'Second review.'])
        ->assertSee('First Reviewer')
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('href="'.route('pricing').'"', false)
        ->assertSee('<title>Platform Features | BlackTechEngine</title>', false)
        ->assertSee('<meta name="description" content="Explore BlackTechEngine platform features.">', false)
        ->assertSee('<link rel="canonical" href="'.route('features').'">', false)
        ->assertSee('<meta property="og:image" content="'.asset('storage/seo/features.png').'">', false)
        ->assertDontSee('Global Title');

    expect(substr_count($response->getContent(), '<h1'))->toBe(1)
        ->and(substr_count($response->getContent(), 'rel="canonical"'))->toBe(1);
});

test('the features page is public and handles missing settings with reference sections', function () {
    $response = $this->get('/features');

    $response->assertOk()
        ->assertSee('Full-Stack Autopilot Capabilities')
        ->assertSee('The 6 Pillars')
        ->assertSee('Proprietary Brand DNA &amp; Voice Extraction Engine', false)
        ->assertSee('Continuous Semantic Keyword &amp; Gap Hunter', false)
        ->assertDontSee('What Teams Say');

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

test('feature content is escaped and invalid icon names safely fall back', function () {
    $script = '<script>alert("features")</script>';
    FeaturePageSetting::create([
        'hero_badge' => $script,
        'hero_title_normal' => $script,
        'features_json' => [[
            'icon' => 'shield" onload="alert(1)',
            'title' => $script,
            'description' => $script,
        ]],
        'testimonials_json' => [[
            'quote' => $script,
            'author_initials' => $script,
            'author_name' => $script,
            'author_role' => $script,
        ]],
    ]);

    $response = $this->get(route('features'));

    $response->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false)
        ->assertSee('data-lucide="dna"', false)
        ->assertDontSee('onload="alert(1)', false)
        ->assertDontSee('javascript:', false);
});

test('a central features SEO record fills fields absent from feature settings', function () {
    FeaturePageSetting::create(['hero_title_normal' => 'Feature Hero']);
    SeoSetting::create([
        'page_name' => 'Features Page',
        'meta_title' => 'Central Features SEO',
        'meta_description' => 'Central features description.',
    ]);

    $this->get(route('features'))
        ->assertOk()
        ->assertSee('<title>Central Features SEO</title>', false)
        ->assertSee('<meta name="description" content="Central features description.">', false);
});

test('existing public about and pricing pages remain available', function () {
    $this->get(route('about'))->assertOk();
    $this->get(route('pricing'))->assertOk();
});
