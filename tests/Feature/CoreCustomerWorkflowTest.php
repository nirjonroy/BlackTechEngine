<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\EntitlementKey;
use App\Enums\SocialPlatform;
use App\Models\AiContentDraft;
use App\Models\AiGenerationAttempt;
use App\Models\Brand;
use App\Models\PricingPlan;
use App\Models\PricingPlanEntitlement;
use App\Models\SocialPost;
use App\Models\UsageCounter;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function configureCoreWorkflowFreePlan(): PricingPlan
{
    $plan = PricingPlan::factory()->create([
        'name' => 'Free',
        'code' => 'free',
        'monthly_price' => '0.00',
        'annual_price' => '0.00',
        'monthly_price_minor' => 0,
        'annual_price_minor' => 0,
        'is_active' => true,
        'show_on_pricing_page' => false,
        'is_billable' => false,
    ]);

    $entitlements = [
        EntitlementKey::MaximumWorkspaces->value => [true, 1],
        EntitlementKey::MaximumBrands->value => [true, 1],
        EntitlementKey::ContentStudio->value => [true, null],
        EntitlementKey::ContentDraftsPerPeriod->value => [true, 10],
        EntitlementKey::SocialContent->value => [false, 0],
        EntitlementKey::SocialPostsPerPeriod->value => [false, 0],
        EntitlementKey::EditorialReview->value => [false, 0],
        EntitlementKey::AiGenerationAttempts->value => [false, 0],
        EntitlementKey::AiTokens->value => [false, 0],
    ];

    foreach ($entitlements as $key => [$enabled, $limit]) {
        PricingPlanEntitlement::factory()->for($plan, 'plan')->create([
            'key' => $key,
            'is_enabled' => $enabled,
            'limit' => $limit,
            'is_unlimited' => false,
        ]);
    }

    return $plan;
}

test('the complete Free customer workflow enforces quotas and updates dashboard metrics', function () {
    config()->set('subscriptions.enforcement_enabled', true);
    config()->set('subscriptions.free_plan_code', 'free');
    configureCoreWorkflowFreePlan();

    $this->post(route('register'), [
        'name' => 'Core Workflow Customer',
        'email' => 'core-workflow@example.test',
        'password' => 'Secure-password-123',
        'password_confirmation' => 'Secure-password-123',
    ])->assertRedirectToRoute('onboarding.start');
    $user = User::query()->sole();
    $user->markEmailAsVerified();
    $this->post(route('logout'))->assertRedirect('/');
    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Secure-password-123',
    ])->assertRedirectToRoute('dashboard');

    $this->post(route('onboarding.workspace.store'), validWorkspacePayload())
        ->assertRedirect();
    $workspace = Workspace::query()->sole();
    $this->post(route('onboarding.brand.store', $workspace), validBrandPayload())
        ->assertSessionHasNoErrors();
    $brand = Brand::query()->sole();

    expect(WorkspaceSubscription::query()->sole()->activation_source)->toBe('complimentary_free');

    $this->from(route('workspaces.create'))->post(
        route('workspaces.store'),
        validWorkspacePayload(['name' => 'Blocked second workspace']),
    )->assertSessionHasErrors('subscription');
    $this->from(route('workspaces.brands.create', $workspace))->post(
        route('workspaces.brands.store', $workspace),
        validBrandPayload(['name' => 'Blocked second brand']),
    )->assertSessionHasErrors('subscription');

    foreach (range(1, 10) as $draftNumber) {
        $this->post(
            route('content-studio.drafts.store', [$workspace, $brand]),
            contentDraftPayload(['topic' => "Draft {$draftNumber}"]),
        )->assertSessionHasNoErrors();
    }

    $this->from(route('content-studio.drafts.create', [$workspace, $brand]))->post(
        route('content-studio.drafts.store', [$workspace, $brand]),
        contentDraftPayload(['topic' => 'Blocked eleventh draft']),
    )->assertSessionHasErrors('subscription');

    $dashboard = $this->get(route('dashboard'))->assertOk()->assertSee('Free');
    expect(Workspace::query()->count())->toBe(1)
        ->and(Brand::query()->count())->toBe(1)
        ->and(AiContentDraft::query()->count())->toBe(10)
        ->and(UsageCounter::query()->where('entitlement_key', EntitlementKey::ContentDraftsPerPeriod->value)->sole()->used)->toBe(10)
        ->and($dashboard->viewData('quotaUsage')['drafts'])->toMatchArray(['used' => 10, 'limit' => 10, 'remaining' => 0])
        ->and($dashboard->viewData('contentStats')['drafts']->sum('count'))->toBe(10);
});

test('Free access blocks Social Editorial and AI while preserving tenant isolation', function () {
    config()->set('subscriptions.enforcement_enabled', true);
    config()->set('subscriptions.free_plan_code', 'free');
    config()->set('ai.content_generation_enabled', true);
    configureCoreWorkflowFreePlan();
    Http::preventStrayRequests();

    $user = User::factory()->create();
    $this->actingAs($user)->post(route('onboarding.workspace.store'), validWorkspacePayload());
    $workspace = Workspace::query()->sole();
    $this->actingAs($user)->post(route('onboarding.brand.store', $workspace), validBrandPayload());
    $brand = Brand::query()->sole();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'content' => 'Review-ready content.',
    ]);

    $this->actingAs($user)->post(route('social-content.posts.store', [$workspace, $brand]), [
        'idempotency_key' => (string) Str::uuid(),
        'title' => 'Blocked social draft',
        'variants' => [[
            'platform' => SocialPlatform::LinkedIn->value,
            'caption' => 'This must remain blocked for Free customers.',
        ]],
    ])->assertSessionHasErrors('subscription');
    $this->actingAs($user)->post(
        route('content-studio.drafts.review-requests.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0],
    )->assertSessionHasErrors('subscription');
    $this->actingAs($user)->post(
        route('content-studio.generations.store', [$workspace, $brand]),
        contentDraftPayload(['idempotency_key' => (string) Str::uuid()]),
    )->assertSessionHasErrors('generation');

    $attacker = User::factory()->create();
    $this->actingAs($attacker)->get(
        route('content-studio.drafts.show', [$workspace, $brand, $draft]),
    )->assertNotFound();

    expect(SocialPost::query()->count())->toBe(0)
        ->and(AiGenerationAttempt::query()->count())->toBe(0)
        ->and($draft->fresh()->status)->toBe(AiContentDraftStatus::Draft);
    Http::assertNothingSent();
});

test('customer content writes require CSRF outside the test environment', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    $this->actingAs($user);
    $this->app['env'] = 'production';

    $this->post(
        route('content-studio.drafts.store', [$workspace, $brand]),
        contentDraftPayload(),
    )->assertStatus(419);

    expect(AiContentDraft::query()->count())->toBe(0);
});
