<div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" x-on:click="sidebarOpen = false" aria-hidden="true"></div>

<aside class="bte-surface fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 transition-transform duration-200 lg:translate-x-0 dark:border-slate-800" x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" aria-label="Customer navigation">
    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5 dark:border-slate-800">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/25">
                <i data-lucide="sparkles" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <span>
                <span class="block font-heading text-base font-black leading-none">BlackTechEngine</span>
                <span class="mt-1 block text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-500">Customer Studio</span>
            </span>
        </a>
        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 lg:hidden dark:hover:bg-slate-800" x-on:click="sidebarOpen = false" aria-label="Close customer navigation">
            <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
        </button>
    </div>

    @if (config('customer.workspaces_enabled'))
        <div class="border-b border-slate-200 p-4 dark:border-slate-800">
            <p class="mb-2 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Active workspace</p>
            @if ($customerWorkspaces->isNotEmpty())
                <div x-data="{ open: false }" class="relative">
                    <button type="button" class="bte-card flex w-full items-center gap-3 rounded-xl p-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" x-on:click="open = ! open" x-bind:aria-expanded="open">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300"><i data-lucide="panels-top-left" class="h-4 w-4" aria-hidden="true"></i></span>
                        <span class="min-w-0 flex-1 truncate text-sm font-bold">{{ $customerActiveWorkspace?->name ?? 'Choose workspace' }}</span>
                        <i data-lucide="chevrons-up-down" class="h-4 w-4 text-slate-400" aria-hidden="true"></i>
                    </button>
                    <div x-cloak x-show="open" x-on:click.outside="open = false" class="bte-card absolute left-0 right-0 top-full z-20 mt-2 max-h-60 overflow-y-auto rounded-xl p-2 shadow-xl">
                        @foreach ($customerWorkspaces as $navigationWorkspace)
                            <form method="POST" action="{{ route('workspaces.active.update', $navigationWorkspace) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-indigo-950/50">
                                    <span class="truncate">{{ $navigationWorkspace->name }}</span>
                                    @if ($customerActiveWorkspace?->is($navigationWorkspace))
                                        <i data-lucide="check" class="h-4 w-4 text-indigo-500" aria-hidden="true"></i>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ route('onboarding.start') }}" class="flex items-center gap-2 rounded-xl border border-dashed border-indigo-300 px-3 py-3 text-sm font-bold text-indigo-600 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-950/50">
                    <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i> Create first workspace
                </a>
            @endif
        </div>
    @endif

    <nav class="flex-1 space-y-1 overflow-y-auto p-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
            <i data-lucide="layout-dashboard" class="h-5 w-5" aria-hidden="true"></i> Dashboard
        </a>
        @if (config('customer.workspaces_enabled'))
            <a href="{{ route('workspaces.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('workspaces.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                <i data-lucide="blocks" class="h-5 w-5" aria-hidden="true"></i> Workspaces &amp; brands
            </a>
        @endif
        @if (config('ai.content_studio_enabled'))
            <a href="{{ route('content-studio.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('content-studio.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                <i data-lucide="file-sparkles" class="h-5 w-5" aria-hidden="true"></i> AI Content Studio
            </a>
        @endif
        <div class="pt-4">
            <p class="px-3 text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Account</p>
        </div>
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('profile.*') ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
            <i data-lucide="user-cog" class="h-5 w-5" aria-hidden="true"></i> Account settings
        </a>
    </nav>

    <div class="border-t border-slate-200 p-4 dark:border-slate-800">
        <div class="mb-3 flex items-center gap-3 px-2">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400 to-indigo-600 text-sm font-black text-white">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
            <span class="min-w-0"><span class="block truncate text-sm font-bold">{{ auth()->user()->name }}</span><span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</span></span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:text-slate-300 dark:hover:bg-rose-950/40 dark:hover:text-rose-300">
                <i data-lucide="log-out" class="h-5 w-5" aria-hidden="true"></i> Log out
            </button>
        </form>
    </div>
</aside>
