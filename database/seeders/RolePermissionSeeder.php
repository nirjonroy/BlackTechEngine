<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $requiredPermissions = [
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
            'edit users',
        ];

        foreach ($requiredPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }

        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'admin',
        ]);
        $editorRole = Role::firstOrCreate([
            'name' => 'Editor',
            'guard_name' => 'admin',
        ]);

        $superAdminRole->givePermissionTo(
            Permission::query()
                ->where('guard_name', 'admin')
                ->whereIn('name', $requiredPermissions)
                ->get(),
        );

        if ($editorRole->wasRecentlyCreated) {
            $editorRole->givePermissionTo(['view dashboard', 'create admin', 'edit admin']);
        }
    }
}
