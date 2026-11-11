<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">Customer Studio</p>
                <h1 class="font-heading text-xl font-black sm:text-2xl">AI Content Studio</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('content-studio.calendar', request()->only(['workspace', 'brand'])) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700">
                    <i data-lucide="calendar" class="h-4 w-4" aria-hidden="true"></i>
                    Article Calendar & Autopilot
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="bte-card rounded-2xl p-5 sm:p-6">
            <form method="GET" action="{{ route('content-studio.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_auto] xl:items-end">
                <div>
                    <label for="workspace" class="mb-2 block text-sm font-bold">Workspace</label>
                    <select id="workspace" name="workspace" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">
                        <option value="">Choose workspace</option>
                        @foreach($workspaces as $workspace)
                            <option value="{{ $workspace->id }}" @selected($selectedWorkspace?->is($workspace))>{{ $workspace->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="brand" class="mb-2 block text-sm font-bold">Brand</label>
                    <select id="brand" name="brand" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">
                        <option value="">All brands</option>
                        @foreach($selectedWorkspace?->brands ?? [] as $brand)
                            <option value="{{ $brand->id }}" @selected($selectedBrand?->is($brand))>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="mb-2 block text-sm font-bold">Editorial status</label>
                    <select id="status" name="status" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">
                        <option value="">All statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-900">Apply filters</button>
            </form>
        </section>

        @if($selectedWorkspace && $selectedWorkspace->brands->isNotEmpty())
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Drafts remain private and unpublished, including after approval or export.</p>
                <a href="{{ route('content-studio.drafts.create', [$selectedWorkspace, $selectedBrand ?? $selectedWorkspace->brands->first()]) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                    <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                    New draft
                </a>
            </div>
        @endif

        @if($workspaces->isEmpty())
            <section class="bte-card rounded-2xl border-dashed p-8 text-center">
                <h2 class="font-heading text-xl font-black">Create a workspace first</h2>
                <p class="mt-2 text-sm text-slate-500">Content drafts always belong to an authorized workspace and brand.</p>
                <a href="{{ route('onboarding.start') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black text-white">Start onboarding</a>
            </section>
        @elseif($selectedWorkspace?->brands->isEmpty())
            <section class="bte-card rounded-2xl border-dashed p-8 text-center">
                <h2 class="font-heading text-xl font-black">Add a brand profile</h2>
                <p class="mt-2 text-sm text-slate-500">Verified brand context is required before drafting content.</p>
                <a href="{{ route('workspaces.brands.create', $selectedWorkspace) }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black text-white">Add brand</a>
            </section>
        @elseif($drafts->isEmpty())
            <section class="bte-card rounded-2xl border-dashed p-8 text-center">
                <i data-lucide="file-text" class="mx-auto h-10 w-10 text-slate-400" aria-hidden="true"></i>
                <h2 class="mt-4 font-heading text-xl font-black">No matching drafts</h2>
                <p class="mt-2 text-sm text-slate-500">Create a manual brief or adjust the filters. AI generation remains separately controlled.</p>
            </section>
        @else
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($drafts as $draft)
                    <a href="{{ route('content-studio.drafts.show', [$draft->workspace_id, $draft->brand_id, $draft]) }}" class="bte-card bte-card-hover min-w-0 rounded-2xl p-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <div class="flex items-start justify-between gap-3">
                            <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[10px] font-black uppercase text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ $draft->status->label() }}</span>
                            <span class="shrink-0 text-xs text-slate-500">{{ $draft->updated_at->diffForHumans() }}</span>
                        </div>
                        <h2 class="mt-4 line-clamp-2 break-words font-heading text-lg font-black">{{ $draft->seo_title ?: $draft->topic }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ $draft->brand->name }} &middot; {{ $draft->content_type->label() }}</p>
                        <p class="mt-3 line-clamp-3 break-words text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $draft->meta_description ?: 'Brief saved; content has not been generated yet.' }}</p>
                    </a>
                @endforeach
            </div>
            <div>{{ $drafts->links() }}</div>
        @endif
    </div>
</x-app-layout>
