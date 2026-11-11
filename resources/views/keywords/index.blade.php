<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">
                    <a href="{{ route('workspaces.brands.show', [$workspace, $brand]) }}" class="hover:underline">{{ $brand->name }}</a>
                    <span>/</span>
                    <span>SEO Autopilot</span>
                </div>
                <h1 class="font-heading text-xl font-black sm:text-2xl">Keyword Research &amp; Topic Clustering</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('workspaces.brands.show', [$workspace, $brand]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i> Back to Brand
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        showAddKeywordModal: false,
        showAddClusterModal: false,
        showAiSuggestModal: false,
        aiLoading: false,
        aiError: null,
        aiSuggestions: [],
        selectedCluster: '{{ $selectedClusterId ?? '' }}',
        async runAiSuggestions(e) {
            const form = e.target;
            const formData = new FormData(form);
            this.aiLoading = true;
            this.aiError = null;
            this.aiSuggestions = [];

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'AI keyword discovery failed');
                }
                this.aiSuggestions = data.suggestions || [];
            } catch (err) {
                this.aiError = err.message;
            } finally {
                this.aiLoading = false;
            }
        }
    }">
        {{-- Flash messages & Errors --}}
        @if(session('status'))
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
                {{ session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                <p class="font-black">Please address the following validation issues:</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Top Summary Stats --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bte-card flex items-center gap-4 rounded-2xl p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">
                    <i data-lucide="key-round" class="h-6 w-6"></i>
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Tracked Keywords</p>
                    <p class="font-heading text-2xl font-black">{{ number_format($totalKeywords) }}</p>
                </div>
            </div>

            <div class="bte-card flex items-center gap-4 rounded-2xl p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-300">
                    <i data-lucide="network" class="h-6 w-6"></i>
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Topic Clusters</p>
                    <p class="font-heading text-2xl font-black">{{ number_format($totalClusters) }}</p>
                </div>
            </div>

            <div class="bte-card flex items-center gap-4 rounded-2xl p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-300">
                    <i data-lucide="star" class="h-6 w-6"></i>
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Starred Keywords</p>
                    <p class="font-heading text-2xl font-black">{{ number_format($starredCount) }}</p>
                </div>
            </div>

            <div class="bte-card flex items-center gap-4 rounded-2xl p-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $aiGenerationEnabled ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                    <i data-lucide="sparkles" class="h-6 w-6"></i>
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">AI Engine</p>
                    <p class="text-sm font-black">{{ $aiGenerationEnabled ? 'Gemini Active' : 'Standby (Safe Mode)' }}</p>
                </div>
            </div>
        </div>

        {{-- Action & Search Bar --}}
        <section class="bte-card rounded-2xl p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="font-heading text-lg font-black">Brand Keyword Explorer</h2>
                    <p class="mt-1 text-sm text-slate-500">Discover search opportunities mapped to <strong class="text-slate-700 dark:text-slate-200">{{ $brand->name }}</strong>'s market, language, and audience.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" x-on:click="showAiSuggestModal = true" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-black text-white shadow-md hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <i data-lucide="sparkles" class="h-4 w-4"></i> AI Keyword Discovery
                    </button>
                    <button type="button" x-on:click="showAddKeywordModal = true" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i data-lucide="plus" class="h-4 w-4"></i> Add Keyword
                    </button>
                    <button type="button" x-on:click="showAddClusterModal = true" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i data-lucide="folder-plus" class="h-4 w-4"></i> New Cluster
                    </button>
                </div>
            </div>

            {{-- Filter and Search Row --}}
            <form method="GET" action="{{ route('workspaces.brands.keywords.index', [$workspace, $brand]) }}" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                <div>
                    <label for="search" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Search Keywords</label>
                    <div class="relative">
                        <input type="text" id="search" name="search" value="{{ $searchTerm }}" placeholder="Filter keywords..." class="bte-surface w-full rounded-xl border border-slate-300 pl-9 text-sm dark:border-slate-700">
                        <i data-lucide="search" class="absolute left-3 top-3 h-4 w-4 text-slate-400"></i>
                    </div>
                </div>

                <div>
                    <label for="cluster_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Topic Cluster</label>
                    <select id="cluster_id" name="cluster_id" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">
                        <option value="">All Topic Clusters ({{ $totalKeywords }})</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->id }}" @selected($selectedClusterId == $cluster->id)>
                                {{ $cluster->name }} ({{ $cluster->keywords_count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="intent" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Search Intent</label>
                    <select id="intent" name="intent" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">
                        <option value="">All Search Intents</option>
                        @foreach($searchIntents as $intent)
                            <option value="{{ $intent->value }}" @selected($selectedIntent === $intent->value)>
                                {{ $intent->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                        Apply Filters
                    </button>
                    @if($searchTerm || $selectedClusterId || $selectedIntent)
                        <a href="{{ route('workspaces.brands.keywords.index', [$workspace, $brand]) }}" class="rounded-xl border border-slate-300 p-2.5 text-slate-500 hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800" title="Clear Filters">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        {{-- Topic Clusters Bar --}}
        @if($clusters->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-1">Clusters:</span>
                <a href="{{ route('workspaces.brands.keywords.index', [$workspace, $brand]) }}" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ empty($selectedClusterId) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    All ({{ $totalKeywords }})
                </a>
                @foreach($clusters as $cluster)
                    <div class="inline-flex items-center rounded-full border border-slate-200 bg-white pl-3 pr-1 py-0.5 text-xs font-bold dark:border-slate-700 dark:bg-slate-800">
                        <a href="{{ route('workspaces.brands.keywords.index', [$workspace, $brand, 'cluster_id' => $cluster->id]) }}" class="{{ $selectedClusterId == $cluster->id ? 'text-indigo-600 dark:text-indigo-400 font-black' : 'text-slate-700 dark:text-slate-200' }}">
                            {{ $cluster->name }}
                            <span class="ml-1 text-[11px] text-slate-400 font-normal">({{ $cluster->keywords_count }})</span>
                        </a>
                        <form method="POST" action="{{ route('workspaces.brands.keyword-clusters.destroy', [$workspace, $brand, $cluster]) }}" class="inline ml-1" onsubmit="return confirm('Delete cluster {{ $cluster->name }}? Keywords will be unassigned but not deleted.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-500 focus:outline-none" title="Delete cluster">
                                <i data-lucide="x" class="h-3 w-3"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Keywords Table --}}
        <section class="bte-card overflow-hidden rounded-2xl">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <h3 class="font-heading text-base font-black">
                        Keywords ({{ $keywords->total() }})
                    </h3>
                    <p class="text-xs text-slate-400">Click &ldquo;Create Brief&rdquo; to launch Content Studio with pre-filled SEO context.</p>
                </div>
            </div>

            @if($keywords->isEmpty())
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                        <i data-lucide="key" class="h-7 w-7"></i>
                    </div>
                    <h4 class="mt-4 font-heading text-lg font-black">No keywords found</h4>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                        @if($searchTerm || $selectedClusterId || $selectedIntent)
                            No keywords match your current filters. Try resetting the filters or add a new keyword.
                        @else
                            Start building your SEO strategy by discovering keyword suggestions with AI or adding your seed keywords manually.
                        @endif
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <button type="button" x-on:click="showAiSuggestModal = true" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                            <i data-lucide="sparkles" class="h-4 w-4"></i> AI Keyword Discovery
                        </button>
                        <button type="button" x-on:click="showAddKeywordModal = true" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <i data-lucide="plus" class="h-4 w-4"></i> Add Manually
                        </button>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                            <tr>
                                <th class="w-10 px-4 py-3.5 text-center"></th>
                                <th class="px-4 py-3.5">Keyword</th>
                                <th class="px-4 py-3.5">Topic Cluster</th>
                                <th class="px-4 py-3.5">Search Intent</th>
                                <th class="px-4 py-3.5">Search Volume / KD</th>
                                <th class="px-4 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach($keywords as $keyword)
                                <tr class="hover:bg-slate-50/75 dark:hover:bg-slate-800/40">
                                    {{-- Star toggle --}}
                                    <td class="px-4 py-4 text-center">
                                        <form method="POST" action="{{ route('workspaces.brands.keywords.star', [$workspace, $brand, $keyword]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="focus:outline-none" title="{{ $keyword->is_starred ? 'Unstar' : 'Star' }}">
                                                <i data-lucide="star" class="h-4 w-4 {{ $keyword->is_starred ? 'fill-amber-400 text-amber-400' : 'text-slate-300 hover:text-slate-400 dark:text-slate-600' }}"></i>
                                            </button>
                                        </form>
                                    </td>

                                    {{-- Keyword --}}
                                    <td class="px-4 py-4">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $keyword->keyword }}</div>
                                        @if($keyword->notes)
                                            <p class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ $keyword->notes }}</p>
                                        @endif
                                    </td>

                                    {{-- Cluster --}}
                                    <td class="px-4 py-4">
                                        @if($keyword->topicCluster)
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                                <i data-lucide="folder" class="h-3 w-3"></i>
                                                {{ $keyword->topicCluster->name }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">Unclustered</span>
                                        @endif
                                    </td>

                                    {{-- Search Intent --}}
                                    <td class="px-4 py-4">
                                        @if($keyword->search_intent)
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $keyword->search_intent->badgeClass() }}">
                                                {{ $keyword->search_intent->label() }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">&mdash;</span>
                                        @endif
                                    </td>

                                    {{-- Metrics --}}
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400" title="External SEO metric provider is not configured. Volume & KD remain unverified.">
                                                <i data-lucide="info" class="h-3 w-3"></i>
                                                Unverified
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('content-studio.drafts.create', [$workspace, $brand, 'primary_keyword' => $keyword->keyword, 'topic' => $keyword->topicCluster?->name ?? $keyword->keyword]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900" title="Create Content Studio brief with this keyword">
                                                <i data-lucide="file-plus-2" class="h-3.5 w-3.5"></i>
                                                Create Brief
                                            </a>
                                            <form method="POST" action="{{ route('workspaces.brands.keywords.destroy', [$workspace, $brand, $keyword]) }}" onsubmit="return confirm('Remove keyword {{ $keyword->keyword }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/50 dark:hover:text-rose-400" title="Delete keyword">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                    {{ $keywords->links() }}
                </div>
            @endif
        </section>

        {{-- Modal: Add Keyword Manually --}}
        <div x-cloak x-show="showAddKeywordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60" x-transition.opacity>
            <div x-on:click.outside="showAddKeywordModal = false" class="bte-card w-full max-w-md rounded-2xl p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="font-heading text-lg font-black">Add Keyword</h3>
                    <button type="button" x-on:click="showAddKeywordModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('workspaces.brands.keywords.store', [$workspace, $brand]) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="modal_keyword" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Keyword *</label>
                        <input type="text" id="modal_keyword" name="keyword" required maxlength="191" placeholder="e.g. ai seo content strategy" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                    </div>
                    <div>
                        <label for="modal_cluster" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Topic Cluster</label>
                        <select id="modal_cluster" name="brand_topic_cluster_id" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                            <option value="">None (Unclustered)</option>
                            @foreach($clusters as $cluster)
                                <option value="{{ $cluster->id }}">{{ $cluster->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="modal_intent" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Search Intent</label>
                        <select id="modal_intent" name="search_intent" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                            <option value="">Auto / Unspecified</option>
                            @foreach($searchIntents as $intent)
                                <option value="{{ $intent->value }}">{{ $intent->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="modal_notes" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Notes</label>
                        <textarea id="modal_notes" name="notes" rows="2" maxlength="500" placeholder="Optional context or target angle" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700"></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" x-on:click="showAddKeywordModal = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700">
                            Save Keyword
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Add Topic Cluster --}}
        <div x-cloak x-show="showAddClusterModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60" x-transition.opacity>
            <div x-on:click.outside="showAddClusterModal = false" class="bte-card w-full max-w-md rounded-2xl p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="font-heading text-lg font-black">New Topic Cluster</h3>
                    <button type="button" x-on:click="showAddClusterModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('workspaces.brands.keyword-clusters.store', [$workspace, $brand]) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="cluster_name" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Cluster Name *</label>
                        <input type="text" id="cluster_name" name="name" required maxlength="120" placeholder="e.g. Programmatic SEO Guides" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                    </div>
                    <div>
                        <label for="pillar_keyword" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Pillar Keyword (Optional)</label>
                        <input type="text" id="pillar_keyword" name="pillar_keyword" maxlength="191" placeholder="e.g. programmatic seo" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                    </div>
                    <div>
                        <label for="cluster_description" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Description</label>
                        <textarea id="cluster_description" name="description" rows="2" maxlength="500" placeholder="Summary of this content silo" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700"></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" x-on:click="showAddClusterModal = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700">
                            Create Cluster
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal / Drawer: AI Keyword Discovery --}}
        <div x-cloak x-show="showAiSuggestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60" x-transition.opacity>
            <div x-on:click.outside="showAiSuggestModal = false" class="bte-card w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl p-6 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">
                            <i data-lucide="sparkles" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h3 class="font-heading text-lg font-black">AI Keyword &amp; Cluster Discovery</h3>
                            <p class="text-xs text-slate-500">Powered by Gemini 2.5 Flash via registered route</p>
                        </div>
                    </div>
                    <button type="button" x-on:click="showAiSuggestModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                @if(!$aiGenerationEnabled)
                    <div class="mt-5 rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/40">
                        <div class="flex items-start gap-3">
                            <i data-lucide="shield-alert" class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
                            <div>
                                <h4 class="text-sm font-black text-amber-900 dark:text-amber-200">AI Generation Gate Inactive</h4>
                                <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">
                                    System policy safety gate (<code class="font-mono">AI_CONTENT_GENERATION_ENABLED=false</code>) is currently active.
                                    AI keyword generation calls to external LLMs are prevented until live AI activation is enabled by administrators.
                                </p>
                                <p class="mt-2 text-xs font-semibold text-amber-800 dark:text-amber-300">
                                    You can still create, organize, star, and link topic clusters and keywords manually using the manual controls.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('workspaces.brands.keywords.ai-suggestions', [$workspace, $brand]) }}" x-on:submit.prevent="runAiSuggestions($event)" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="ai_seed_keyword" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Seed Keyword / Topic *</label>
                                <input type="text" id="ai_seed_keyword" name="seed_keyword" required maxlength="191" placeholder="e.g. programmatic seo for saas" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                            </div>
                            <div>
                                <label for="ai_niche" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Niche / Industry</label>
                                <input type="text" id="ai_niche" name="niche" value="{{ $brand->industry }}" maxlength="120" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                            </div>
                            <div>
                                <label for="ai_country" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Target Country / Market</label>
                                <input type="text" id="ai_country" name="country" value="{{ $brand->primary_market }}" maxlength="100" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                            </div>
                            <div>
                                <label for="ai_language" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Language</label>
                                <input type="text" id="ai_language" name="language" value="{{ $brand->preferred_language }}" maxlength="50" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                            </div>
                            <div>
                                <label for="ai_count" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Max Suggestions</label>
                                <input type="number" id="ai_count" name="count" value="15" min="3" max="30" class="bte-surface w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="submit" x-bind:disabled="aiLoading" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-black text-white hover:from-indigo-700 hover:to-violet-700 disabled:opacity-50">
                                <span x-show="!aiLoading" class="flex items-center gap-2">
                                    <i data-lucide="sparkles" class="h-4 w-4"></i> Generate Suggestions
                                </span>
                                <span x-show="aiLoading" class="flex items-center gap-2" style="display: none;">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Analyzing Semantic Groups...
                                </span>
                            </button>
                        </div>
                    </form>

                    {{-- AI Error state --}}
                    <div x-cloak x-show="aiError" class="mt-4 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                        <p class="font-bold">Generation Notice</p>
                        <p class="mt-1" x-text="aiError"></p>
                    </div>

                    {{-- AI Results Review & Import --}}
                    <div x-cloak x-show="aiSuggestions.length > 0" class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-800">
                        <form method="POST" action="{{ route('workspaces.brands.keywords.import-suggestions', [$workspace, $brand]) }}">
                            @csrf
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-heading text-sm font-black">
                                    Found <span x-text="aiSuggestions.length"></span> Suggested Keywords
                                </h4>
                                <span class="text-xs text-slate-500">Select keywords to import into Brand</span>
                            </div>

                            <div class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100 dark:border-slate-800 dark:divide-slate-800">
                                <template x-for="(sug, index) in aiSuggestions" :key="index">
                                    <div class="flex items-center justify-between p-3 text-xs" :class="sug.is_duplicate ? 'bg-slate-50 opacity-60 dark:bg-slate-900' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" :name="`keywords[${index}][selected]`" value="1" :disabled="sug.is_duplicate" :checked="!sug.is_duplicate" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            <input type="hidden" :name="`keywords[${index}][keyword]`" :value="sug.keyword">
                                            <input type="hidden" :name="`keywords[${index}][cluster_name]`" :value="sug.cluster_name">
                                            <input type="hidden" :name="`keywords[${index}][search_intent]`" :value="sug.search_intent">
                                            <div>
                                                <span class="font-bold text-slate-900 dark:text-white" x-text="sug.keyword"></span>
                                                <span x-show="sug.is_duplicate" class="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">Existing</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="rounded bg-indigo-50 px-2 py-0.5 font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300" x-text="sug.cluster_name"></span>
                                            <span class="rounded bg-slate-100 px-2 py-0.5 font-bold uppercase text-[10px] text-slate-600 dark:bg-slate-800 dark:text-slate-300" x-text="sug.search_intent"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4 flex items-center justify-end gap-3">
                                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-black text-white hover:bg-indigo-700">
                                    Import Selected Keywords
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

