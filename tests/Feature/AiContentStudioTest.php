<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\EntitlementKey;
use App\Models\Admin;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\UsageCounter;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

test('guests and unverified customers cannot enter the content studio', function () {
    [$user, $workspace, $brand] = contentStudioTenant(User::factory()->unverified()->create());

    $this->get(route('content-studio.index'))->assertRedirectToRoute('login');
    $this->actingAs($user)->get(route('content-studio.drafts.create', [$workspace, $brand]))
        ->assertRedirectToRoute('verification.notice');
});

test('an authorized customer saves views and edits an unpublished draft', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $this->actingAs($user)->get(route('content-studio.index'))
        ->assertOk()
        ->assertSee('AI Content Studio');
    $this->actingAs($user)->get(route('content-studio.drafts.create', [$workspace, $brand]))
        ->assertOk()
        ->assertSee('Generate with AI');

    $storeResponse = $this->actingAs($user)->post(
        route('content-studio.drafts.store', [$workspace, $brand]),
        contentDraftPayload(),
    );
    $draft = AiContentDraft::query()->sole();

    $storeResponse->assertRedirectToRoute('content-studio.drafts.show', [$workspace, $brand, $draft]);
    expect($draft->workspace_id)->toBe($workspace->id)
        ->and($draft->brand_id)->toBe($brand->id)
        ->and($draft->created_by_user_id)->toBe($user->id)
        ->and($draft->ai_model_id)->toBeNull()
        ->and($draft->secondary_keywords)->toBe(['site audits', 'search visibility']);

    $this->actingAs($user)->get(route('content-studio.drafts.show', [$workspace, $brand, $draft]))
        ->assertOk()
        ->assertSee('Unpublished draft');
    $this->actingAs($user)->get(route('content-studio.drafts.edit', [$workspace, $brand, $draft]))
        ->assertOk()
        ->assertSee('Draft editor');

    $this->actingAs($user)->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['seo_title' => 'Updated Human-Reviewed Title']),
    )->assertRedirectToRoute('content-studio.drafts.show', [$workspace, $brand, $draft]);

    expect($draft->fresh()->seo_title)->toBe('Updated Human-Reviewed Title');
});

test('the generation form shows live entitlement availability and loading feedback', function () {
    config()->set('ai.content_generation_enabled', true);
    config()->set('subscriptions.enforcement_enabled', true);
    [$user, $workspace] = subscriptionFixture([
        EntitlementKey::ContentStudio->value => null,
        EntitlementKey::AiGenerationAttempts->value => 2,
        EntitlementKey::AiTokens->value => 10000,
    ]);
    $brand = Brand::factory()->for($workspace)->create();

    $this->actingAs($user)->get(route('content-studio.drafts.create', [$workspace, $brand]))
        ->assertOk()
        ->assertSee('AI generation is available.')
        ->assertSee('2 remaining')
        ->assertSee('10,000 remaining')
        ->assertSee('Generating&hellip;', false)
        ->assertSee('Counting tokens and generating a private draft.');
});

test('a free plan without ai quota receives clear feedback and cannot use the generation control', function () {
    config()->set('ai.content_generation_enabled', true);
    config()->set('subscriptions.enforcement_enabled', true);
    [$user, $workspace, $plan] = subscriptionFixture([
        EntitlementKey::ContentStudio->value => null,
        EntitlementKey::AiGenerationAttempts->value => 0,
        EntitlementKey::AiTokens->value => 0,
    ]);
    $plan->update(['code' => 'free', 'is_billable' => false]);
    $brand = Brand::factory()->for($workspace)->create();

    $this->actingAs($user)->get(route('content-studio.drafts.create', [$workspace, $brand]))
        ->assertOk()
        ->assertSee('Your AI generation quota for this monthly usage period is exhausted.')
        ->assertSee('disabled x-bind:disabled="submitting || true"', false);
});

