<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
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

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }

        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'admin',
        ]);
        $superAdminRoleWasCreated = $superAdminRole->wasRecentlyCreated;

        $editorRole = Role::firstOrCreate([
            'name' => 'Editor',
            'guard_name' => 'admin',
        ]);

        if ($superAdminRoleWasCreated) {
            $superAdminRole->givePermissionTo(
                Permission::where('guard_name', 'admin')->get(),
            );
        }

        if ($editorRole->wasRecentlyCreated) {
            $editorRole->givePermissionTo(['view dashboard', 'create admin', 'edit admin']);
        }

        $admin = Admin::where('email', 'admin24@gmail.com')->first();
        if ($admin && $superAdminRoleWasCreated) {
            $admin->assignRole($superAdminRole);
        }
    }
}
