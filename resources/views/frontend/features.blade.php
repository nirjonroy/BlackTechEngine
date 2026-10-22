@extends('frontend.app')

@section('content')
    <section class="relative overflow-hidden pb-20 pt-20">
        <div class="pointer-events-none absolute left-1/2 top-1/3 h-[350px] w-[700px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-tr from-indigo-600/15 via-violet-600/15 to-cyan-400/10 blur-[130px]"></div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                <i data-lucide="layers" class="h-3.5 w-3.5"></i>
                <span>{{ $setting?->hero_badge ?: 'Full-Stack Autopilot Capabilities' }}</span>
            </div>
            <h1 class="mx-auto max-w-4xl font-heading text-4xl font-black leading-tight tracking-tight sm:text-6xl">
                {{ $setting?->hero_title_normal ?: 'Engineered for the Entire Content Lifecycle' }}
                <span class="gradient-text-primary">{{ $setting?->hero_title_highlighted ?: 'The 6 Pillars' }}</span>
            </h1>
            <p class="mx-auto mt-6 max-w-3xl text-lg font-normal leading-relaxed text-slate-600 dark:text-slate-300 sm:text-xl">
                {{ $setting?->hero_description ?: 'Discover how autonomous workflows support the content lifecycle, from brand voice guidance and answer-engine structure to publishing, social distribution, local visibility, and opportunity discovery.' }}
            </p>
        </div>
    </section>

    @foreach($features->take(4) as $index => $feature)
        <section @class([
            'border-t border-slate-200 py-20 dark:border-slate-800/80',
            'bg-slate-100/50 dark:bg-[#0E1526]/50' => $index % 2 === 1,
        ])>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div @class(['lg:order-2' => $index % 2 === 1])>
                        <div class="mb-4 inline-flex items-center gap-2 rounded-lg bg-indigo-500/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="{{ $feature['icon'] }}" class="h-4 w-4"></i>
                            Feature {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                        </div>
                        <h2 class="font-heading text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ $feature['title'] }}</h2>
                        <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">{{ $feature['description'] }}</p>

                        @switch($index)
                            @case(0)
                                <div class="mt-8 space-y-4">
                                    @foreach([
                                        ['sliders', 'Tone & Personality Calibration', 'Tune formality, enthusiasm, analytical depth, and humor against approved brand guidance.'],
                                        ['shield-check', 'Banned Words Filter', 'Exclude phrases and expressions that do not belong in your organization’s voice.'],
                                        ['link-2', 'Internal Linking Knowledge Graph', 'Connect relevant topics to approved pages and resources with contextual anchor suggestions.'],
                                    ] as [$icon, $title, $description])
                                        <div class="flex items-start gap-3.5">
                                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></div>
                                            <div><h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $title }}</h3><p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $description }}</p></div>
                                        </div>
                                    @endforeach
                                </div>
                                @break
                            @case(1)
                                <ul class="mt-6 space-y-3.5 text-sm text-slate-700 dark:text-slate-300">
                                    @foreach([
                                        ['Information-Gain Structure', 'Organize original insights and supporting context for answer-engine retrieval.'],
                                        ['Syntactic Direct Answers', 'Shape concise question-and-answer passages for people and retrieval systems.'],
                                        ['Schema & Entity Graphs', 'Connect entities and page relationships with structured semantic context.'],
                                    ] as [$title, $description])
                                        <li class="flex items-start gap-3"><i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500"></i><span><strong>{{ $title }}:</strong> {{ $description }}</span></li>
                                    @endforeach
                                </ul>
                                @break
                            @case(2)
                                <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="bte-surface rounded-2xl border border-slate-200 p-4 dark:border-slate-800"><div class="font-heading text-xl font-black text-indigo-500">Rolling Queue</div><div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Organize research, drafting, review, and publication in one visible workflow.</div></div>
                                    <div class="bte-surface rounded-2xl border border-slate-200 p-4 dark:border-slate-800"><div class="font-heading text-xl font-black text-emerald-500">Approval Controls</div><div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Keep editorial decisions with your team before content reaches a connected CMS.</div></div>
                                </div>
                                @break
                            @default
                                <div class="mt-6 flex flex-wrap gap-2">
                                    @foreach(['Format Repurposing', 'Hashtag Clustering', 'Schedule Planning', 'Drip Delays'] as $capability)
                                        <span class="rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-bold dark:bg-slate-800">{{ $capability }}</span>
                                    @endforeach
                                </div>
                        @endswitch
                    </div>

                    <div @class(['lg:order-1' => $index % 2 === 1])>
                        @switch($index)
                            @case(0)
                                <div class="bte-card glow-indigo rounded-3xl border border-slate-200 p-6 dark:border-slate-800 sm:p-8">
                                    <div class="mb-6 flex items-center justify-between gap-4 border-b border-slate-200 pb-4 dark:border-slate-800">
                                        <div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white"><i data-lucide="activity" class="h-5 w-5"></i></div><div><div class="text-sm font-bold">Brand Voice Profile</div><div class="text-[11px] text-slate-500">Illustrative interface preview</div></div></div>
                                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/15 px-2.5 py-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">Ready</span>
                                    </div>
                                    <div class="space-y-5 text-xs">
                                        <div><div class="mb-1 flex justify-between font-bold"><span class="text-slate-600 dark:text-slate-400">Tone calibration</span><span class="text-indigo-500">Configured</span></div><div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"><div class="h-full w-4/5 rounded-full bg-indigo-500"></div></div></div>
                                        <div><div class="mb-1 flex justify-between font-bold"><span class="text-slate-600 dark:text-slate-400">Technical depth</span><span class="text-violet-500">Configured</span></div><div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"><div class="h-full w-2/3 rounded-full bg-violet-500"></div></div></div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-slate-600 dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-300">A preview area can show how approved brand guidance affects generated drafts.</div>
                                    </div>
                                </div>
                                @break
                            @case(1)
                                <div class="bte-card rounded-3xl border border-slate-200 p-6 dark:border-slate-800 sm:p-8">
                                    <div class="mb-4 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-500"><i data-lucide="cpu" class="h-4 w-4"></i> Illustrative answer-engine preview</div>
                                    <div class="space-y-4">
                                        @foreach(['Direct answer structure', 'Citation-ready source context'] as $preview)
                                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/90"><div class="mb-2 flex items-center gap-2"><span class="h-3 w-3 rounded-full {{ $loop->first ? 'bg-emerald-500' : 'bg-cyan-400' }}"></span><span class="text-xs font-bold">{{ $preview }}</span></div><p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300">Structured content can provide concise answers, clear entities, and attributable supporting context.</p></div>
                                        @endforeach
                                    </div>
                                </div>
                                @break
                            @case(2)
                                <div class="bte-card rounded-3xl border border-slate-200 p-6 dark:border-slate-800">
                                    <div class="mb-4 flex items-center justify-between gap-4 border-b border-slate-200 pb-4 dark:border-slate-800"><div class="text-sm font-bold">Illustrative Publishing Queue</div><span class="rounded-lg bg-indigo-500/10 px-2.5 py-1 text-[11px] font-bold text-indigo-500">Workflow Preview</span></div>
                                    <div class="space-y-3 text-xs">
                                        @foreach([['Topic research approved', 'Ready'], ['Draft awaiting editorial review', 'Review'], ['CMS publication scheduled', 'Scheduled']] as [$item, $status])
                                            <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700/80 dark:bg-slate-800/60"><div class="flex items-center gap-3"><span class="h-2.5 w-2.5 rounded-full bg-indigo-400"></span><span class="font-bold">{{ $item }}</span></div><span class="rounded bg-indigo-500/10 px-2 py-0.5 text-[10px] font-bold text-indigo-500">{{ $status }}</span></div>
                                        @endforeach
                                    </div>
                                </div>
                                @break
                            @default
                                <div class="bte-card rounded-3xl border border-slate-200 p-6 dark:border-slate-800 sm:p-8">
                                    <div class="mb-4 flex items-center justify-between"><span class="text-xs font-bold uppercase tracking-wider text-indigo-500">One approved article, multiple formats</span><span class="rounded bg-indigo-500/10 px-2 py-0.5 text-[10px] font-bold text-indigo-500">Illustrative</span></div>
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        @foreach([['share-2', 'LinkedIn'], ['messages-square', 'X Thread'], ['megaphone', 'Facebook'], ['image', 'Instagram']] as [$icon, $channel])
                                            <div class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900"><div class="mb-2 flex items-center gap-2 font-bold text-indigo-500"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i>{{ $channel }}</div><p class="text-[11px] text-slate-500 dark:text-slate-400">Channel-specific copy prepared for review and scheduling.</p></div>
                                        @endforeach
                                    </div>
                                </div>
                        @endswitch
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    <section class="border-t border-slate-200 py-20 dark:border-slate-800/80" aria-labelledby="additional-features-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-16 max-w-3xl text-center"><h2 id="additional-features-heading" class="font-heading text-3xl font-extrabold tracking-tight sm:text-4xl">Local Visibility & Continuous Opportunity Discovery</h2><p class="mt-3 text-base text-slate-600 dark:text-slate-300">Additional pillars for location-focused organizations, agencies, and growth teams.</p></div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                @foreach($features->slice(4)->values() as $index => $feature)
                    <article class="bte-card flex flex-col justify-between rounded-3xl border border-slate-200 p-8 dark:border-slate-800">
                        <div>
                            <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500"><i data-lucide="{{ $feature['icon'] }}" class="h-6 w-6"></i></div>
                            <div class="mb-1 text-xs font-bold uppercase tracking-wider text-indigo-500">Feature {{ str_pad((string) ($index + 5), 2, '0', STR_PAD_LEFT) }}</div>
                            <h3 class="mb-3 font-heading text-2xl font-bold">{{ $feature['title'] }}</h3>
                            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $feature['description'] }}</p>
                            @if($index < 2)
                                <ul class="mt-6 space-y-2 text-xs text-slate-600 dark:text-slate-300">
                                    @foreach($index === 0
                                        ? ['Google Business publishing workflows', 'Review response drafting with tone guidance', 'Local citation consistency checks']
                                        : ['Competitor topic monitoring', 'Search-intent opportunity clustering', 'Seasonal topic planning'] as $capability)
                                        <li class="flex items-center gap-2"><i data-lucide="check" class="h-4 w-4 shrink-0 text-emerald-400"></i>{{ $capability }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="mt-8 flex items-center gap-2 border-t border-slate-200 pt-4 text-xs font-bold text-indigo-500 dark:border-slate-800"><span>Platform capability</span><i data-lucide="arrow-right" class="h-4 w-4"></i></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if(filled($setting?->calc_title) || filled($setting?->calc_description))
        <section class="border-t border-slate-200 bg-slate-100/50 py-20 dark:border-slate-800/80 dark:bg-[#0E1526]/50">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <div class="bte-card rounded-3xl border border-slate-200 p-8 dark:border-slate-800 sm:p-12">
                    <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-500"><i data-lucide="calculator" class="h-6 w-6"></i></div>
                    @if(filled($setting?->calc_title))<h2 class="font-heading text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $setting->calc_title }}</h2>@endif
                    @if(filled($setting?->calc_description))<p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $setting->calc_description }}</p>@endif
                    <p class="mt-5 text-xs text-slate-500 dark:text-slate-400">Interactive calculations are not displayed because no validated calculator inputs or formulas exist in the current settings schema.</p>
                </div>
            </div>
        </section>
    @endif

    @if($testimonials->isNotEmpty())
        <section class="border-t border-slate-200 py-20 dark:border-slate-800/80" aria-labelledby="feature-testimonials-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-14 max-w-3xl text-center">
                    @if(filled($setting?->testimonial_badge))<div class="mb-4 inline-flex rounded-full border border-violet-500/20 bg-violet-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-violet-500">{{ $setting->testimonial_badge }}</div>@endif
                    <h2 id="feature-testimonials-heading" class="font-heading text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $setting?->testimonial_title ?: 'What Teams Say' }}</h2>
                </div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($testimonials as $testimonial)
                        <figure class="bte-card rounded-3xl border border-slate-200 p-7 dark:border-slate-800"><blockquote class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">“{{ $testimonial['quote'] }}”</blockquote><figcaption class="mt-6 flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-600 to-violet-600 text-xs font-black text-white">{{ $testimonial['author_initials'] ?: '•' }}</div><div><div class="text-sm font-bold">{{ $testimonial['author_name'] }}</div>@if(filled($testimonial['author_role']))<div class="text-xs text-slate-500">{{ $testimonial['author_role'] }}</div>@endif</div></figcaption></figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="relative overflow-hidden py-20">
        <div class="relative z-10 mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-indigo-500/30 bg-gradient-to-tr from-indigo-900/60 via-slate-900 to-violet-950/60 p-8 shadow-2xl sm:p-14">
                <h2 class="font-heading text-3xl font-black text-white sm:text-5xl">Experience Autonomous Content Growth</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base text-slate-300 sm:text-lg">Review the available plans or create an account to explore the platform’s current capabilities.</p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row"><a href="{{ route('register') }}" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-indigo-500/30 transition-all hover:scale-105 sm:w-auto">Create Account</a><a href="{{ route('pricing') }}" class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-8 py-4 text-base font-bold text-white transition-all hover:bg-slate-700/80 sm:w-auto">View All Pricing Plans</a></div>
            </div>
        </div>
    </section>
@endsection
