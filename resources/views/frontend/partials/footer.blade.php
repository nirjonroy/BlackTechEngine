<footer class="mt-auto bg-slate-100 dark:bg-[#060910] border-t border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8">
      <div class="sm:col-span-2 space-y-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ $siteSetting?->site_name ?: 'BlackTechEngine' }} home">
          @if($siteSetting?->logo_dark || $siteSetting?->logo_light)
            <img src="{{ asset('storage/'.($siteSetting?->logo_light ?: $siteSetting?->logo_dark)) }}" alt="{{ $siteSetting?->site_name ?: 'BlackTechEngine' }}" class="h-8 block dark:hidden">
            <img src="{{ asset('storage/'.($siteSetting?->logo_dark ?: $siteSetting?->logo_light)) }}" alt="{{ $siteSetting?->site_name ?: 'BlackTechEngine' }}" class="h-8 hidden dark:block">
          @else
            <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white"><i data-lucide="sparkles" class="w-5 h-5"></i></div>
            <span class="font-heading text-xl font-black text-slate-900 dark:text-white">{{ $siteSetting?->site_name ?: 'BlackTechEngine' }}</span>
          @endif
        </a>

        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm leading-relaxed">{{ $frontendNavigation['footerDescription'] }}</p>

        <div class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
          @if(filled($siteSetting?->address))<div class="flex items-start gap-2"><i data-lucide="map-pin" class="w-4 h-4 mt-1 shrink-0"></i><span>{{ $siteSetting->address }}</span></div>@endif
          @if(filled($siteSetting?->email))<div class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4 shrink-0"></i><a href="mailto:{{ $siteSetting->email }}" class="hover:text-indigo-500">{{ $siteSetting->email }}</a></div>@endif
          @if(filled($siteSetting?->mobile))<div class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4 shrink-0"></i><a href="tel:{{ $siteSetting->mobile }}" class="hover:text-indigo-500">{{ $siteSetting->mobile }}</a></div>@endif
        </div>

        @if($frontendNavigation['socialLinks'] !== [])
          <div class="flex flex-wrap items-center gap-3 pt-2">
            @foreach($frontendNavigation['socialLinks'] as $socialLink)
              <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $socialLink['label'] }}" class="p-2 rounded-lg bg-slate-200 dark:bg-slate-800 hover:text-indigo-500 transition-colors"><i data-lucide="{{ $socialLink['icon'] }}" class="w-4 h-4"></i></a>
            @endforeach
          </div>
        @endif
      </div>

      @foreach($frontendNavigation['footerColumns'] as $column)
        <div class="space-y-3 text-xs">
          <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider font-heading">{{ $column['title'] }}</h4>
          <ul class="space-y-2.5 font-medium">
            @foreach($column['links'] as $link)
              <li><a href="{{ $link['url'] }}" class="hover:text-indigo-500 transition-colors{{ $link['route'] === 'dashboard' ? ' text-indigo-500 font-bold' : '' }}">{{ $link['label'] }}</a></li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>

    <div class="mt-14 pt-6 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
      <div>&copy; {{ date('Y') }} {{ $siteSetting?->site_name ?: 'BlackTechEngine' }}. {{ $frontendNavigation['footerCopyright'] }}</div>
    </div>
  </div>
</footer>
