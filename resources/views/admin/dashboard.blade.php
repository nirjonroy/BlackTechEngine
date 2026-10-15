<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
</head>
<body>
    <div class="wrapper">
        <!-- Left Sidebar -->
        @include('admin.partials.sidebar')

        <!-- Right Side (Main Content) -->
        <div class="main-content">
            
            <!-- Top Header -->
            @include('admin.partials.header')

            <!-- Main Body Content -->
            <div class="content">
                <div class="card">
                    <h2>Dashboard Overview</h2>
                    <p>Welcome to your new simple dashboard. You can start adding more cards, charts, or tables here.</p>
                </div>
            </div>

            <!-- Bottom Footer -->
            @include('admin.partials.footer')
            
        </div>
    </div>
</body>
</html>