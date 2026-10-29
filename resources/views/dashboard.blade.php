<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">Customer studio</p>
            <h1 class="truncate font-heading text-xl font-black sm:text-2xl">Dashboard</h1>
        </div>
    </x-slot>

    @if (! config('customer.workspaces_enabled'))
        <section class="bte-card rounded-2xl p-6">
            <h2 class="font-heading text-xl font-bold">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Your customer workspace is being prepared. Your account and profile settings remain available.</p>
            <a href="{{ route('profile.edit') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                Account settings <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
            </a>
        </section>
    @else
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-cyan-600 p-6 text-white shadow-2xl shadow-indigo-600/20 sm:p-8">
                <div class="relative z-10 max-w-3xl">
                    <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-100">Build trusted brand context first</p>
                    <h2 class="mt-3 font-heading text-3xl font-black sm:text-4xl">Welcome back, {{ auth()->user()->name }}</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-indigo-50 sm:text-base">Organize each brand in a secure workspace now. Content generation, approval, and publishing will be connected in later phases.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        @if ($activeWorkspace)
                            <a href="{{ route('workspaces.brands.create', $activeWorkspace) }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-black text-indigo-700 shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                <i data-lucide="badge-plus" class="h-4 w-4" aria-hidden="true"></i> Add a brand
                            </a>
                        @else
                            <a href="{{ route('onboarding.start') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-black text-indigo-700 shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                Start onboarding <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                            </a>
                        @endif
                        <a href="{{ route('workspaces.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">Manage workspaces</a>
                    </div>
                </div>
                <div class="absolute -right-16 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            </section>

            @if (! $activeWorkspace)
                <section class="bte-card rounded-2xl border-dashed border-indigo-300 p-6 text-center dark:border-indigo-800" aria-labelledby="empty-workspace-heading">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300"><i data-lucide="panels-top-left" class="h-6 w-6" aria-hidden="true"></i></span>
                    <h2 id="empty-workspace-heading" class="mt-4 font-heading text-xl font-bold">Create your first workspace</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-slate-600 dark:text-slate-400">A workspace keeps each customer, brand, and future content workflow isolated.</p>
                    <a href="{{ route('onboarding.start') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Continue onboarding</a>
                </section>
            @else
                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Real account usage overview">
                    @foreach ([
                        ['label' => 'Accessible workspaces', 'value' => $workspaces->count(), 'icon' => 'blocks'],
                        ['label' => 'Brands in active workspace', 'value' => $activeWorkspace->brands_count, 'icon' => 'badge-check'],
                        ['label' => 'Active brand profiles', 'value' => $activeWorkspace->active_brands_count, 'icon' => 'circle-check-big'],
                    ] as $metric)
                        <article class="bte-card rounded-2xl p-5">
                            <div class="flex items-center justify-between gap-3"><p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $metric['label'] }}</p><i data-lucide="{{ $metric['icon'] }}" class="h-5 w-5 text-indigo-500" aria-hidden="true"></i></div>
                            <p class="mt-3 font-heading text-3xl font-black">{{ $metric['value'] }}</p>
                        </article>
                    @endforeach
                    <article class="bte-card rounded-2xl p-5">
                        <div class="flex items-center justify-between gap-3"><p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Generated drafts</p><i data-lucide="file-clock" class="h-5 w-5 text-slate-400" aria-hidden="true"></i></div>
                        <p class="mt-3 text-sm font-bold text-slate-600 dark:text-slate-300">Not connected yet</p>
                    </article>
                </section>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <section class="bte-card rounded-2xl p-5 sm:p-6 xl:col-span-2" aria-labelledby="brands-heading">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-indigo-500">{{ $activeWorkspace->name }}</p><h2 id="brands-heading" class="mt-1 font-heading text-xl font-black">Brand profiles</h2></div>
                            <a href="{{ route('workspaces.brands.index', $activeWorkspace) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300">View all</a>
                        </div>
                        @if ($recentBrands->isEmpty())
                            <div class="mt-5 rounded-2xl border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
                                <p class="font-bold">No brand profiles yet</p><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Add a trusted brand profile before future content workflows are enabled.</p>
                            </div>
                        @else
                            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($recentBrands as $brand)
                                    <a href="{{ route('workspaces.brands.show', [$activeWorkspace, $brand]) }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-indigo-400 hover:bg-indigo-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:hover:border-indigo-600 dark:hover:bg-indigo-950/30">
                                        <div class="flex items-start justify-between gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300"><i data-lucide="badge" class="h-5 w-5" aria-hidden="true"></i></span><span class="rounded-full px-2 py-1 text-[10px] font-extrabold uppercase {{ $brand->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span></div>
                                        <h3 class="mt-4 truncate font-bold">{{ $brand->name }}</h3><p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $brand->industry }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="bte-card rounded-2xl p-5 sm:p-6" aria-labelledby="quick-start-heading">
                        <h2 id="quick-start-heading" class="font-heading text-xl font-black">Quick start</h2>
                        <ol class="mt-5 space-y-4">
                            @foreach ([
                                ['done' => true, 'label' => 'Create your account'],
                                ['done' => true, 'label' => 'Create a workspace'],
                                ['done' => $activeWorkspace->brands_count > 0, 'label' => 'Add a brand profile'],
                                ['done' => false, 'label' => 'Review brand context'],
                            ] as $step)
                                <li class="flex items-center gap-3 text-sm font-semibold"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $step['done'] ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-400 dark:bg-slate-800' }}"><i data-lucide="{{ $step['done'] ? 'check' : 'circle' }}" class="h-4 w-4" aria-hidden="true"></i></span>{{ $step['label'] }}</li>
                            @endforeach
                        </ol>
                    </section>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section class="bte-card rounded-2xl p-5 sm:p-6" aria-labelledby="workflow-heading">
                        <h2 id="workflow-heading" class="font-heading text-xl font-black">Content workflow</h2>
                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach (['Brand context', 'Briefs', 'Drafts', 'Publishing'] as $index => $stage)
                                <div class="rounded-xl border border-slate-200 p-3 text-center dark:border-slate-700"><span class="mx-auto flex h-7 w-7 items-center justify-center rounded-full text-xs font-black {{ $index === 0 ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">{{ $index + 1 }}</span><p class="mt-2 text-xs font-bold">{{ $stage }}</p><p class="mt-1 text-[10px] text-slate-500">{{ $index === 0 ? 'Available' : 'Later phase' }}</p></div>
                            @endforeach
                        </div>
                    </section>
                    <section class="bte-card rounded-2xl p-5 sm:p-6" aria-labelledby="drafts-heading">
                        <h2 id="drafts-heading" class="font-heading text-xl font-black">Recent drafts</h2>
                        <div class="mt-5 rounded-xl border border-dashed border-slate-300 p-5 text-center dark:border-slate-700"><i data-lucide="file-text" class="mx-auto h-6 w-6 text-slate-400" aria-hidden="true"></i><p class="mt-3 text-sm font-bold">No drafts are available</p><p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Draft creation has not been implemented, so no generated content is shown here.</p></div>
                    </section>
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
