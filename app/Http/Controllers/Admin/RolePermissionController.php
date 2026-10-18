<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    private const SUPER_ADMIN_ROLE = 'Super Admin';

    public function index(Request $request): View
    {
        $authenticatedAdmin = $this->authenticatedAdmin($request);
        $admins = Admin::with('roles')->get();
        $roles = Role::where('guard_name', 'admin')
            ->when(
                ! $this->isSuperAdmin($authenticatedAdmin),
                fn ($query) => $query->where('name', '!=', self::SUPER_ADMIN_ROLE),
            )
            ->get();

        return view('admin.role-permission.index', compact('admins', 'roles'));
    }

    public function createAdmin(Request $request): View
    {
        $authenticatedAdmin = $this->authenticatedAdmin($request);
        $roles = Role::where('guard_name', 'admin')
            ->when(
                ! $this->isSuperAdmin($authenticatedAdmin),
                fn ($query) => $query->where('name', '!=', self::SUPER_ADMIN_ROLE),
            )
            ->get();

        return view('admin.role-permission.create-admin', compact('roles'));
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $authenticatedAdmin = $this->authenticatedAdmin($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:6',
            'role' => [
                'required',
                Rule::exists('roles', 'name')->where('guard_name', 'admin'),
            ],
        ]);

        if ($validated['role'] === self::SUPER_ADMIN_ROLE && ! $this->isSuperAdmin($authenticatedAdmin)) {
            abort(403);
        }

        $role = Role::findByName($validated['role'], 'admin');
        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $admin->assignRole($role);

        return redirect()->route('admin.role-permission.index')->with('success', 'Admin created successfully.');
    }

    public function editRole(Request $request, Role $role): View
    {
        $this->ensureRoleCanBeManaged($role, $this->authenticatedAdmin($request));

        $permissions = Permission::where('guard_name', 'admin')->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.role-permission.edit-role', compact('role', 'permissions', 'rolePermissions'));
    }

    public function createRole(): View
    {
        return view('admin.role-permission.create-role');
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('roles', 'name')->where('guard_name', 'admin'),
            ],
        ]);

        if (strcasecmp(trim($validated['name']), self::SUPER_ADMIN_ROLE) === 0) {
            abort(403);
        }

        Role::create(['name' => $validated['name'], 'guard_name' => 'admin']);

        return redirect()->route('admin.role-permission.index')->with('success', 'Role created successfully.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $authenticatedAdmin = $this->authenticatedAdmin($request);
        $this->ensureRoleCanBeManaged($role, $authenticatedAdmin);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'admin')
                    ->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'admin'),
            ],
        ]);

        $requestedRoleName = trim($validated['name']);
        if ($role->name === self::SUPER_ADMIN_ROLE && $requestedRoleName !== self::SUPER_ADMIN_ROLE) {
            abort(403);
        }

        if ($role->name !== self::SUPER_ADMIN_ROLE && strcasecmp($requestedRoleName, self::SUPER_ADMIN_ROLE) === 0) {
            abort(403);
        }

        $role->update(['name' => $requestedRoleName]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('admin.role-permission.index')->with('success', 'Role updated successfully.');
    }

    private function authenticatedAdmin(Request $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }

    private function ensureRoleCanBeManaged(Role $role, Admin $admin): void
    {
        abort_unless($role->guard_name === 'admin', 404);

        if ($role->name === self::SUPER_ADMIN_ROLE && ! $this->isSuperAdmin($admin)) {
            abort(403);
        }
    }

    private function isSuperAdmin(Admin $admin): bool
    {
        return $admin->hasRole(self::SUPER_ADMIN_ROLE, 'admin');
    }
}
