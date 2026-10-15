<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolePermissionController extends Controller
{
    public function index()
    {
        $admins = Admin::with('roles')->get();
        $roles = Role::where('guard_name', 'admin')->get();
        return view('admin.role-permission.index', compact('admins', 'roles'));
    }

    public function createAdmin()
    {
        $roles = Role::where('guard_name', 'admin')->get();
        return view('admin.role-permission.create-admin', compact('roles'));
    }

    public function storeAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $admin->assignRole($request->role);
        return redirect()->route('admin.role-permission.index')->with('success', 'Admin created successfully.');
    }

    public function editRole(Role $role)
    {
        $permissions = Permission::where('guard_name', 'admin')->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('admin.role-permission.edit-role', compact('role', 'permissions', 'rolePermissions'));
    }

    public function createRole()
    {
        return view('admin.role-permission.create-role');
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name'
        ]);

        Role::create(['name' => $request->name, 'guard_name' => 'admin']);
        return redirect()->route('admin.role-permission.index')->with('success', 'Role created successfully.');
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id
        ]);

        // Role er nam update korbe
        $role->update(['name' => $request->name]);
        // Permission gulo update korbe
        $role->syncPermissions($request->permissions ?? []);
        
        return redirect()->route('admin.role-permission.index')->with('success', 'Role updated successfully.');
    }
}