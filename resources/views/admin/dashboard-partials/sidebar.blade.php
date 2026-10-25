@php
    $admin = auth('admin')->user();
    $initials = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($admin?->name ?? 'Admin', 0, 2));
@endphp

<aside id="adminSidebar" class="bte-surface fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full shrink-0 flex-col justify-between border-r border-slate-200 transition-transform dark:border-slate-800 lg:translate-x-0" aria-label="Admin sidebar">
    <div class="flex h-full flex-col overflow-y-auto p-5">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 border-b border-slate-200 pb-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-800">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-cyan-500 via-indigo-600 to-violet-600 text-white shadow-md shadow-cyan-500/25">
                <i data-lucide="shield-alert" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <span class="min-w-0">
                <span class="block truncate font-heading text-lg font-black tracking-tight text-slate-900 dark:text-white">BlackTech<span class="text-cyan-500">Admin</span></span>
                <span class="flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-cyan-500"><span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>Secure admin mode</span>
            </span>
        </a>

        <div class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-3 text-xs dark:border-indigo-900/50 dark:bg-[#131C31]">
            <div class="flex items-center justify-between gap-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Data integrity</span>
                <span class="font-mono text-[10px] font-bold text-emerald-500">Live records</span>
            </div>
            <p class="mt-1 text-[10px] leading-4 text-slate-500 dark:text-slate-400">Dashboard values come from current application tables.</p>
        </div>

        @include('admin.dashboard-partials.navigation')

        <div class="relative border-t border-slate-200 pt-4 dark:border-slate-800" x-on:click.outside="profileOpen = false">
            <button type="button" class="bte-card flex w-full items-center justify-between rounded-xl border border-slate-200 p-2.5 text-left transition-all hover:border-cyan-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-800" x-on:click="profileOpen = ! profileOpen" x-bind:aria-expanded="profileOpen" aria-controls="adminProfileDropdown">
                <span class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500 text-xs font-black text-slate-950">{{ $initials }}</span>
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $admin?->name }}</span>
                        <span class="block truncate text-[10px] text-cyan-500">{{ $admin?->email }}</span>
                    </span>
                </span>
                <i data-lucide="more-vertical" class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true"></i>
            </button>

            <div id="adminProfileDropdown" x-cloak x-show="profileOpen" x-transition class="bte-card absolute bottom-full left-0 z-50 mb-2 w-full rounded-2xl border border-slate-200 p-2 text-xs shadow-2xl dark:border-slate-800">
                <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="external-link" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span>View public website</span>
                </a>
                <div class="my-1 border-t border-slate-200 dark:border-slate-800"></div>
                <button type="button" x-ref="logoutTrigger" x-on:click="profileOpen = false; logoutOpen = true; $nextTick(() => $refs.logoutCancel.focus())" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 font-bold text-rose-500 hover:bg-rose-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                    <i data-lucide="log-out" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span>Exit admin mode</span>
                </button>
            </div>
        </div>
    </div>
</aside>
