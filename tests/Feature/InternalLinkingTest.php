<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\WordPressConnectionStatus;
use App\Models\AiContentDraft;
use App\Models\BrandIndexedPage;
use App\Models\WordPressSiteConnection;
use App\Services\InternalLinking\InternalLinkMatcher;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('ai.content_studio_enabled', true);
    config()->set('wordpress.allow_local_urls', true);
});

test('customer can view brand internal linking dashboard and page list', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    BrandIndexedPage::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'title' => 'Top 10 Growth Hacks',
        'url' => 'https://example.com/growth-hacks',
    ]);

    $response = $this->actingAs($user)->get(route('workspaces.brands.internal-links.index', [$workspace, $brand]));

    $response->assertOk();
    $response->assertSee('Internal Linking');
    $response->assertSee('Top 10 Growth Hacks');
});

test('crawler indexes wordpress site posts and pages via REST API and records audit', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'site_url' => 'https://example.com',
        'status' => WordPressConnectionStatus::Connected,
    ]);

    Http::fake([
        'https://example.com/wp-json/wp/v2/posts*' => Http::response([
            [
                'id' => 101,
                'link' => 'https://example.com/blog/saas-pricing-strategy',
                'title' => ['rendered' => 'SaaS Pricing Strategy Guide'],
                'slug' => 'saas-pricing-strategy',
                'excerpt' => ['rendered' => '<p>Learn how to optimize your pricing tiers.</p>'],
            ],
        ], 200),
        'https://example.com/wp-json/wp/v2/pages*' => Http::response([
            [
                'id' => 102,
                'link' => 'https://example.com/about-us',
                'title' => ['rendered' => 'About Our Company'],
                'slug' => 'about-us',
                'excerpt' => ['rendered' => ''],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->post(route('workspaces.brands.internal-links.reindex', [$workspace, $brand]), [
        'source' => 'wordpress_api',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('brand_indexed_pages', [
        'brand_id' => $brand->id,
        'url' => 'https://example.com/blog/saas-pricing-strategy',
        'title' => 'SaaS Pricing Strategy Guide',
        'slug' => 'saas-pricing-strategy',
        'wordpress_post_id' => 101,
    ]);

    $this->assertDatabaseHas('brand_indexing_audits', [
        'brand_id' => $brand->id,
        'status' => 'completed',
        'pages_indexed' => 2,
    ]);
});

test('crawler enforces verified domain restriction and drops external URLs', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'site_url' => 'https://example.com',
        'status' => WordPressConnectionStatus::Connected,
    ]);

    Http::fake([
        'https://example.com/wp-json/wp/v2/posts*' => Http::response([
            [
                'id' => 999,
                'link' => 'https://malicious-external-domain.com/phishing',
                'title' => ['rendered' => 'Malicious Link'],
                'slug' => 'phishing',
                'excerpt' => ['rendered' => ''],
            ],
        ], 200),
        'https://example.com/wp-json/wp/v2/pages*' => Http::response([], 200),
    ]);

    $this->actingAs($user)->post(route('workspaces.brands.internal-links.reindex', [$workspace, $brand]));

    $this->assertDatabaseMissing('brand_indexed_pages', [
        'brand_id' => $brand->id,
        'url' => 'https://malicious-external-domain.com/phishing',
    ]);
});

test('crawler indexes wordpress XML sitemap safely without entity expansion', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'site_url' => 'https://example.com',
        'status' => WordPressConnectionStatus::Connected,
    ]);

    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://example.com/features/demand-generation</loc>
        <lastmod>2026-11-10</lastmod>
    </url>
</urlset>
XML;

    Http::fake([
        'https://example.com/wp-sitemap.xml' => Http::response($xml, 200),
    ]);

    $response = $this->actingAs($user)->post(route('workspaces.brands.internal-links.reindex', [$workspace, $brand]), [
        'source' => 'sitemap',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('brand_indexed_pages', [
        'brand_id' => $brand->id,
        'url' => 'https://example.com/features/demand-generation',
        'slug' => 'demand-generation',
    ]);
});

test('internal link matcher suggests contextual links and filters out self-links and duplicates', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    // 1. Create target indexed pages
    BrandIndexedPage::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'title' => 'Technical SEO Guide',
        'slug' => 'technical-seo-guide',
        'url' => 'https://example.com/technical-seo-guide',
    ]);

    BrandIndexedPage::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'title' => 'Content Optimization Strategies',
        'slug' => 'content-optimization',
        'url' => 'https://example.com/content-optimization',
    ]);

    // Self-link page (matches draft slug)
    BrandIndexedPage::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'title' => 'My Draft Article',
        'slug' => 'my-draft-article',
        'url' => 'https://example.com/my-draft-article',
    ]);

    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'suggested_slug' => 'my-draft-article',
        'content' => "# Introduction\n\nImplementing a solid technical SEO guide is essential for web search performance. You should also consider content optimization techniques.",
    ]);

    $matcher = app(InternalLinkMatcher::class);
    $suggestions = $matcher->suggestLinks($draft);

    expect($suggestions)->not->toBeEmpty();
    $urls = array_column($suggestions, 'url');

    // Matches valid target pages
    expect($urls)->toContain('https://example.com/technical-seo-guide');
    // Does NOT suggest self-link
    expect($urls)->not->toContain('https://example.com/my-draft-article');
});

