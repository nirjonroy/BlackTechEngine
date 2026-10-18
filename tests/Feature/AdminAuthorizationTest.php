<?php

use App\Models\Admin;
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
    'manage about page',
    'manage seo settings',
    'manage blogs',
    'manage pricing plans',
    'manage pricing page settings',
    'manage feature page settings',
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

    $endpoints = [
        ['GET', route('admin.dashboard')],
        ['GET', route('admin.role-permission.index')],
        ['GET', route('admin.role-permission.admin.create')],
        ['POST', route('admin.role-permission.admin.store')],
        ['GET', route('admin.role-permission.role.create')],
        ['POST', route('admin.role-permission.role.store')],
        ['GET', route('admin.role-permission.role.edit', $role)],
        ['POST', route('admin.role-permission.role.update', $role)],
        ['GET', route('admin.settings.index')],
        ['POST', route('admin.settings.update')],
        ['GET', route('admin.about.index')],
        ['POST', route('admin.about.update')],
        ['GET', route('admin.seo.index')],
        ['GET', route('admin.seo.create')],
        ['POST', route('admin.seo.store')],
        ['GET', route('admin.seo.edit', 999)],
        ['POST', route('admin.seo.update', 999)],
        ['GET', route('admin.seo.destroy', 999)],
        ['GET', route('admin.blogs.index')],
        ['GET', route('admin.blogs.create')],
        ['POST', route('admin.blogs.store')],
        ['GET', route('admin.blogs.edit', 999)],
        ['POST', route('admin.blogs.update', 999)],
        ['GET', route('admin.blogs.destroy', 999)],
        ['GET', route('admin.pricing.index')],
        ['GET', route('admin.pricing.create')],
        ['POST', route('admin.pricing.store')],
        ['GET', route('admin.pricing.edit', 999)],
        ['POST', route('admin.pricing.update', 999)],
        ['GET', route('admin.pricing.destroy', 999)],
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
    'about page' => ['manage about page', 'admin.about.index'],
    'SEO settings' => ['manage seo settings', 'admin.seo.index'],
    'blogs' => ['manage blogs', 'admin.blogs.index'],
    'pricing plans' => ['manage pricing plans', 'admin.pricing.index'],
    'pricing page' => ['manage pricing page settings', 'admin.pricing_page.index'],
    'feature page' => ['manage feature page settings', 'admin.feature_page.index'],
]);

test('a super admin can access every protected admin module without direct permissions', function () {
    $admin = createAuthorizationAdmin();
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdminRole);

    $routes = [
        'admin.dashboard',
        'admin.role-permission.index',
        'admin.role-permission.admin.create',
        'admin.role-permission.role.create',
        'admin.settings.index',
        'admin.about.index',
        'admin.seo.index',
        'admin.blogs.index',
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
        'password' => 'password',
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

    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::where('guard_name', 'admin')->count())->toBe(count(ADMIN_PERMISSIONS))
        ->and(Role::where('name', 'Editor')->where('guard_name', 'admin')->count())->toBe(1)
        ->and(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->count())->toBe(1)
        ->and($editorRole->fresh()->permissions->pluck('name')->all())->toBe(['view dashboard']);
});
