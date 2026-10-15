<!DOCTYPE html>
<html lang="en">
<head>@include('admin.partials.head')</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card" style="margin-bottom: 20px;">
                    <h3>Admins <a href="{{ route('admin.role-permission.admin.create') }}" style="float:right; font-size:14px;">+ Add New Admin</a></h3>
                    <table border="1" width="100%" cellpadding="10" style="border-collapse: collapse;">
                        <tr><th>Name</th><th>Email</th><th>Role</th></tr>
                        @foreach($admins as $admin)
                        <tr>
                            <td>{{ $admin->name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>{{ $admin->roles->pluck('name')->implode(', ') }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="card">
                    <h3>Roles & Permissions <a href="{{ route('admin.role-permission.role.create') }}" style="float:right; font-size:14px;">+ Add New Role</a></h3>
                    <table border="1" width="100%" cellpadding="10" style="border-collapse: collapse;">
                        <tr><th>Role Name</th><th>Action</th></tr>
                        @foreach($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td><a href="{{ route('admin.role-permission.role.edit', $role->id) }}">Edit Role & Permissions</a></td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>