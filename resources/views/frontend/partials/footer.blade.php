<footer class="mt-auto bg-slate-100 dark:bg-[#060910] border-t border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
      
      <!-- Brand Column -->
      <div class="col-span-2 space-y-4">
        <div class="flex items-center gap-3">
          @if(isset($siteSetting) && ($siteSetting->logo_dark || $siteSetting->logo_light))
              <img src="{{ asset('storage/' . $siteSetting->logo_light) }}" alt="{{ $siteSetting->site_name ?? 'Logo' }}" class="h-8 block dark:hidden">
              <img src="{{ asset('storage/' . $siteSetting->logo_dark) }}" alt="{{ $siteSetting->site_name ?? 'Logo' }}" class="h-8 hidden dark:block">
          @else
              <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
              </div>
              <span class="font-heading text-xl font-black text-slate-900 dark:text-white">{{ $siteSetting->site_name ?? 'BlackTechEngine' }}</span>
          @endif
        </div>
        
        <!-- Dynamic Contact Info from Site Settings -->
        <div class="space-y-2 mt-4 text-sm text-slate-500 dark:text-slate-400">
            @if(isset($siteSetting->address))
                <div class="flex items-start gap-2"><i data-lucide="map-pin" class="w-4 h-4 mt-1 shrink-0"></i> <span>{{ $siteSetting->address }}</span></div>
            @endif
            @if(isset($siteSetting->email))
                <div class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4 shrink-0"></i> <a href="mailto:{{ $siteSetting->email }}" class="hover:text-indigo-500">{{ $siteSetting->email }}</a></div>
            @endif
            @if(isset($siteSetting->mobile))
                <div class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4 shrink-0"></i> <a href="tel:{{ $siteSetting->mobile }}" class="hover:text-indigo-500">{{ $siteSetting->mobile }}</a></div>
            @endif
        </div>

        @if(filled($siteSetting?->facebook_url))
          <div class="flex items-center gap-3 pt-2">
            <a href="{{ $siteSetting->facebook_url }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="p-2 rounded-lg bg-slate-200 dark:bg-slate-800 hover:text-indigo-500 transition-colors"><i data-lucide="facebook" class="w-4 h-4"></i></a>
          </div>
        @endif
      </div>

      <!-- Product Links -->
      <div class="space-y-3 text-xs">
        <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider font-heading">Product</h4>
        <ul class="space-y-2.5 font-medium">
          @if(Route::has('features'))
            <li><a href="{{ route('features') }}" class="hover:text-indigo-500 transition-colors">All Features</a></li>
          @endif
          @if(Route::has('pricing'))
            <li><a href="{{ route('pricing') }}" class="hover:text-indigo-500 transition-colors">Pricing & Plans</a></li>
          @endif
        </ul>
      </div>

      <!-- Resources -->
      <div class="space-y-3 text-xs">
        <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider font-heading">Resources</h4>
        <ul class="space-y-2.5 font-medium">
          <li><a href="{{ route('about') }}" class="hover:text-indigo-500 transition-colors">About GEO Architecture</a></li>
          @if(Route::has('blog.index'))
            <li><a href="{{ route('blog.index') }}" class="hover:text-indigo-500 transition-colors">Research Blog</a></li>
          @endif
        </ul>
      </div>

      <!-- Direct Portals -->
      <div class="space-y-3 text-xs">
        <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider font-heading">Portals</h4>
        <ul class="space-y-2.5 font-medium">
          <li><a href="{{ route('dashboard') }}" class="text-indigo-500 font-bold hover:underline">User Dashboard Panel</a></li>
          <li><a href="{{ route('register') }}" class="hover:text-indigo-500 transition-colors">Start Free Trial</a></li>
          <li><a href="{{ route('login') }}" class="hover:text-indigo-500 transition-colors">Client Sign In</a></li>
        </ul>
      </div>
    </div>

    <div class="mt-14 pt-6 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
      <div>
        &copy; {{ date('Y') }} {{ $siteSetting->site_name ?? 'BlackTechEngine' }}. All rights reserved.
      </div>
    </div>
  </div>
</footer>
