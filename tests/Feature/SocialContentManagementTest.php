<?php

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\SocialPostVariant;
use App\Models\User;
use App\Models\Workspace;
use App\Services\SocialPostWorkflow;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function socialTenant(?User $user = null): array
{
    $user ??= User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create(['settings' => ['timezone' => 'Asia/Dhaka']]);
    $workspace->members()->attach($user, ['role' => 'owner']);
    $brand = Brand::factory()->for($workspace)->create();

    return [$user, $workspace, $brand];
}

function socialPostPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'idempotency_key' => Str::uuid()->toString(),
        'title' => 'Technical SEO launch campaign',
        'campaign' => 'Q4 launch',
        'variants' => [[
            'platform' => SocialPlatform::LinkedIn->value,
            'caption' => 'A practical technical SEO guide for growing teams.',
            'hashtags' => '#SEO #SaaS',
            'cta_text' => 'Read the guide',
            'target_url' => 'https://example.com/guide',
            'image_alt_text' => 'A technical SEO dashboard illustration.',
        ]],
    ], $overrides);
}

test('guests are redirected to customer login from social content', function () {
    $this->get(route('social-content.index'))->assertRedirect(route('login'));
});

test('workspace members create platform variants without publishing or outbound requests', function () {
    Http::preventStrayRequests();
    [$user, $workspace, $brand] = socialTenant();

    $response = $this->actingAs($user)->post(route('social-content.posts.store', [$workspace, $brand]), socialPostPayload());

    $response->assertSessionHasNoErrors();
    $post = SocialPost::query()->sole();
    $response->assertRedirect(route('social-content.posts.show', [$workspace, $brand, $post]));
    expect($post->status)->toBe(SocialPostStatus::Draft);
    $this->assertDatabaseHas('social_post_variants', ['social_post_id' => $post->id, 'platform' => 'linkedin']);
    $this->assertDatabaseHas('social_post_events', ['social_post_id' => $post->id, 'action' => 'created']);
    $this->assertDatabaseCount('social_post_revisions', 1);
});

test('identical create replays return the original post without duplicate side effects', function () {
    [$user, $workspace, $brand] = socialTenant();
    $payload = socialPostPayload(['idempotency_key' => '3de64ca5-45f7-4cb5-af92-d958fca4af3a']);

    $first = $this->actingAs($user)->post(route('social-content.posts.store', [$workspace, $brand]), $payload);
    $second = $this->actingAs($user)->post(route('social-content.posts.store', [$workspace, $brand]), $payload);

    $post = SocialPost::query()->sole();
    $first->assertRedirect(route('social-content.posts.show', [$workspace, $brand, $post]));
    $second->assertRedirect(route('social-content.posts.show', [$workspace, $brand, $post]));
    $this->assertDatabaseCount('social_posts', 1);
    $this->assertDatabaseCount('social_post_variants', 1);
    $this->assertDatabaseCount('social_post_revisions', 1);
    $this->assertDatabaseCount('social_post_events', 1);
});

test('conflicting or cross user idempotency key reuse is rejected without disclosure', function () {
    [$owner, $workspace, $brand] = socialTenant();
    $member = User::factory()->create();
    $workspace->members()->attach($member, ['role' => 'member']);
    $key = 'bf64aa1e-02ef-45b7-a4c9-da6e799fc5b6';

    $this->actingAs($owner)->post(route('social-content.posts.store', [$workspace, $brand]), socialPostPayload(['idempotency_key' => $key]))->assertRedirect();
    $this->actingAs($owner)->post(route('social-content.posts.store', [$workspace, $brand]), socialPostPayload(['idempotency_key' => $key, 'title' => 'Conflicting content']))->assertConflict();
    $this->actingAs($member)->post(route('social-content.posts.store', [$workspace, $brand]), socialPostPayload(['idempotency_key' => $key]))->assertConflict();

    $this->assertDatabaseCount('social_posts', 1);
    $this->assertDatabaseCount('social_post_variants', 1);
    $this->assertDatabaseCount('social_post_revisions', 1);
    $this->assertDatabaseCount('social_post_events', 1);
});

