<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BlackTechEngine') }} · Customer Workspace</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bte-bg-base font-sans text-slate-900 antialiased dark:text-slate-100">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
            @include('layouts.navigation')

            <div class="min-w-0 flex-1 lg:pl-72">
                <header class="bte-glass-nav sticky top-0 z-30 flex min-h-16 items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                    <button type="button" class="rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 lg:hidden dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" x-on:click="sidebarOpen = true" aria-label="Open customer navigation">
                        <i data-lucide="menu" class="h-5 w-5" aria-hidden="true"></i>
                    </button>

                    <div class="min-w-0 flex-1">
                        @isset($header)
                            {{ $header }}
                        @else
                            <p class="truncate font-heading text-lg font-bold">Customer workspace</p>
                        @endisset
                    </div>

                    <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:flex dark:text-slate-300 dark:hover:bg-slate-800">
                        <i data-lucide="settings" class="h-4 w-4" aria-hidden="true"></i>
                        Account
                    </a>
                    <button type="button" class="theme-toggle-btn rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Toggle color theme">
                        <i data-lucide="sun" class="theme-icon-sun hidden h-5 w-5" aria-hidden="true"></i>
                        <i data-lucide="moon" class="theme-icon-moon h-5 w-5" aria-hidden="true"></i>
                    </button>
                </header>

                @if (session('status'))
                    <div class="mx-4 mt-5 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 sm:mx-6 lg:mx-8 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
