<?php

use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\BrandDnaProfile;
use App\Services\Ai\AiContentPromptBuilder;
use App\Services\Ai\Images\AiImagePromptBuilder;
use App\Services\Ai\Keywords\AiKeywordPromptBuilder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('ai.brand_dna_enabled', true);
    config()->set('ai.brand_dna_generation_enabled', false);
    config()->set('wordpress.allow_local_urls', true);
});

test('customer can view brand dna management page', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $response = $this->actingAs($user)->get(route('workspaces.brands.brand-dna.show', [$workspace, $brand]));

    $response->assertOk();
    $response->assertSee('Brand DNA Profile');
    $response->assertSee($brand->website_url);
});

test('crawler and analyzer safely extract brand dna from website with ssrf protection', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    $brand->update(['website_url' => 'https://example.com']);

    Http::fake([
        'https://example.com' => Http::response(<<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Acme Software - Enterprise SaaS Solutions</title>
    <meta name="description" content="Acme Software builds autonomous marketing and workflow software for modern teams.">
    <meta name="keywords" content="saas, marketing automation, workflow">
</head>
<body>
    <h1>Autonomous Growth Platform</h1>
    <h2>B2B Marketing Infrastructure</h2>
    <p>We empower revenue teams with intelligent content generation and programmatic SEO tools.</p>
</body>
</html>
HTML, 200),
        'https://example.com/about*' => Http::response(<<<'HTML'
<!DOCTYPE html>
<html>
<head><title>About Us - Acme Software</title></head>
<body>
    <h1>Our Mission and Values</h1>
    <p>Driven by transparency and technical precision.</p>
</body>
</html>
HTML, 200),
        'https://example.com/services*' => Http::response('', 404),
    ]);

    $response = $this->actingAs($user)->post(route('workspaces.brands.brand-dna.analyze', [$workspace, $brand]));

    $response->assertRedirect(route('workspaces.brands.brand-dna.show', [$workspace, $brand]));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('brand_dna_profiles', [
        'brand_id' => $brand->id,
        'workspace_id' => $workspace->id,
        'is_approved' => false,
        'status' => 'pending_review',
    ]);

    $profile = BrandDnaProfile::where('brand_id', $brand->id)->first();
    expect($profile)->not->toBeNull()
        ->and($profile->business_description)->toContain('Acme Software builds autonomous marketing')
        ->and($profile->key_services)->not->toBeEmpty()
        ->and($profile->suggested_keywords)->toContain('marketing automation')
        ->and($profile->source_urls)->toContain('https://example.com');
});

test('crawler blocks invalid and ssrf attempt urls when local urls disabled', function () {
    config()->set('wordpress.allow_local_urls', false);
    [$user, $workspace, $brand] = contentStudioTenant();
    $brand->update(['website_url' => 'http://169.254.169.254/metadata']);

    $response = $this->actingAs($user)->post(route('workspaces.brands.brand-dna.analyze', [$workspace, $brand]));

    $response->assertSessionHasErrors();
    expect(BrandDnaProfile::count())->toBe(0);
});

test('customer can update brand dna profile fields', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $profile = BrandDnaProfile::factory()->for($brand)->for($workspace)->create([
        'created_by_user_id' => $user->id,
        'voice_tone' => 'Original Tone',
    ]);

    $response = $this->actingAs($user)->patch(
        route('workspaces.brands.brand-dna.update', [$workspace, $brand, $profile]),
        [
            'brand_identity' => 'Customized Brand Identity',
            'business_description' => 'Updated Business Description',
            'industry' => 'FinTech',
            'target_audience' => 'CFOs and Finance Leaders',
            'primary_market' => 'United Kingdom',
            'voice_tone' => 'Empathetic and Data-Driven',
            'key_services' => "Automated Invoicing\nReal-time Auditing",
            'suggested_keywords' => "fintech automation\ncfo dashboard",
            'content_themes' => "Cash Flow\nCompliance",
            'brand_values' => "Security\nAccuracy",
            'competitor_differentiation' => 'Zero-latency reconciliation engine',
        ]
    );

    $response->assertRedirect(route('workspaces.brands.brand-dna.show', [$workspace, $brand]));

    $profile->refresh();
    expect($profile->brand_identity)->toBe('Customized Brand Identity')
        ->and($profile->voice_tone)->toBe('Empathetic and Data-Driven')
        ->and($profile->industry)->toBe('FinTech')
        ->and($profile->key_services)->toBe(['Automated Invoicing', 'Real-time Auditing'])
        ->and($profile->brand_values)->toBe(['Security', 'Accuracy']);
});

test('approving brand dna activates profile and preserves brand core settings when sync is unchecked', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    $brand->update([
        'business_description' => 'Original Brand Manual Description',
        'industry' => 'Original Industry',
    ]);

    $profile = BrandDnaProfile::factory()->for($brand)->for($workspace)->create([
        'created_by_user_id' => $user->id,
        'business_description' => 'Generated DNA Description',
        'industry' => 'Generated Industry',
        'is_approved' => false,
    ]);

    $response = $this->actingAs($user)->post(
        route('workspaces.brands.brand-dna.approve', [$workspace, $brand, $profile]),
        ['sync_to_brand' => '0']
    );

    $response->assertRedirect(route('workspaces.brands.brand-dna.show', [$workspace, $brand]));

    $profile->refresh();
    expect($profile->is_approved)->toBeTrue()
        ->and($profile->status)->toBe('approved')
        ->and($profile->approved_by_user_id)->toBe($user->id);

    // Brand core attributes are strictly preserved
    $brand->refresh();
    expect($brand->business_description)->toBe('Original Brand Manual Description')
        ->and($brand->industry)->toBe('Original Industry');
});

