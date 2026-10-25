<?php

use App\Models\Admin;
use App\Models\AdminUserAudit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
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
    Permission::create([
        'name' => 'edit users',
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

function createUserEditingAdmin(): Admin
{
    $admin = createUserDirectoryAdmin();
    $admin->givePermissionTo('edit users');

    return $admin;
}

test('a guest is redirected to the admin login from every user directory route', function () {
    $user = User::factory()->create();

    $this->get(route('admin.users.index'))->assertRedirectToRoute('admin.login');
    $this->get(route('admin.users.show', $user))->assertRedirectToRoute('admin.login');
    $this->get(route('admin.users.edit', $user))->assertRedirectToRoute('admin.login');
    $this->patch(route('admin.users.update', $user), [
        'name' => 'Changed Name',
        'lock_version' => 0,
    ])->assertRedirectToRoute('admin.login');
});

test('a regular web user cannot use web authentication to access the admin directory', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertRedirectToRoute('admin.login');

    $this->actingAs($user)
        ->patch(route('admin.users.update', $user), [
            'name' => 'Changed Name',
            'lock_version' => 0,
        ])
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

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.edit', User::factory()->create()))
        ->assertOk();
});

test('manage users remains read only while edit users protects both editing routes', function () {
    $admin = createUserDirectoryAdmin();
    $user = User::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.edit', $user))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.users.update', $user), [
            'name' => 'Changed Name',
            'lock_version' => 0,
        ])
        ->assertForbidden();
});

test('an authorized admin can render the name-only edit form with csrf protection', function () {
    $admin = createUserEditingAdmin();
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.users.edit', $user))
        ->assertOk()
        ->assertSee('Edit user name')
        ->assertSee('name="_token"', false)
        ->assertSee('name="_method" value="PATCH"', false)
        ->assertSee('name="lock_version"', false)
        ->assertSee('name="name"', false)
        ->assertDontSee('name="email"', false)
        ->assertDontSee('name="password"', false);
});

test('the details page shows editing only to an admin with edit users permission', function () {
    $user = User::factory()->create();

    $this->actingAs(createUserDirectoryAdmin(), 'admin')
        ->get(route('admin.users.show', $user))
        ->assertOk()
        ->assertDontSee('Edit Profile');

    $this->actingAs(createUserEditingAdmin(), 'admin')
        ->get(route('admin.users.show', $user))
        ->assertOk()
        ->assertSee('Edit Profile');
});

test('name editing validates required string and maximum length rules', function (mixed $name) {
    $admin = createUserEditingAdmin();
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.users.edit', $user))
        ->patch(route('admin.users.update', $user), [
            'name' => $name,
            'lock_version' => 0,
        ])
        ->assertRedirect(route('admin.users.edit', $user))
        ->assertSessionHasErrors('name');

    expect($user->fresh()->name)->toBe('Original Name');
    $this->assertDatabaseCount('admin_user_audits', 0);
})->with([
    'empty name' => [''],
    'whitespace-only name' => ['   '],
    'non-string name' => [['Injected Name']],
    'name longer than 255 characters' => [str_repeat('a', 256)],
]);

test('name editing requires a valid non-negative lock version', function (array $payload) {
    $admin = createUserEditingAdmin();
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.users.edit', $user))
        ->patch(route('admin.users.update', $user), $payload + ['name' => 'Changed Name'])
        ->assertRedirect(route('admin.users.edit', $user))
        ->assertSessionHasErrors('lock_version');

    expect($user->fresh()->name)->toBe('Original Name');
    $this->assertDatabaseCount('admin_user_audits', 0);
})->with([
    'missing version' => [[]],
    'non-integer version' => [['lock_version' => 'current']],
    'negative version' => [['lock_version' => -1]],
]);

