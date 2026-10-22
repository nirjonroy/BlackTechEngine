<?php

use App\Models\AboutPage;
use App\Models\FeaturePageSetting;
use App\Models\PricingPlan;
use App\Models\SiteSetting;

test('the public homepage renders every reference section without fabricated results', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Put Your SEO & Social Content on', false)
        ->assertSee('Publishing and distribution workflow destinations')
        ->assertSee('The 6 Pillars of BlackTechEngine')
        ->assertSee('Estimate External Content Production Cost')
        ->assertSee('Website scanner is not connected yet')
        ->assertSee('href="'.route('features').'"', false)
        ->assertSee('href="'.route('pricing').'"', false)
        ->assertSee('href="'.route('register').'"', false)
        ->assertDontSee('4.8M+')
        ->assertDontSee('Loved by 4,200+ Growth Teams')
        ->assertDontSee('34 URL entities crawled')
        ->assertDontSee('45 High-Intent Keyword Opportunities')
        ->assertDontSee('98.4%')
        ->assertDontSee('What Teams Say');

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

test('the homepage renders configured features statistics testimonials and calculator content in order', function () {
    $aboutPage = AboutPage::create(['hero_title' => 'About']);
    $aboutPage->stats()->createMany([
        ['value' => 'Second Value', 'label' => 'Second Stat', 'order_by' => 20],
        ['value' => 'First Value', 'label' => 'First Stat', 'order_by' => 10],
    ]);
    FeaturePageSetting::create([
        'hero_badge' => 'Configured Capabilities',
        'features_json' => [
            ['icon' => 'calendar', 'title' => 'First Feature', 'description' => 'First feature description.'],
            ['icon' => 'dna', 'title' => 'Second Feature', 'description' => 'Second feature description.'],
        ],
        'calc_title' => 'Configured Cost Calculator',
        'calc_description' => 'Configured calculator explanation.',
        'testimonial_badge' => 'Verified Stories',
        'testimonial_title' => 'Configured Testimonials',
        'testimonials_json' => [
            ['quote' => 'First review.', 'author_initials' => 'FR', 'author_name' => 'First Reviewer', 'author_role' => 'Founder'],
            ['quote' => 'Second review.', 'author_initials' => 'SR', 'author_name' => 'Second Reviewer', 'author_role' => 'Editor'],
        ],
    ]);
    PricingPlan::create([
        'name' => 'Starter',
        'monthly_price' => 29.50,
        'annual_price' => 295,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Configured Capabilities')
        ->assertSeeInOrder(['First Feature', 'Second Feature'])
        ->assertSeeInOrder(['First Value', 'Second Value'])
        ->assertSee('Configured Cost Calculator')
        ->assertSee('Configured calculator explanation.')
        ->assertSee('From $29.50/mo')
        ->assertSee('Verified Stories')
        ->assertSee('Configured Testimonials')
        ->assertSeeInOrder(['First review.', 'Second review.'])
        ->assertSeeInOrder(['First Reviewer', 'Second Reviewer']);
});

test('the homepage escapes configured content and rejects unsupported icons and external schemes', function () {
    $script = '<script>alert("homepage")</script>';
    FeaturePageSetting::create([
        'hero_badge' => $script,
        'features_json' => [[
            'icon' => 'not-a-real-icon',
            'title' => $script,
            'description' => $script,
        ]],
        'testimonials_json' => [[
            'quote' => $script,
            'author_initials' => 'XX',
            'author_name' => $script,
            'author_role' => $script,
        ]],
    ]);
    SiteSetting::create([
        'site_name' => 'BlackTechEngine',
        'facebook_url' => 'javascript:alert(1)',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false)
        ->assertSee('data-lucide="calendar"', false)
        ->assertDontSee('data-lucide="not-a-real-icon"', false)
        ->assertDontSee('href="javascript:alert(1)"', false);
});

test('the homepage preserves existing public page availability', function () {
    $this->get(route('about'))->assertOk();
    $this->get(route('features'))->assertOk();
    $this->get(route('pricing'))->assertOk();
});
