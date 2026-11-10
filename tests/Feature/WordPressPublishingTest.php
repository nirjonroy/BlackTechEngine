<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\WordPressConnectionStatus;
use App\Enums\WordPressPostStatus;
use App\Models\AiContentDraft;
use App\Models\WordPressPublishedPost;
use App\Models\WordPressSiteConnection;
use App\Services\WordPress\WordPressPublishingService;
use App\Services\WordPress\WordPressUrlValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

test('url validator blocks SSRF private and reserved IPs in production mode', function (string $unsafeUrl) {
    config()->set('wordpress.allow_local_urls', false);
    $validator = new WordPressUrlValidator;

    expect(fn () => $validator->validateAndResolve($unsafeUrl))
        ->toThrow(ValidationException::class);
})->with([
    'http://127.0.0.1',
    'http://localhost',
    'http://169.254.169.254/latest/meta-data',
    'http://10.0.0.1',
    'http://192.168.1.1',
    'ftp://example.com',
    'javascript:alert(1)',
]);

test('url validator resolves and pins public IP for DNS rebinding protection', function () {
    config()->set('wordpress.allow_local_urls', false);
    $validator = new WordPressUrlValidator;

    // Test a reliable public domain (e.g. one.one.one.one or google.com)
    $resolved = $validator->validateAndResolve('https://one.one.one.one');

    expect($resolved['host'])->toBe('one.one.one.one')
        ->and($resolved['port'])->toBe(443)
        ->and($resolved['pinned_ip'])->not->toBeNull()
        ->and(filter_var($resolved['pinned_ip'], FILTER_VALIDATE_IP))->not->toBeFalse();
});

test('customer can connect and verify a valid wordpress site', function () {
    config()->set('wordpress.allow_local_urls', true);
    [$owner, $workspace, $brand] = contentStudioTenant();

    Http::fake([
        'https://my-blog.test/wp-json/' => Http::response([
            'name' => 'My WordPress Blog',
            'namespaces' => ['wp/v2'],
        ], 200),
        'https://my-blog.test/wp-json/wp/v2/users/me*' => Http::response([
            'id' => 42,
            'name' => 'Editor Dave',
            'capabilities' => ['publish_posts' => true],
            'roles' => ['editor'],
        ], 200),
    ]);

    $response = $this->actingAs($owner)->post(
        route('workspaces.brands.wordpress.connect', [$workspace, $brand]),
        [
            'site_url' => 'https://my-blog.test',
            'username' => 'editor_dave',
            'application_password' => 'abcd efgh ijkl mnop',
        ]
    );

    $response->assertRedirect(route('workspaces.brands.show', [$workspace, $brand]));
    $response->assertSessionHas('status', 'WordPress site connected and verified successfully.');

    $connection = WordPressSiteConnection::query()->where('brand_id', $brand->id)->first();
    expect($connection)->not->toBeNull()
        ->and($connection->status)->toBe(WordPressConnectionStatus::Connected)
        ->and($connection->site_name)->toBe('My WordPress Blog')
        ->and($connection->username)->toBe('editor_dave')
        ->and($connection->application_password)->toBe('abcdefghijklmnop');
});

test('customer can re-verify and disconnect wordpress connection', function () {
    config()->set('wordpress.allow_local_urls', true);
    [$owner, $workspace, $brand] = contentStudioTenant();

    $connection = WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'site_url' => 'https://my-blog.test',
        'username' => 'editor_dave',
        'application_password' => 'secret1234',
        'status' => WordPressConnectionStatus::Connected,
    ]);

    Http::fake([
        'https://my-blog.test/wp-json/' => Http::response(['name' => 'Verified Blog', 'namespaces' => ['wp/v2']], 200),
        'https://my-blog.test/wp-json/wp/v2/users/me*' => Http::response([
            'id' => 42,
            'name' => 'Editor Dave',
            'capabilities' => ['publish_posts' => true],
        ], 200),
    ]);

    $verifyResponse = $this->actingAs($owner)->post(
        route('workspaces.brands.wordpress.verify', [$workspace, $brand])
    );
    $verifyResponse->assertRedirect(route('workspaces.brands.show', [$workspace, $brand]));
    expect($connection->fresh()->status)->toBe(WordPressConnectionStatus::Connected);

    $disconnectResponse = $this->actingAs($owner)->delete(
        route('workspaces.brands.wordpress.disconnect', [$workspace, $brand])
    );
    $disconnectResponse->assertRedirect(route('workspaces.brands.show', [$workspace, $brand]));
    expect($connection->fresh()->status)->toBe(WordPressConnectionStatus::Disconnected);
});

