<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    @include('frontend.partials.seo')
    @include('frontend.partials.head')
</head>
<body class="bg-slate-50 dark:bg-[#090D16] text-slate-900 dark:text-slate-100 min-h-screen flex flex-col antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Header Section -->
    @include('frontend.partials.header')

    <!-- Dynamic Content Injection -->
    <main>
        @yield('content')
    </main>

    <!-- Footer Section -->
    @include('frontend.partials.footer')

    <!-- Dynamic Modals Location -->
    @stack('modals')

    <!-- Scripts with Asset Helper -->
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>