@extends('frontend.app')

@section('content')
  <!-- HERO SECTION -->
  <section class="relative pt-20 pb-28 overflow-hidden">
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[850px] h-[400px] bg-gradient-to-tr from-indigo-600/20 via-violet-600/20 to-cyan-400/15 blur-[140px] rounded-full pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
      
      <div class="inline-flex items-center gap-2.5 px-5 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/25 text-indigo-600 dark:text-indigo-400 text-xs sm:text-sm font-bold uppercase tracking-wider mb-8 animate-float">
        <span class="flex h-2.5 w-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
        <span>GEO 3.0 Live: Dominate ChatGPT, Gemini & Perplexity Search</span>
      </div>
      
      <h1 class="font-heading text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight max-w-5xl mx-auto leading-[1.1]">
        Put Your SEO & Social Content on <span class="gradient-text-primary">100% Autopilot</span>
      </h1>
      
      <p class="mt-6 text-lg sm:text-xl text-slate-600 dark:text-slate-300 max-w-3xl mx-auto font-normal leading-relaxed">
        BlackTechEngine autonomously crawls your website, extracts your proprietary Brand DNA, detects high-converting semantic search gaps, and synthesizes 2,500+ word deep-dive guides with omnichannel social drip syndication.
      </p>

      <!-- INTERACTIVE LIVE WEBSITE SCANNER BOX -->
      <div class="mt-12 max-w-2xl mx-auto">
        <form id="websiteScannerForm" class="p-2.5 sm:p-3 rounded-2xl bte-card glow-indigo flex flex-col sm:flex-row items-center gap-3">
          <div class="flex-1 flex items-center gap-3 w-full px-3 py-1">
            <i data-lucide="globe" class="w-6 h-6 text-indigo-500 shrink-0"></i>
            <input id="scannerUrlInput" type="text" required value="https://thehorizonsunlimited.com" placeholder="Enter your website URL (e.g. company.com)" class="w-full bg-transparent text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none text-base font-semibold">
          </div>
          <button type="submit" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 text-white font-bold text-sm hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shrink-0 shadow-lg shadow-indigo-500/30">
            <i data-lucide="sparkles" class="w-5 h-5"></i>
            <span>Generate My Plan</span>
          </button>
        </form>
        <div class="mt-4 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-500 dark:text-slate-400 font-medium">
          <span class="flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> 14-Day Risk-Free Trial</span>
          <span class="flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> No Credit Card Required</span>
          <span class="flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4 text-emerald-500"></i> Instant 60s Deep Scan</span>
        </div>
      </div>

      <!-- Quick Proof Counters (Dynamic from about_stats) -->
      <div class="mt-16 pt-12 border-t border-slate-200 dark:border-slate-800/80 grid grid-cols-2 sm:grid-cols-4 gap-8 max-w-4xl mx-auto">
        @if(isset($aboutStats) && $aboutStats->count() > 0)
            @foreach($aboutStats as $stat)
            <div>
              <div class="font-heading text-3xl sm:text-4xl font-extrabold @if($loop->index == 0) text-slate-900 dark:text-white @elseif($loop->index == 1) text-indigo-600 dark:text-indigo-400 @elseif($loop->index == 2) text-violet-600 dark:text-violet-400 @else text-cyan-500 @endif">
                  {{ $stat->value ?? '0' }}
              </div>
              <div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-semibold mt-1">{{ $stat->label ?? 'Metric' }}</div>
            </div>
            @endforeach
        @else
            <!-- Fallback Static Stats if DB is empty -->
            <div><div class="font-heading text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">4.8M+</div><div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-semibold mt-1">Articles Published</div></div>
            <div><div class="font-heading text-3xl sm:text-4xl font-extrabold text-indigo-600 dark:text-indigo-400">98.4%</div><div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-semibold mt-1">Avg SEO Health Score</div></div>
            <div><div class="font-heading text-3xl sm:text-4xl font-extrabold text-violet-600 dark:text-violet-400">12.5x</div><div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-semibold mt-1">AI Citation Growth</div></div>
            <div><div class="font-heading text-3xl sm:text-4xl font-extrabold text-cyan-500">35+ hrs</div><div class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-semibold mt-1">Saved Per Week</div></div>
        @endif
      </div>

    </div>
  </section>

  <!-- COMPREHENSIVE FEATURES PREVIEW (Dynamic from FeaturePageSetting) -->
  <section class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-3xl mx-auto mb-16">
      <span class="text-xs uppercase tracking-widest text-indigo-600 dark:text-indigo-400 font-bold mb-2 block">{{ $featureSetting->hero_badge ?? 'Autonomous Capabilities' }}</span>
      <h2 class="font-heading text-3xl sm:text-5xl font-extrabold tracking-tight">The 6 Pillars of BlackTechEngine</h2>
      <p class="mt-4 text-base text-slate-600 dark:text-slate-300">Engineered specifically to outrank traditional search engines and win conversational citations on LLMs.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      @if(isset($featureSetting) && $featureSetting->features_json)
          @foreach(array_slice($featureSetting->features_json, 0, 6) as $index => $feature)
          <div class="bte-card bte-card-hover rounded-3xl p-8 space-y-4">
            <div class="w-12 h-12 rounded-2xl @if($index % 3 == 0) bg-indigo-500/10 text-indigo-500 @elseif($index % 3 == 1) bg-violet-500/10 text-violet-500 @else bg-cyan-500/10 text-cyan-500 @endif flex items-center justify-center font-bold">
              <i data-lucide="{{ $feature['icon'] ?? 'star' }}" class="w-6 h-6"></i>
            </div>
            <h3 class="font-heading text-xl font-bold">{{ $feature['title'] ?? '' }}</h3>
            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ $feature['description'] ?? '' }}</p>
            <a href="{{ url('/features') }}" class="inline-flex items-center gap-1.5 text-xs font-bold @if($index % 3 == 0) text-indigo-500 @elseif($index % 3 == 1) text-violet-500 @else text-cyan-500 @endif hover:underline">
              Explore Specs &rarr;
            </a>
          </div>
          @endforeach
      @else
          <p class="text-center text-slate-500 col-span-3">No features added in admin panel yet.</p>
      @endif
    </div>
  </section>

  <!-- CUSTOMER REVIEWS & WALL OF LOVE (Dynamic from FeaturePageSetting) -->
  <section class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-2xl mx-auto mb-16">
      <span class="text-xs uppercase tracking-widest text-indigo-500 font-bold mb-2 block">{{ $featureSetting->testimonial_badge ?? 'Social Proof' }}</span>
      <h3 class="font-heading text-3xl sm:text-4xl font-extrabold">{{ $featureSetting->testimonial_title ?? 'Loved by 4,200+ Growth Teams' }}</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      @if(isset($featureSetting) && $featureSetting->testimonials_json)
          @foreach(array_slice($featureSetting->testimonials_json, 0, 3) as $testimonial)
          <div class="bte-card rounded-3xl p-6 border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center gap-1 text-amber-400">
              <i data-lucide="star" class="w-4 h-4 fill-amber-400"></i><i data-lucide="star" class="w-4 h-4 fill-amber-400"></i><i data-lucide="star" class="w-4 h-4 fill-amber-400"></i><i data-lucide="star" class="w-4 h-4 fill-amber-400"></i><i data-lucide="star" class="w-4 h-4 fill-amber-400"></i>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed italic">
              "{{ $testimonial['quote'] ?? '' }}"
            </p>
            <div class="flex items-center gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
              <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-cyan-400 flex items-center justify-center font-bold text-white text-xs">
                {{ $testimonial['author_initials'] ?? 'User' }}
              </div>
              <div>
                <div class="font-bold text-sm text-slate-900 dark:text-white">{{ $testimonial['author_name'] ?? '' }}</div>
                <div class="text-xs text-slate-400">{{ $testimonial['author_role'] ?? '' }}</div>
              </div>
            </div>
          </div>
          @endforeach
      @endif
    </div>
  </section>

  <!-- CTA BANNER -->
  <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="rounded-3xl bg-gradient-to-r from-indigo-600 via-violet-600 to-cyan-500 p-8 sm:p-14 text-white shadow-2xl flex flex-col md:flex-row items-center justify-between gap-8">
      <div class="max-w-xl">
        <h3 class="font-heading text-3xl sm:text-4xl font-extrabold tracking-tight">Ready to Dominate Search on Autopilot?</h3>
        <p class="mt-3 text-indigo-100 text-sm sm:text-base leading-relaxed">Join thousands of high-growth companies conquering Google and generative AI engines today. 14-day free trial.</p>
      </div>
      <div class="flex flex-col sm:flex-row gap-3.5 w-full md:w-auto">
        <a href="{{ route('register') }}" class="px-8 py-4 rounded-xl bg-white text-indigo-600 font-bold text-sm shadow-lg hover:bg-slate-100 transition-all text-center">Get Started Free</a>
        <a href="{{ route('login') }}" class="px-8 py-4 rounded-xl bg-indigo-900/40 border border-white/30 text-white font-semibold text-sm hover:bg-indigo-900/60 transition-all text-center">1-Click Live Demo</a>
      </div>
    </div>
  </section>
@endsection