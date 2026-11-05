<x-admin.layout title="Mission Control" :breadcrumbs="[['label' => 'Mission Control']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Platform overview</p>
            <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Admin Mission Control</h1>
            <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400">Verified customer, subscription, content, and AI activity from the current BlackTechEngine database.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            Application available
        </span>
    </div>

    <section aria-labelledby="dashboard-metrics-heading">
        <h2 id="dashboard-metrics-heading" class="sr-only">Current platform metrics</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 xl:gap-6">
            @foreach([
                ['key' => 'customers', 'label' => 'Customers', 'icon' => 'users', 'accent' => 'text-indigo-500', 'note' => 'Web user accounts'],
                ['key' => 'workspaces', 'label' => 'Workspaces', 'icon' => 'blocks', 'accent' => 'text-cyan-500', 'note' => 'Customer workspaces'],
                ['key' => 'brands', 'label' => 'Brands', 'icon' => 'badge-check', 'accent' => 'text-violet-500', 'note' => 'Brand profiles'],
                ['key' => 'subscriptions', 'label' => 'Subscriptions', 'icon' => 'receipt-text', 'accent' => 'text-emerald-500', 'note' => 'All lifecycle states'],
                ['key' => 'plans', 'label' => 'Plans', 'icon' => 'layers-3', 'accent' => 'text-amber-500', 'note' => 'Configured plans'],
            ] as $metric)
                <article class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-6" data-metric="{{ $metric['key'] }}">
                    <div class="flex items-center justify-between gap-3 text-xs font-bold uppercase tracking-wider text-slate-400">
                        <span>{{ $metric['label'] }}</span>
                        <i data-lucide="{{ $metric['icon'] }}" class="h-4 w-4 {{ $metric['accent'] }}" aria-hidden="true"></i>
                    </div>
                    <p class="mt-3 font-heading text-3xl font-black text-slate-900 dark:text-white">{{ number_format($metrics[$metric['key']]) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $metric['note'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800 xl:col-span-7" aria-labelledby="subscription-distribution-heading">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
                <h2 id="subscription-distribution-heading" class="flex items-center gap-2 font-heading text-lg font-bold">
                    <i data-lucide="chart-no-axes-combined" class="h-5 w-5 text-emerald-500" aria-hidden="true"></i>
                    Subscription distribution
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Real subscription records grouped by plan and lifecycle status.</p>
            </div>
            @if ($subscriptionDistribution->isEmpty())
                <div class="p-5 sm:p-7">
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center dark:border-slate-700">
                        <i data-lucide="receipt-text" class="mx-auto h-6 w-6 text-slate-400" aria-hidden="true"></i>
                        <p class="mt-2 text-xs font-bold text-slate-700 dark:text-slate-300">No subscriptions yet</p>
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Plan distribution will appear after a workspace receives a subscription.</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[30rem] text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">
                            <tr><th class="px-5 py-3 font-bold sm:px-7">Plan</th><th class="px-5 py-3 font-bold">Status</th><th class="px-5 py-3 text-right font-bold sm:px-7">Subscriptions</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach ($subscriptionDistribution as $distribution)
                                <tr><th class="px-5 py-4 font-bold sm:px-7">{{ $distribution['plan'] }}</th><td class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ $distribution['status'] }}</td><td class="px-5 py-4 text-right font-mono font-bold sm:px-7">{{ number_format($distribution['count']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-5" aria-labelledby="activity-heading">
            <h2 id="activity-heading" class="flex items-center gap-2 font-heading text-lg font-bold">
                <i data-lucide="activity" class="h-5 w-5 text-cyan-500" aria-hidden="true"></i>
                Content and AI activity
            </h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Recorded platform totals without revenue or cost estimates.</p>
            <div class="mt-5 grid grid-cols-2 gap-3">
                @foreach([
                    ['key' => 'content_drafts', 'label' => 'Content drafts', 'icon' => 'file-text'],
                    ['key' => 'social_posts', 'label' => 'Social posts', 'icon' => 'calendar-days'],
                    ['key' => 'ai_attempts', 'label' => 'AI attempts', 'icon' => 'sparkles'],
                    ['key' => 'ai_tokens', 'label' => 'AI tokens', 'icon' => 'binary'],
                ] as $activity)
                    <article class="min-w-0 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="flex items-center justify-between gap-2"><p class="truncate text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $activity['label'] }}</p><i data-lucide="{{ $activity['icon'] }}" class="h-4 w-4 shrink-0 text-indigo-500" aria-hidden="true"></i></div>
                        <p class="mt-2 font-heading text-2xl font-black">{{ number_format($contentActivity[$activity['key']]) }}</p>
                    </article>
                @endforeach
            </div>
            @if (array_sum($contentActivity) === 0)
                <p class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-center text-[11px] text-slate-500 dark:border-slate-700 dark:text-slate-400">No platform content activity has been recorded yet.</p>
            @endif
        </section>
    </div>

    <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="monthly-trends-heading">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
            <h2 id="monthly-trends-heading" class="flex items-center gap-2 font-heading text-lg font-bold"><i data-lucide="chart-no-axes-combined" class="h-5 w-5 text-indigo-500" aria-hidden="true"></i>Six-month content trend</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Drafts, social posts, and AI generation attempts created each month.</p>
        </div>
        @if ($monthlyTrends->sum('drafts') + $monthlyTrends->sum('social_posts') + $monthlyTrends->sum('ai_attempts') === 0)
            <div class="p-5 sm:p-7"><div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center dark:border-slate-700"><p class="text-xs font-bold text-slate-700 dark:text-slate-300">No monthly activity yet</p><p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Six-month trends will populate as customers create content.</p></div></div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[38rem] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-900/40 dark:text-slate-400"><tr><th class="px-5 py-3 font-bold sm:px-7">Month</th><th class="px-5 py-3 text-right font-bold">Drafts</th><th class="px-5 py-3 text-right font-bold">Social posts</th><th class="px-5 py-3 text-right font-bold sm:px-7">AI attempts</th></tr></thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($monthlyTrends as $month)
                            <tr><th class="px-5 py-4 font-bold sm:px-7">{{ $month['label'] }}</th><td class="px-5 py-4 text-right font-mono">{{ number_format($month['drafts']) }}</td><td class="px-5 py-4 text-right font-mono">{{ number_format($month['social_posts']) }}</td><td class="px-5 py-4 text-right font-mono sm:px-7">{{ number_format($month['ai_attempts']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800 xl:col-span-7" aria-labelledby="content-status-heading">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
                <div>
                    <h2 id="content-status-heading" class="flex items-center gap-2 font-heading text-base font-bold">
                        <i data-lucide="list-checks" class="h-4 w-4 text-cyan-500" aria-hidden="true"></i>
                        Content readiness
                    </h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Current publishing and catalog states.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[34rem] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-7">Area</th>
                            <th scope="col" class="px-5 py-3 font-bold">Count</th>
                            <th scope="col" class="px-5 py-3 font-bold">State</th>
                            <th scope="col" class="px-5 py-3 text-right font-bold sm:px-7">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <tr>
                            <th scope="row" class="px-5 py-4 font-bold text-slate-800 dark:text-slate-200 sm:px-7">Published articles</th>
                            <td class="px-5 py-4 font-mono">{{ number_format($metrics['published_blogs']) }}</td>
                            <td class="px-5 py-4 text-emerald-600 dark:text-emerald-400">Public</td>
                            <td class="px-5 py-4 text-right sm:px-7">@can('manage blogs')<a href="{{ route('admin.blogs.index') }}" class="font-bold text-indigo-500 hover:underline">Manage</a>@else<span class="text-slate-400">Restricted</span>@endcan</td>
                        </tr>
                        <tr>
                            <th scope="row" class="px-5 py-4 font-bold text-slate-800 dark:text-slate-200 sm:px-7">Draft or scheduled articles</th>
                            <td class="px-5 py-4 font-mono">{{ number_format($contentStatus['draft_blogs']) }}</td>
                            <td class="px-5 py-4 text-amber-600 dark:text-amber-400">Not public</td>
                            <td class="px-5 py-4 text-right sm:px-7">@can('manage blogs')<a href="{{ route('admin.blogs.index') }}" class="font-bold text-indigo-500 hover:underline">Review</a>@else<span class="text-slate-400">Restricted</span>@endcan</td>
                        </tr>
                        <tr>
                            <th scope="row" class="px-5 py-4 font-bold text-slate-800 dark:text-slate-200 sm:px-7">Active pricing plans</th>
                            <td class="px-5 py-4 font-mono">{{ number_format($metrics['active_pricing_plans']) }}</td>
                            <td class="px-5 py-4 text-emerald-600 dark:text-emerald-400">Public</td>
                            <td class="px-5 py-4 text-right sm:px-7">@can('manage pricing plans')<a href="{{ route('admin.pricing.index') }}" class="font-bold text-indigo-500 hover:underline">Manage</a>@else<span class="text-slate-400">Restricted</span>@endcan</td>
                        </tr>
                        <tr>
                            <th scope="row" class="px-5 py-4 font-bold text-slate-800 dark:text-slate-200 sm:px-7">Inactive pricing plans</th>
                            <td class="px-5 py-4 font-mono">{{ number_format($contentStatus['inactive_pricing_plans']) }}</td>
                            <td class="px-5 py-4 text-slate-500 dark:text-slate-400">Hidden</td>
                            <td class="px-5 py-4 text-right sm:px-7">@can('manage pricing plans')<a href="{{ route('admin.pricing.index') }}" class="font-bold text-indigo-500 hover:underline">Review</a>@else<span class="text-slate-400">Restricted</span>@endcan</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-5" aria-labelledby="recent-content-heading">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
                <h2 id="recent-content-heading" class="flex items-center gap-2 font-heading text-base font-bold">
                    <i data-lucide="radio" class="h-4 w-4 text-indigo-500" aria-hidden="true"></i>
                    Recent published content
                </h2>
                @can('manage blogs')
                    <a href="{{ route('admin.blogs.index') }}" class="text-xs font-bold text-cyan-500 hover:underline">All articles</a>
                @endcan
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentPublishedBlogs as $blog)
                    <article class="bte-surface min-w-0 rounded-2xl border border-slate-200 p-3.5 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-3 text-[10px] text-slate-400">
                            <span class="truncate font-bold text-indigo-500">{{ $blog->category ?: 'Article' }}</span>
                            <time datetime="{{ $blog->published_at?->toAtomString() }}" class="shrink-0">{{ $blog->published_at?->format('M j, Y') }}</time>
                        </div>
                        <h3 class="mt-1 truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $blog->title }}</h3>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center dark:border-slate-700">
                        <i data-lucide="file-clock" class="mx-auto h-6 w-6 text-slate-400" aria-hidden="true"></i>
                        <p class="mt-2 text-xs font-bold text-slate-700 dark:text-slate-300">No published articles yet</p>
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">This panel will populate from published blog records.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2" aria-label="Unavailable operational modules">
        @foreach([
            ['icon' => 'server', 'title' => 'Queue telemetry', 'text' => 'No operational monitoring source exists.'],
            ['icon' => 'badge-dollar-sign', 'title' => 'Revenue analytics', 'text' => 'Verified payment revenue is not available and is not estimated.'],
        ] as $unavailable)
            <div class="rounded-2xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><i data-lucide="{{ $unavailable['icon'] }}" class="h-4 w-4" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <h2 class="text-xs font-bold">{{ $unavailable['title'] }} unavailable</h2>
                        <p class="mt-1 text-[11px] leading-5 text-slate-500 dark:text-slate-400">{{ $unavailable['text'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </section>
</x-admin.layout>
