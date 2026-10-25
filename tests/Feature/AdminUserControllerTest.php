<?php

use App\Models\Admin;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::create([
        'name' => 'manage users',
        'guard_name' => 'admin',
    ]);
});

function createUserDirectoryAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage users');
    }

    return $admin;
}

test('a guest is redirected to the admin login from every user directory route', function () {
    $user = User::factory()->create();

    $this->get(route('admin.users.index'))->assertRedirectToRoute('admin.login');
    $this->get(route('admin.users.show', $user))->assertRedirectToRoute('admin.login');
});

test('a regular web user cannot use web authentication to access the admin directory', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertRedirectToRoute('admin.login');

    $this->assertGuest('admin');
});

test('an admin without manage users permission receives 403', function () {
    $admin = createUserDirectoryAdmin(false);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('a super admin can access the user directory without a direct permission', function () {
    $admin = createUserDirectoryAdmin(false);
    $superAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdmin);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Registered Users');
});

test('the directory lists only supported account data and accurate totals', function () {
    $admin = createUserDirectoryAdmin();
    $verified = User::factory()->create(['name' => 'Verified Account']);
    $unverified = User::factory()->unverified()->create(['name' => 'Pending Account']);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.users.index'));

    $response
        ->assertOk()
        ->assertViewHas('totalUsers', 2)
        ->assertViewHas('verifiedUsers', 1)
        ->assertViewHas('unverifiedUsers', 1)
        ->assertSee($verified->name)
        ->assertSee($unverified->name)
        ->assertSee('Workspace, plan, billing, and usage data are not available')
        ->assertDontSee('Impersonate')
        ->assertDontSee('Suspend')
        ->assertDontSee('password')
        ->assertDontSee('remember_token');
});

test('users can be searched by name email or exact id', function (string $search) {
    $admin = createUserDirectoryAdmin();
    $matchingUser = User::factory()->create([
        'id' => 42,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
    ]);
    User::factory()->create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.test',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.index', ['search' => $search]))
        ->assertOk()
        ->assertSee($matchingUser->name)
        ->assertDontSee('Grace Hopper');
})->with([
    'name' => ['Lovelace'],
    'email' => ['ada@example.test'],
    'id' => ['42'],
]);

test('email verification filtering uses the real email verified timestamp', function (string $filter, string $visible, string $hidden) {
    $admin = createUserDirectoryAdmin();
    User::factory()->create(['name' => 'Verified Account']);
    User::factory()->unverified()->create(['name' => 'Pending Account']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.index', ['verification' => $filter]))
        ->assertOk()
        ->assertSee($visible)
        ->assertDontSee($hidden);
})->with([
    'verified accounts' => ['verified', 'Verified Account', 'Pending Account'],
    'unverified accounts' => ['unverified', 'Pending Account', 'Verified Account'],
]);

test('directory query controls reject unbounded or unrecognized values', function (array $query, string $error) {
    $admin = createUserDirectoryAdmin();

    $this->actingAs($admin, 'admin')
        ->from(route('admin.users.index'))
        ->get(route('admin.users.index', $query))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors($error);
})->with([
    'long search' => [['search' => str_repeat('x', 101)], 'search'],
    'unknown verification' => [['verification' => 'disabled'], 'verification'],
    'unknown sort' => [['sort' => 'password'], 'sort'],
    'unbounded page size' => [['per_page' => 5000], 'per_page'],
]);

test('pagination is bounded and preserves active query parameters', function () {
    $admin = createUserDirectoryAdmin();
    User::factory()->count(12)->create(['name' => 'Matching User']);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.users.index', [
        'search' => 'Matching',
        'sort' => 'name',
        'per_page' => 10,
    ]));

    $response
        ->assertOk()
        ->assertViewHas('users', function (LengthAwarePaginator $users): bool {
            return $users->count() === 10
                && $users->total() === 12
                && str_contains((string) $users->nextPageUrl(), 'search=Matching')
                && str_contains((string) $users->nextPageUrl(), 'sort=name')
                && str_contains((string) $users->nextPageUrl(), 'per_page=10');
        });
});

test('whitelisted sorting is stable', function (string $sort, string $expectedFirstName) {
    $admin = createUserDirectoryAdmin();
    User::factory()->create([
        'name' => 'Zulu Account',
        'email' => 'zulu@example.test',
        'created_at' => now()->subDay(),
    ]);
    User::factory()->create([
        'name' => 'Alpha Account',
        'email' => 'alpha@example.test',
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.index', ['sort' => $sort]))
        ->assertOk()
        ->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->first()->name === $expectedFirstName);
})->with([
    'newest' => ['newest', 'Alpha Account'],
    'oldest' => ['oldest', 'Zulu Account'],
    'name' => ['name', 'Alpha Account'],
    'email' => ['email', 'Alpha Account'],
]);

test('the directory has an honest empty state', function () {
    $admin = createUserDirectoryAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('No registered users found');
});

test('the detail route displays escaped safe fields without secrets or mutation actions', function () {
    $admin = createUserDirectoryAdmin();
    $unsafeName = '<script>alert("user")</script>';
    $unsafeEmail = 'escaped@example.test';
    $secretToken = 'never-render-this-token';
    $user = User::factory()->create([
        'name' => $unsafeName,
        'email' => $unsafeEmail,
        'remember_token' => $secretToken,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.show', $user))
        ->assertOk()
        ->assertSee($unsafeName)
        ->assertDontSee($unsafeName, false)
        ->assertSee($unsafeEmail)
        ->assertDontSee($secretToken)
        ->assertDontSee('password')
        ->assertDontSee('Impersonate')
        ->assertDontSee('Suspend')
        ->assertDontSee('Delete user');
});

test('the detail route returns 404 for an unknown user', function () {
    $admin = createUserDirectoryAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.show', 999999))
        ->assertNotFound();
});

test('the user management surface registers get routes only', function () {
    $userRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin.users.'));

    expect($userRoutes)->toHaveCount(2)
        ->and($userRoutes->pluck('methods')->flatten()->unique()->sort()->values()->all())
        ->toBe(['GET', 'HEAD']);

    foreach (['store', 'create', 'edit', 'update', 'destroy', 'impersonate'] as $action) {
        expect(Route::has("admin.users.{$action}"))->toBeFalse();
    }
});

test('the permission seeder adds manage users for super admin without expanding an existing editor', function () {
    $editorPermission = Permission::create([
        'name' => 'manage blogs',
        'guard_name' => 'admin',
    ]);
    $editor = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    $editor->givePermissionTo($editorPermission);

    $this->seed(RolePermissionSeeder::class);

    $manageUsersPermissions = Permission::query()
        ->where('name', 'manage users')
        ->where('guard_name', 'admin')
        ->get();
    $superAdmin = Role::findByName('Super Admin', 'admin');

    expect($manageUsersPermissions)->toHaveCount(1)
        ->and($superAdmin->hasPermissionTo('manage users'))->toBeTrue()
        ->and($editor->fresh()->hasPermissionTo('manage blogs'))->toBeTrue()
        ->and($editor->fresh()->hasPermissionTo('manage users'))->toBeFalse();
});
