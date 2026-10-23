@extends('frontend.app')

@section('content')
    <section class="relative overflow-hidden pb-20 pt-20 text-center">
        <div class="pointer-events-none absolute left-1/2 top-1/4 h-[380px] w-[780px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-tr from-violet-600/20 via-indigo-600/20 to-cyan-400/10 blur-[140px]"></div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-violet-500/20 bg-violet-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-violet-600 dark:text-violet-400">
                <i data-lucide="gift" class="h-3.5 w-3.5" aria-hidden="true"></i>
                <span>{{ $setting?->hero_badge ?: 'Affiliate program preview' }}</span>
            </div>
            <h1 class="mx-auto max-w-4xl font-heading text-4xl font-black leading-tight tracking-tight sm:text-6xl">
                {{ $setting?->hero_title ?: 'Partner With BlackTechEngine' }}
                <span class="gradient-text-primary">{{ $setting?->hero_highlighted_title ?: 'When Applications Open' }}</span>
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg font-normal leading-relaxed text-slate-600 dark:text-slate-300 sm:text-xl">
                {{ $setting?->hero_description ?: 'We are exploring a partner program for creators, agencies, and educators. Enrollment, commission terms, tracking, and payouts are not available yet.' }}
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ $primaryCtaUrl }}" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-indigo-500/30 transition-all hover:scale-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 motion-reduce:transform-none sm:w-auto dark:focus-visible:ring-offset-[#090D16]">
                    {{ $setting?->primary_cta_label ?: 'Review Program Status' }}
                </a>
                <a href="{{ $secondaryCtaUrl }}" class="w-full rounded-xl border border-slate-300 bg-white px-8 py-4 text-base font-bold transition-all hover:border-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:w-auto dark:border-slate-700 dark:bg-slate-800">
                    {{ $setting?->secondary_cta_label ?: 'Create a Standard Account' }}
                </a>
            </div>
            @if(filled($setting?->hero_disclosure))
                <p class="mx-auto mt-4 max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $setting->hero_disclosure }}</p>
            @endif
            <p class="mx-auto mt-2 max-w-2xl text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">Standard account registration is not affiliate enrollment. Affiliate applications are not currently available.</p>
        </div>
    </section>

    <section id="program-status" class="scroll-mt-24 border-t border-slate-200 bg-slate-100/40 py-20 dark:border-slate-800/80 dark:bg-[#0E1526]/40" aria-labelledby="program-status-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <h2 id="program-status-heading" class="font-heading text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $setting?->status_heading ?: 'Program Terms Are Pending Approval' }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $setting?->status_description ?: 'The original concept included recurring commissions and an earnings calculator. No rate, cookie window, payout schedule, or payment provider has been approved.' }}</p>
            </div>

            <div class="bte-card rounded-3xl border border-slate-200 p-6 shadow-xl dark:border-slate-800 sm:p-10">
                <div class="grid grid-cols-1 items-stretch gap-8 md:grid-cols-2">
                    <div class="space-y-4">
                        <h3 class="font-heading text-xl font-bold">{{ $setting?->terms_heading ?: 'Terms requiring business approval' }}</h3>
                        <dl class="space-y-3 text-sm">
                            @foreach([
                                ['Commission structure', 'Not configured'],
                                ['Attribution window', 'Not configured'],
                                ['Payout timing', 'Not configured'],
                                ['Payment methods', 'Not configured'],
                            ] as [$term, $status])
                                <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                                    <dt class="font-bold text-slate-700 dark:text-slate-200">{{ $term }}</dt>
                                    <dd class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ $status }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="flex flex-col justify-center rounded-2xl border border-violet-500/30 bg-gradient-to-br from-violet-950 via-indigo-950 to-slate-900 p-6 text-center text-white">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-500/20 text-violet-300"><i data-lucide="badge-alert" class="h-6 w-6" aria-hidden="true"></i></div>
                        <div class="mt-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Current availability</div>
                        <div class="mt-1 font-heading text-3xl font-black text-cyan-400">{{ $availabilityLabel }}</div>
                        <p class="mt-3 text-xs leading-5 text-slate-300">{{ $setting?->availability_description ?: 'There is no application form, referral link generator, earnings dashboard, or payout workflow in the current platform.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200 py-20 dark:border-slate-800/80" aria-labelledby="affiliate-process-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-16 max-w-3xl text-center">
                <h2 id="affiliate-process-heading" class="font-heading text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $setting?->workflow_heading ?: 'How the Planned Partner Program Will Work' }}</h2>
                <p class="mt-2 text-base text-slate-600 dark:text-slate-300">{{ $setting?->workflow_description ?: 'This proposed sequence is informational and may change before launch.' }}</p>
            </div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                @foreach($workflowSteps as $index => $step)
                    <article class="bte-card rounded-3xl border border-slate-200 p-8 text-center dark:border-slate-800">
                        <div @class([
                            'mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl',
                            'bg-indigo-500/10 text-indigo-500' => $index === 0,
                            'bg-violet-500/10 text-violet-500' => $index === 1,
                            'bg-cyan-500/10 text-cyan-500' => $index === 2,
                        ])>
                            <i data-lucide="{{ $step['icon'] }}" class="h-7 w-7" aria-hidden="true"></i>
                        </div>
                        <div @class([
                            'mb-2 text-xs font-bold uppercase tracking-wider',
                            'text-indigo-500' => $index === 0,
                            'text-violet-500' => $index === 1,
                            'text-cyan-500' => $index === 2,
                        ])>Step {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                        <h3 class="mb-2 font-heading text-xl font-bold">{{ $step['title'] }}</h3>
                        <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ $step['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200 bg-slate-100/50 py-16 dark:border-slate-800/80 dark:bg-[#0E1526]/50" aria-labelledby="partner-resources-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="bte-card flex flex-col items-start justify-between gap-8 rounded-3xl border border-slate-200 p-8 dark:border-slate-800 sm:p-10 md:flex-row md:items-center">
                <div>
                    <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-indigo-500">{{ $setting?->resources_badge ?: 'Future resources' }}</span>
                    <h2 id="partner-resources-heading" class="mt-2 font-heading text-2xl font-bold">{{ $setting?->resources_heading ?: 'Partner Resources Are Planned' }}</h2>
                    <p class="mt-1 max-w-lg text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $setting?->resources_description ?: 'Approved brand assets, disclosure guidance, and campaign materials may be provided after the program and its usage terms are finalized.' }}</p>
                </div>
                <div class="flex shrink-0 flex-col items-stretch gap-3 sm:items-end">
                    <span class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-slate-100 px-6 py-3 text-xs font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400" role="status">
                        <i data-lucide="clock" class="h-4 w-4" aria-hidden="true"></i>
                        {{ $resourcesAvailabilityLabel }}
                    </span>
                    <a href="{{ $finalCtaUrl }}" aria-label="Affiliate informational link" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3 text-center text-xs font-bold text-white shadow-lg shadow-indigo-500/20 transition-transform hover:scale-105 motion-reduce:transform-none">{{ $setting?->final_cta_label ?: 'Explore Platform Features' }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection
