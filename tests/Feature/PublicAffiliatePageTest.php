<?php

use App\Models\SeoSetting;
use App\Models\User;

test('the public affiliate page renders its complete truthful landing page', function () {
    $response = $this->get(route('affiliate'));

    $response->assertOk()
        ->assertViewIs('frontend.affiliate')
        ->assertSee('Partner With BlackTechEngine')
        ->assertSee('Program Terms Are Pending Approval')
        ->assertSee('How the Planned Partner Program Will Work')
        ->assertSee('Partner Resources Are Planned')
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('href="'.route('affiliate').'"', false)
        ->assertSee('href="#program-status"', false)
        ->assertDontSee('href="#"', false)
        ->assertDontSee('javascript:', false)
        ->assertDontSee('signup.html')
        ->assertDontSee('30% For Life')
        ->assertDontSee('$742.50');

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

test('the named affiliate route is publicly accessible', function () {
    $this->get('/affiliate')
        ->assertOk();
});

test('the affiliate route remains accessible to authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('affiliate'))
        ->assertOk();
});

test('affiliate SEO uses a page-specific record and canonical URL', function () {
    SeoSetting::create([
        'page_name' => 'Affiliate Page',
        'meta_title' => 'Affiliate Program Status | BlackTechEngine',
        'meta_description' => 'Review current affiliate program availability and pending terms.',
        'meta_image' => 'seo/affiliate.png',
    ]);

    $response = $this->get(route('affiliate'));

    $response->assertOk()
        ->assertSee('<title>Affiliate Program Status | BlackTechEngine</title>', false)
        ->assertSee('<meta name="description" content="Review current affiliate program availability and pending terms.">', false)
        ->assertSee('<link rel="canonical" href="'.route('affiliate').'">', false)
        ->assertSee('<meta property="og:image" content="'.asset('storage/seo/affiliate.png').'">', false)
        ->assertSee('<meta name="twitter:title" content="Affiliate Program Status | BlackTechEngine">', false);

    expect(substr_count($response->getContent(), '<title>'))->toBe(1)
        ->and(substr_count($response->getContent(), 'rel="canonical"'))->toBe(1);
});

test('affiliate metadata is escaped and never executes stored content', function () {
    $script = '<script>alert("affiliate")</script>';
    SeoSetting::create([
        'page_name' => 'Affiliate Program',
        'meta_title' => $script,
        'meta_description' => $script,
    ]);

    $response = $this->get(route('affiliate'));

    $response->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false);
});

test('existing public and authentication pages remain available', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk();
})->with([
    'home' => 'home',
    'about' => 'about',
    'pricing' => 'pricing',
    'features' => 'features',
    'blog' => 'blog.index',
    'login' => 'login',
    'registration' => 'register',
]);
