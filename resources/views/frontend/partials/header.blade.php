<header class="sticky top-0 z-40 bte-glass-nav transition-colors duration-200">
  <div class="mx-auto flex h-20 max-w-7xl min-w-0 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
    <a href="{{ route('home') }}" class="group flex min-w-0 items-center gap-3" aria-label="{{ $siteSetting?->site_name ?: 'BlackTechEngine' }} home">
      @if($siteSetting?->logo_dark || $siteSetting?->logo_light)
        <img src="{{ asset('storage/'.($siteSetting?->logo_light ?: $siteSetting?->logo_dark)) }}" alt="{{ $siteSetting?->site_name ?: 'BlackTechEngine' }}" class="h-10 block dark:hidden">
        <img src="{{ asset('storage/'.($siteSetting?->logo_dark ?: $siteSetting?->logo_light)) }}" alt="{{ $siteSetting?->site_name ?: 'BlackTechEngine' }}" class="h-10 hidden dark:block">
      @else
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-violet-600 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform">
          <i data-lucide="sparkles" class="w-6 h-6"></i>
        </div>
        <span class="truncate font-heading text-xl font-black tracking-tight text-slate-900 sm:text-2xl dark:text-white">{{ $siteSetting?->site_name ?: 'BlackTechEngine' }}</span>
      @endif
    </a>

    <nav aria-label="Primary navigation" data-navigation-location="desktop" class="hidden lg:flex items-center gap-8 text-sm font-semibold text-slate-600 dark:text-slate-300">
      @foreach($frontendNavigation['headerLinks'] as $link)
        <a href="{{ $link['url'] }}" @if($link['is_active']) aria-current="page" @endif class="{{ $link['is_active'] ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors' }}{{ $link['route'] === 'affiliate' ? ' flex items-center gap-1.5' : '' }}">
          <span>{{ $link['label'] }}</span>
          @if($link['route'] === 'affiliate')
            <span class="rounded-full border border-violet-500/30 bg-violet-500/20 px-2 py-0.5 text-[10px] font-bold text-violet-600 dark:text-violet-400">Planned</span>
          @endif
        </a>
      @endforeach
    </nav>

    <div class="flex shrink-0 items-center gap-2 min-[420px]:gap-3.5">
      <button id="themeToggle" type="button" aria-label="Toggle color theme" class="theme-toggle-btn p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all hover:scale-105">
        <i data-lucide="sun" class="w-5 h-5 theme-icon-sun"></i>
        <i data-lucide="moon" class="w-5 h-5 theme-icon-moon hidden"></i>
      </button>

      @guest
        <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Sign In</a>
        <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 text-white text-sm font-bold shadow-lg shadow-indigo-500/25 hover:scale-[1.02] transition-all"><span>Start Autopilot</span></a>
      @else
        <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center px-4 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dashboard</a>
        <form action="{{ route('logout') }}" method="POST" class="hidden sm:block">@csrf<button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-500 transition-colors">Log Out</button></form>
      @endguest

      <button id="mobileMenuBtn" type="button" aria-label="Open navigation menu" aria-controls="mobileMenu" aria-expanded="false" class="lg:hidden p-2.5 rounded-xl text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800">
        <i data-lucide="menu" class="w-6 h-6"></i>
      </button>
    </div>
  </div>

  <nav id="mobileMenu" aria-label="Mobile navigation" data-navigation-location="mobile" class="hidden lg:hidden px-6 pt-3 pb-6 space-y-3 bg-white dark:bg-[#0E1526] border-b border-slate-200 dark:border-slate-800">
    @foreach($frontendNavigation['headerLinks'] as $link)
      <a href="{{ $link['url'] }}" @if($link['is_active']) aria-current="page" @endif class="block py-2 text-sm {{ $link['is_active'] ? 'font-bold text-indigo-600 dark:text-indigo-400' : 'font-semibold text-slate-700 dark:text-slate-300' }}">
        {{ $link['label'] }}
        @if($link['route'] === 'affiliate')<span class="text-[10px] uppercase tracking-wider text-violet-500">Planned</span>@endif
      </a>
    @endforeach
    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex gap-3">
      @guest
        <a href="{{ route('login') }}" class="w-1/2 text-center py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-sm font-bold">Sign In</a>
        <a href="{{ route('register') }}" class="w-1/2 text-center py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold">Start Free</a>
      @else
        <a href="{{ route('dashboard') }}" class="w-1/2 text-center py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-sm font-bold">Dashboard</a>
        <form action="{{ route('logout') }}" method="POST" class="w-1/2">@csrf<button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold">Log Out</button></form>
      @endguest
    </div>
  </nav>
</header>
