@extends('frontend.app')

@php
    $statColors = [
        'text-indigo-600 dark:text-indigo-400',
        'text-violet-600 dark:text-violet-400',
        'text-cyan-600 dark:text-cyan-400',
        'text-emerald-600 dark:text-emerald-400',
    ];
    $stepColors = ['bg-indigo-600', 'bg-violet-600', 'bg-cyan-600', 'bg-emerald-600'];
    $valueColors = [
        'bg-indigo-500/10 text-indigo-500',
        'bg-violet-500/10 text-violet-500',
        'bg-cyan-500/10 text-cyan-500',
    ];
@endphp

@section('content')
    <section class="relative pt-20 pb-20 overflow-hidden">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[750px] h-[380px] bg-gradient-to-tr from-indigo-600/15 via-violet-600/15 to-cyan-400/10 blur-[140px] rounded-full pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            @if(filled($about?->hero_badge))
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 text-xs font-bold uppercase tracking-wider mb-6">
                    <i data-lucide="compass" class="w-3.5 h-3.5"></i>
                    <span>{{ $about->hero_badge }}</span>
                </div>
            @endif
            <h1 class="font-heading text-4xl sm:text-6xl font-black tracking-tight max-w-4xl mx-auto leading-tight">
                {{ $about?->hero_title ?: 'About BlackTechEngine' }}
            </h1>
            @if(filled($about?->hero_description))
                <p class="mt-6 text-lg sm:text-xl text-slate-600 dark:text-slate-300 max-w-3xl mx-auto font-normal leading-relaxed">
                    {{ $about->hero_description }}
                </p>
            @endif
        </div>
    </section>

    @if($about?->stats->isNotEmpty())
        <section class="py-12 border-y border-slate-200 dark:border-slate-800/80 bg-slate-100/40 dark:bg-[#0E1526]/40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    @foreach($about->stats as $stat)
                        <div>
                            <div class="font-heading text-4xl sm:text-5xl font-black {{ $statColors[$loop->index % count($statColors)] }}">{{ $stat->value }}</div>
                            <div class="text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wider">{{ $stat->label }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(filled($about?->comparison_title) || filled($about?->comparison_description) || $about?->comparisons->isNotEmpty())
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if(filled($about?->comparison_title) || filled($about?->comparison_description))
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        @if(filled($about?->comparison_title))
                            <h2 class="font-heading text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $about->comparison_title }}</h2>
                        @endif
                        @if(filled($about?->comparison_description))
                            <p class="text-slate-600 dark:text-slate-300 mt-3 text-base">{{ $about->comparison_description }}</p>
                        @endif
                    </div>
                @endif

                @if($about?->comparisons->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse bte-card rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/60 text-xs uppercase tracking-wider font-bold">
                                    <th class="py-5 px-6 text-slate-500">Capability / Metric</th>
                                    <th class="py-5 px-6 text-rose-500">Traditional SEO Agency</th>
                                    <th class="py-5 px-6 text-indigo-500 dark:text-indigo-400 bg-indigo-500/5 dark:bg-indigo-950/20 font-black">BlackTechEngine GEO 3.0</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                                @foreach($about->comparisons as $comparison)
                                    <tr>
                                        <td class="py-4 px-6 font-semibold text-slate-800 dark:text-slate-200">{{ $comparison->capability }}</td>
                                        <td class="py-4 px-6 text-slate-500">{{ $comparison->traditional_text }}</td>
                                        <td class="py-4 px-6 font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-500/5 dark:bg-indigo-950/20">{{ $comparison->bte_text }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if(filled($about?->architecture_badge) || filled($about?->architecture_title) || filled($about?->architecture_description) || $about?->architectures->isNotEmpty())
        <section class="py-20 border-t border-slate-200 dark:border-slate-800/80 bg-slate-100/50 dark:bg-[#0E1526]/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if(filled($about?->architecture_badge) || filled($about?->architecture_title) || filled($about?->architecture_description))
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        @if(filled($about?->architecture_badge))
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xs font-bold uppercase tracking-wider mb-4">
                                <i data-lucide="cpu" class="w-4 h-4"></i>
                                {{ $about->architecture_badge }}
                            </div>
                        @endif
                        @if(filled($about?->architecture_title))
                            <h2 class="font-heading text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $about->architecture_title }}</h2>
                        @endif
                        @if(filled($about?->architecture_description))
                            <p class="text-slate-600 dark:text-slate-300 mt-3 text-base">{{ $about->architecture_description }}</p>
                        @endif
                    </div>
                @endif

                @if($about?->architectures->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        @foreach($about->architectures as $architecture)
                            <div class="bte-card p-6 rounded-3xl border border-slate-200 dark:border-slate-800">
                                <div class="w-10 h-10 rounded-xl {{ $stepColors[$loop->index % count($stepColors)] }} text-white font-bold flex items-center justify-center mb-4">{{ $architecture->step_number }}</div>
                                <h3 class="font-heading font-bold text-lg mb-2">{{ $architecture->title }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $architecture->description }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if(filled($about?->core_values_title) || filled($about?->core_values_description) || $about?->coreValues->isNotEmpty())
        <section class="py-20 border-t border-slate-200 dark:border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if(filled($about?->core_values_title) || filled($about?->core_values_description))
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        @if(filled($about?->core_values_title))
                            <h2 class="font-heading text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $about->core_values_title }}</h2>
                        @endif
                        @if(filled($about?->core_values_description))
                            <p class="text-slate-600 dark:text-slate-300 mt-3 text-base">{{ $about->core_values_description }}</p>
                        @endif
                    </div>
                @endif

                @if($about?->coreValues->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        @foreach($about->coreValues as $coreValue)
                            <div class="p-6 rounded-3xl bte-card border border-slate-200 dark:border-slate-800">
                                <div class="w-12 h-12 rounded-2xl {{ $valueColors[$loop->index % count($valueColors)] }} flex items-center justify-center mb-4">
                                    <i data-lucide="{{ $coreValue->icon ?: 'circle' }}" class="w-6 h-6"></i>
                                </div>
                                <h3 class="font-heading text-xl font-bold mb-2">{{ $coreValue->title }}</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ $coreValue->description }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if(filled($about?->cta_title) || filled($about?->cta_description) || (filled($about?->cta_btn1_text) && $primaryCtaUrl) || (filled($about?->cta_btn2_text) && $secondaryCtaUrl))
        <section class="py-20 relative overflow-hidden">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="p-8 sm:p-14 rounded-3xl bg-gradient-to-tr from-indigo-900/60 via-slate-900 to-violet-950/60 border border-indigo-500/30 shadow-2xl relative overflow-hidden">
                    @if(filled($about?->cta_title))
                        <h2 class="font-heading text-3xl sm:text-5xl font-black text-white">{{ $about->cta_title }}</h2>
                    @endif
                    @if(filled($about?->cta_description))
                        <p class="mt-4 text-slate-300 text-base sm:text-lg max-w-2xl mx-auto">{{ $about->cta_description }}</p>
                    @endif
                    @if((filled($about?->cta_btn1_text) && $primaryCtaUrl) || (filled($about?->cta_btn2_text) && $secondaryCtaUrl))
                        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                            @if(filled($about?->cta_btn1_text) && $primaryCtaUrl)
                                <a href="{{ $primaryCtaUrl }}" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold text-base shadow-xl shadow-indigo-500/30 hover:scale-105 transition-all">{{ $about->cta_btn1_text }}</a>
                            @endif
                            @if(filled($about?->cta_btn2_text) && $secondaryCtaUrl)
                                <a href="{{ $secondaryCtaUrl }}" class="w-full sm:w-auto px-8 py-4 rounded-xl border border-slate-700 bg-slate-800/80 text-white font-bold text-base hover:bg-slate-700/80 transition-all">{{ $about->cta_btn2_text }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif
@endsection