test('customer can publish approved article draft to connected wordpress site', function () {
    config()->set('wordpress.allow_local_urls', true);
    [$owner, $workspace, $brand] = contentStudioTenant();

    $connection = WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'site_url' => 'https://my-blog.test',
        'username' => 'editor_dave',
        'application_password' => 'secret1234',
        'status' => WordPressConnectionStatus::Connected,
    ]);

    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'topic' => 'Topical Authority Guide',
        'seo_title' => 'The Complete Topical Authority Guide',
        'suggested_slug' => 'topical-authority-guide',
        'meta_description' => 'A structured guide to ranking.',
        'content' => "## Strategic SEO\n\nBuild topical clusters.",
        'faq_entries' => [
            ['question' => 'What is topical authority?', 'answer' => 'Depth of subject matter coverage.'],
        ],
        'status' => AiContentDraftStatus::Approved,
    ]);

    Http::fake([
        'https://my-blog.test/wp-json/wp/v2/posts' => Http::response([
            'id' => 9876,
            'link' => 'https://my-blog.test/topical-authority-guide/',
            'status' => 'draft',
        ], 201),
    ]);

    $response = $this->actingAs($owner)->post(
        route('content-studio.drafts.wordpress.publish', [$workspace, $brand, $draft]),
        ['target_status' => 'draft']
    );

    $response->assertRedirect(route('content-studio.drafts.show', [$workspace, $brand, $draft]));
    $response->assertSessionHas('status');

    $publication = WordPressPublishedPost::query()
        ->where('ai_content_draft_id', $draft->id)
        ->first();

    expect($publication)->not->toBeNull()
        ->and($publication->wordpress_post_id)->toBe(9876)
        ->and($publication->post_url)->toBe('https://my-blog.test/topical-authority-guide/')
        ->and($publication->status)->toBe(WordPressPostStatus::Draft)
        ->and($publication->transferred_payload['title'])->toBe('The Complete Topical Authority Guide')
        ->and($publication->transferred_payload['meta']['_yoast_wpseo_metadesc'])->toBe('A structured guide to ranking.');
});

test('subsequent publish reuses existing wordpress post id for update instead of creating duplicate', function () {
    config()->set('wordpress.allow_local_urls', true);
    [$owner, $workspace, $brand] = contentStudioTenant();

    $connection = WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'site_url' => 'https://my-blog.test',
        'username' => 'editor_dave',
        'application_password' => 'secret1234',
        'status' => WordPressConnectionStatus::Connected,
    ]);

    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'topic' => 'Initial Topic',
        'status' => AiContentDraftStatus::Approved,
    ]);

    // Initial publish
    WordPressPublishedPost::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'wordpress_site_connection_id' => $connection->id,
        'ai_content_draft_id' => $draft->id,
        'wordpress_post_id' => 5555,
        'status' => WordPressPostStatus::Draft,
    ]);

    // Expect POST to update endpoint /posts/5555
    Http::fake([
        'https://my-blog.test/wp-json/wp/v2/posts/5555' => Http::response([
            'id' => 5555,
            'link' => 'https://my-blog.test/updated-topic/',
            'status' => 'publish',
        ], 200),
    ]);

    $this->actingAs($owner)->post(
        route('content-studio.drafts.wordpress.publish', [$workspace, $brand, $draft]),
        ['target_status' => 'publish']
    );

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/wp-json/wp/v2/posts/5555'));
});

