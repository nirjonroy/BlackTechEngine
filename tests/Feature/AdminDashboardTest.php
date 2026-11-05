<?php

use App\Enums\AiGenerationAttemptStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Admin;
use App\Models\AiContentDraft;
use App\Models\AiGenerationAttempt;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\PricingPlan;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

const DASHBOARD_ADMIN_PERMISSIONS = [
    'view dashboard',
    'create admin',
    'edit admin',
    'delete admin',
    'manage roles',
    'manage site settings',
    'manage homepage settings',
    'manage affiliate page settings',
    'manage frontend navigation',
    'manage about page',
    'manage seo settings',
    'manage blogs',
    'manage blog page settings',
    'manage pricing plans',
    'manage pricing page settings',
    'manage feature page settings',
    'manage users',
];

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (DASHBOARD_ADMIN_PERMISSIONS as $permission) {
        Permission::create([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }
});

function createDashboardAdmin(array $permissions = ['view dashboard'], array $attributes = []): Admin
{
    $admin = Admin::create(array_merge([
        'name' => 'Dashboard Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ], $attributes));

    if ($permissions !== []) {
        $admin->givePermissionTo($permissions);
    }

    return $admin;
}

test('a guest is redirected from the dashboard to the registered admin login route', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirectToRoute('admin.login');

    $this->assertGuest('admin');
});

test('an admin without dashboard permission receives 403', function () {
    $admin = createDashboardAdmin([]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a regular customer cannot use web authentication to access the admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertRedirectToRoute('admin.login');

    $this->assertGuest('admin');
});

test('a super admin can view the dashboard without direct permissions', function () {
    $admin = createDashboardAdmin([]);
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdminRole);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin Mission Control');
});

test('the dashboard renders accurate database aggregates and publication states', function () {
    $this->travelTo('2026-11-15 12:00:00');
    $admin = createDashboardAdmin();
    createDashboardAdmin([], ['email' => 'second-admin@example.com']);
    $users = User::factory()->count(3)->create();
    $firstWorkspace = Workspace::factory()->for($users[0], 'owner')->create();
    $secondWorkspace = Workspace::factory()->for($users[1], 'owner')->create();
    $firstBrand = Brand::factory()->for($firstWorkspace)->create();
    Brand::factory()->count(2)->for($secondWorkspace)->create();
    $activeOne = PricingPlan::create(['name' => 'Active One', 'is_active' => true]);
    $activeTwo = PricingPlan::create(['name' => 'Active Two', 'is_active' => true]);
    PricingPlan::create(['name' => 'Hidden Plan', 'is_active' => false]);
    WorkspaceSubscription::factory()->for($firstWorkspace)->for($activeOne, 'plan')->create([
        'status' => SubscriptionStatus::Active,
    ]);
    WorkspaceSubscription::factory()->for($secondWorkspace)->for($activeTwo, 'plan')->create([
        'status' => SubscriptionStatus::Cancelled,
    ]);
    AiContentDraft::factory()->create([
        'workspace_id' => $firstWorkspace->id,
        'brand_id' => $firstBrand->id,
        'created_by_user_id' => $users[0]->id,
        'created_at' => '2026-09-10 09:00:00',
    ]);
    AiContentDraft::factory()->count(2)->create([
        'workspace_id' => $firstWorkspace->id,
        'brand_id' => $firstBrand->id,
        'created_by_user_id' => $users[0]->id,
        'created_at' => '2026-11-02 09:00:00',
    ]);
    SocialPost::factory()->create([
        'workspace_id' => $firstWorkspace->id,
        'brand_id' => $firstBrand->id,
        'owner_user_id' => $users[0]->id,
        'created_by_user_id' => $users[0]->id,
        'created_at' => '2026-10-08 09:00:00',
    ]);
    AiGenerationAttempt::factory()->create([
        'workspace_id' => $firstWorkspace->id,
        'brand_id' => $firstBrand->id,
        'user_id' => $users[0]->id,
        'status' => AiGenerationAttemptStatus::Completed,
        'input_tokens' => 100,
        'output_tokens' => 50,
        'created_at' => '2026-11-03 09:00:00',
    ]);
    AiGenerationAttempt::factory()->create([
        'workspace_id' => $firstWorkspace->id,
        'brand_id' => $firstBrand->id,
        'user_id' => $users[0]->id,
        'status' => AiGenerationAttemptStatus::Failed,
        'input_tokens' => 25,
        'output_tokens' => 5,
        'created_at' => '2026-11-04 09:00:00',
    ]);
    Blog::create(['title' => 'Published One', 'slug' => 'published-one', 'published_at' => now()->subDay()]);
    Blog::create(['title' => 'Published Two', 'slug' => 'published-two', 'published_at' => now()->subHour()]);
    Blog::create(['title' => 'Draft Article', 'slug' => 'draft-article']);
    Blog::create(['title' => 'Scheduled Article', 'slug' => 'scheduled-article', 'published_at' => now()->addDay()]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertViewHas('metrics', [
            'customers' => 3,
            'workspaces' => 2,
            'brands' => 3,
            'subscriptions' => 2,
            'plans' => 3,
            'active_pricing_plans' => 2,
            'published_blogs' => 2,
            'admin_accounts' => 2,
        ])
        ->assertViewHas('contentStatus', [
            'draft_blogs' => 2,
            'inactive_pricing_plans' => 1,
        ])
        ->assertViewHas('contentActivity', [
            'content_drafts' => 3,
            'social_posts' => 1,
            'ai_attempts' => 2,
            'ai_tokens' => 180,
        ])
        ->assertSee('Subscription distribution')
        ->assertSee('href="'.route('admin.dashboard.analytics.export').'"', false)
        ->assertSee('Active One')
        ->assertSee('Cancelled')
        ->assertSee('Sep 2026')
        ->assertSee('Nov 2026')
        ->assertSee('Published Two')
        ->assertDontSee('Scheduled Article');
    expect($response->viewData('subscriptionDistribution')->all())->toBe([
        ['plan' => 'Active One', 'status' => 'Active', 'count' => 1],
        ['plan' => 'Active Two', 'status' => 'Cancelled', 'count' => 1],
    ]);
    expect($response->viewData('monthlyTrends')->firstWhere('label', 'Sep 2026'))->toMatchArray(['drafts' => 1, 'social_posts' => 0, 'ai_attempts' => 0]);
    expect($response->viewData('monthlyTrends')->firstWhere('label', 'Oct 2026'))->toMatchArray(['drafts' => 0, 'social_posts' => 1, 'ai_attempts' => 0]);
    expect($response->viewData('monthlyTrends')->firstWhere('label', 'Nov 2026'))->toMatchArray(['drafts' => 2, 'social_posts' => 0, 'ai_attempts' => 2]);
});

test('the dashboard renders honest zero and empty states', function () {
    $admin = createDashboardAdmin();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertViewHas('metrics', [
            'customers' => 0,
            'workspaces' => 0,
            'brands' => 0,
            'subscriptions' => 0,
            'plans' => 0,
            'active_pricing_plans' => 0,
            'published_blogs' => 0,
            'admin_accounts' => 1,
        ])
        ->assertSee('No published articles yet')
        ->assertSee('No subscriptions yet')
        ->assertSee('No platform content activity has been recorded yet')
        ->assertSee('No monthly activity yet');
});

test('sidebar links are limited to the authenticated admin permissions', function () {
    $admin = createDashboardAdmin(['view dashboard', 'manage blogs']);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertSee('href="'.route('admin.blogs.index').'"', false)
        ->assertSee('Blog Management')
        ->assertDontSee('href="'.route('admin.settings.index').'"', false)
        ->assertDontSee('Site Settings')
        ->assertDontSee('href="'.route('admin.users.index').'"', false)
        ->assertSee('Workspace Management')
        ->assertSee('aria-disabled="true"', false)
        ->assertDontSee('users.html')
        ->assertDontSee('queues.html');
});

test('super admin navigation uses registered routes for every implemented module', function () {
    $admin = createDashboardAdmin([]);
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdminRole);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response->assertOk();

    foreach ([
        'admin.dashboard',
        'admin.users.index',
        'admin.ai-configuration.index',
        'admin.homepage.index',
        'admin.about.index',
        'admin.feature_page.index',
        'admin.pricing.index',
        'admin.pricing_page.index',
        'admin.blogs.index',
        'admin.blog_page.index',
        'admin.affiliate_page.index',
        'admin.frontend_navigation.index',
        'admin.settings.index',
        'admin.seo.index',
        'admin.role-permission.index',
    ] as $routeName) {
        $response->assertSee('href="'.route($routeName).'"', false);
    }
});

test('dynamic admin and blog output is escaped', function () {
    $unsafeAdminName = '<script>alert("admin")</script>';
    $unsafeBlogTitle = '<img src=x onerror=alert("blog")>';
    $admin = createDashboardAdmin(['view dashboard'], ['name' => $unsafeAdminName]);
    Blog::create([
        'title' => $unsafeBlogTitle,
        'slug' => 'escaped-dashboard-blog',
        'published_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertSee($unsafeAdminName)
        ->assertSee($unsafeBlogTitle)
        ->assertDontSee($unsafeAdminName, false)
        ->assertDontSee($unsafeBlogTitle, false);
});

test('the dashboard exposes only a csrf-protected post logout action', function () {
    $admin = createDashboardAdmin();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertSee('action="'.route('admin.logout').'"', false)
        ->assertSee('method="POST"', false)
        ->assertSee('name="_token"', false)
        ->assertDontSee('href="'.route('admin.logout').'"', false);
});
