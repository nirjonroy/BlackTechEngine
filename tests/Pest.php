<?php

use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function validWorkspacePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Acme Growth',
        'timezone' => 'Asia/Dhaka',
    ], $overrides);
}

function validBrandPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Acme AI',
        'website_url' => 'https://acme.example',
        'business_description' => 'A practical SaaS platform for growing teams.',
        'industry' => 'SaaS',
        'target_audience' => 'Marketing teams at growing B2B companies.',
        'primary_market' => 'United States',
        'preferred_language' => 'English',
        'voice_tone' => 'Clear, confident, and helpful.',
        'content_objectives' => "Build qualified traffic\nEducate buyers",
        'seo_topic_categories' => "Technical SEO\nContent operations",
        'is_active' => '1',
    ], $overrides);
}

function contentStudioTenant(?User $user = null): array
{
    $user ??= User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $workspace->members()->attach($user, ['role' => 'owner']);
    $brand = Brand::factory()->for($workspace)->create();

    return [$user, $workspace, $brand];
}

function contentDraftPayload(array $overrides = []): array
{
    return array_merge([
        'content_type' => AiContentType::SeoBlogArticle->value,
        'topic' => 'A safe guide to technical SEO',
        'primary_keyword' => 'technical seo guide',
        'secondary_keywords' => "site audits\nsearch visibility",
        'target_audience' => 'Marketing leaders at growing SaaS companies.',
        'language' => 'English',
        'region' => 'United States',
        'content_length' => AiContentLength::Standard->value,
        'tone' => 'Clear and practical.',
        'seo_title' => 'A Safe Technical SEO Guide',
        'meta_description' => 'Learn a practical and careful approach to technical SEO.',
        'suggested_slug' => 'safe-technical-seo-guide',
        'outline' => "Introduction\nAudit foundations\nConclusion",
        'content' => "# A Safe Guide\n\nReview every claim before publishing.",
        'faq_entries' => [['question' => 'What is technical SEO?', 'answer' => 'It improves how search systems access a site.']],
        'internal_link_opportunities' => 'SEO audit page',
        'external_research_topics' => 'Current search engine documentation',
        'verification_notes' => 'Verify current platform limits',
        'lock_version' => 0,
    ], $overrides);
}