test('approving brand dna with sync updates brand core settings', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    $brand->update([
        'business_description' => 'Old Description',
        'voice_tone' => 'Old Tone',
    ]);

    $profile = BrandDnaProfile::factory()->for($brand)->for($workspace)->create([
        'created_by_user_id' => $user->id,
        'business_description' => 'New DNA Description',
        'voice_tone' => 'Crisp, Technical, Bold',
        'content_themes' => ['Cloud Architecture', 'DevOps'],
        'is_approved' => false,
    ]);

    $response = $this->actingAs($user)->post(
        route('workspaces.brands.brand-dna.approve', [$workspace, $brand, $profile]),
        ['sync_to_brand' => '1']
    );

    $response->assertRedirect(route('workspaces.brands.brand-dna.show', [$workspace, $brand]));

    $brand->refresh();
    expect($brand->business_description)->toBe('New DNA Description')
        ->and($brand->voice_tone)->toBe('Crisp, Technical, Bold')
        ->and($brand->seo_topic_categories)->toBe(['Cloud Architecture', 'DevOps']);
});

test('approved brand dna is reused in article, keyword and image prompts', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $profile = BrandDnaProfile::factory()->approved()->for($brand)->for($workspace)->create([
        'created_by_user_id' => $user->id,
        'brand_identity' => 'Pioneer in Sustainable Logistics',
        'key_services' => ['Route Optimization', 'Carbon Offsetting'],
        'suggested_keywords' => ['green logistics', 'fleet efficiency'],
        'content_themes' => ['Clean Transport', 'Supply Chain Tech'],
        'brand_values' => ['Sustainability', 'Precision'],
        'competitor_differentiation' => 'AI-driven green corridor routing',
    ]);

    // 1. Article Content Prompt Builder
    $contentBuilder = app(AiContentPromptBuilder::class);
    $prompt = $contentBuilder->build($brand, [
        'content_type' => 'seo_blog_article',
        'content_length' => 'standard',
        'topic' => 'Future of Fleet Decarbonization',
        'primary_keyword' => 'green logistics',
        'target_audience' => 'Fleet Managers',
        'language' => 'English',
        'region' => 'United States',
        'tone' => 'Analytical',
    ]);

    expect($prompt)->toContain('Pioneer in Sustainable Logistics')
        ->and($prompt)->toContain('Route Optimization')
        ->and($prompt)->toContain('AI-driven green corridor routing');

    // 2. Keyword Strategy Prompt Builder
    $keywordBuilder = app(AiKeywordPromptBuilder::class);
    $kwPrompt = $keywordBuilder->build($brand, [
        'seed_keyword' => 'fleet decarbonization',
    ]);

    expect($kwPrompt)->toContain('green logistics')
        ->and($kwPrompt)->toContain('Clean Transport');

    // 3. Featured Image Prompt Builder
    $draft = AiContentDraft::factory()->for($brand)->for($workspace)->create([
        'topic' => 'Autonomous Fleet Efficiency',
        'seo_title' => 'Green Logistics in 2026',
        'primary_keyword' => 'fleet efficiency',
    ]);

    $imageBuilder = app(AiImagePromptBuilder::class);
    $imgPrompt = $imageBuilder->buildPrompt($draft);

    expect($imgPrompt)->toContain('brand values: Sustainability, Precision')
        ->and($imgPrompt)->toContain('brand identity: Pioneer in Sustainable Logistics');
});

test('tenant boundary blocks unauthorized users from viewing or modifying brand dna', function () {
    [$userA, $workspaceA, $brandA] = contentStudioTenant();
    [$userB, $workspaceB, $brandB] = contentStudioTenant();

    $profileA = BrandDnaProfile::factory()->for($brandA)->for($workspaceA)->create([
        'created_by_user_id' => $userA->id,
    ]);

    // User B attempts to view Brand A DNA
    $response = $this->actingAs($userB)->get(route('workspaces.brands.brand-dna.show', [$workspaceA, $brandA]));
    expect($response->status())->toBeIn([403, 404]);

    // User B attempts to analyze Brand A
    $response = $this->actingAs($userB)->post(route('workspaces.brands.brand-dna.analyze', [$workspaceA, $brandA]));
    expect($response->status())->toBeIn([403, 404]);

    // User B attempts to update Profile A
    $response = $this->actingAs($userB)->patch(
        route('workspaces.brands.brand-dna.update', [$workspaceA, $brandA, $profileA]),
        ['brand_identity' => 'Hacked']
    );
    expect($response->status())->toBeIn([403, 404]);

    // User B attempts to approve Profile A
    $response = $this->actingAs($userB)->post(
        route('workspaces.brands.brand-dna.approve', [$workspaceA, $brandA, $profileA])
    );
    expect($response->status())->toBeIn([403, 404]);
});
