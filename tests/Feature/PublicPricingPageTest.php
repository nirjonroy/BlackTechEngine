<?php

use App\Models\PricingPageSetting;
use App\Models\PricingPlan;
use App\Models\SeoSetting;
use App\Models\SiteSetting;

test('the public pricing page renders settings plans ordered features FAQs and page SEO', function () {
    SiteSetting::create(['site_name' => 'Fallback Brand']);
    SeoSetting::create([
        'page_name' => 'Global',
        'meta_title' => 'Global SEO Title',
    ]);
    PricingPageSetting::create([
        'hero_badge_icon' => 'badge-dollar-sign',
        'hero_badge_text' => 'Database Pricing',
        'hero_title_normal' => 'Choose Your Engine',
        'hero_title_highlighted' => 'Scale With Confidence',
        'hero_description' => 'Transparent plans backed by autonomous workflows.',
        'calc_title' => 'Plan Your Content Budget',
        'calc_description' => 'Estimate external writing costs.',
        'faq_title' => 'Pricing Questions',
        'faq_description' => 'Answers from the pricing team.',
        'faqs' => [
            ['question' => 'Can I change plans?', 'answer' => 'Yes, contact the team for current options.'],
        ],
        'meta_title' => 'Pricing Plans | BlackTechEngine',
        'meta_description' => 'Compare BlackTechEngine pricing plans.',
        'meta_image' => 'seo/pricing.png',
        'site_name' => 'BlackTechEngine',
    ]);
    $laterPlan = PricingPlan::create([
        'name' => 'Scale',
        'title' => 'Scale Engine',
        'monthly_price' => 199,
        'annual_price' => 1990,
        'button_text' => 'Contact Sales',
        'button_link' => 'login',
    ]);
    $earlierPlan = PricingPlan::create([
        'name' => 'Growth',
        'badge' => 'For Teams',
        'title' => 'Authority Engine',
        'description' => 'Grow durable search authority.',
        'monthly_price' => 99,
        'annual_price' => 999,
        'is_popular' => true,
        'button_text' => 'Start Growth',
        'button_link' => 'register',
    ]);
    $earlierPlan->features()->create(['name' => 'Second feature', 'is_included' => false, 'order_by' => 20]);
    $earlierPlan->features()->create(['name' => 'First feature', 'is_included' => true, 'order_by' => 10]);
    $laterPlan->features()->create(['name' => 'Scale support', 'is_included' => true, 'order_by' => 0]);

    $response = $this->get(route('pricing'));

    $response->assertOk()
        ->assertSee('Database Pricing')
        ->assertSee('Choose Your Engine')
        ->assertSee('Scale With Confidence')
        ->assertSee('Plan Your Content Budget')
        ->assertSeeInOrder(['Authority Engine', 'Scale Engine'])
        ->assertSeeInOrder(['First feature', 'Second feature'])
        ->assertSee('$99.00')
        ->assertSee('$999.00')
        ->assertSee('Most Popular')
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('Pricing Questions')
        ->assertSee('Can I change plans?')
        ->assertSee('Yes, contact the team for current options.')
        ->assertSee('<title>Pricing Plans | BlackTechEngine</title>', false)
        ->assertSee('<meta name="description" content="Compare BlackTechEngine pricing plans.">', false)
        ->assertSee('<link rel="canonical" href="'.route('pricing').'">', false)
        ->assertSee('<meta property="og:image" content="'.asset('storage/seo/pricing.png').'">', false)
        ->assertDontSee('Global SEO Title');

    expect(substr_count($response->getContent(), '<h1'))->toBe(1)
        ->and(substr_count($response->getContent(), 'rel="canonical"'))->toBe(1);
});

test('the pricing page is public and handles missing records without fake plans', function () {
    $response = $this->get('/pricing');

    $response->assertOk()
        ->assertSee('Plans are being prepared')
        ->assertSee('No pricing questions have been published yet.')
        ->assertSee('href="'.route('home').'"', false)
        ->assertSee('href="'.route('pricing').'"', false)
        ->assertDontSee('Niche Autopilot')
        ->assertDontSee('Authority Engine')
        ->assertDontSee('Agency Matrix');
});

test('pricing database content is escaped and unsafe legacy CTA and icon values are rejected', function () {
    $script = '<script>alert("pricing")</script>';
    PricingPageSetting::create([
        'hero_badge_icon' => 'tag" onload="alert(1)',
        'hero_badge_text' => $script,
        'hero_title_normal' => $script,
        'faqs' => [['question' => $script, 'answer' => $script]],
    ]);
    $plan = PricingPlan::create([
        'name' => $script,
        'description' => $script,
        'monthly_price' => 10,
        'annual_price' => 100,
        'button_text' => 'Unsafe action',
        'button_link' => 'javascript:alert(1)',
    ]);
    $plan->features()->create(['name' => $script, 'is_included' => true, 'order_by' => 0]);

    $response = $this->get(route('pricing'));

    $response->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false)
        ->assertSee('data-lucide="tag"', false)
        ->assertDontSee('javascript:alert(1)', false)
        ->assertDontSee('onload="alert(1)', false);
});

test('a pricing SEO record fills fields absent from pricing page settings', function () {
    PricingPageSetting::create(['hero_title_normal' => 'Pricing Hero']);
    SeoSetting::create([
        'page_name' => 'Pricing Page',
        'meta_title' => 'Central Pricing SEO',
        'meta_description' => 'Central pricing description.',
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('<title>Central Pricing SEO</title>', false)
        ->assertSee('<meta name="description" content="Central pricing description.">', false);
});
