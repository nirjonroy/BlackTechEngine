<?php

use App\Models\SeoSetting;
use App\Models\SiteSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function createGoogleAnalyticsTable(): void
{
    if (Schema::hasTable('google_analytics')) {
        DB::table('google_analytics')->delete();

        return;
    }

    Schema::create('google_analytics', function (Blueprint $table) {
        $table->id();
        $table->boolean('status')->default(false);
        $table->text('analytic_id')->nullable();
        $table->text('analytic_script')->nullable();
    });
}

test('homepage uses its page-specific SEO record and canonical URL', function () {
    SiteSetting::create(['site_name' => 'Fallback Brand']);
    SeoSetting::create([
        'page_name' => 'About',
        'meta_title' => 'Wrong Page Title',
    ]);
    SeoSetting::create([
        'page_name' => 'Home Page',
        'meta_title' => 'Homepage SEO Title',
        'meta_description' => 'Homepage SEO description.',
        'keywords' => 'seo, automation',
        'author' => 'BlackTechEngine',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('<title>Homepage SEO Title</title>', false)
        ->assertSee('<meta name="description" content="Homepage SEO description.">', false)
        ->assertSee('<meta name="keywords" content="seo, automation">', false)
        ->assertSee('<meta property="og:title" content="Homepage SEO Title">', false)
        ->assertSee('<meta name="twitter:title" content="Homepage SEO Title">', false)
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
        ->assertDontSee('Wrong Page Title');

    expect(substr_count($response->getContent(), '<title>'))->toBe(1)
        ->and(substr_count($response->getContent(), 'rel="canonical"'))->toBe(1);
});

test('frontend SEO falls back to site settings without using another page record', function () {
    SiteSetting::create(['site_name' => 'Fallback Brand']);
    SeoSetting::create([
        'page_name' => 'About',
        'meta_title' => 'About Only Title',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>Fallback Brand</title>', false)
        ->assertSee('<meta property="og:site_name" content="Fallback Brand">', false)
        ->assertDontSee('About Only Title');
});

test('a valid GA4 measurement ID renders the controlled integration', function () {
    createGoogleAnalyticsTable();
    DB::table('google_analytics')->insert([
        'status' => true,
        'analytic_id' => 'G-ABC1234567',
        'analytic_script' => null,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC1234567', false)
        ->assertSee("gtag('config', 'G-ABC1234567');", false)
        ->assertDontSee('application/ld+json', false);
});

test('a legacy GTM script stores only the validated container ID in rendered output', function () {
    createGoogleAnalyticsTable();
    DB::table('google_analytics')->insert([
        'status' => true,
        'analytic_id' => null,
        'analytic_script' => '<script>GTM-ABC1234; alert("stored payload")</script>',
    ]);

    $response = $this->get(route('about'));

    $response->assertOk()
        ->assertSee("'GTM-ABC1234'", false)
        ->assertSee('https://www.googletagmanager.com/ns.html?id=GTM-ABC1234', false)
        ->assertDontSee('alert("stored payload")', false);
});

test('invalid analytics and SEO scripts never render as executable content', function () {
    createGoogleAnalyticsTable();
    DB::table('google_analytics')->insert([
        'status' => true,
        'analytic_id' => 'not-an-analytics-id',
        'analytic_script' => '<script>alert("analytics injection")</script>',
    ]);
    SeoSetting::create([
        'page_name' => 'Home',
        'meta_title' => '<script>alert("seo injection")</script>',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('<script>alert("seo injection")</script>')
        ->assertDontSee('<script>alert("seo injection")</script>', false)
        ->assertDontSee('alert("analytics injection")', false)
        ->assertDontSee('googletagmanager.com/gtag', false)
        ->assertDontSee('googletagmanager.com/gtm.js', false);
});

test('shared navigation renders accessible mobile controls and only valid routes', function () {
    $response = $this->get(route('about'));

    $response->assertOk()
        ->assertSee('id="mobileMenuBtn"', false)
        ->assertSee('aria-controls="mobileMenu"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('id="mobileMenu"', false)
        ->assertSee('aria-label="Mobile navigation"', false)
        ->assertSee('href="'.route('home').'"', false)
        ->assertSee('href="'.route('about').'"', false)
        ->assertSee('href="'.route('pricing').'"', false)
        ->assertDontSee('href="/features"', false)
        ->assertDontSee('href="/blog"', false);
});