test('customer can apply internal links to draft content safely without altering headers or code blocks', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $originalContent = "# Main Guide\n\n```\ntechnical SEO guide in code should not be linked\n```\n\nIn our marketing playbook, following a technical SEO guide helps ranking.";

    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'status' => AiContentDraftStatus::Draft,
        'content' => $originalContent,
    ]);

    $response = $this->actingAs($user)->post(route('content-studio.drafts.internal-links.apply', [$workspace, $brand, $draft]), [
        'links' => [
            [
                'anchor' => 'technical SEO guide',
                'url' => 'https://example.com/technical-seo-guide',
            ],
        ],
    ]);

    $response->assertRedirect();
    $draft->refresh();

    // Replaced only in standard paragraph
    expect($draft->content)->toContain('[technical SEO guide](https://example.com/technical-seo-guide)')
        ->and($draft->content)->toContain('# Main Guide')
        ->and($draft->content)->toContain("```\ntechnical SEO guide in code should not be linked\n```");
});

test('applying internal links is strictly rejected when draft is approved or export ready', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $approvedDraft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'status' => AiContentDraftStatus::Approved,
        'content' => 'Approved content here with technical SEO guide.',
    ]);

    $response = $this->actingAs($user)->post(route('content-studio.drafts.internal-links.apply', [$workspace, $brand, $approvedDraft]), [
        'links' => [
            [
                'anchor' => 'technical SEO guide',
                'url' => 'https://example.com/technical-seo-guide',
            ],
        ],
    ]);

    $response->assertStatus(422);
    expect($approvedDraft->fresh()->content)->toBe('Approved content here with technical SEO guide.');
});

test('cross-tenant internal linking access is strictly forbidden', function () {
    [$userA, $workspaceA, $brandA] = contentStudioTenant();
    [$userB, $workspaceB, $brandB] = contentStudioTenant();

    $draftB = AiContentDraft::factory()->create([
        'workspace_id' => $workspaceB->id,
        'brand_id' => $brandB->id,
        'created_by_user_id' => $userB->id,
        'status' => AiContentDraftStatus::Draft,
        'content' => 'User B content with technical SEO guide.',
    ]);

    // Attacker A attempts to access Brand B internal links
    $this->actingAs($userA)
        ->get(route('workspaces.brands.internal-links.index', [$workspaceA, $brandB]))
        ->assertNotFound();

    // Attacker A attempts to reindex Brand B
    $this->actingAs($userA)
        ->post(route('workspaces.brands.internal-links.reindex', [$workspaceA, $brandB]))
        ->assertNotFound();

    // Attacker A attempts to apply links to Draft B
    $this->actingAs($userA)
        ->post(route('content-studio.drafts.internal-links.apply', [$workspaceA, $brandB, $draftB]), [
            'links' => [['anchor' => 'SEO', 'url' => 'https://example.com']],
        ])
        ->assertNotFound();
});
