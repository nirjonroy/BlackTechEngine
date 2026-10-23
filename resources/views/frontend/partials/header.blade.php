<header class="sticky top-0 z-40 bte-glass-nav transition-colors duration-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
    
    <!-- Dynamic Brand Logo / Name -->
      <a href="{{ route('home') }}" class="flex items-center gap-3 group" aria-label="{{ $siteSetting?->site_name ?: 'BlackTechEngine' }} home">
      @if(isset($siteSetting) && ($siteSetting->logo_dark || $siteSetting->logo_light))
          <!-- Light Mode Logo -->
          <img src="{{ asset('storage/' . $siteSetting->logo_light) }}" alt="{{ $siteSetting->site_name ?? 'Logo' }}" class="h-10 block dark:hidden">
          <!-- Dark Mode Logo -->
          <img src="{{ asset('storage/' . $siteSetting->logo_dark) }}" alt="{{ $siteSetting->site_name ?? 'Logo' }}" class="h-10 hidden dark:block">
      @else
          <!-- Fallback Text Logo -->
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-violet-600 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform">
            <i data-lucide="sparkles" class="w-6 h-6"></i>
          </div>
          <div>
            <span class="font-heading text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ $siteSetting->site_name ?? 'BlackTechEngine' }}</span>
          </div>
      @endif
    </a>

    <!-- Desktop Links -->
    <nav class="hidden lg:flex items-center gap-8 text-sm font-semibold text-slate-600 dark:text-slate-300">
      <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors' }}">Home</a>
      @if(Route::has('features'))
        <a href="{{ route('features') }}" class="{{ request()->routeIs('features') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors' }}">Features</a>
      @endif
      <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors' }}">About GEO</a>
      @if(Route::has('pricing'))
        <a href="{{ route('pricing') }}" class="{{ request()->routeIs('pricing') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors' }}">Pricing</a>
      @endif
      @if(Route::has('blog.index'))
        <a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog.*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors' }}">Blog</a>
      @endif
      @if(Route::has('affiliate'))
        <a href="{{ route('affiliate') }}" class="{{ request()->routeIs('affiliate') ? 'flex items-center gap-1.5 font-bold text-indigo-600 dark:text-indigo-400' : 'flex items-center gap-1.5 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400' }}">
          <span>Affiliates</span>
          <span class="rounded-full border border-violet-500/30 bg-violet-500/20 px-2 py-0.5 text-[10px] font-bold text-violet-600 dark:text-violet-400">Planned</span>
        </a>
      @endif
    </nav>

    <!-- Right Utility Actions -->
    <div class="flex items-center gap-3.5">
      <button id="themeToggle" type="button" aria-label="Toggle color theme" class="theme-toggle-btn p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all hover:scale-105">
        <i data-lucide="sun" class="w-5 h-5 theme-icon-sun"></i>
        <i data-lucide="moon" class="w-5 h-5 theme-icon-moon hidden"></i>
      </button>

      <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
        Sign In
      </a>

      <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 text-white text-sm font-bold shadow-lg shadow-indigo-500/25 hover:scale-[1.02] transition-all">
        <span>Start Autopilot</span>
      </a>

      <button id="mobileMenuBtn" type="button" aria-label="Open navigation menu" aria-controls="mobileMenu" aria-expanded="false" class="lg:hidden p-2.5 rounded-xl text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800">
        <i data-lucide="menu" class="w-6 h-6"></i>
      </button>
    </div>
  </div>

  <nav id="mobileMenu" aria-label="Mobile navigation" class="hidden lg:hidden px-6 pt-3 pb-6 space-y-3 bg-white dark:bg-[#0E1526] border-b border-slate-200 dark:border-slate-800">
    <a href="{{ route('home') }}" class="block py-2 text-sm {{ request()->routeIs('home') ? 'font-bold text-indigo-600 dark:text-indigo-400' : 'font-semibold text-slate-700 dark:text-slate-300' }}">Home</a>
    @if(Route::has('features'))
      <a href="{{ route('features') }}" class="block py-2 text-sm {{ request()->routeIs('features') ? 'font-bold text-indigo-600 dark:text-indigo-400' : 'font-semibold text-slate-700 dark:text-slate-300' }}">Features</a>
    @endif
    <a href="{{ route('about') }}" class="block py-2 text-sm {{ request()->routeIs('about') ? 'font-bold text-indigo-600 dark:text-indigo-400' : 'font-semibold text-slate-700 dark:text-slate-300' }}">About GEO</a>
    @if(Route::has('pricing'))
      <a href="{{ route('pricing') }}" class="block py-2 text-sm {{ request()->routeIs('pricing') ? 'font-bold text-indigo-600 dark:text-indigo-400' : 'font-semibold text-slate-700 dark:text-slate-300' }}">Pricing</a>
    @endif
    @if(Route::has('blog.index'))
      <a href="{{ route('blog.index') }}" class="block py-2 text-sm {{ request()->routeIs('blog.*') ? 'font-bold text-indigo-600 dark:text-indigo-400' : 'font-semibold text-slate-700 dark:text-slate-300' }}">Blog</a>
    @endif
    @if(Route::has('affiliate'))
      <a href="{{ route('affiliate') }}" class="block py-2 text-sm {{ request()->routeIs('affiliate') ? 'font-bold text-indigo-600 dark:text-indigo-400' : 'font-semibold text-slate-700 dark:text-slate-300' }}">Affiliate Program <span class="text-[10px] uppercase tracking-wider text-violet-500">Planned</span></a>
    @endif
    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex gap-3">
      <a href="{{ route('login') }}" class="w-1/2 text-center py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-sm font-bold">Sign In</a>
      <a href="{{ route('register') }}" class="w-1/2 text-center py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold">Start Free</a>
    </div>
  </nav>
</header>