test('the same idempotency key cannot retrieve a post from another workspace', function () {
    [$firstUser, $firstWorkspace, $firstBrand] = socialTenant();
    [$secondUser, $secondWorkspace, $secondBrand] = socialTenant();
    $key = '0170bd94-93fb-4f78-af3f-e0b4857655bc';

    $this->actingAs($firstUser)->post(route('social-content.posts.store', [$firstWorkspace, $firstBrand]), socialPostPayload(['idempotency_key' => $key]))->assertRedirect();
    $this->actingAs($secondUser)->post(route('social-content.posts.store', [$secondWorkspace, $secondBrand]), socialPostPayload(['idempotency_key' => $key]))->assertRedirect();

    $this->assertDatabaseCount('social_posts', 2);
    expect(SocialPost::query()->where('idempotency_key', $key)->pluck('workspace_id')->sort()->values()->all())
        ->toBe(collect([$firstWorkspace->id, $secondWorkspace->id])->sort()->values()->all());
});

test('a failed create transaction leaves no partial post variants revision or event', function () {
    [$user, $workspace, $brand] = socialTenant();

    expect(fn () => app(SocialPostWorkflow::class)->create(
        $workspace,
        $brand,
        $user,
        '747169ef-12c5-4a01-9103-b1ec7f918d22',
        ['title' => 'Atomic create', 'campaign' => null],
        [['platform' => SocialPlatform::LinkedIn->value, 'caption' => null]],
        null,
    ))->toThrow(QueryException::class);

    $this->assertDatabaseCount('social_posts', 0);
    $this->assertDatabaseCount('social_post_variants', 0);
    $this->assertDatabaseCount('social_post_revisions', 0);
    $this->assertDatabaseCount('social_post_events', 0);
});

test('all supported platforms persist once and duplicate platforms are rejected', function () {
    [$user, $workspace, $brand] = socialTenant();
    $variants = collect(SocialPlatform::cases())->map(fn (SocialPlatform $platform): array => [
        'platform' => $platform->value,
        'caption' => 'Reviewed copy for '.$platform->label(),
        'hashtags' => '#BlackTech',
    ])->all();

    $this->actingAs($user)->post(route('social-content.posts.store', [$workspace, $brand]), socialPostPayload(['variants' => $variants]))->assertRedirect();

    $this->assertDatabaseCount('social_post_variants', 5);
    expect(SocialPostVariant::query()->get()->pluck('platform')->map(fn (SocialPlatform $platform): string => $platform->value)->sort()->values()->all())
        ->toBe(collect(SocialPlatform::cases())->pluck('value')->sort()->values()->all());

    $duplicate = [$variants[0], $variants[0]];
    $this->actingAs($user)->post(route('social-content.posts.store', [$workspace, $brand]), socialPostPayload(['title' => 'Duplicate platform', 'variants' => $duplicate]))->assertSessionHasErrors('variants.1.platform');
    $this->assertDatabaseCount('social_posts', 1);
});

test('approved article repurposing stores a revision snapshot and reports later source changes', function () {
    [$user, $workspace, $brand] = socialTenant();
    $source = AiContentDraft::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'created_by_user_id' => $user->id, 'status' => 'approved', 'current_revision' => 3, 'seo_title' => 'Approved SEO guide']);

    $this->actingAs($user)->post(route('social-content.posts.store', [$workspace, $brand]), socialPostPayload(['source_ai_content_draft_id' => $source->id]))->assertRedirect();

    $post = SocialPost::query()->sole();
    expect($post->source_revision)->toBe(3);
    expect($post->source_snapshot['title'])->toBe('Approved SEO guide');

    $source->update(['current_revision' => 4]);
    $this->actingAs($user)->get(route('social-content.posts.show', [$workspace, $brand, $post]))->assertOk()->assertSee('source article has changed');
});

test('cross tenant users cannot discover social posts', function () {
    [$owner, $workspace, $brand] = socialTenant();
    [$outsider] = socialTenant();
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id]);

    $this->actingAs($outsider)->get(route('social-content.posts.show', [$workspace, $brand, $post]))->assertNotFound();
});

test('only workspace owners approve social content', function () {
    [$owner, $workspace, $brand] = socialTenant();
    $member = User::factory()->create();
    $workspace->members()->attach($member, ['role' => 'member']);
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'status' => SocialPostStatus::NeedsReview]);

    $this->actingAs($member)->post(route('social-content.posts.workflow', [$workspace, $brand, $post, 'approve']), ['lock_version' => 0])->assertNotFound();
    $this->actingAs($owner)->post(route('social-content.posts.workflow', [$workspace, $brand, $post, 'approve']), ['lock_version' => 0])->assertRedirect();

    expect($post->refresh()->status)->toBe(SocialPostStatus::Approved);
    expect($post->approved_by_user_id)->toBe($owner->id);
});