test('replaying a manual draft request returns the original draft without consuming quota twice', function () {
    config()->set('subscriptions.enforcement_enabled', true);
    [$user, $workspace] = subscriptionFixture([
        EntitlementKey::ContentStudio->value => null,
        EntitlementKey::ContentDraftsPerPeriod->value => 10,
    ]);
    $brand = Brand::factory()->for($workspace)->create();
    $payload = contentDraftPayload();

    $first = $this->actingAs($user)->post(route('content-studio.drafts.store', [$workspace, $brand]), $payload);
    $draft = AiContentDraft::query()->sole();
    $second = $this->actingAs($user)->post(route('content-studio.drafts.store', [$workspace, $brand]), $payload);

    $first->assertRedirectToRoute('content-studio.drafts.show', [$workspace, $brand, $draft]);
    $second->assertRedirectToRoute('content-studio.drafts.show', [$workspace, $brand, $draft]);
    expect(AiContentDraft::query()->count())->toBe(1)
        ->and(UsageEvent::query()->count())->toBe(1)
        ->and(UsageCounter::query()->sole()->used)->toBe(1);
});

test('nested bindings hide drafts and brands belonging to another tenant', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    [$attacker, $attackerWorkspace, $attackerBrand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
    ]);

    $this->actingAs($attacker)->get(route('content-studio.drafts.show', [$workspace, $brand, $draft]))->assertNotFound();
    $this->actingAs($attacker)->get(route('content-studio.drafts.show', [$attackerWorkspace, $attackerBrand, $draft]))->assertNotFound();
    $this->actingAs($attacker)->post(route('content-studio.drafts.store', [$workspace, $brand]), contentDraftPayload())->assertNotFound();
});

test('authorized membership permits drafts without transferring workspace ownership', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    [$unused, $workspace, $brand] = contentStudioTenant($owner);
    $workspace->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)->post(route('content-studio.drafts.store', [$workspace, $brand]), contentDraftPayload())
        ->assertSessionHasNoErrors();

    expect($workspace->fresh()->owner_id)->toBe($owner->id)
        ->and(AiContentDraft::query()->sole()->created_by_user_id)->toBe($member->id);
});

test('draft output is escaped and protected ownership fields cannot be edited', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    $dangerous = '<script>alert("draft")</script>';
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'content' => $dangerous,
    ]);

    $this->actingAs($user)->get(route('content-studio.drafts.show', [$workspace, $brand, $draft]))
        ->assertOk()
        ->assertDontSee($dangerous, false);

    $this->actingAs($user)->from(route('content-studio.drafts.edit', [$workspace, $brand, $draft]))->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['workspace_id' => Workspace::factory()->create()->id, 'created_by_user_id' => 999]),
    )->assertSessionHasErrors(['workspace_id', 'created_by_user_id']);

    expect($draft->fresh()->workspace_id)->toBe($workspace->id)
        ->and($draft->fresh()->created_by_user_id)->toBe($user->id);
});

test('an admin guard session does not grant customer content access', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    $admin = Admin::create(['name' => 'Admin', 'email' => 'studio-admin@example.test', 'password' => Hash::make('password')]);
    Auth::guard('admin')->login($admin);

    $this->get(route('content-studio.drafts.create', [$workspace, $brand]))->assertRedirectToRoute('login');
    $this->assertAuthenticatedAs($admin, 'admin');
    $this->assertGuest('web');
});

test('the content studio has no delete or public publishing routes', function () {
    expect(route('content-studio.index'))->toContain('/content-studio')
        ->and(collect(app('router')->getRoutes()->getRoutes())->contains(fn ($route): bool => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'content-drafts')))->toBeFalse()
        ->and(collect(app('router')->getRoutes()->getRoutes())->contains(fn ($route): bool => str_contains($route->uri(), 'publish')))->toBeFalse();
});

test('customers can filter the draft listing by editorial status', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'seo_title' => 'Visible approved content',
        'status' => AiContentDraftStatus::Approved,
    ]);
    AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'seo_title' => 'Hidden draft content',
        'status' => AiContentDraftStatus::Draft,
    ]);

    $this->actingAs($user)->get(route('content-studio.index', [
        'workspace' => $workspace->id,
        'brand' => $brand->id,
        'status' => 'approved',
    ]))->assertOk()
        ->assertSee('Visible approved content')
        ->assertDontSee('Hidden draft content');
});
