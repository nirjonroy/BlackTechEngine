<?php

use App\Models\Admin;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('the recovered permission baseline rehearses to the exact Phase 15B target idempotently', function () {
    $baselinePermissions = [
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

    foreach ($baselinePermissions as $permission) {
        Permission::query()->create(['name' => $permission, 'guard_name' => 'admin']);
    }

    $superAdminRole = Role::query()->create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $editorRole = Role::query()->create(['name' => 'Editor', 'guard_name' => 'admin']);
    $superAdminRole->givePermissionTo($baselinePermissions);
    $editorRole->givePermissionTo(['view dashboard', 'create admin', 'edit admin']);

    $superAdmin = Admin::query()->create([
        'name' => 'Permission Rehearsal Super Admin',
        'email' => 'phase15b-super@example.test',
        'password' => Hash::make('synthetic-password'),
    ]);
    $editor = Admin::query()->create([
        'name' => 'Permission Rehearsal Editor',
        'email' => 'phase15b-editor@example.test',
        'password' => Hash::make('synthetic-password'),
    ]);
    $superAdmin->assignRole($superAdminRole);
    $editor->assignRole($editorRole);
    $assignmentsBefore = $superAdmin->roles()->pluck('roles.id')->merge($editor->roles()->pluck('roles.id'))->sort()->values()->all();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    $assignmentsAfter = $superAdmin->fresh()->roles()->pluck('roles.id')->merge($editor->fresh()->roles()->pluck('roles.id'))->sort()->values()->all();

    expect(Permission::query()->where('guard_name', 'admin')->count())->toBe(21)
        ->and(Permission::query()->where('name', 'verify ai providers')->where('guard_name', 'admin')->count())->toBe(1)
        ->and($superAdminRole->fresh()->permissions()->count())->toBe(21)
        ->and($editorRole->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe([
            'create admin',
            'edit admin',
            'view dashboard',
        ])
        ->and($superAdminRole->permissions()->count() + $editorRole->permissions()->count())->toBe(24)
        ->and($assignmentsAfter)->toBe($assignmentsBefore);

    $this->assertDatabaseCount('model_has_roles', 2);
});
