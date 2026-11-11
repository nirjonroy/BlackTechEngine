<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">{{ $workspace->name }} &middot; {{ $brand->name }}</p>
            <h1 class="font-heading text-xl font-black sm:text-2xl">Brand DNA Profile</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        @if(session('status'))
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                <p class="font-black">The action could not be completed.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('workspaces.brands.show', [$workspace, $brand]) }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-indigo-600 dark:text-slate-300">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Brand Overview
            </a>
            @if($approvedProfile)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Approved DNA Active
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span> Pending Approval
                </span>
            @endif
        </div>

        <!-- Website Analysis Trigger Card -->
        <section class="bte-card rounded-3xl p-5 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-500">Automated Website Extraction</span>
                    <h2 class="mt-1 font-heading text-2xl font-black">{{ $latestProfile ? 'Re-analyze Website Content' : 'Extract Brand DNA from Website' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Automatically scans <strong>{{ $brand->website_url }}</strong> (homepage, about, and services pages) with SSRF validation, DNS pinning, and crawl bounding to formulate a reusable Brand DNA profile.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('workspaces.brands.brand-dna.analyze', [$workspace, $brand]) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="custom_urls" class="block text-xs font-bold uppercase tracking-wider text-slate-500">
                        Target Page URLs <span class="font-normal text-slate-400">(optional - defaults to homepage, about, and services)</span>
                    </label>
                    <textarea id="custom_urls" name="custom_urls" rows="2" placeholder="https://example.com/about&#10;https://example.com/solutions" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs dark:border-slate-700">{{ old('custom_urls') }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-400">Must belong to the same verified brand host. Non-standard ports, private IPs, and redirects are strictly blocked.</p>
                </div>

                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700">
                    <i data-lucide="sparkles" class="h-4 w-4"></i> {{ $latestProfile ? 'Re-analyze Website' : 'Analyze Website & Generate DNA' }}
                </button>
            </form>
        </section>

        @if($latestProfile)
            <!-- DNA Review, Edit & Approval Section -->
            <section class="bte-card rounded-3xl p-5 sm:p-8 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
                    <div>
                        <h2 class="font-heading text-2xl font-black">Review &amp; Edit Brand DNA Profile</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Analyzed on {{ $latestProfile->last_analyzed_at?->format('M j, Y g:i A') ?? 'recently' }}.
                            Sources: {{ implode(', ', $latestProfile->source_urls ?? []) }}
                        </p>
                    </div>
                    @if($latestProfile->is_approved)
                        <div class="text-right">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Approved</span>
                            <p class="mt-1 text-[11px] text-slate-400">{{ $latestProfile->approved_at?->format('M j, Y') }}</p>
                        </div>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">Awaiting Customer Approval</span>
                    @endif
                </div>

                <!-- Edit Profile Form -->
                <form method="POST" action="{{ route('workspaces.brands.brand-dna.update', [$workspace, $brand, $latestProfile]) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="brand_identity" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Brand Identity &amp; Positioning</label>
                            <textarea id="brand_identity" name="brand_identity" rows="3" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">{{ old('brand_identity', $latestProfile->brand_identity) }}</textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label for="business_description" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Business Description</label>
                            <textarea id="business_description" name="business_description" rows="3" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">{{ old('business_description', $latestProfile->business_description) }}</textarea>
                        </div>

                        <div>
                            <label for="industry" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Industry</label>
                            <input type="text" id="industry" name="industry" value="{{ old('industry', $latestProfile->industry) }}" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                        </div>

                        <div>
                            <label for="primary_market" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Primary Market</label>
                            <input type="text" id="primary_market" name="primary_market" value="{{ old('primary_market', $latestProfile->primary_market) }}" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                        </div>

                        <div>
                            <label for="target_audience" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Target Audience</label>
                            <textarea id="target_audience" name="target_audience" rows="2" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">{{ old('target_audience', $latestProfile->target_audience) }}</textarea>
                        </div>

                        <div>
                            <label for="voice_tone" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Voice &amp; Tone</label>
                            <textarea id="voice_tone" name="voice_tone" rows="2" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">{{ old('voice_tone', $latestProfile->voice_tone) }}</textarea>
                        </div>

                        <div>
                            <label for="key_services" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Key Products &amp; Services <span class="font-normal text-slate-400">(one per line)</span></label>
                            <textarea id="key_services" name="key_services" rows="4" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs dark:border-slate-700">{{ old('key_services', is_array($latestProfile->key_services) ? implode("\n", $latestProfile->key_services) : '') }}</textarea>
                        </div>

                        <div>
                            <label for="suggested_keywords" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Discovered Seed Keywords <span class="font-normal text-slate-400">(one per line)</span></label>
                            <textarea id="suggested_keywords" name="suggested_keywords" rows="4" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs dark:border-slate-700">{{ old('suggested_keywords', is_array($latestProfile->suggested_keywords) ? implode("\n", $latestProfile->suggested_keywords) : '') }}</textarea>
                        </div>

                        <div>
                            <label for="content_themes" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Content Themes &amp; Categories <span class="font-normal text-slate-400">(one per line)</span></label>
                            <textarea id="content_themes" name="content_themes" rows="3" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs dark:border-slate-700">{{ old('content_themes', is_array($latestProfile->content_themes) ? implode("\n", $latestProfile->content_themes) : '') }}</textarea>
                        </div>

                        <div>
                            <label for="brand_values" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Brand Values <span class="font-normal text-slate-400">(one per line)</span></label>
                            <textarea id="brand_values" name="brand_values" rows="3" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs dark:border-slate-700">{{ old('brand_values', is_array($latestProfile->brand_values) ? implode("\n", $latestProfile->brand_values) : '') }}</textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label for="competitor_differentiation" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Unique Differentiator / Value Proposition</label>
                            <textarea id="competitor_differentiation" name="competitor_differentiation" rows="2" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">{{ old('competitor_differentiation', $latestProfile->competitor_differentiation) }}</textarea>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600">
                            <i data-lucide="save" class="h-4 w-4"></i> Save Edits
                        </button>
                    </div>
                </form>

                <!-- Approval Section -->
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-900/40">
                    <h3 class="font-heading text-base font-bold">Approve Brand DNA Profile</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Approving activates this Brand DNA to guide all future article writing prompts, keyword discovery, and featured image designs.
                    </p>

                    <form method="POST" action="{{ route('workspaces.brands.brand-dna.approve', [$workspace, $brand, $latestProfile]) }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="flex items-start gap-2.5">
                            <input type="checkbox" id="sync_to_brand" name="sync_to_brand" value="1" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500">
                            <label for="sync_to_brand" class="text-xs text-slate-700 dark:text-slate-300">
                                <strong>Apply approved values to Brand core settings</strong> (replaces business description, industry, target audience, voice, and topic categories with the approved DNA). Leave unchecked to preserve existing manually entered values.
                            </label>
                        </div>

                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-black text-white hover:bg-emerald-700 shadow-sm">
                            <i data-lucide="check-circle" class="h-4 w-4"></i> Approve &amp; Activate Brand DNA
                        </button>
                    </form>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>

