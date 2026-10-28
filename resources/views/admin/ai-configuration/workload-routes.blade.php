<x-admin.layout title="AI Workload Routes" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'AI Models & Routing', 'route' => 'admin.ai-configuration.index'], ['label' => 'Workload routes']]">
    @include('admin.ai-configuration.partials.header')

    @unless($draftWritesAvailable)
        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-xs leading-5 text-amber-800 dark:text-amber-200" role="note"><strong>Workload draft changes are currently unavailable.</strong> The independent draft gate remains off until its concurrency migration is separately rehearsed and deployed.</div>
    @endunless

    <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="workload-routes-heading">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="workload-routes-heading" class="font-heading text-lg font-bold">Workload routing drafts</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Persisted primary and fallback assignments only. No workload can execute from this interface.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($workloadRoutes->total()) }} matching</span>
                    @if($draftWritesAvailable)
                        <a href="{{ route('admin.ai-configuration.workload-routes.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>Create draft</a>
                    @endif
                </div>
            </div>

            <form method="GET" action="{{ route('admin.ai-configuration.workload-routes') }}" class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12" role="search">
                <div class="relative lg:col-span-5"><label for="route-search" class="sr-only">Search workload routes</label><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i><input id="route-search" name="search" type="search" maxlength="100" value="{{ $filters['search'] }}" placeholder="Workload key or assigned model" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"></div>
                <select name="state" aria-label="Workload route state" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 lg:col-span-2"><option value="all">All states</option><option value="enabled" @selected($filters['state'] === 'enabled')>Enabled</option><option value="disabled" @selected($filters['state'] === 'disabled')>Disabled</option><option value="unassigned" @selected($filters['state'] === 'unassigned')>Primary unassigned</option></select>
                <select name="sort" aria-label="Sort workload routes" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 lg:col-span-2">@foreach(['key' => 'Key A-Z', 'state' => 'Enabled first', 'newest' => 'Newest', 'oldest' => 'Oldest'] as $value => $label)<option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>@endforeach</select>
                <select name="per_page" aria-label="Routes per page" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 lg:col-span-1">@foreach([10, 25, 50] as $perPage)<option value="{{ $perPage }}" @selected($filters['per_page'] === $perPage)>{{ $perPage }}</option>@endforeach</select>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 lg:col-span-2">Apply filters</button>
            </form>
        </div>

        @if($workloadRoutes->isEmpty())
            <div class="px-6 py-16 text-center"><i data-lucide="route-off" class="mx-auto h-8 w-8 text-slate-400" aria-hidden="true"></i><h3 class="mt-4 font-heading text-base font-bold">No workload routes found</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No persisted routing records match these filters. An unassigned draft can be saved once the separate gate is available.</p></div>
        @else
            <div class="grid grid-cols-1 gap-4 p-5 sm:p-7 xl:grid-cols-2">
                @foreach($workloadRoutes as $workloadRoute)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-900/30">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0"><h3 class="truncate font-heading text-base font-bold">{{ Str::headline($workloadRoute->workload_key) }}</h3><p class="mt-1 truncate font-mono text-[10px] text-slate-400">{{ $workloadRoute->workload_key }}</p></div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $workloadRoute->is_enabled ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-slate-500/10 text-slate-600 dark:text-slate-400' }}">{{ $workloadRoute->is_enabled ? 'Enabled' : 'Disabled draft' }}</span>
                                @if($draftWritesAvailable && ! $workloadRoute->is_enabled)
                                    <a href="{{ route('admin.ai-configuration.workload-routes.edit', $workloadRoute) }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-indigo-600 transition hover:bg-indigo-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400">Edit <i data-lucide="pencil" class="h-3.5 w-3.5" aria-hidden="true"></i></a>
                                @endif
                            </div>
                        </div>
                        <dl class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach([['label' => 'Primary model', 'model' => $workloadRoute->primaryModel], ['label' => 'Fallback model', 'model' => $workloadRoute->fallbackModel]] as $assignment)
                                <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-950/60"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $assignment['label'] }}</dt><dd class="mt-2 text-xs">@if($assignment['model'])<span class="block truncate font-bold text-slate-800 dark:text-slate-200">{{ $assignment['model']->display_name }}</span><span class="block truncate font-mono text-[10px] text-slate-400">{{ $assignment['model']->provider?->key }} / {{ $assignment['model']->provider_model_id }}</span><span class="mt-1 block text-[10px] text-slate-500">{{ $assignment['model']->is_enabled ? 'Enabled' : 'Disabled' }} · {{ Str::headline($assignment['model']->getRawOriginal('availability_status') ?: 'unknown') }}</span>@else<span class="text-slate-400">Not assigned</span>@endif</dd></div>
                            @endforeach
                        </dl>
                    </article>
                @endforeach
            </div>
            <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-7 lg:flex-row lg:items-center lg:justify-between"><p class="text-xs text-slate-500 dark:text-slate-400">Showing {{ number_format($workloadRoutes->firstItem()) }}-{{ number_format($workloadRoutes->lastItem()) }} of {{ number_format($workloadRoutes->total()) }}</p><div class="min-w-0">{{ $workloadRoutes->links() }}</div></div>
        @endif
    </section>
</x-admin.layout>
