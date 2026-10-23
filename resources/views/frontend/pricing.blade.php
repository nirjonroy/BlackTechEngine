@extends('frontend.app')

@section('content')
<div x-data="{
    billing: 'monthly',
    articles: {{ Js::from($setting?->calc_default_articles ?? 16) }},
    costPerArticle: {{ Js::from((float) ($setting?->calc_default_cost_per_article ?? 150)) }},
    safeAmount(value) {
        const amount = Number(value);
        return Number.isFinite(amount) && amount >= 0 ? amount : 0;
    },
    money(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: {{ Js::from($currencyCode) }},
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(this.safeAmount(value));
    },
}">
    <section class="relative overflow-hidden pb-16 pt-20 text-center">
        <div class="pointer-events-none absolute left-1/2 top-1/4 h-[380px] w-[750px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-tr from-indigo-600/15 via-violet-600/15 to-cyan-400/10 blur-[140px]"></div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                <i data-lucide="{{ $heroBadgeIcon }}" class="h-3.5 w-3.5"></i>
                <span>{{ $setting?->hero_badge_text ?: 'Simple, Transparent Pricing' }}</span>
            </div>
            <h1 class="mx-auto max-w-4xl font-heading text-4xl font-black leading-tight tracking-tight sm:text-6xl">
                {{ $setting?->hero_title_normal ?: 'Scale Your Organic Traffic' }}
                @if(filled($setting?->hero_title_highlighted))
                    <span class="gradient-text-primary">{{ $setting->hero_title_highlighted }}</span>
                @endif
            </h1>
            @if(filled($setting?->hero_description))
                <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600 dark:text-slate-300">{{ $setting->hero_description }}</p>
            @endif

            <div class="mt-10 inline-flex items-center rounded-2xl border border-slate-300 bg-slate-200/80 p-1.5 dark:border-slate-700 dark:bg-slate-800/80" role="group" aria-label="Billing period">
                <button
                    type="button"
                    class="rounded-xl px-5 py-2.5 text-xs font-bold transition-all sm:text-sm"
                    :class="billing === 'monthly' ? 'bg-white text-slate-900 shadow-sm dark:bg-indigo-600 dark:text-white' : 'text-slate-600 dark:text-slate-400'"
                    :aria-pressed="billing === 'monthly'"
                    @click="billing = 'monthly'"
                >{{ $setting?->billing_monthly_label ?: 'Monthly Billing' }}</button>
                <button
                    type="button"
                    class="rounded-xl px-5 py-2.5 text-xs font-bold transition-all sm:text-sm"
                    :class="billing === 'annual' ? 'bg-white text-slate-900 shadow-sm dark:bg-indigo-600 dark:text-white' : 'text-slate-600 dark:text-slate-400'"
                    :aria-pressed="billing === 'annual'"
                    @click="billing = 'annual'"
                >{{ $setting?->billing_annual_label ?: 'Annual Billing' }}</button>
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ $setting?->annual_billing_description ?: 'Annual prices are displayed as the total billed per year.' }}</p>
        </div>
    </section>

    <section class="py-12" aria-labelledby="pricing-plans-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="pricing-plans-heading" class="sr-only">{{ $setting?->plans_heading ?: 'Pricing plans' }}</h2>
            @if($plans->isEmpty())
                <div class="bte-card rounded-3xl border border-slate-200 p-10 text-center dark:border-slate-800">
                    <h3 class="font-heading text-2xl font-bold">{{ $setting?->empty_state_title ?: 'Plans are being prepared' }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $setting?->empty_state_description ?: 'Please check back soon for current pricing options.' }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 items-stretch gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($plans as $plan)
                        <article @class([
                            'bte-card relative flex flex-col justify-between rounded-3xl p-8',
                            'border-2 border-indigo-500 shadow-2xl shadow-indigo-500/20 lg:-translate-y-2' => $plan->is_popular,
                            'border border-slate-200 dark:border-slate-800' => ! $plan->is_popular,
                        ])>
                            @if($plan->is_popular)
                                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-1 text-[11px] font-black uppercase tracking-wider text-white shadow-md">{{ $plan->popular_label ?: 'Most Popular' }}</div>
                            @endif

                            <div>
                                <div class="mb-4 flex items-center justify-between gap-4 {{ $plan->is_popular ? 'mt-2' : '' }}">
                                    <span class="text-xs font-bold uppercase tracking-wider {{ $plan->is_popular ? 'text-indigo-500' : 'text-slate-500 dark:text-slate-400' }}">{{ $plan->name }}</span>
                                    @if(filled($plan->badge))
                                        <span class="rounded-full bg-indigo-500/15 px-2.5 py-1 text-[10px] font-bold text-indigo-600 dark:text-indigo-400">{{ $plan->badge }}</span>
                                    @endif
                                </div>
                                <h3 class="font-heading text-2xl font-bold">{{ $plan->title ?: $plan->name }}</h3>
                                @if(filled($plan->description))
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $plan->description }}</p>
                                @endif

                                <div class="mb-8 mt-6 flex items-baseline gap-1" aria-live="polite">
                                    <span x-show="billing === 'monthly'" class="font-heading text-4xl font-black sm:text-5xl {{ $plan->is_popular ? 'text-indigo-600 dark:text-indigo-400' : '' }}">{{ $formattedPlanPrices[$plan->getKey()]['monthly'] }}</span>
                                    <span x-show="billing === 'monthly'" class="text-sm text-slate-500 dark:text-slate-400">{{ $setting?->monthly_period_label ?: '/ month' }}</span>
                                    <span x-cloak x-show="billing === 'annual'" class="font-heading text-4xl font-black sm:text-5xl {{ $plan->is_popular ? 'text-indigo-600 dark:text-indigo-400' : '' }}">{{ $formattedPlanPrices[$plan->getKey()]['annual'] }}</span>
                                    <span x-cloak x-show="billing === 'annual'" class="text-sm text-slate-500 dark:text-slate-400">{{ $setting?->annual_period_label ?: '/ year' }}</span>
                                </div>

                                @if($plan->features->isNotEmpty())
                                    <ul class="space-y-3.5 text-xs text-slate-700 dark:text-slate-300">
                                        @foreach($plan->features as $feature)
                                            <li class="flex items-center gap-3 {{ $feature->is_included ? '' : 'text-slate-400 dark:text-slate-600' }}">
                                                <i data-lucide="{{ $feature->is_included ? ($plan->is_popular ? 'check-circle-2' : 'check') : 'minus' }}" class="h-4 w-4 shrink-0 {{ $feature->is_included ? 'text-emerald-500' : '' }}"></i>
                                                <span>{{ $feature->name }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            @if(filled($plan->button_text) && filled($planCtaUrls[$plan->getKey()] ?? null))
                                <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
                                    <a href="{{ $planCtaUrls[$plan->getKey()] }}" class="block w-full rounded-xl py-3.5 text-center text-xs font-bold transition-all {{ $plan->is_popular ? 'bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/30 hover:scale-[1.02]' : 'border border-slate-300 hover:border-indigo-500 hover:text-indigo-500 dark:border-slate-700' }}">{{ $plan->button_text }}</a>
                                </div>
                            @elseif(filled($plan->button_text))
                                <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800"><span class="block w-full cursor-not-allowed rounded-xl border border-slate-300 px-4 py-3.5 text-center text-xs font-bold text-slate-500 dark:border-slate-700 dark:text-slate-400" aria-disabled="true">{{ $setting?->unavailable_plan_label ?: 'Contact us for current availability' }}</span></div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="border-t border-slate-200 bg-slate-100/40 py-20 dark:border-slate-800/80 dark:bg-[#0E1526]/40" aria-labelledby="pricing-calculator-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <h2 id="pricing-calculator-heading" class="font-heading text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $setting?->calc_title ?: 'Content Cost Calculator' }}</h2>
                @if(filled($setting?->calc_description))
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $setting->calc_description }}</p>
                @endif
            </div>

            <div class="bte-card rounded-3xl border border-slate-200 p-6 shadow-xl dark:border-slate-800 sm:p-10">
                <div class="grid grid-cols-1 items-center gap-8 md:grid-cols-2">
                    <div class="space-y-6">
                        <div>
                            <div class="mb-2 flex justify-between gap-4 text-xs font-bold">
                                <label for="pricingArticles" class="text-slate-700 dark:text-slate-300">{{ $setting?->calc_articles_label ?: 'Articles Needed Monthly' }}</label>
                                <span class="font-mono text-sm text-indigo-500" x-text="`${safeAmount(articles)} {{ $setting?->calc_articles_unit ?: 'Articles' }}`"></span>
                            </div>
                            <input id="pricingArticles" x-model.number="articles" type="range" min="4" max="60" step="2" class="w-full cursor-pointer accent-indigo-600">
                            <div class="mt-1 flex justify-between font-mono text-[10px] text-slate-400"><span>4 / mo</span><span>30 / mo</span><span>60 / mo</span></div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between gap-4 text-xs font-bold">
                                <label for="pricingArticleCost" class="text-slate-700 dark:text-slate-300">{{ $setting?->calc_cost_label ?: 'External Cost per Article' }}</label>
                                <span class="font-mono text-sm text-indigo-500" x-text="`${money(costPerArticle)} / article`"></span>
                            </div>
                            <input id="pricingArticleCost" x-model.number="costPerArticle" type="range" min="50" max="400" step="25" class="w-full cursor-pointer accent-indigo-600">
                            <div class="mt-1 flex justify-between font-mono text-[10px] text-slate-400"><span x-text="money(50)"></span><span x-text="money(200)"></span><span x-text="money(400)"></span></div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl border border-indigo-500/30 bg-gradient-to-br from-indigo-950/40 to-slate-900 p-6 text-center">
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $setting?->calc_monthly_result_label ?: 'Estimated Monthly Content Cost' }}</div>
                            <div class="mt-1 font-heading text-4xl font-black text-emerald-400 sm:text-5xl" x-text="money(safeAmount(articles) * safeAmount(costPerArticle))"></div>
                        </div>
                        <div class="border-t border-slate-800/80 pt-3">
                            <div class="text-[10px] text-slate-400">{{ $setting?->calc_annual_result_label ?: 'Estimated Annual Content Cost' }}</div>
                            <div class="text-sm font-bold text-slate-200" x-text="money(safeAmount(articles) * safeAmount(costPerArticle) * 12)"></div>
                        </div>
                        <p class="text-[10px] leading-relaxed text-slate-400">{{ $setting?->calc_disclaimer ?: 'This estimate uses only your inputs. Platform savings are not calculated because article capacity is not stored for pricing plans.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200 py-20 dark:border-slate-800/80" aria-labelledby="pricing-faq-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-16 text-center">
                <h2 id="pricing-faq-heading" class="font-heading text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $setting?->faq_title ?: 'Frequently Asked Questions' }}</h2>
                @if(filled($setting?->faq_description))
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $setting->faq_description }}</p>
                @endif
            </div>

            @if(filled($setting?->faqs))
                <div class="space-y-4">
                    @foreach($setting->faqs as $index => $faq)
                        <article x-data="{ open: false }" class="bte-card overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                            <h3>
                                <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left text-sm font-bold text-slate-800 dark:text-slate-200 sm:text-base" :aria-expanded="open" aria-controls="pricing-faq-{{ $index }}" @click="open = ! open">
                                    <span>{{ $faq['question'] ?? '' }}</span>
                                    <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                            </h3>
                            <div id="pricing-faq-{{ $index }}" x-cloak x-show="open" role="region" class="px-5 pb-5 text-xs leading-relaxed text-slate-600 dark:text-slate-400 sm:text-sm">{{ $faq['answer'] ?? '' }}</div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="text-center text-sm text-slate-500 dark:text-slate-400">{{ $setting?->faq_empty_state ?: 'No pricing questions have been published yet.' }}</p>
            @endif
        </div>
    </section>

    <section class="relative overflow-hidden py-20">
        <div class="relative z-10 mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-indigo-500/30 bg-gradient-to-tr from-indigo-900/60 via-slate-900 to-violet-950/60 p-8 shadow-2xl sm:p-14">
                <h2 class="font-heading text-3xl font-black text-white sm:text-5xl">{{ $setting?->final_cta_heading ?: 'Choose the Right Starting Point' }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-base text-slate-300 sm:text-lg">{{ $setting?->final_cta_description ?: 'Create an account to explore the platform, or review the available capabilities before choosing a plan.' }}</p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row"><a href="{{ $finalPrimaryCtaUrl }}" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-indigo-500/30 transition-all hover:scale-105 sm:w-auto">{{ $setting?->final_primary_cta_label ?: 'Create Account' }}</a><a href="{{ $finalSecondaryCtaUrl }}" class="w-full rounded-xl border border-slate-700 bg-slate-800/80 px-8 py-4 text-base font-bold text-white transition-all hover:bg-slate-700/80 sm:w-auto">{{ $setting?->final_secondary_cta_label ?: 'Review Features' }}</a></div>
            </div>
        </div>
    </section>
</div>
@endsection
