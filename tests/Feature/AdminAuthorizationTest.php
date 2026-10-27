<?php

use App\Models\Admin;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use App\Models\Blog;
use App\Models\PricingPlan;
use App\Models\SeoSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

const ADMIN_PERMISSIONS = [
    'view dashboard',
    'create admin',
    'edit admin',
    'delete admin',
    'manage roles',
    'manage site settings',
    'manage homepage settings',
    'manage affiliate page settings',
    'manage affiliates',
    'manage frontend navigation',
    'manage about page',
    'manage seo settings',
    'manage blogs',
    'manage blog page settings',
    'manage pricing plans',
    'manage pricing page settings',
    'manage feature page settings',
    'manage users',
    'edit users',
    'manage ai models',
];

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (ADMIN_PERMISSIONS as $permission) {
        Permission::create([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }
});

function createAuthorizationAdmin(): Admin
{
    return Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);
}

test('every sensitive admin endpoint rejects an admin without permission', function () {
    $admin = createAuthorizationAdmin();
    $role = Role::create(['name' => 'Content Manager', 'guard_name' => 'admin']);
    $blog = Blog::create(['title' => 'Protected blog', 'slug' => 'protected-blog']);
    $seo = SeoSetting::create(['page_name' => 'Protected SEO']);
    $plan = PricingPlan::create(['name' => 'Protected plan']);
    $user = User::factory()->create();
    $affiliateApplication = AffiliateApplication::create(['user_id' => $user->id]);
    $affiliate = Affiliate::create([
        'user_id' => $user->id,
        'application_id' => $affiliateApplication->id,
    ]);

    $endpoints = [
        ['GET', route('admin.dashboard')],
        ['GET', route('admin.users.index')],
        ['GET', route('admin.users.show', $user)],
        ['GET', route('admin.users.edit', $user)],
        ['PATCH', route('admin.users.update', $user)],
        ['GET', route('admin.role-permission.index')],
        ['GET', route('admin.role-permission.admin.create')],
        ['POST', route('admin.role-permission.admin.store')],
        ['GET', route('admin.role-permission.role.create')],
        ['POST', route('admin.role-permission.role.store')],
        ['GET', route('admin.role-permission.role.edit', $role)],
        ['POST', route('admin.role-permission.role.update', $role)],
        ['GET', route('admin.settings.index')],
        ['POST', route('admin.settings.update')],
        ['GET', route('admin.homepage.index')],
        ['POST', route('admin.homepage.update')],
        ['GET', route('admin.affiliate_page.index')],
        ['POST', route('admin.affiliate_page.update')],
        ['GET', route('admin.affiliates.index')],
        ['GET', route('admin.affiliates.show', $affiliate)],
        ['GET', route('admin.affiliate-applications.index')],
        ['GET', route('admin.affiliate-applications.show', $affiliateApplication)],
        ['GET', route('admin.frontend_navigation.index')],
        ['POST', route('admin.frontend_navigation.update')],
        ['GET', route('admin.about.index')],
        ['POST', route('admin.about.update')],
        ['GET', route('admin.seo.index')],
        ['GET', route('admin.seo.create')],
        ['POST', route('admin.seo.store')],
        ['GET', route('admin.seo.edit', $seo)],
        ['POST', route('admin.seo.update', $seo)],
        ['DELETE', route('admin.seo.destroy', $seo)],
        ['GET', route('admin.blogs.index')],
        ['GET', route('admin.blogs.create')],
        ['POST', route('admin.blogs.store')],
        ['GET', route('admin.blogs.edit', $blog)],
        ['POST', route('admin.blogs.update', $blog)],
        ['DELETE', route('admin.blogs.destroy', $blog)],
        ['GET', route('admin.blog_page.index')],
        ['POST', route('admin.blog_page.update')],
        ['GET', route('admin.pricing.index')],
        ['GET', route('admin.pricing.create')],
        ['POST', route('admin.pricing.store')],
        ['GET', route('admin.pricing.edit', $plan)],
        ['POST', route('admin.pricing.update', $plan)],
        ['DELETE', route('admin.pricing.destroy', $plan)],
        ['GET', route('admin.pricing_page.index')],
        ['POST', route('admin.pricing_page.update')],
        ['GET', route('admin.feature_page.index')],
        ['POST', route('admin.feature_page.update')],
    ];

    $this->actingAs($admin, 'admin');

    foreach ($endpoints as [$method, $uri]) {
        $this->call($method, $uri)->assertForbidden();
    }
});

test('an authorized admin can access only the permitted module', function (string $permission, string $allowedRoute) {
    $admin = createAuthorizationAdmin();
    $admin->givePermissionTo($permission);

    $this->actingAs($admin, 'admin')
        ->get(route($allowedRoute))
        ->assertOk();

    $deniedRoute = $allowedRoute === 'admin.blogs.index'
        ? 'admin.settings.index'
        : 'admin.blogs.index';

    $this->get(route($deniedRoute))->assertForbidden();
})->with([
    'dashboard' => ['view dashboard', 'admin.dashboard'],
    'admin creation' => ['create admin', 'admin.role-permission.admin.create'],
    'role management' => ['manage roles', 'admin.role-permission.role.create'],
    'site settings' => ['manage site settings', 'admin.settings.index'],
    'homepage settings' => ['manage homepage settings', 'admin.homepage.index'],
    'affiliate page settings' => ['manage affiliate page settings', 'admin.affiliate_page.index'],
    'affiliate management' => ['manage affiliates', 'admin.affiliates.index'],
    'frontend navigation' => ['manage frontend navigation', 'admin.frontend_navigation.index'],
    'about page' => ['manage about page', 'admin.about.index'],
    'SEO settings' => ['manage seo settings', 'admin.seo.index'],
    'blogs' => ['manage blogs', 'admin.blogs.index'],
    'blog page' => ['manage blog page settings', 'admin.blog_page.index'],
    'pricing plans' => ['manage pricing plans', 'admin.pricing.index'],
    'pricing page' => ['manage pricing page settings', 'admin.pricing_page.index'],
    'feature page' => ['manage feature page settings', 'admin.feature_page.index'],
    'registered users' => ['manage users', 'admin.users.index'],
]);

test('a super admin can access every protected admin module without direct permissions', function () {
    $admin = createAuthorizationAdmin();
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdminRole);

    $routes = [
        'admin.dashboard',
        'admin.users.index',
        'admin.role-permission.index',
        'admin.role-permission.admin.create',
        'admin.role-permission.role.create',
        'admin.settings.index',
        'admin.homepage.index',
        'admin.affiliate_page.index',
        'admin.affiliates.index',
        'admin.affiliate-applications.index',
        'admin.frontend_navigation.index',
        'admin.about.index',
        'admin.seo.index',
        'admin.blogs.index',
        'admin.blog_page.index',
        'admin.pricing.index',
        'admin.pricing_page.index',
        'admin.feature_page.index',
    ];

    $this->actingAs($admin, 'admin');

    foreach ($routes as $routeName) {
        $this->get(route($routeName))->assertOk();
    }
});

test('a regular web user cannot access admin functionality', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertRedirect();

    $this->assertGuest('admin');
});

test('an unauthenticated request cannot access admin functionality', function () {
    $this->get(route('admin.settings.index'))->assertRedirect();
    $this->assertGuest('admin');
});

test('an authenticated admin can log out without a permission', function () {
    $admin = createAuthorizationAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest('admin');
});

test('a permission from the web guard does not authorize an admin', function () {
    $admin = createAuthorizationAdmin();
    $webPermission = Permission::create([
        'name' => 'manage blogs',
        'guard_name' => 'web',
    ]);

    expect($webPermission->guard_name)->toBe('web');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.blogs.index'))
        ->assertForbidden();
});

test('the super admin bypass does not apply when an admin model is authenticated on the web guard', function () {
    $admin = createAuthorizationAdmin();
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdminRole);

    $this->actingAs($admin, 'web');

    expect(Gate::forUser($admin)->allows('manage blogs'))->toBeFalse();
    $this->assertGuest('admin');
});

test('an admin creator cannot assign the super admin role', function () {
    $admin = createAuthorizationAdmin();
    $admin->givePermissionTo('create admin');
    Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.role-permission.admin.create'))
        ->assertOk()
        ->assertDontSee('<option value="Super Admin">', false);

    $this->post(route('admin.role-permission.admin.store'), [
        'name' => 'Escalated Admin',
        'email' => 'escalated@example.com',
        'password' => 'Password123',
        'role' => 'Super Admin',
    ])->assertForbidden();

    $this->assertDatabaseMissing('admins', ['email' => 'escalated@example.com']);
});

test('a role manager cannot modify the super admin role', function () {
    $admin = createAuthorizationAdmin();
    $admin->givePermissionTo('manage roles');
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.role-permission.role.edit', $superAdminRole))
        ->assertForbidden();

    $this->post(route('admin.role-permission.role.update', $superAdminRole), [
        'name' => 'Super Admin',
        'permissions' => ADMIN_PERMISSIONS,
    ])->assertForbidden();
});

test('a role manager cannot rename another role to super admin', function () {
    $admin = createAuthorizationAdmin();
    $admin->givePermissionTo('manage roles');
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.role-permission.role.update', $role), [
            'name' => 'Super Admin',
            'permissions' => [],
        ])
        ->assertForbidden();

    expect($role->refresh()->name)->toBe('Editor');
});

test('the permission seeder is idempotent and preserves existing editor assignments', function () {
    $editorRole = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    $editorRole->givePermissionTo('view dashboard');
    $customPermission = Permission::create(['name' => 'review audit logs', 'guard_name' => 'admin']);
    $contentManagerRole = Role::create(['name' => 'Content Manager', 'guard_name' => 'admin']);
    $contentManagerRole->givePermissionTo($customPermission);
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $superAdminRole->givePermissionTo(['view dashboard', $customPermission]);
    Permission::where('name', 'manage blog page settings')->where('guard_name', 'admin')->delete();

    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::where('guard_name', 'admin')->count())->toBe(count(ADMIN_PERMISSIONS) + 1)
        ->and(Permission::where('name', 'manage blog page settings')->where('guard_name', 'admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'Editor')->where('guard_name', 'admin')->count())->toBe(1)
        ->and(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->count())->toBe(1)
        ->and($editorRole->fresh()->permissions->pluck('name')->all())->toBe(['view dashboard'])
        ->and($contentManagerRole->fresh()->permissions->pluck('name')->all())->toBe(['review audit logs'])
        ->and($superAdminRole->fresh()->hasAllPermissions(ADMIN_PERMISSIONS))->toBeTrue()
        ->and($superAdminRole->fresh()->hasPermissionTo($customPermission))->toBeTrue();
});

test('the permission seeder does not assign super admin to an existing account', function () {
    $admin = Admin::create([
        'name' => 'Existing Admin',
        'email' => 'admin24@gmail.com',
        'password' => Hash::make('password'),
    ]);

    $this->seed(RolePermissionSeeder::class);

    expect($admin->fresh()->hasRole('Super Admin', 'admin'))->toBeFalse();
});
