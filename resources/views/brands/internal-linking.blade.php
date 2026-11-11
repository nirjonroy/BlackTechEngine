<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-500">{{ $workspace->name }} &middot; {{ $brand->name }}</p>
                <h1 class="font-heading text-xl font-black sm:text-2xl">Internal Linking & Site Index</h1>
            </div>
            <a href="{{ route('workspaces.brands.show', [$workspace, $brand]) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700">
                &larr; Back to Brand
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Integration Status and Indexer Trigger -->
        <section class="bte-card rounded-2xl p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-heading text-lg font-black">Connected Site Index</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Indexed URLs and metadata are used to automatically suggest natural contextual internal links in article drafts.
                    </p>
                </div>

                @if($wpConnection && $wpConnection->isConnected())
                    <form method="POST" action="{{ route('workspaces.brands.internal-links.reindex', [$workspace, $brand]) }}" class="flex items-center gap-2">
                        @csrf
                        <select name="source" class="rounded-xl border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                            <option value="wordpress_api">WordPress REST API</option>
                            <option value="sitemap">XML Sitemap</option>
                        </select>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-black text-white hover:bg-indigo-700">
                            Re-index Site
                        </button>
                    </form>
                @else
                    <div class="text-xs text-amber-600 dark:text-amber-400 font-bold">
                        WordPress site is not connected. Connect WordPress in brand settings to enable automatic crawling.
                    </div>
                @endif
            </div>

            <!-- Stats -->
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4 border-t border-slate-100 pt-5 dark:border-slate-800">
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400">Total Indexed Pages</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $pages->total() }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400">Target Domain</p>
                    <p class="mt-1 truncate text-sm font-bold text-slate-900 dark:text-white">{{ $wpConnection?->site_url ?: 'None' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400">Last Crawl</p>
                    <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                        {{ $audits->first()?->created_at?->diffForHumans() ?: 'Never' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase text-slate-400">Last Status</p>
                    <p class="mt-1 text-sm font-bold">
                        @if($audits->first()?->status === 'completed')
                            <span class="text-emerald-600 dark:text-emerald-400">Completed ({{ $audits->first()->pages_indexed }} pages)</span>
                        @elseif($audits->first()?->status === 'failed')
                            <span class="text-rose-600 dark:text-rose-400">Failed</span>
                        @else
                            <span class="text-slate-500">Idle</span>
                        @endif
                    </p>
                </div>
            </div>
        </section>

        <!-- Indexed Pages List -->
        <section class="bte-card rounded-2xl p-5 sm:p-6">
            <h2 class="font-heading text-lg font-black mb-4">Indexed Pages and Posts ({{ $pages->total() }})</h2>

            @if($pages->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-500 dark:border-slate-800">
                    <p class="text-sm">No pages indexed yet. Click "Re-index Site" above to scan your WordPress site.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-200 text-slate-400 dark:border-slate-800">
                            <tr>
                                <th class="pb-3 font-bold uppercase">Title &amp; URL</th>
                                <th class="pb-3 font-bold uppercase">Slug</th>
                                <th class="pb-3 font-bold uppercase">Type</th>
                                <th class="pb-3 font-bold uppercase">Source</th>
                                <th class="pb-3 font-bold uppercase">Indexed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($pages as $page)
                                <tr>
                                    <td class="py-3 pr-4 max-w-sm">
                                        <p class="font-bold text-slate-900 dark:text-white truncate">{{ $page->title }}</p>
                                        <a href="{{ $page->url }}" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-indigo-600 truncate block">
                                            {{ $page->url }}
                                        </a>
                                    </td>
                                    <td class="py-3 pr-4 font-mono text-slate-500">{{ $page->slug ?: '-' }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded bg-slate-100 px-2 py-0.5 font-bold uppercase text-[10px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $page->page_type }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-slate-500">{{ $page->source }}</td>
                                    <td class="py-3 text-slate-500">{{ $page->last_indexed_at?->diffForHumans() ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $pages->links() }}
                </div>
            @endif
        </section>

        <!-- Audit History -->
        @if($audits->isNotEmpty())
            <section class="bte-card rounded-2xl p-5 sm:p-6">
                <h2 class="font-heading text-lg font-black mb-3">Recent Crawl Audits</h2>
                <div class="space-y-2">
                    @foreach($audits as $audit)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 p-3 text-xs dark:border-slate-800">
                            <div>
                                <span class="font-bold {{ $audit->status === 'completed' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ ucfirst($audit->status) }}
                                </span>
                                &bull; Discovered: {{ $audit->pages_discovered }} &bull; Indexed: {{ $audit->pages_indexed }}
                                &bull; Source: {{ $audit->source }}
                                @if($audit->error_message)
                                    <p class="text-rose-500 mt-0.5">{{ $audit->error_message }}</p>
                                @endif
                            </div>
                            <span class="text-slate-400">{{ $audit->created_at->format('M d, Y H:i') }} ({{ $audit->duration_ms }}ms)</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>