test('a 255 character trimmed name is updated and audited without changing sensitive fields', function () {
    $admin = createUserEditingAdmin();
    $password = Hash::make('original-password');
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.test',
        'email_verified_at' => $verifiedAt,
        'password' => $password,
        'remember_token' => 'original-remember-token',
    ]);
    DB::table('sessions')->insert([
        'id' => 'preserved-user-session',
        'user_id' => $user->id,
        'ip_address' => null,
        'user_agent' => null,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);
    $submittedName = str_repeat('n', 255);

    $response = $this->actingAs($admin, 'admin')
        ->patch(route('admin.users.update', $user), [
            'name' => "  {$submittedName}  ",
            'lock_version' => 0,
            'email' => 'attacker@example.test',
            'email_verified_at' => null,
            'password' => 'attacker-password',
            'remember_token' => 'attacker-token',
            'permissions' => ['Super Admin'],
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.users.show', $user))
        ->assertSessionHas('status', 'user-name-updated');

    $freshUser = $user->fresh();
    expect($freshUser->name)->toBe($submittedName)
        ->and($freshUser->lock_version)->toBe(1)
        ->and($freshUser->email)->toBe('original@example.test')
        ->and($freshUser->email_verified_at->equalTo($verifiedAt))->toBeTrue()
        ->and($freshUser->getRawOriginal('password'))->toBe($password)
        ->and($freshUser->remember_token)->toBe('original-remember-token');

    $audit = AdminUserAudit::query()->sole();
    expect($audit->acting_admin_id)->toBe($admin->id)
        ->and($audit->target_user_id)->toBe($user->id)
        ->and($audit->action)->toBe('user.name_updated')
        ->and($audit->request_id)->toBeUuid()
        ->and($audit->changes)->toBe(['fields' => ['name']]);
    $this->assertDatabaseHas('sessions', [
        'id' => 'preserved-user-session',
        'user_id' => $user->id,
    ]);
});

test('a stale update is rejected without changing the user or creating an audit', function () {
    $admin = createUserEditingAdmin();
    $user = User::factory()->create(['name' => 'Current Name', 'lock_version' => 1]);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.users.edit', $user))
        ->patch(route('admin.users.update', $user), [
            'name' => 'Stale Name',
            'lock_version' => 0,
        ])
        ->assertRedirect(route('admin.users.edit', $user))
        ->assertSessionHasErrors('lock_version');

    expect($user->fresh()->name)->toBe('Current Name')
        ->and($user->fresh()->lock_version)->toBe(1);
    $this->assertDatabaseCount('admin_user_audits', 0);
});

test('unsupported user mutation methods are not registered', function (string $method) {
    $admin = createUserEditingAdmin();
    $user = User::factory()->create();

    $this->actingAs($admin, 'admin')
        ->call($method, route('admin.users.update', $user))
        ->assertMethodNotAllowed();
})->with([
    'post' => ['POST'],
    'put' => ['PUT'],
    'delete' => ['DELETE'],
]);

test('two updates from the same version allow only the first update', function () {
    $admin = createUserEditingAdmin();
    $user = User::factory()->create(['name' => 'Original Name']);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.users.update', $user), [
            'name' => 'First Name',
            'lock_version' => 0,
        ])
        ->assertRedirect(route('admin.users.show', $user));

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.users.update', $user), [
            'name' => 'Second Name',
            'lock_version' => 0,
        ])
        ->assertSessionHasErrors('lock_version');

    expect($user->fresh()->name)->toBe('First Name')
        ->and($user->fresh()->lock_version)->toBe(1);
    $this->assertDatabaseCount('admin_user_audits', 1);
});

test('an unchanged name creates no audit and does not increment the version', function () {
    $admin = createUserEditingAdmin();
    $user = User::factory()->create(['name' => 'Same Name']);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.users.update', $user), [
            'name' => '  Same Name  ',
            'lock_version' => 0,
        ])
        ->assertRedirect(route('admin.users.show', $user))
        ->assertSessionHas('status', 'user-name-unchanged');

    expect($user->fresh()->lock_version)->toBe(0);
    $this->assertDatabaseCount('admin_user_audits', 0);
});

test('the user update rolls back when audit creation fails', function () {
    $admin = createUserEditingAdmin();
    $user = User::factory()->create(['name' => 'Original Name']);

    AdminUserAudit::creating(function (): void {
        throw new RuntimeException('Simulated audit failure.');
    });

    try {
        $this->actingAs($admin, 'admin')
            ->patch(route('admin.users.update', $user), [
                'name' => 'Changed Name',
                'lock_version' => 0,
            ])
            ->assertServerError();
    } finally {
        AdminUserAudit::flushEventListeners();
    }

    expect($user->fresh()->name)->toBe('Original Name')
        ->and($user->fresh()->lock_version)->toBe(0);
    $this->assertDatabaseCount('admin_user_audits', 0);
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

test('the user management surface registers only the approved read and name-edit routes', function () {
    $userRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'admin.users.'));

    expect($userRoutes)->toHaveCount(4)
        ->and($userRoutes->pluck('methods')->flatten()->unique()->sort()->values()->all())
        ->toBe(['GET', 'HEAD', 'PATCH'])
        ->and(Route::has('admin.users.edit'))->toBeTrue()
        ->and(Route::has('admin.users.update'))->toBeTrue();

    foreach (['store', 'create', 'destroy', 'impersonate'] as $action) {
        expect(Route::has("admin.users.{$action}"))->toBeFalse();
    }
});

test('the permission seeder adds both user permissions for super admin without expanding an existing editor', function () {
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
        ->and(Permission::query()->where('name', 'edit users')->where('guard_name', 'admin')->count())->toBe(1)
        ->and($superAdmin->hasPermissionTo('edit users'))->toBeTrue()
        ->and($editor->fresh()->hasPermissionTo('manage blogs'))->toBeTrue()
        ->and($editor->fresh()->hasPermissionTo('manage users'))->toBeFalse()
        ->and($editor->fresh()->hasPermissionTo('edit users'))->toBeFalse();
});