test('atomic lock prevents concurrent publishing for the same draft', function () {
    config()->set('wordpress.allow_local_urls', true);
    [$owner, $workspace, $brand] = contentStudioTenant();

    WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'status' => WordPressConnectionStatus::Connected,
    ]);

    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'status' => AiContentDraftStatus::Approved,
    ]);

    // Acquire lock externally to simulate ongoing concurrent process
    $lock = Cache::lock("wp-publish:draft:{$draft->id}", 30);
    $lock->get();

    try {
        $response = $this->actingAs($owner)->post(
            route('content-studio.drafts.wordpress.publish', [$workspace, $brand, $draft]),
            ['target_status' => 'draft']
        );

        $response->assertSessionHasErrors('publishing');
    } finally {
        $lock->release();
    }
});

test('ambiguous remote timeout records failure and blocks blind retry without reconciliation', function () {
    config()->set('wordpress.allow_local_urls', true);
    [$owner, $workspace, $brand] = contentStudioTenant();

    $connection = WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'site_url' => 'https://my-blog.test',
        'status' => WordPressConnectionStatus::Connected,
    ]);

    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'status' => AiContentDraftStatus::Approved,
    ]);

    // Record an earlier ambiguous timeout
    WordPressPublishedPost::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'wordpress_site_connection_id' => $connection->id,
        'ai_content_draft_id' => $draft->id,
        'wordpress_post_id' => null,
        'status' => WordPressPostStatus::Failed,
        'last_error' => WordPressPublishingService::AMBIGUOUS_TIMEOUT_MESSAGE,
    ]);

    // Blind retry without reconciled flag must fail validation
    $response = $this->actingAs($owner)->post(
        route('content-studio.drafts.wordpress.publish', [$workspace, $brand, $draft]),
        ['target_status' => 'draft']
    );

    $response->assertSessionHasErrors('reconciliation');

    // Retry with explicit reconciled flag proceeds
    Http::fake([
        'https://my-blog.test/wp-json/wp/v2/posts' => Http::response([
            'id' => 7777,
            'link' => 'https://my-blog.test/post/',
            'status' => 'draft',
        ], 201),
    ]);

    $reconciledResponse = $this->actingAs($owner)->post(
        route('content-studio.drafts.wordpress.publish', [$workspace, $brand, $draft]),
        ['target_status' => 'draft', 'reconciled' => true]
    );

    $reconciledResponse->assertRedirect(route('content-studio.drafts.show', [$workspace, $brand, $draft]));
    $reconciledResponse->assertSessionHas('status');
});

test('unapproved drafts cannot be published to wordpress', function () {
    config()->set('wordpress.allow_local_urls', true);
    [$owner, $workspace, $brand] = contentStudioTenant();

    WordPressSiteConnection::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'status' => WordPressConnectionStatus::Connected,
    ]);

    $unapprovedDraft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'status' => AiContentDraftStatus::Draft,
    ]);

    $response = $this->actingAs($owner)->post(
        route('content-studio.drafts.wordpress.publish', [$workspace, $brand, $unapprovedDraft]),
        ['target_status' => 'draft']
    );

    $response->assertSessionHasErrors('status');
    expect(WordPressPublishedPost::count())->toBe(0);
});

test('cross-tenant publishing is strictly forbidden', function () {
    config()->set('wordpress.allow_local_urls', true);
    [$owner, $workspace, $brand] = contentStudioTenant();
    [$attacker, $otherWorkspace, $otherBrand] = contentStudioTenant();

    $otherDraft = AiContentDraft::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'brand_id' => $otherBrand->id,
        'status' => AiContentDraftStatus::Approved,
    ]);

    // Attacker tries to publish other workspace's draft through attacker's brand/workspace
    $response = $this->actingAs($attacker)->post(
        route('content-studio.drafts.wordpress.publish', [$workspace, $brand, $otherDraft]),
        ['target_status' => 'draft']
    );

    $response->assertNotFound();
});
