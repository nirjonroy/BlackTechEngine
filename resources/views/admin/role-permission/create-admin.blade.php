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
                    <h3>Add New Admin</h3>
                    @include('admin.partials.validation-errors')
                    <form action="{{ route('admin.role-permission.admin.store') }}" method="POST">
                        @csrf
                        <p>Name: <input type="text" name="name" required></p>
                        <p>Email: <input type="email" name="email" required></p>
                        <p>Password: <input type="password" name="password" required></p>
                        <p>Assign Role: 
                            <select name="role" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </p>
                        <button type="submit" style="padding: 10px; background: #17a2b8; color: white; border: none;">Create Admin</button>
                    </form>
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>
</body>
</html>
