<div class="sidebar">
    <h3>Admin Panel</h3>
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a href="#">Users List</a>
    <a href="{{ route('admin.settings.index') }}">Site Settings</a>
    <a href="{{ route('admin.about.index') }}">About Page Edit</a>
    <a href="{{ route('admin.seo.index') }}">SEO Settings</a>
    <a href="{{ route('admin.blogs.index') }}">Blogs</a>
    <a href="{{ route('admin.pricing.index') }}">Pricing Plans</a>
    @can('manage roles', 'admin')
    <a href="{{ route('admin.role-permission.index') }}">Roles & Permissions</a>
@endcan

@role('Super Admin', 'admin')
    <a href="#">Settings</a>
@endrole

</div>