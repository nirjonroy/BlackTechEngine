<x-admin.layout title="Mission Control" :breadcrumbs="[['label' => 'Mission Control']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Platform overview</p>
            <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Admin Mission Control</h1>
            <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400">Verified account, publishing, and pricing data from the current BlackTechEngine database.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            Application available
        </span>
    </div>

    <section aria-labelledby="dashboard-metrics-heading">
        <h2 id="dashboard-metrics-heading" class="sr-only">Current platform metrics</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 xl:gap-6">
            @foreach([
                ['key' => 'registered_users', 'label' => 'Registered Users', 'icon' => 'users', 'accent' => 'text-indigo-500', 'note' => 'Web user accounts'],
                ['key' => 'active_pricing_plans', 'label' => 'Active Plans', 'icon' => 'layers-3', 'accent' => 'text-emerald-500', 'note' => 'Public pricing tiers'],
                ['key' => 'published_blogs', 'label' => 'Published Articles', 'icon' => 'file-check-2', 'accent' => 'text-cyan-500', 'note' => 'Currently public posts'],
                ['key' => 'admin_accounts', 'label' => 'Admin Accounts', 'icon' => 'shield-check', 'accent' => 'text-violet-500', 'note' => 'Admin guard identities'],
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

    <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="telemetry-heading">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:p-7">
            <div>
                <h2 id="telemetry-heading" class="font-heading text-lg font-bold">Revenue and AI cost telemetry</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">The reference chart is retained as an honest unavailable state.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                <i data-lucide="database-zap" class="h-3.5 w-3.5" aria-hidden="true"></i>
                Data source unavailable
            </span>
        </div>
        <div class="relative flex min-h-64 items-center justify-center overflow-hidden bg-gradient-to-b from-slate-50 to-white p-6 dark:from-slate-950/30 dark:to-slate-900/10 sm:p-8">
            <div class="admin-chart-grid pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>
            <div class="relative max-w-lg text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <i data-lucide="chart-no-axes-combined" class="h-6 w-6" aria-hidden="true"></i>
                </span>
                <h3 class="mt-4 font-heading text-base font-bold">No billing or AI-usage backend is connected</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Revenue, subscriptions, token spend, growth, and margin are intentionally not estimated or fabricated.</p>
            </div>
        </div>
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

    <section class="grid grid-cols-1 gap-4 md:grid-cols-3" aria-label="Unavailable operational modules">
        @foreach([
            ['icon' => 'cpu', 'title' => 'AI models', 'text' => 'No AI routing or usage source exists.'],
            ['icon' => 'server', 'title' => 'Queue telemetry', 'text' => 'No operational monitoring source exists.'],
            ['icon' => 'badge-dollar-sign', 'title' => 'Billing analytics', 'text' => 'No subscription or transaction source exists.'],
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
