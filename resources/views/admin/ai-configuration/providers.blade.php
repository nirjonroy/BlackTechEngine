<x-admin.layout title="AI Provider Catalog" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'AI Models & Routing', 'route' => 'admin.ai-configuration.index'], ['label' => 'Providers']]">
    @include('admin.ai-configuration.partials.header')

    @unless($metadataWritesAvailable)
        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-xs leading-5 text-amber-800 dark:text-amber-200" role="note"><strong>Metadata changes are currently unavailable.</strong> The concurrency migration must be separately rehearsed and deployed before the server-side write gate can be enabled.</div>
    @endunless

    <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="provider-catalog-heading">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="provider-catalog-heading" class="font-heading text-lg font-bold">Provider catalog</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Persisted provider metadata only; no credentials or live connectivity checks.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($providers->total()) }} matching</span>
                    @if($metadataWritesAvailable)
                        <a href="{{ route('admin.ai-configuration.providers.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>Create provider</a>
                    @endif
                </div>
            </div>

            <form method="GET" action="{{ route('admin.ai-configuration.providers') }}" class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-12" role="search">
                <div class="relative lg:col-span-4">
                    <label for="provider-search" class="sr-only">Search providers</label>
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input id="provider-search" name="search" type="search" maxlength="100" value="{{ $filters['search'] }}" placeholder="Name, key, adapter, or description" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </div>
                <select name="health" aria-label="Provider health" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 lg:col-span-2">
                    <option value="all">All health states</option>
                    @foreach($healthStatuses as $status)<option value="{{ $status->value }}" @selected($filters['health'] === $status->value)>{{ Str::headline($status->value) }}</option>@endforeach
                </select>
                <select name="state" aria-label="Provider enablement" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 lg:col-span-2">
                    <option value="all">All enablement</option><option value="enabled" @selected($filters['state'] === 'enabled')>Enabled</option><option value="disabled" @selected($filters['state'] === 'disabled')>Disabled</option>
                </select>
                <select name="sort" aria-label="Sort providers" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 lg:col-span-2">
                    @foreach(['name' => 'Name A-Z', 'key' => 'Key A-Z', 'health' => 'Health A-Z', 'newest' => 'Newest', 'oldest' => 'Oldest'] as $value => $label)<option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>@endforeach
                </select>
                <div class="flex gap-2 lg:col-span-2">
                    <select name="per_page" aria-label="Providers per page" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-2 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">@foreach([10, 25, 50] as $perPage)<option value="{{ $perPage }}" @selected($filters['per_page'] === $perPage)>{{ $perPage }}</option>@endforeach</select>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Apply</button>
                </div>
            </form>
        </div>

        @if($providers->isEmpty())
            <div class="px-6 py-16 text-center"><i data-lucide="server-off" class="mx-auto h-8 w-8 text-slate-400" aria-hidden="true"></i><h3 class="mt-4 font-heading text-base font-bold">No providers found</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No persisted provider records match these filters.</p></div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[76rem] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-900/40 dark:text-slate-400"><tr><th class="px-5 py-3 sm:px-7" scope="col">Provider</th><th class="px-5 py-3" scope="col">State</th><th class="px-5 py-3" scope="col">Health</th><th class="px-5 py-3" scope="col">Adapter</th><th class="px-5 py-3" scope="col">Models</th><th class="px-5 py-3" scope="col">Policy</th><th class="px-5 py-3" scope="col">Last verified</th>@if($metadataWritesAvailable)<th class="px-5 py-3 text-right sm:px-7" scope="col">Action</th>@endif</tr></thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach($providers as $provider)
                            @php($health = $provider->getRawOriginal('health_status'))
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                <th scope="row" class="px-5 py-4 sm:px-7"><span class="block max-w-64 truncate font-bold text-slate-800 dark:text-slate-200">{{ $provider->display_name }}</span><span class="block max-w-64 truncate font-mono text-[10px] font-normal text-slate-400">{{ $provider->key }}</span>@if($provider->description)<span class="mt-1 block max-w-sm truncate font-normal text-slate-500 dark:text-slate-400">{{ $provider->description }}</span>@endif</th>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 font-bold {{ $provider->is_enabled ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-slate-500/10 text-slate-600 dark:text-slate-400' }}">{{ $provider->is_enabled ? 'Enabled' : 'Disabled' }}</span></td>
                                <td class="px-5 py-4"><span class="rounded-full bg-amber-500/10 px-2.5 py-1 font-bold text-amber-700 dark:text-amber-300">{{ Str::headline($health ?: 'unknown') }}</span></td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">{{ $provider->adapter_type ?: 'Not configured' }}</td>
                                <td class="px-5 py-4 font-bold">{{ number_format($provider->models_count) }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">{{ $provider->request_timeout_seconds }}s timeout · {{ $provider->retry_limit }} retries</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400"><time datetime="{{ $provider->last_verified_at?->toAtomString() }}">{{ $provider->last_verified_at?->format('M j, Y H:i') ?? 'Never' }}</time></td>
                                @if($metadataWritesAvailable)<td class="px-5 py-4 text-right sm:px-7"><a href="{{ route('admin.ai-configuration.providers.edit', $provider) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-bold text-indigo-600 transition hover:bg-indigo-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400">Edit <i data-lucide="pencil" class="h-3.5 w-3.5" aria-hidden="true"></i></a></td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-7 lg:flex-row lg:items-center lg:justify-between"><p class="text-xs text-slate-500 dark:text-slate-400">Showing {{ number_format($providers->firstItem()) }}-{{ number_format($providers->lastItem()) }} of {{ number_format($providers->total()) }}</p><div class="min-w-0">{{ $providers->links() }}</div></div>
        @endif
    </section>
</x-admin.layout>
