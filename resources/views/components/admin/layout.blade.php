@props([
    'title' => 'Admin Dashboard',
    'breadcrumbs' => [],
])

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} — BlackTechEngine Admin</title>
    <script>
        if (localStorage.getItem('bte_theme') === 'light') {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@1.61.0/dist/umd/lucide.min.js"></script>
    @stack('styles')
</head>
<body class="flex min-h-screen min-w-0 bg-slate-50 font-sans text-slate-900 antialiased selection:bg-indigo-500 selection:text-white dark:bg-[#070A12] dark:text-slate-100" x-data="{ profileOpen: false, logoutOpen: false }" x-on:keydown.escape.window="profileOpen = false; if (logoutOpen) { logoutOpen = false; $nextTick(() => $refs.logoutTrigger.focus()) }">
    @include('admin.dashboard-partials.sidebar')

    <div class="flex min-h-screen min-w-0 flex-1 flex-col lg:pl-72">
        @include('admin.dashboard-partials.topbar', ['breadcrumbs' => $breadcrumbs])

        <main class="flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
            @include('admin.dashboard-partials.flash-messages')
            {{ $slot }}
        </main>

        @include('admin.dashboard-partials.footer')
    </div>

    @include('admin.dashboard-partials.logout-modal')
    @stack('scripts')
</body>
</html>
