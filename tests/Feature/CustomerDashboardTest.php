<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\EntitlementKey;
use App\Enums\SocialPostStatus;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\UsageCounter;
use App\Models\User;
use App\Models\Workspace;

test('a guest is redirected from the customer dashboard to customer login', function () {
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});

test('an existing customer without a workspace sees an honest onboarding empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Create your first workspace')
        ->assertSee('Start onboarding')
        ->assertDontSee('Generated 1,000');
});

test('the dashboard renders only real workspace and brand counts', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create(['name' => 'Growth Lab']);
    $workspace->members()->attach($user, ['role' => 'owner']);
    $activeBrand = Brand::factory()->for($workspace)->create(['name' => 'Active Brand', 'is_active' => true]);
    Brand::factory()->for($workspace)->create(['name' => 'Paused Brand', 'is_active' => false]);
    $user->active_workspace_id = $workspace->id;
    $user->save();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('activeWorkspace', fn (Workspace $value): bool => $value->is($workspace))
        ->assertViewHas('recentBrands', fn ($brands): bool => $brands->contains($activeBrand))
        ->assertSee('Growth Lab')
        ->assertSee('Active Brand')
        ->assertSee('No active subscription')
        ->assertSee('No content drafts yet')
        ->assertSee('No monthly activity yet');
    expect($response->viewData('activeWorkspace')->brands_count)->toBe(2)
        ->and($response->viewData('activeWorkspace')->active_brands_count)->toBe(1);
});

test('the dashboard calculates plan quotas status totals and monthly activity for the active workspace', function () {
    $this->travelTo('2026-11-15 12:00:00');
    config()->set('subscriptions.enforcement_enabled', true);
    [$user, $workspace, $plan, $subscription] = subscriptionFixture([
        EntitlementKey::MaximumWorkspaces->value => 1,
        EntitlementKey::MaximumBrands->value => 1,
        EntitlementKey::ContentDraftsPerPeriod->value => 10,
        EntitlementKey::SocialPostsPerPeriod->value => 5,
        EntitlementKey::AiGenerationAttempts->value => 2,
        EntitlementKey::AiTokens->value => 1000,
    ]);
    $plan->update(['name' => 'Free']);
    $brand = Brand::factory()->for($workspace)->create(['is_active' => true]);
    $user->update(['active_workspace_id' => $workspace->id]);

    foreach ([
        EntitlementKey::ContentDraftsPerPeriod->value => 4,
        EntitlementKey::SocialPostsPerPeriod->value => 1,
        EntitlementKey::AiGenerationAttempts->value => 1,
        EntitlementKey::AiTokens->value => 200,
    ] as $key => $used) {
        UsageCounter::factory()->for($subscription, 'subscription')->for($workspace)->create([
            'entitlement_key' => $key,
            'used' => $used,
        ]);
    }

    AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'status' => AiContentDraftStatus::Draft,
        'created_at' => '2026-09-10 09:00:00',
    ]);
    AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'status' => AiContentDraftStatus::Approved,
        'created_at' => '2026-11-02 09:00:00',
    ]);
    AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'status' => AiContentDraftStatus::NeedsReview,
        'created_at' => '2026-11-03 09:00:00',
    ]);
    SocialPost::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'owner_user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'status' => SocialPostStatus::Planned,
        'created_at' => '2026-10-08 09:00:00',
    ]);
    SocialPost::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'owner_user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'status' => SocialPostStatus::Draft,
        'created_at' => '2026-11-04 09:00:00',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Free')
        ->assertSee('6 remaining of 10')
        ->assertSee('Content drafts by status')
        ->assertSee('Social posts by status')
        ->assertSee('Sep 2026')
        ->assertSee('Nov 2026');
    $quotaUsage = $response->viewData('quotaUsage');
    expect($quotaUsage['workspaces'])->toMatchArray(['used' => 1, 'limit' => 1, 'remaining' => 0])
        ->and($quotaUsage['brands'])->toMatchArray(['used' => 1, 'limit' => 1, 'remaining' => 0])
        ->and($quotaUsage['drafts'])->toMatchArray(['used' => 4, 'limit' => 10, 'remaining' => 6])
        ->and($quotaUsage['social_posts'])->toMatchArray(['used' => 1, 'limit' => 5, 'remaining' => 4])
        ->and($quotaUsage['ai_attempts'])->toMatchArray(['used' => 1, 'limit' => 2, 'remaining' => 1])
        ->and($quotaUsage['ai_tokens'])->toMatchArray(['used' => 200, 'limit' => 1000, 'remaining' => 800]);
    expect($response->viewData('contentStats')['drafts']->pluck('count', 'label')->all())
        ->toMatchArray(['Draft' => 1, 'Needs review' => 1, 'Approved' => 1, 'Export ready' => 0]);
    expect($response->viewData('monthlyTrends')->firstWhere('label', 'Sep 2026'))->toMatchArray(['drafts' => 1, 'social_posts' => 0])
        ->and($response->viewData('monthlyTrends')->firstWhere('label', 'Oct 2026'))->toMatchArray(['drafts' => 0, 'social_posts' => 1])
        ->and($response->viewData('monthlyTrends')->firstWhere('label', 'Nov 2026'))->toMatchArray(['drafts' => 2, 'social_posts' => 1]);
});

test('the dashboard ignores a forged active workspace from another tenant', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create(['name' => 'Customer Workspace']);
    $workspace->members()->attach($user, ['role' => 'owner']);
    $brand = Brand::factory()->for($workspace)->create();
    AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
    ]);
    $otherUser = User::factory()->create();
    $otherWorkspace = Workspace::factory()->for($otherUser, 'owner')->create(['name' => 'Hidden Tenant Workspace']);
    $otherBrand = Brand::factory()->for($otherWorkspace)->create();
    AiContentDraft::factory()->count(3)->create([
        'workspace_id' => $otherWorkspace->id,
        'brand_id' => $otherBrand->id,
        'created_by_user_id' => $otherUser->id,
    ]);
    $user->update(['active_workspace_id' => $otherWorkspace->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertViewHas('activeWorkspace', fn (Workspace $value): bool => $value->is($workspace))
        ->assertSee('Customer Workspace')
        ->assertDontSee('Hidden Tenant Workspace');
    expect($response->viewData('contentStats')['drafts']->sum('count'))->toBe(1);
});

test('customer supplied dashboard content is escaped', function () {
    $user = User::factory()->create(['name' => '<script>alert("user")</script>']);
    $workspace = Workspace::factory()->for($user, 'owner')->create(['name' => '<img src=x onerror=alert(1)>']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($user->name)
        ->assertDontSee($user->name, false)
        ->assertSee($workspace->name)
        ->assertDontSee($workspace->name, false);
});
