<x-admin.layout title="Plans & Pricing Tiers" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'Plans & Pricing Tiers']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Pricing catalog</p>
            <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Plans & Pricing Tiers</h1>
            <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500 dark:text-slate-400">Manage the real marketing plans shown by the Pricing CMS. Subscription, billing, entitlement, tenant, and usage systems are not connected.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('manage pricing page settings')
                <a href="{{ route('admin.pricing_page.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300 dark:hover:text-cyan-400">
                    <i data-lucide="panels-top-left" class="h-4 w-4" aria-hidden="true"></i>
                    Pricing Page CMS
                </a>
            @endcan
            <a href="{{ route('admin.pricing.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 px-4 py-2.5 text-xs font-black text-white shadow-md shadow-indigo-500/20 transition hover:from-cyan-400 hover:to-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                Add pricing plan
            </a>
        </div>
    </div>

    <section class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4" aria-labelledby="catalog-boundary-heading">
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                <i data-lucide="database-zap" class="h-4 w-4" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <h2 id="catalog-boundary-heading" class="text-xs font-bold text-amber-800 dark:text-amber-300">Marketing catalog only</h2>
                <p class="mt-1 text-[11px] leading-5 text-amber-800/80 dark:text-amber-200/70">These records configure public plan presentation. Customer subscriptions, quotas, invoices, payment-provider products, and usage enforcement require separate future infrastructure.</p>
            </div>
        </div>
    </section>

    <section aria-labelledby="pricing-tier-heading">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="pricing-tier-heading" class="font-heading text-lg font-bold">Configured tiers</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Prices use the centralized {{ $currencyCode }} Pricing CMS currency.</p>
            </div>
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($plans->total()) }} {{ Str::plural('plan', $plans->total()) }}</p>
        </div>

        @if($plans->isEmpty())
            <div class="bte-card mt-4 rounded-3xl border border-dashed border-slate-300 px-6 py-16 text-center shadow-xl dark:border-slate-700">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                    <i data-lucide="layers-3" class="h-6 w-6" aria-hidden="true"></i>
                </span>
                <h3 class="mt-4 font-heading text-base font-bold">No pricing plans configured</h3>
                <p class="mx-auto mt-1 max-w-md text-xs leading-5 text-slate-500 dark:text-slate-400">Create the first marketing plan to make a real tier available to the Pricing CMS.</p>
                <a href="{{ route('admin.pricing.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                    Create pricing plan
                </a>
            </div>
        @else
            <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3 xl:gap-6">
                @foreach($plans as $plan)
                    <article class="bte-card flex min-w-0 flex-col rounded-3xl border p-5 shadow-xl sm:p-6 {{ $plan->is_popular ? 'border-2 border-indigo-500/50 shadow-indigo-500/10' : 'border-slate-200 dark:border-slate-800' }}" aria-labelledby="plan-{{ $plan->id }}-heading">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider {{ $plan->is_popular ? 'text-indigo-500' : 'text-slate-400' }}">Plan #{{ $plan->id }} &bull; Order {{ $plan->display_order }}</p>
                                <h3 id="plan-{{ $plan->id }}-heading" class="mt-1 break-words font-heading text-xl font-bold text-slate-900 dark:text-white">{{ $plan->name }}</h3>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $plan->is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $plan->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="pt-4">
                            @if($plan->title)
                                <p class="break-words text-sm font-bold text-slate-800 dark:text-slate-200">{{ $plan->title }}</p>
                            @endif
                            <p class="mt-1 break-words text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $plan->description ?: 'No plan description has been provided.' }}</p>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-3">
                            <div class="bte-surface min-w-0 rounded-2xl border border-slate-200 p-3 dark:border-slate-800">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Monthly</dt>
                                <dd class="mt-1 break-words font-mono text-base font-black text-cyan-600 dark:text-cyan-400">{{ $formattedPlanPrices[$plan->getKey()]['monthly'] }}</dd>
                            </div>
                            <div class="bte-surface min-w-0 rounded-2xl border border-slate-200 p-3 dark:border-slate-800">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Annual</dt>
                                <dd class="mt-1 break-words font-mono text-base font-black text-indigo-600 dark:text-indigo-400">{{ $formattedPlanPrices[$plan->getKey()]['annual'] }}</dd>
                            </div>
                        </dl>

                        @if($plan->is_popular)
                            <p class="mt-4 inline-flex w-fit items-center gap-1.5 rounded-full bg-indigo-500/10 px-2.5 py-1 text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                                <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                {{ $plan->popular_label ?: 'Popular plan' }}
                            </p>
                        @endif

                        <div class="mt-5 flex-1 border-t border-slate-200 pt-4 dark:border-slate-800">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Plan features</h4>
                                <span class="text-[10px] font-semibold text-slate-400">{{ $plan->features->count() }} {{ Str::plural('item', $plan->features->count()) }}</span>
                            </div>
                            <ul class="mt-3 space-y-2">
                                @forelse($plan->features as $feature)
                                    <li class="flex min-w-0 items-start gap-2 text-xs leading-5 {{ $feature->is_included ? 'text-slate-600 dark:text-slate-300' : 'text-slate-400 line-through' }}">
                                        <i data-lucide="{{ $feature->is_included ? 'check-circle-2' : 'minus-circle' }}" class="mt-0.5 h-3.5 w-3.5 shrink-0 {{ $feature->is_included ? 'text-emerald-500' : 'text-slate-400' }}" aria-hidden="true"></i>
                                        <span class="break-words">{{ $feature->name }}</span>
                                        <span class="sr-only">{{ $feature->is_included ? 'Included' : 'Not included' }}</span>
                                    </li>
                                @empty
                                    <li class="rounded-xl border border-dashed border-slate-300 px-3 py-4 text-center text-[11px] text-slate-500 dark:border-slate-700 dark:text-slate-400">No features are attached to this plan.</li>
                                @endforelse
                            </ul>
                        </div>

                        <div class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-800">
                            <dl class="grid grid-cols-2 gap-3 text-[10px] text-slate-500 dark:text-slate-400">
                                <div>
                                    <dt class="font-bold uppercase tracking-wider">Created</dt>
                                    <dd class="mt-1"><time datetime="{{ $plan->created_at?->toAtomString() }}">{{ $plan->created_at?->format('M j, Y') ?? 'Unavailable' }}</time></dd>
                                </div>
                                <div>
                                    <dt class="font-bold uppercase tracking-wider">Updated</dt>
                                    <dd class="mt-1"><time datetime="{{ $plan->updated_at?->toAtomString() }}">{{ $plan->updated_at?->format('M j, Y') ?? 'Unavailable' }}</time></dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.pricing.edit', $plan) }}" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-3 py-2.5 text-xs font-bold text-white transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    Edit plan
                                </a>
                                <form action="{{ route('admin.pricing.destroy', $plan) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this pricing plan?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-rose-500/30 px-3 py-2.5 text-rose-600 transition hover:bg-rose-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:text-rose-400" aria-label="Delete {{ $plan->name }}">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($plans->hasPages())
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white/70 p-4 dark:border-slate-800 dark:bg-slate-900/30">
                    {{ $plans->links() }}
                </div>
            @endif
        @endif
    </section>
</x-admin.layout>
