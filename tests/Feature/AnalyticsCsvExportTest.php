<?php

use App\Enums\EntitlementKey;
use App\Enums\SubscriptionStatus;
use App\Models\Admin;
use App\Models\AiContentDraft;
use App\Models\AiGenerationAttempt;
use App\Models\Brand;
use App\Models\PricingPlan;
use App\Models\SocialPost;
use App\Models\UsageCounter;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::findOrCreate('view dashboard', 'admin');
});

function analyticsAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => 'Analytics Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('view dashboard');
    }

    return $admin;
}

/** @return array<int, array<int, string|null>> */
function analyticsCsvRows(TestResponse $response): array
{
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $response->streamedContent());
    rewind($stream);
    $rows = [];

    while (($row = fgetcsv($stream)) !== false) {
        $rows[] = $row;
    }

    fclose($stream);

    return $rows;
}

test('a guest cannot export customer analytics', function () {
    $this->get(route('dashboard.analytics.export'))
        ->assertRedirectToRoute('login');
});

test('a customer export contains only active tenant analytics and neutralizes formulas', function () {
    $this->travelTo('2026-11-15 12:00:00');
    [$user, $workspace, $plan, $subscription] = subscriptionFixture([
        EntitlementKey::ContentDraftsPerPeriod->value => 10,
    ]);
    $plan->update(['name' => '=Complimentary']);
    $brand = Brand::factory()->for($workspace)->create();
    $user->update(['active_workspace_id' => $workspace->id]);
    UsageCounter::factory()->for($subscription, 'subscription')->for($workspace)->create([
        'entitlement_key' => EntitlementKey::ContentDraftsPerPeriod->value,
        'used' => 3,
    ]);
    AiContentDraft::factory()->count(2)->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'created_at' => '2026-11-05 09:00:00',
    ]);
    SocialPost::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'owner_user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'created_at' => '2026-10-05 09:00:00',
    ]);
    $otherUser = User::factory()->create();
    $otherWorkspace = Workspace::factory()->for($otherUser, 'owner')->create();
    $otherPlan = PricingPlan::factory()->create(['name' => 'Other Tenant Secret']);
    WorkspaceSubscription::factory()->for($otherWorkspace)->for($otherPlan, 'plan')->create();
    $otherBrand = Brand::factory()->for($otherWorkspace)->create();
    AiContentDraft::factory()->count(4)->create([
        'workspace_id' => $otherWorkspace->id,
        'brand_id' => $otherBrand->id,
        'created_by_user_id' => $otherUser->id,
        'created_at' => '2026-11-05 09:00:00',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.analytics.export'));

    $response->assertOk()
        ->assertDownload('customer-analytics-2026-11-15.csv')
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertHeader('x-content-type-options', 'nosniff');
    $rows = collect(analyticsCsvRows($response));
    expect($rows->first())->toBe(['Section', 'Metric', 'Value', 'Context']);
    expect($rows->contains(fn (array $row): bool => $row === ['Subscription', 'Plan', "'=Complimentary", '']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row === ['Quota usage', 'Drafts this month', '3', 'Limit: 10; Remaining: 7; Included: Yes']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row === ['Content drafts', 'Draft', '2', '']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row === ['Six-month trend', 'Content drafts', '2', 'Nov 2026']))->toBeTrue();
    expect($response->streamedContent())->not->toContain('Other Tenant Secret')->not->toContain($user->email);
});

test('a workspace-less customer receives a safe empty export', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard.analytics.export'));

    $response->assertOk()->assertDownload();
    expect(analyticsCsvRows($response))->toBe([
        ['Section', 'Metric', 'Value', 'Context'],
        ['Subscription', 'Status', 'No active workspace', ''],
    ]);
});

test('admin analytics exports require the admin guard and dashboard permission', function () {
    $user = User::factory()->create();
    $unauthorizedAdmin = analyticsAdmin(false);

    $this->get(route('admin.dashboard.analytics.export'))->assertRedirectToRoute('admin.login');
    $this->actingAs($user)->get(route('admin.dashboard.analytics.export'))->assertRedirectToRoute('admin.login');
    $this->actingAs($unauthorizedAdmin, 'admin')->get(route('admin.dashboard.analytics.export'))->assertForbidden();
});

test('an authorized admin export contains aggregate analytics and neutralizes plan formulas', function () {
    $this->travelTo('2026-11-15 12:00:00');
    $admin = analyticsAdmin();
    $user = User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $brand = Brand::factory()->for($workspace)->create();
    $plan = PricingPlan::factory()->create(['name' => '+Starter']);
    WorkspaceSubscription::factory()->for($workspace)->for($plan, 'plan')->create([
        'status' => SubscriptionStatus::Active,
    ]);
    AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'created_at' => '2026-11-05 09:00:00',
    ]);
    AiGenerationAttempt::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'input_tokens' => 120,
        'output_tokens' => 30,
        'created_at' => '2026-11-06 09:00:00',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard.analytics.export'));

    $response->assertOk()
        ->assertDownload('admin-analytics-2026-11-15.csv')
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $rows = collect(analyticsCsvRows($response));
    expect($rows->contains(fn (array $row): bool => $row === ['Platform metrics', 'Customers', '1', '']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row === ['Platform metrics', 'Workspaces', '1', '']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row === ['Subscription distribution', "'+Starter", '1', 'Active']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row === ['Content and AI activity', 'Ai Tokens', '150', '']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row === ['Six-month trend', 'AI attempts', '1', 'Nov 2026']))->toBeTrue();
});

test('an authorized admin receives explicit zero metrics when analytics data is empty', function () {
    $admin = analyticsAdmin();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard.analytics.export'));

    $response->assertOk()->assertDownload();
    $rows = collect(analyticsCsvRows($response));
    expect($rows->contains(fn (array $row): bool => $row === ['Platform metrics', 'Customers', '0', '']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row === ['Content and AI activity', 'Ai Tokens', '0', '']))->toBeTrue();
    expect($rows->contains(fn (array $row): bool => $row[0] === 'Subscription distribution'))->toBeFalse();
});
