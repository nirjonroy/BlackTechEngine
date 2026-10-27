<x-admin.layout title="AI Models & Routing" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'AI Models & Routing']]">
    @include('admin.ai-configuration.partials.header')

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="AI configuration summary">
        @foreach([
            ['label' => 'Providers', 'value' => $metrics['providers'], 'secondary' => $metrics['enabled_providers'].' enabled', 'icon' => 'server-cog', 'accent' => 'text-cyan-500'],
            ['label' => 'Models', 'value' => $metrics['models'], 'secondary' => $metrics['enabled_models'].' enabled', 'icon' => 'boxes', 'accent' => 'text-indigo-500'],
            ['label' => 'Workload routes', 'value' => $metrics['workload_routes'], 'secondary' => $metrics['enabled_workload_routes'].' enabled', 'icon' => 'route', 'accent' => 'text-violet-500'],
        ] as $metric)
            <article class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800">
                <div class="flex items-center justify-between gap-3 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <span>{{ $metric['label'] }}</span>
                    <i data-lucide="{{ $metric['icon'] }}" class="h-5 w-5 {{ $metric['accent'] }}" aria-hidden="true"></i>
                </div>
                <p class="mt-3 font-heading text-3xl font-black text-slate-900 dark:text-white">{{ number_format($metric['value']) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $metric['secondary'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3" aria-label="AI catalog shortcuts">
        @foreach([
            ['route' => 'admin.ai-configuration.providers', 'title' => 'Provider catalog', 'description' => 'Health, enablement, adapter readiness, timeouts, and model counts.', 'icon' => 'server-cog'],
            ['route' => 'admin.ai-configuration.models', 'title' => 'Model catalog', 'description' => 'Provider identifiers, modalities, capabilities, and availability.', 'icon' => 'boxes'],
            ['route' => 'admin.ai-configuration.workload-routes', 'title' => 'Workload routes', 'description' => 'Persisted primary and fallback assignments without exposing route settings.', 'icon' => 'route'],
        ] as $shortcut)
            <a href="{{ route($shortcut['route']) }}" class="bte-card group rounded-3xl border border-slate-200 p-5 shadow-xl transition hover:border-cyan-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-800">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-500"><i data-lucide="{{ $shortcut['icon'] }}" class="h-5 w-5" aria-hidden="true"></i></span>
                <h2 class="mt-4 font-heading text-base font-bold group-hover:text-cyan-600 dark:group-hover:text-cyan-400">{{ $shortcut['title'] }}</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $shortcut['description'] }}</p>
            </a>
        @endforeach
    </section>

    <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="ai-audit-heading">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
            <h2 id="ai-audit-heading" class="font-heading text-lg font-bold">Recent configuration activity</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">A bounded, field-name-only summary. Stored values and request payloads are never rendered.</p>
        </div>
        @if($auditActivity->isEmpty())
            <div class="px-6 py-14 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800"><i data-lucide="history" class="h-6 w-6" aria-hidden="true"></i></span>
                <h3 class="mt-4 font-heading text-base font-bold">No AI configuration activity yet</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No audit records have been persisted.</p>
            </div>
        @else
            <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                @foreach($auditActivity as $activity)
                    <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-7">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $activity['action'] }} <span class="font-normal text-slate-500 dark:text-slate-400">on {{ $activity['subject'] }}</span></p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">By {{ $activity['actor'] }}@if($activity['changed_fields'] !== []) · Fields: {{ implode(', ', $activity['changed_fields']) }}@endif</p>
                        </div>
                        <time class="shrink-0 text-xs text-slate-400" datetime="{{ $activity['created_at']?->toAtomString() }}">{{ $activity['created_at']?->diffForHumans() ?? 'Time unavailable' }}</time>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-admin.layout>
