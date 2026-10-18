<!DOCTYPE html>
<html lang="en">
<head>@include('admin.partials.head')</head>
<body>
    <div class="wrapper">
        @include('admin.partials.sidebar')
        <div class="main-content">
            @include('admin.partials.header')
            <div class="content">
                <div class="card">
                    <h3>Edit Permissions for: {{ $role->name }}</h3>
                    @include('admin.partials.validation-errors')
                    <form action="{{ route('admin.role-permission.role.update', $role->id) }}" method="POST">
                        @csrf
                        <p>Role Name: <input type="text" name="name" value="{{ $role->name }}" required></p>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                            @foreach($permissions as $permission)
                                <label>
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                                    {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                    {{ $permission->name }}
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" style="padding: 10px; background: #28a745; color: white; border: none;">Update Permissions</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>
