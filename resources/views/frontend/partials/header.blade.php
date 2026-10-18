<header class="sticky top-0 z-40 bte-glass-nav transition-colors duration-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
    
    <!-- Dynamic Brand Logo / Name -->
    <a href="{{ url('/') }}" class="flex items-center gap-3 group">
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
      <a href="{{ url('/features') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Features</a>
      <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors' }}">About GEO</a>
      <a href="{{ url('/pricing') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Pricing</a>
      <a href="{{ url('/blog') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Blog</a>
    </nav>

    <!-- Right Utility Actions -->
    <div class="flex items-center gap-3.5">
      <button id="themeToggle" class="theme-toggle-btn p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all hover:scale-105">
        <i data-lucide="sun" class="w-5 h-5 theme-icon-sun"></i>
        <i data-lucide="moon" class="w-5 h-5 theme-icon-moon hidden"></i>
      </button>

      <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
        Sign In
      </a>

      <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 text-white text-sm font-bold shadow-lg shadow-indigo-500/25 hover:scale-[1.02] transition-all">
        <span>Start Autopilot</span>
      </a>
    </div>
  </div>
</header>
