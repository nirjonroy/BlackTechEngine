<header class="bte-glass-nav sticky top-0 z-30 flex min-h-16 items-center justify-between gap-3 border-b border-slate-200 px-4 dark:border-slate-800/80 sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <button id="sidebarMobileBtn" type="button" class="rounded-xl border border-slate-200 p-2 text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-800 dark:text-slate-300 lg:hidden" aria-label="Open admin navigation" aria-controls="adminSidebar" aria-expanded="false">
            <i data-lucide="menu" class="h-5 w-5" aria-hidden="true"></i>
        </button>
        <nav class="hidden min-w-0 items-center gap-2 text-xs text-slate-400 sm:flex" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}" class="shrink-0 hover:text-cyan-500">Admin</a>
            @foreach($breadcrumbs as $breadcrumb)
                <i data-lucide="chevron-right" class="h-3 w-3 shrink-0" aria-hidden="true"></i>
                @if(isset($breadcrumb['url']))
                    <a href="{{ $breadcrumb['url'] }}" class="truncate font-bold text-cyan-500 hover:underline">{{ $breadcrumb['label'] }}</a>
                @else
                    <span class="truncate font-bold text-cyan-500" aria-current="page">{{ $breadcrumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    </div>

    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
        <span class="hidden items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 md:inline-flex">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            Database connected
        </span>
        <button id="themeToggle" type="button" class="theme-toggle-btn rounded-xl border border-slate-200 bg-white/80 p-2 text-slate-700 transition-all hover:scale-105 hover:text-cyan-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-800 dark:bg-slate-800/80 dark:text-slate-200" aria-label="Toggle color theme">
            <i data-lucide="sun" class="theme-icon-sun h-4 w-4" aria-hidden="true"></i>
            <i data-lucide="moon" class="theme-icon-moon hidden h-4 w-4" aria-hidden="true"></i>
        </button>
        <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-bold text-slate-200 transition-all hover:border-cyan-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:bg-slate-800">
            <i data-lucide="external-link" class="h-3.5 w-3.5" aria-hidden="true"></i>
            <span class="hidden sm:inline">View site</span>
        </a>
    </div>
</header>
