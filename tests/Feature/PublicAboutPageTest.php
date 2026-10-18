<?php

use App\Models\AboutPage;
use App\Models\SeoSetting;
use App\Models\SiteSetting;

test('the public about page renders every configured section with page SEO', function () {
    SiteSetting::create(['site_name' => 'BlackTechEngine']);
    SeoSetting::create([
        'page_name' => 'About',
        'meta_title' => 'About GEO | BlackTechEngine',
        'meta_description' => 'Learn how the GEO architecture operates.',
        'meta_image' => 'seo/about.png',
        'site_name' => 'BlackTechEngine',
    ]);
    $about = AboutPage::create([
        'hero_badge' => 'Future of Search',
        'hero_title' => 'Why GEO Wins',
        'hero_description' => 'Autonomous authority for generative search.',
        'comparison_title' => 'Architectural Comparison',
        'comparison_description' => 'Compare traditional SEO and GEO.',
        'architecture_badge' => 'Proprietary Pipeline',
        'architecture_title' => 'How the Core Operates',
        'architecture_description' => 'A synchronized multi-agent framework.',
        'core_values_title' => 'Core Engineering Principles',
        'core_values_description' => 'What guides every release.',
        'cta_title' => 'Join the Search Revolution',
        'cta_description' => 'Build authority today.',
        'cta_btn1_text' => 'Start Free',
        'cta_btn1_link' => 'https://example.com/signup',
        'cta_btn2_text' => 'See Pricing',
        'cta_btn2_link' => 'https://example.com/pricing',
    ]);
    $about->stats()->create(['value' => '100%', 'label' => 'Automated', 'order_by' => 0]);
    $about->comparisons()->create([
        'capability' => 'Turnaround Speed',
        'traditional_text' => 'Two weeks',
        'bte_text' => 'Continuous pipeline',
        'order_by' => 0,
    ]);
    $about->architectures()->create([
        'step_number' => 1,
        'title' => 'Deep Site Ingestion',
        'description' => 'Analyzes existing content.',
        'order_by' => 0,
    ]);
    $about->coreValues()->create([
        'icon' => 'shield-check',
        'title' => 'Zero Hallucinations',
        'description' => 'Validate every factual claim.',
        'order_by' => 0,
    ]);

    $response = $this->get(route('about'));

    $response->assertOk()
        ->assertSee('Future of Search')
        ->assertSee('Why GEO Wins')
        ->assertSee('Autonomous authority for generative search.')
        ->assertSee('100%')
        ->assertSee('Automated')
        ->assertSee('Architectural Comparison')
        ->assertSee('Turnaround Speed')
        ->assertSee('Two weeks')
        ->assertSee('Continuous pipeline')
        ->assertSee('Proprietary Pipeline')
        ->assertSee('Deep Site Ingestion')
        ->assertSee('Core Engineering Principles')
        ->assertSee('data-lucide="shield-check"', false)
        ->assertSee('Join the Search Revolution')
        ->assertSee('href="https://example.com/signup"', false)
        ->assertSee('<title>About GEO | BlackTechEngine</title>', false)
        ->assertSee('<meta name="description" content="Learn how the GEO architecture operates.">', false)
        ->assertSee('<link rel="canonical" href="'.route('about').'">', false)
        ->assertSee('<meta property="og:image" content="'.asset('storage/seo/about.png').'">', false);

    expect(substr_count($response->getContent(), '<h1'))->toBe(1)
        ->and(substr_count($response->getContent(), 'rel="canonical"'))->toBe(1);
});

test('repeatable about records render in their saved display order', function () {
    $about = AboutPage::create(['hero_title' => 'Ordered About']);
    $about->stats()->create(['value' => 'Second', 'label' => 'Second stat', 'order_by' => 20]);
    $about->stats()->create(['value' => 'First', 'label' => 'First stat', 'order_by' => 10]);
    $about->architectures()->create([
        'step_number' => 2,
        'title' => 'Second step',
        'description' => 'Rendered second',
        'order_by' => 20,
    ]);
    $about->architectures()->create([
        'step_number' => 1,
        'title' => 'First step',
        'description' => 'Rendered first',
        'order_by' => 10,
    ]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSeeInOrder(['First stat', 'Second stat'])
        ->assertSeeInOrder(['First step', 'Second step']);
});

test('the public about page handles missing data and retains the shared header and footer', function () {
    SiteSetting::create(['site_name' => 'Shared Brand']);

    $response = $this->get(route('about'));

    $response->assertOk()
        ->assertSee('About BlackTechEngine')
        ->assertSee('About GEO')
        ->assertSee('Shared Brand')
        ->assertSee('href="'.route('home').'"', false)
        ->assertSee('href="'.route('about').'"', false)
        ->assertDontSee('Zero-Touch Autopilot')
        ->assertDontSee('Higher AI Citations');
    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

test('database content is escaped and unsafe legacy CTA schemes are not rendered', function () {
    $script = '<script>alert("about")</script>';
    $about = AboutPage::create([
        'hero_title' => $script,
        'hero_description' => $script,
        'cta_btn1_text' => 'Unsafe link',
        'cta_btn1_link' => 'javascript:alert(1)',
    ]);
    $about->coreValues()->create([
        'icon' => 'shield-check" onload="alert(1)',
        'title' => $script,
        'description' => $script,
        'order_by' => 0,
    ]);

    $response = $this->get(route('about'));

    $response->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false)
        ->assertDontSee('javascript:alert(1)', false)
        ->assertDontSee('onload="alert(1)', false);
});