test('planning converts workspace local time to utc and does not publish', function () {
    [$owner, $workspace, $brand] = socialTenant();
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'status' => SocialPostStatus::Approved]);

    $this->actingAs($owner)->post(route('social-content.posts.workflow', [$workspace, $brand, $post, 'plan']), ['lock_version' => 0, 'planned_at' => '2026-11-10 12:00', 'timezone' => 'Asia/Dhaka'])->assertRedirect();

    $post->refresh();
    expect($post->status)->toBe(SocialPostStatus::Planned);
    expect($post->planned_at->format('Y-m-d H:i'))->toBe('2026-11-10 06:00');
    expect($post->published_at)->toBeNull();
});

test('workspace local scheduling handles dst and date boundaries as canonical utc', function (string $timezone, string $localTime, string $expectedUtc) {
    [$owner, $workspace, $brand] = socialTenant();
    $workspace->update(['settings' => ['timezone' => $timezone]]);
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'status' => SocialPostStatus::Approved]);

    $this->actingAs($owner)->post(route('social-content.posts.workflow', [$workspace, $brand, $post, 'plan']), [
        'lock_version' => 0,
        'planned_at' => $localTime,
        'timezone' => 'Pacific/Honolulu',
    ])->assertRedirect();

    expect($post->refresh()->planned_at->utc()->format('Y-m-d H:i:s'))->toBe($expectedUtc);
})->with([
    'Asia Dhaka local midnight month boundary' => ['Asia/Dhaka', '2026-08-01 00:00:00', '2026-07-31 18:00:00'],
    'New York before spring DST jump' => ['America/New_York', '2026-03-08 01:30:00', '2026-03-08 06:30:00'],
    'New York after spring DST jump' => ['America/New_York', '2026-03-08 03:30:00', '2026-03-08 07:30:00'],
    'UTC remains UTC' => ['UTC', '2026-07-01 16:00:00', '2026-07-01 16:00:00'],
]);

test('mariadb datetime preserves canonical utc across session timezones', function () {
    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('This cross-session storage check requires MariaDB.');
    }

    [$owner, $workspace, $brand] = socialTenant();
    DB::statement("SET time_zone = '+06:00'");
    $post = SocialPost::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'owner_user_id' => $owner->id,
        'planned_at' => CarbonImmutable::parse('2026-07-01 16:00:00', 'UTC'),
        'published_at' => CarbonImmutable::parse('2026-07-02 16:00:00', 'UTC'),
    ]);

    $plusSix = DB::table('social_posts')->where('id', $post->id)->first(['planned_at', 'published_at']);
    DB::statement("SET time_zone = '+00:00'");
    $utc = DB::table('social_posts')->where('id', $post->id)->first(['planned_at', 'published_at']);

    expect($plusSix->planned_at)->toBe('2026-07-01 16:00:00')
        ->and($utc->planned_at)->toBe('2026-07-01 16:00:00')
        ->and($plusSix->published_at)->toBe('2026-07-02 16:00:00')
        ->and($utc->published_at)->toBe('2026-07-02 16:00:00');
});

test('social list and calendar modes combine platform status and brand filters without duplicates', function (string $view) {
    [$owner, $workspace, $brand] = socialTenant();
    $otherBrand = Brand::factory()->for($workspace)->create();
    $matching = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'title' => 'Matching planned post', 'status' => SocialPostStatus::Planned, 'planned_at' => '2026-11-15 06:00:00']);
    SocialPostVariant::factory()->create(['social_post_id' => $matching->id, 'platform' => SocialPlatform::LinkedIn]);
    SocialPostVariant::factory()->create(['social_post_id' => $matching->id, 'platform' => SocialPlatform::Facebook]);
    $wrongStatus = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'title' => 'Wrong status post', 'status' => SocialPostStatus::Approved, 'planned_at' => '2026-11-15 06:00:00']);
    SocialPostVariant::factory()->create(['social_post_id' => $wrongStatus->id, 'platform' => SocialPlatform::LinkedIn]);
    $wrongBrand = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $otherBrand->id, 'owner_user_id' => $owner->id, 'title' => 'Wrong brand post', 'status' => SocialPostStatus::Planned, 'planned_at' => '2026-11-15 06:00:00']);
    SocialPostVariant::factory()->create(['social_post_id' => $wrongBrand->id, 'platform' => SocialPlatform::LinkedIn]);

    $query = ['workspace' => $workspace->id, 'brand' => $brand->id, 'platform' => SocialPlatform::LinkedIn->value, 'status' => SocialPostStatus::Planned->value];
    $this->actingAs($owner)->get(route('social-content.index', $query))->assertOk()->assertSee('Matching planned post')->assertDontSee('Wrong status post')->assertDontSee('Wrong brand post');
    $this->actingAs($owner)->get(route('social-content.calendar', [...$query, 'view' => $view, 'date' => '2026-11-15']))->assertOk()->assertSee('Matching planned post')->assertDontSee('Wrong status post')->assertDontSee('Wrong brand post');
})->with(['month', 'week', 'list']);

