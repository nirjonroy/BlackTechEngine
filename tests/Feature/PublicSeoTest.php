<?php

use App\Models\AboutPage;
use App\Models\Blog;
use App\Models\FeaturePageSetting;
use App\Models\PricingPageSetting;
use App\Models\SeoSetting;
use App\Models\User;

test('the sitemap returns valid XML with public routes and published blogs only', function () {
    config(['app.url' => 'https://public.example']);
    $published = Blog::create([
        'title' => 'Published research',
        'slug' => 'published-research',
        'published_at' => now()->subHour(),
    ]);
    Blog::create([
        'title' => 'Draft research',
        'slug' => 'draft-research',
    ]);
    Blog::create([
        'title' => 'Scheduled research',
        'slug' => 'scheduled-research',
        'published_at' => now()->addHour(),
    ]);

    $response = $this->get(route('sitemap'));

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $document = new DOMDocument;
    expect($document->loadXML($response->getContent()))->toBeTrue();

    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $locations = collect($xpath->query('//sitemap:loc'))
        ->map(fn (DOMNode $node): string => $node->textContent)
        ->all();

    expect($locations)->toBe([
        'https://public.example/',
        'https://public.example/about',
        'https://public.example/features',
        'https://public.example/pricing',
        'https://public.example/blog',
        'https://public.example/affiliate',
        'https://public.example/blog/published-research',
    ])->not->toContain(
        'https://public.example/blog/draft-research',
        'https://public.example/blog/scheduled-research',
        'https://public.example/login',
        'https://public.example/register',
        'https://public.example/admin',
        'https://public.example/dashboard',
    );

    expect($xpath->query('//sitemap:lastmod')->length)->toBe(1)
        ->and($response->getContent())->toContain($published->updated_at->toAtomString());
});

test('the sitemap remains valid when no blogs are published', function () {
    config(['app.url' => 'https://public.example']);

    $response = $this->get(route('sitemap'));

    $document = new DOMDocument;
    expect($response->isOk())->toBeTrue()
        ->and($document->loadXML($response->getContent()))->toBeTrue();

    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    expect($xpath->query('//sitemap:url')->length)->toBe(6)
        ->and($xpath->query('//sitemap:lastmod')->length)->toBe(0);
});

test('robots txt preserves crawling and references the configured sitemap URL', function () {
    config(['app.url' => 'https://public.example']);

    $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSeeText('User-agent: *')
        ->assertSeeText('Disallow:')
        ->assertSeeText('Sitemap: https://public.example/sitemap.xml');
});

test('guest authentication screens are marked noindex', function (string $routeName, array $parameters = []) {
    $response = $this->get(route($routeName, $parameters));

    $response
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex,follow">', false);

    expect(substr_count($response->getContent(), 'name="robots"'))->toBe(1);
})->with([
    'login' => ['login'],
    'registration' => ['register'],
    'forgot password' => ['password.request'],
    'reset password' => ['password.reset', ['token' => 'test-token']],
    'admin login' => ['admin.login'],
]);

test('authenticated verification and confirmation screens are marked noindex', function (string $routeName) {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex,follow">', false);
})->with([
    'email verification' => ['verification.notice'],
    'password confirmation' => ['password.confirm'],
]);

test('public marketing pages remain indexable', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">', false)
        ->assertDontSee('content="noindex,follow"', false);
})->with([
    'homepage' => ['home'],
    'about' => ['about'],
    'features' => ['features'],
    'pricing' => ['pricing'],
    'blog' => ['blog.index'],
    'affiliate' => ['affiliate'],
]);

test('about features and pricing use static descriptions when CMS and SEO descriptions are empty', function () {
    AboutPage::create(['hero_description' => '']);
    FeaturePageSetting::create([
        'hero_description' => '',
        'seo_description' => '',
        'meta_description' => '',
    ]);
    PricingPageSetting::create([
        'hero_description' => '',
        'seo_description' => '',
        'meta_description' => '',
    ]);

    $about = $this->get(route('about'))->assertOk();
    $features = $this->get(route('features'))->assertOk();
    $pricing = $this->get(route('pricing'))->assertOk();

    $about->assertSee('<meta name="description" content="Learn about BlackTechEngine and its approach to content workflows, search visibility, and generative engine optimization.">', false);
    $features->assertSee('<meta name="description" content="Explore BlackTechEngine features for content workflows, search optimization, and generative engine visibility.">', false);
    $pricing->assertSee('<meta name="description" content="Review BlackTechEngine pricing plans and available features for content and search optimization needs.">', false);

    expect(substr_count($about->getContent(), '<meta name="description"'))->toBe(1)
        ->and(substr_count($features->getContent(), '<meta name="description"'))->toBe(1)
        ->and(substr_count($pricing->getContent(), '<meta name="description"'))->toBe(1);
});

test('explicit descriptions take precedence over hero and static fallbacks', function () {
    AboutPage::create(['hero_description' => 'About hero description.']);
    SeoSetting::create([
        'page_name' => 'About',
        'meta_description' => 'Explicit about SEO description.',
    ]);
    FeaturePageSetting::create([
        'hero_description' => 'Features hero description.',
        'meta_description' => 'Explicit features SEO description.',
    ]);
    PricingPageSetting::create([
        'hero_description' => 'Pricing hero description.',
        'meta_description' => 'Explicit pricing SEO description.',
    ]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Explicit about SEO description.">', false);
    $this->get(route('features'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Explicit features SEO description.">', false);
    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Explicit pricing SEO description.">', false);
});

test('database metadata is escaped in the description tag', function () {
    $unsafeDescription = '<script>alert("seo")</script>';
    FeaturePageSetting::create(['meta_description' => $unsafeDescription]);

    $response = $this->get(route('features'));

    $response
        ->assertOk()
        ->assertSee($unsafeDescription)
        ->assertDontSee($unsafeDescription, false);

    expect(substr_count($response->getContent(), '<meta name="description"'))->toBe(1);
});
