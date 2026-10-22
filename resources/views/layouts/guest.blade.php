<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name', 'BlackTechEngine') }}</title>

        <script>
            if (localStorage.getItem('bte_theme') === 'light') {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            } else {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://unpkg.com/lucide@latest"></script>
    </head>
    <body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 antialiased selection:bg-indigo-500 selection:text-white dark:bg-[#090D16] dark:text-slate-100">
        <header class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
            <a href="{{ route('home') }}" class="group flex items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#090D16]" aria-label="BlackTechEngine home">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 via-violet-600 to-cyan-400 text-white shadow-md shadow-indigo-500/20 transition-transform group-hover:scale-105" aria-hidden="true">
                    <i data-lucide="sparkles" class="h-5 w-5"></i>
                </span>
                <span class="font-heading text-xl font-black text-slate-900 sm:text-2xl dark:text-white">BlackTech<span class="text-indigo-600 dark:text-indigo-400">Engine</span></span>
            </a>

            <div class="flex items-center gap-2 sm:gap-4">
                <button id="themeToggle" type="button" class="theme-toggle-btn rounded-xl border border-slate-200 bg-white/80 p-2.5 text-slate-700 transition-all hover:scale-105 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-200 dark:hover:text-indigo-400" aria-label="Toggle color theme">
                    <i data-lucide="sun" class="theme-icon-sun h-5 w-5"></i>
                    <i data-lucide="moon" class="theme-icon-moon hidden h-5 w-5"></i>
                </button>

                @isset($secondaryUrl)
                    <a href="{{ $secondaryUrl }}" class="hidden rounded-lg text-xs font-bold text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:block dark:text-slate-300">
                        {{ $secondaryPrompt }} <span class="ms-1 text-indigo-500 underline underline-offset-2">{{ $secondaryText }}</span>
                    </a>
                @endisset
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            {{ $slot }}
        </main>

        @isset($secondaryUrl)
            <div class="px-4 pb-5 text-center text-xs font-bold text-slate-600 sm:hidden dark:text-slate-300">
                {{ $secondaryPrompt }} <a href="{{ $secondaryUrl }}" class="ms-1 text-indigo-500 underline underline-offset-2">{{ $secondaryText }}</a>
            </div>
        @endisset

        <footer class="border-t border-slate-200 px-4 py-5 text-center text-xs text-slate-500 dark:border-slate-800/80">
            &copy; {{ now()->year }} BlackTechEngine, Inc. All rights reserved.
            <span aria-hidden="true">&bull;</span>
            <a href="{{ route('home') }}" class="rounded hover:text-indigo-500 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Back to Home</a>
        </footer>
    </body>
</html>