test('social filters reject unsupported platform and status values', function () {
    [$owner, $workspace] = socialTenant();

    $this->actingAs($owner)->get(route('social-content.index', ['workspace' => $workspace->id, 'platform' => 'threads']))->assertSessionHasErrors('platform');
    $this->actingAs($owner)->get(route('social-content.calendar', ['workspace' => $workspace->id, 'status' => 'auto_published']))->assertSessionHasErrors('status');
});

test('manual publishing records a safe audit without contacting a platform', function () {
    Http::preventStrayRequests();
    [$owner, $workspace, $brand] = socialTenant();
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'status' => SocialPostStatus::Approved]);

    $this->actingAs($owner)->post(route('social-content.posts.workflow', [$workspace, $brand, $post, 'publish']), ['lock_version' => 0, 'published_url' => 'https://www.linkedin.com/posts/example'])->assertRedirect();

    $post->refresh();
    expect($post->status)->toBe(SocialPostStatus::ManuallyPublished);
    expect($post->published_url)->toBe('https://www.linkedin.com/posts/example');
    $this->assertDatabaseHas('social_post_events', ['social_post_id' => $post->id, 'action' => 'publish']);
});

test('material edits invalidate approval and stale versions cannot overwrite them', function () {
    [$owner, $workspace, $brand] = socialTenant();
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'status' => SocialPostStatus::Approved, 'approved_by_user_id' => $owner->id, 'approved_at' => now()]);
    SocialPostVariant::factory()->create(['social_post_id' => $post->id]);

    $this->actingAs($owner)->patch(route('social-content.posts.update', [$workspace, $brand, $post]), socialPostPayload(['title' => 'Revised campaign', 'lock_version' => 0]))->assertRedirect();
    $this->actingAs($owner)->patch(route('social-content.posts.update', [$workspace, $brand, $post]), socialPostPayload(['title' => 'Stale overwrite', 'lock_version' => 0]))->assertSessionHasErrors('lock_version');

    $post->refresh();
    expect($post->title)->toBe('Revised campaign');
    expect($post->status)->toBe(SocialPostStatus::NeedsReview);
    expect($post->approved_by_user_id)->toBeNull();
});

test('duplicate creates an independent unapproved draft', function () {
    [$owner, $workspace, $brand] = socialTenant();
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'status' => SocialPostStatus::Approved]);
    SocialPostVariant::factory()->create(['social_post_id' => $post->id]);

    $this->actingAs($owner)->post(route('social-content.posts.duplicate', [$workspace, $brand, $post]))->assertRedirect();

    $copy = SocialPost::query()->whereKeyNot($post->id)->sole();
    expect($copy->status)->toBe(SocialPostStatus::Draft);
    expect($copy->variants)->toHaveCount(1);
    expect($copy->approved_at)->toBeNull();
});

test('rendered captions are escaped and csv exports neutralize formulas', function () {
    [$owner, $workspace, $brand] = socialTenant();
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id, 'status' => SocialPostStatus::Approved]);
    SocialPostVariant::factory()->create(['social_post_id' => $post->id, 'caption' => '=HYPERLINK("https://evil.example")<script>alert(1)</script>']);

    $this->actingAs($owner)->get(route('social-content.posts.show', [$workspace, $brand, $post]))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
    $response = $this->actingAs($owner)->post(route('social-content.posts.exports', [$workspace, $brand, $post]), ['format' => 'csv']);

    $response->assertDownload('social-post-'.$post->id.'.csv');
    expect($response->streamedContent())->toContain("'=HYPERLINK");
});

test('forged workflow fields and automatic publishing routes are unavailable', function () {
    [$owner, $workspace, $brand] = socialTenant();
    $post = SocialPost::factory()->create(['workspace_id' => $workspace->id, 'brand_id' => $brand->id, 'owner_user_id' => $owner->id]);

    $this->actingAs($owner)->post('/workspaces/'.$workspace->id.'/brands/'.$brand->id.'/social-posts/'.$post->id.'/workflow/auto-publish', ['lock_version' => 0])->assertNotFound();
    expect(collect(app('router')->getRoutes())->contains(fn ($route) => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'social-posts')))->toBeFalse();
});
