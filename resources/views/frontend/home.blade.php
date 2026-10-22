@extends('frontend.app')

@section('content')
<div
    x-data="{
        scannerOpen: false,
        scannerUrl: '',
        scannerError: '',
        articles: 15,
        costPerArticle: 150,
        money(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                maximumFractionDigits: 0,
            }).format(value);
        },
        openScannerNotice() {
            let candidate = this.scannerUrl.trim();

            if (! /^https?:\/\//i.test(candidate)) {
                candidate = `https://${candidate}`;
            }

            try {
                const parsedUrl = new URL(candidate);

                if (! ['http:', 'https:'].includes(parsedUrl.protocol) || ! parsedUrl.hostname.includes('.')) {
                    throw new Error('Invalid website URL');
                }

                this.scannerError = '';
                this.scannerUrl = candidate;
                this.scannerOpen = true;
                this.$nextTick(() => this.$refs.scannerClose.focus());
            } catch (error) {
                this.scannerError = 'Enter a valid public website address, such as example.com.';
            }
        },
    }"
    @keydown.escape.window="scannerOpen = false"
>
    <section class="relative overflow-hidden pb-28 pt-20">
        <div class="pointer-events-none absolute left-1/2 top-1/4 h-[400px] w-[850px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-tr from-indigo-600/20 via-violet-600/20 to-cyan-400/15 blur-[140px]"></div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <div class="animate-float mb-8 inline-flex items-center gap-2.5 rounded-full border border-indigo-500/25 bg-indigo-500/10 px-5 py-2 text-xs font-bold uppercase tracking-wider text-indigo-600 motion-reduce:animate-none dark:text-indigo-400 sm:text-sm">
                <span class="flex h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-400 motion-reduce:animate-none"></span>
                <span>GEO 3.0 Workflows for Modern Search</span>
            </div>

            <h1 class="mx-auto max-w-5xl font-heading text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-6xl lg:text-7xl">
                Put Your SEO & Social Content on <span class="gradient-text-primary">Autopilot</span>
            </h1>

            <p class="mx-auto mt-6 max-w-3xl text-lg font-normal leading-relaxed text-slate-600 dark:text-slate-300 sm:text-xl">
                BlackTechEngine brings brand guidance, topic discovery, content preparation, publishing workflows, and omnichannel distribution into one platform.
            </p>

            <div class="mx-auto mt-12 max-w-2xl">
                <form class="bte-card glow-indigo flex flex-col items-center gap-3 rounded-2xl p-2.5 sm:flex-row sm:p-3" novalidate @submit.prevent="openScannerNotice">
                    <div class="flex w-full flex-1 items-center gap-3 px-3 py-1">
                        <i data-lucide="globe-2" class="h-6 w-6 shrink-0 text-indigo-500"></i>
                        <label for="homepageScannerUrl" class="sr-only">Website address</label>
                        <input
                            id="homepageScannerUrl"
                            x-model="scannerUrl"
                            type="text"
                            required
                            inputmode="url"
                            autocomplete="url"
                            aria-describedby="scannerAvailability scannerError"
                            placeholder="Enter your website URL (e.g. company.com)"
                            class="w-full bg-transparent text-base font-semibold text-slate-900 placeholder-slate-400 focus:outline-none dark:text-white"
                        >
                    </div>
                    <button type="submit" class="flex w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 px-8 py-4 text-sm font-bold text-white shadow-lg shadow-indigo-500/30 transition-all hover:scale-[1.02] active:scale-[0.98] motion-reduce:transform-none sm:w-auto">
                        <i data-lucide="scan-search" class="h-5 w-5"></i>
                        <span>Check My Website</span>
                    </button>
                </form>
                <p id="scannerError" x-cloak x-show="scannerError" x-text="scannerError" class="mt-3 text-sm font-semibold text-rose-500" role="alert"></p>
                <div id="scannerAvailability" class="mt-4 flex flex-wrap items-center justify-center gap-6 text-xs font-medium text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5"><i data-lucide="shield-check" class="h-4 w-4 text-emerald-500"></i> URL validation only</span>
                    <span class="flex items-center gap-1.5"><i data-lucide="database-zap" class="h-4 w-4 text-amber-500"></i> Scanner backend not connected</span>
                </div>
            </div>

            @if($aboutStats->isNotEmpty())
                <div class="mx-auto mt-16 grid max-w-4xl grid-cols-2 gap-8 border-t border-slate-200 pt-12 dark:border-slate-800/80 sm:grid-cols-4">
                    @foreach($aboutStats as $stat)
                        <div>
                            <div @class([
                                'font-heading text-3xl font-extrabold sm:text-4xl',
                                'text-slate-900 dark:text-white' => $loop->index === 0,
                                'text-indigo-600 dark:text-indigo-400' => $loop->index === 1,
                                'text-violet-600 dark:text-violet-400' => $loop->index === 2,
                                'text-cyan-500' => $loop->index >= 3,
                            ])>{{ $stat->value }}</div>
                            <div class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400 sm:text-sm">{{ $stat->label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="overflow-hidden border-y border-slate-200 bg-slate-100/60 py-10 dark:border-slate-800/80 dark:bg-[#0E1526]/70" aria-labelledby="integration-heading">
        <div class="mx-auto mb-3 max-w-7xl px-4 text-center">
            <h2 id="integration-heading" class="text-xs font-bold uppercase tracking-widest text-slate-400">Publishing and distribution workflow destinations</h2>
        </div>
        <div class="relative w-full overflow-hidden">
            <div class="animate-marquee flex items-center gap-16 py-2 motion-reduce:animate-none motion-reduce:justify-center motion-reduce:flex-wrap">
                @foreach(range(1, 2) as $set)
                    @foreach([
                        ['file-text', 'text-indigo-500', 'WordPress REST'],
                        ['shopping-bag', 'text-emerald-500', 'Shopify Blogs'],
                        ['layout', 'text-sky-500', 'Webflow CMS'],
                        ['share-2', 'text-blue-500', 'LinkedIn Formats'],
                        ['messages-square', 'text-cyan-500', 'X Threads'],
                        ['map-pin', 'text-amber-500', 'Google Business Profile'],
                        ['megaphone', 'text-indigo-400', 'Social Publishing'],
                    ] as [$icon, $color, $label])
                        <div class="flex items-center gap-2.5 whitespace-nowrap text-sm font-bold text-slate-700 dark:text-slate-200" @if($set === 2) aria-hidden="true" @endif>
                            <i data-lucide="{{ $icon }}" class="h-5 w-5 {{ $color }}"></i>{{ $label }}
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8" aria-labelledby="homepage-features-heading">
        <div class="mx-auto mb-16 max-w-3xl text-center">
            <span class="mb-2 block text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">{{ $featureSetting?->hero_badge ?: 'Autonomous Capabilities' }}</span>
            <h2 id="homepage-features-heading" class="font-heading text-3xl font-extrabold tracking-tight sm:text-5xl">The 6 Pillars of BlackTechEngine</h2>
            <p class="mt-4 text-base text-slate-600 dark:text-slate-300">A connected workflow for search research, content operations, publishing, and distribution.</p>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($homepageFeatures as $index => $feature)
                <article class="bte-card bte-card-hover space-y-4 rounded-3xl p-8">
                    <div @class([
                        'flex h-12 w-12 items-center justify-center rounded-2xl font-bold',
                        'bg-indigo-500/10 text-indigo-500' => $index % 3 === 0,
                        'bg-violet-500/10 text-violet-500' => $index % 3 === 1,
                        'bg-cyan-500/10 text-cyan-500' => $index % 3 === 2,
                    ])><i data-lucide="{{ $feature['icon'] }}" class="h-6 w-6"></i></div>
                    <h3 class="font-heading text-xl font-bold">{{ $feature['title'] }}</h3>
                    <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $feature['description'] }}</p>
                    <a href="{{ route('features') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-500 hover:underline">Explore Features <span aria-hidden="true">&rarr;</span></a>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-100/50 py-20 dark:border-slate-800 dark:bg-[#0E1526]/50" aria-labelledby="homepage-calculator-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <span class="mb-2 block text-xs font-bold uppercase tracking-widest text-indigo-500">Transparent Cost Estimate</span>
                <h2 id="homepage-calculator-heading" class="font-heading text-3xl font-extrabold sm:text-4xl">{{ $featureSetting?->calc_title ?: 'Estimate External Content Production Cost' }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $featureSetting?->calc_description ?: 'Use your own article volume and external cost assumptions. This calculator does not claim platform savings or performance returns.' }}</p>
            </div>

            <div class="bte-card space-y-8 rounded-3xl border border-slate-200 p-6 shadow-2xl dark:border-slate-800 sm:p-10">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div>
                        <div class="mb-3 flex items-center justify-between gap-4">
                            <label for="homepageArticles" class="text-sm font-bold text-slate-800 dark:text-slate-200">Articles required per month</label>
                            <span class="rounded-xl bg-indigo-600 px-4 py-1.5 font-mono text-sm font-bold text-white shadow-md" x-text="`${articles} / mo`"></span>
                        </div>
                        <input id="homepageArticles" x-model.number="articles" type="range" min="4" max="60" value="15" step="1" class="w-full cursor-pointer accent-indigo-600">
                        <div class="mt-2 flex justify-between font-mono text-xs text-slate-400"><span>4</span><span>20</span><span>40</span><span>60</span></div>
                    </div>
                    <div>
                        <div class="mb-3 flex items-center justify-between gap-4">
                            <label for="homepageArticleCost" class="text-sm font-bold text-slate-800 dark:text-slate-200">Your external cost per article</label>
                            <span class="rounded-xl bg-violet-600 px-4 py-1.5 font-mono text-sm font-bold text-white shadow-md" x-text="money(costPerArticle)"></span>
                        </div>
                        <input id="homepageArticleCost" x-model.number="costPerArticle" type="range" min="50" max="400" value="150" step="25" class="w-full cursor-pointer accent-violet-600">
                        <div class="mt-2 flex justify-between font-mono text-xs text-slate-400"><span>$50</span><span>$150</span><span>$275</span><span>$400</span></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 border-t border-slate-200 pt-6 text-center dark:border-slate-800 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/60"><div class="text-xs font-semibold text-slate-400">Estimated Monthly External Cost</div><div class="mt-1 text-2xl font-extrabold text-slate-700 dark:text-slate-200" x-text="money(articles * costPerArticle)"></div></div>
                    <div class="rounded-2xl border border-indigo-500/30 bg-indigo-500/10 p-5"><div class="text-xs font-bold uppercase text-indigo-500">Estimated Annual External Cost</div><div class="mt-1 text-2xl font-extrabold text-indigo-500" x-text="money(articles * costPerArticle * 12)"></div></div>
                    <div class="rounded-2xl border border-violet-500/30 bg-violet-500/10 p-5"><div class="text-xs font-bold uppercase text-violet-500">Published Platform Pricing</div><div class="mt-1 text-2xl font-extrabold text-violet-500">{{ $startingMonthlyPrice !== null ? 'From $'.number_format((float) $startingMonthlyPrice, 2).'/mo' : 'View current plans' }}</div></div>
                </div>

                <p class="text-center text-xs leading-relaxed text-slate-500 dark:text-slate-400">External cost estimates equal article count × the cost you select. Plan capacity is not stored in the current schema, so no BlackTechEngine savings percentage is calculated.</p>
                <div class="text-center"><a href="{{ route('pricing') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-8 py-4 text-sm font-bold text-white shadow-xl shadow-indigo-500/25 transition-all hover:scale-105 motion-reduce:transform-none">View Current Plans <i data-lucide="arrow-right" class="h-4 w-4"></i></a></div>
            </div>
        </div>
    </section>

    @if($testimonials->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8" aria-labelledby="homepage-testimonials-heading">
            <div class="mx-auto mb-16 max-w-2xl text-center">
                <span class="mb-2 block text-xs font-bold uppercase tracking-widest text-indigo-500">{{ $featureSetting?->testimonial_badge ?: 'Customer Stories' }}</span>
                <h2 id="homepage-testimonials-heading" class="font-heading text-3xl font-extrabold sm:text-4xl">{{ $featureSetting?->testimonial_title ?: 'What Teams Say' }}</h2>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach($testimonials as $testimonial)
                    <figure class="bte-card space-y-4 rounded-3xl border border-slate-200 p-6 dark:border-slate-800">
                        <div class="flex items-center gap-1 text-amber-400" aria-label="Customer testimonial"><i data-lucide="quote" class="h-5 w-5"></i></div>
                        <blockquote class="text-sm italic leading-relaxed text-slate-600 dark:text-slate-300">“{{ $testimonial['quote'] }}”</blockquote>
                        <figcaption class="flex items-center gap-3 border-t border-slate-200 pt-3 dark:border-slate-800">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-500 to-cyan-400 text-xs font-bold text-white">{{ $testimonial['author_initials'] ?: '•' }}</div>
                            <div><div class="text-sm font-bold text-slate-900 dark:text-white">{{ $testimonial['author_name'] }}</div>@if(filled($testimonial['author_role']))<div class="text-xs text-slate-400">{{ $testimonial['author_role'] }}</div>@endif</div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-8 rounded-3xl bg-gradient-to-r from-indigo-600 via-violet-600 to-cyan-500 p-8 text-white shadow-2xl sm:p-14 md:flex-row">
            <div class="max-w-xl"><h2 class="font-heading text-3xl font-extrabold tracking-tight sm:text-4xl">Ready to Build a More Consistent Content Workflow?</h2><p class="mt-3 text-sm leading-relaxed text-indigo-100 sm:text-base">Explore current plans and platform capabilities without unsupported performance promises.</p></div>
            <div class="flex w-full flex-col gap-3.5 sm:flex-row md:w-auto"><a href="{{ route('register') }}" class="rounded-xl bg-white px-8 py-4 text-center text-sm font-bold text-indigo-600 shadow-lg transition-all hover:bg-slate-100">Create Account</a><a href="{{ route('pricing') }}" class="rounded-xl border border-white/30 bg-indigo-900/40 px-8 py-4 text-center text-sm font-semibold text-white transition-all hover:bg-indigo-900/60">View Pricing</a></div>
        </div>
    </section>

    <div x-cloak x-show="scannerOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="scanner-modal-title">
        <button type="button" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" aria-label="Close scanner notice" @click="scannerOpen = false"></button>
        <div class="bte-card relative z-10 w-full max-w-xl rounded-3xl border border-slate-200 p-6 shadow-2xl dark:border-slate-800 sm:p-8">
            <button x-ref="scannerClose" type="button" class="absolute right-5 top-5 rounded-xl p-2 text-slate-400 hover:text-slate-900 dark:hover:text-white" aria-label="Close scanner notice" @click="scannerOpen = false"><i data-lucide="x" class="h-5 w-5"></i></button>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500"><i data-lucide="construction" class="h-6 w-6"></i></div>
            <h2 id="scanner-modal-title" class="mt-5 font-heading text-2xl font-extrabold">Website scanner is not connected yet</h2>
            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">The address <strong x-text="scannerUrl"></strong> was validated in your browser, but no crawl or SEO analysis was performed. Results, scores, keywords, and competitor data will not be simulated.</p>
            <div class="mt-6" aria-label="Scanner status">
                <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"><div class="h-full w-0 rounded-full bg-indigo-500"></div></div>
                <div class="mt-4 grid grid-cols-1 gap-2 text-xs text-slate-500 dark:text-slate-400 sm:grid-cols-3">
                    <span class="rounded-xl border border-slate-200 px-3 py-2 dark:border-slate-700">Crawl: not started</span>
                    <span class="rounded-xl border border-slate-200 px-3 py-2 dark:border-slate-700">Analysis: unavailable</span>
                    <span class="rounded-xl border border-slate-200 px-3 py-2 dark:border-slate-700">Results: unavailable</span>
                </div>
            </div>
            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-900/70">
                <h3 class="text-sm font-bold">Required for a real scan</h3>
                <ul class="mt-3 space-y-2 text-xs text-slate-500 dark:text-slate-400"><li class="flex gap-2"><i data-lucide="server-cog" class="h-4 w-4 shrink-0 text-indigo-500"></i>A secured, rate-limited scanner endpoint and crawl queue</li><li class="flex gap-2"><i data-lucide="shield-check" class="h-4 w-4 shrink-0 text-indigo-500"></i>SSRF protection, DNS/IP validation, timeouts, and crawl limits</li><li class="flex gap-2"><i data-lucide="database" class="h-4 w-4 shrink-0 text-indigo-500"></i>Auditable analysis rules and persisted scan results</li></ul>
            </div>
            <div class="mt-7 flex justify-end"><button type="button" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-500" @click="scannerOpen = false">Understood</button></div>
        </div>
    </div>
</div>
@endsection
