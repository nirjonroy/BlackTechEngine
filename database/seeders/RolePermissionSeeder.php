<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Admin;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
       
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        
        $permissions = [
            'view dashboard',
            'create admin',
            'edit admin',
            'delete admin',
            'manage roles'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'admin']);
        }

        
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $editorRole = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);

       
        $superAdminRole->givePermissionTo(Permission::all());
        
       
        $editorRole->givePermissionTo(['view dashboard', 'create admin', 'edit admin']);

        $admin = Admin::where('email', 'admin24@gmail.com')->first();
        if ($admin) {
            $admin->assignRole($superAdminRole);
        }
    }
}