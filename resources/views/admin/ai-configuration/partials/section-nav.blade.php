@php
    $items = [
        ['route' => 'admin.ai-configuration.index', 'pattern' => 'admin.ai-configuration.index', 'icon' => 'gauge', 'label' => 'Overview'],
        ['route' => 'admin.ai-configuration.providers', 'pattern' => 'admin.ai-configuration.providers', 'icon' => 'server-cog', 'label' => 'Providers'],
        ['route' => 'admin.ai-configuration.models', 'pattern' => 'admin.ai-configuration.models', 'icon' => 'boxes', 'label' => 'Models'],
        ['route' => 'admin.ai-configuration.workload-routes', 'pattern' => 'admin.ai-configuration.workload-routes', 'icon' => 'route', 'label' => 'Workload routes'],
    ];
@endphp

<nav class="flex max-w-full gap-2 overflow-x-auto pb-1" aria-label="AI configuration sections">
    @foreach($items as $item)
        @php($active = request()->routeIs($item['pattern']))
        <a href="{{ route($item['route']) }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 {{ $active ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'border border-slate-200 text-slate-600 hover:border-cyan-500 hover:text-cyan-600 dark:border-slate-700 dark:text-slate-300 dark:hover:text-cyan-400' }}" @if($active) aria-current="page" @endif>
            <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>{{ $item['label'] }}
        </a>
    @endforeach
</nav>
