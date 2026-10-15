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
                    <h3>Add New Role</h3>
                    <form action="{{ route('admin.role-permission.role.store') }}" method="POST">
                        @csrf
                        <p>Role Name: <input type="text" name="name" required></p>
                        <button type="submit" style="padding: 10px; background: #17a2b8; color: white; border: none;">Create Role</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>