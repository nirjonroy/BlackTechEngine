<div class="sidebar">
    <h3>Admin Panel</h3>
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a href="#">Users List</a>
    @can('manage roles', 'admin')
    <a href="{{ route('admin.role-permission.index') }}">Roles & Permissions</a>
@endcan

@role('Super Admin', 'admin')
    <a href="#">Settings</a>
@endrole
</div>