<?php

use App\Models\Admin;
use App\Models\Blog;
use App\Models\PricingPlan;
use App\Models\User;
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
    $admin = createDashboardAdmin();
    createDashboardAdmin([], ['email' => 'second-admin@example.com']);
    User::factory()->count(3)->create();
    PricingPlan::create(['name' => 'Active One', 'is_active' => true]);
    PricingPlan::create(['name' => 'Active Two', 'is_active' => true]);
    PricingPlan::create(['name' => 'Hidden Plan', 'is_active' => false]);
    Blog::create(['title' => 'Published One', 'slug' => 'published-one', 'published_at' => now()->subDay()]);
    Blog::create(['title' => 'Published Two', 'slug' => 'published-two', 'published_at' => now()->subHour()]);
    Blog::create(['title' => 'Draft Article', 'slug' => 'draft-article']);
    Blog::create(['title' => 'Scheduled Article', 'slug' => 'scheduled-article', 'published_at' => now()->addDay()]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertViewHas('metrics', [
            'registered_users' => 3,
            'active_pricing_plans' => 2,
            'published_blogs' => 2,
            'admin_accounts' => 2,
        ])
        ->assertViewHas('contentStatus', [
            'draft_blogs' => 2,
            'inactive_pricing_plans' => 1,
        ])
        ->assertSee('Published Two')
        ->assertDontSee('Scheduled Article');
});

test('the dashboard renders honest zero and empty states', function () {
    $admin = createDashboardAdmin();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertViewHas('metrics', [
            'registered_users' => 0,
            'active_pricing_plans' => 0,
            'published_blogs' => 0,
            'admin_accounts' => 1,
        ])
        ->assertSee('No published articles yet')
        ->assertSee('No billing or AI-usage backend is connected');
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
