<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-500">Customer studio</p>
                <h1 class="font-heading text-xl font-black sm:text-2xl">Social content</h1>
            </div>
            <a href="{{ route('social-content.calendar', request()->only(['workspace', 'brand', 'platform', 'status'])) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700">Calendar</a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="bte-card rounded-2xl p-4 sm:p-5">
            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <select name="workspace" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    <option value="">Workspace</option>
                    @foreach ($workspaces as $item)
                        <option value="{{ $item->id }}" @selected($selectedWorkspace?->is($item))>{{ $item->name }}</option>
                    @endforeach
                </select>
                <select name="brand" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    <option value="">All brands</option>
                    @foreach ($selectedWorkspace?->brands ?? [] as $item)
                        <option value="{{ $item->id }}" @selected($selectedBrand?->is($item))>{{ $item->name }}</option>
                    @endforeach
                </select>
                <select name="platform" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    <option value="">All platforms</option>
                    @foreach ($platforms as $platform)
                        <option value="{{ $platform->value }}" @selected(request('platform') === $platform->value)>{{ $platform->label() }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-900">Filter</button>
            </form>
        </section>

        @if ($selectedWorkspace && $selectedWorkspace->brands->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($selectedWorkspace->brands as $item)
                    <a href="{{ route('social-content.posts.create', [$selectedWorkspace, $item]) }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white">Create for {{ $item->name }}</a>
                @endforeach
            </div>
        @endif

        @if ($posts->isEmpty())
            <section class="bte-card rounded-2xl border-dashed p-8 text-center">
                <i data-lucide="calendar-plus" class="mx-auto h-8 w-8 text-slate-400"></i>
                <h2 class="mt-3 font-heading text-xl font-black">No social drafts match these filters</h2>
                <p class="mt-2 text-sm text-slate-500">Adjust the filters or create platform-specific copy for manual publishing.</p>
            </section>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ route('social-content.posts.show', [$post->workspace_id, $post->brand_id, $post]) }}" class="bte-card rounded-2xl p-5 transition hover:border-indigo-400">
                        <div class="flex items-start justify-between gap-3">
                            <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-black uppercase text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ $post->status->label() }}</span>
                            <span class="text-xs text-slate-500">{{ $post->variants->count() }} variants</span>
                        </div>
                        <h2 class="mt-4 font-heading text-lg font-black">{{ $post->title }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $post->brand->name }}@if ($post->campaign) · {{ $post->campaign }}@endif</p>
                        @if ($post->planned_at)
                            <p class="mt-3 text-xs font-bold text-indigo-600">Planned {{ $post->planned_at->utc()->format('M j, Y H:i') }} UTC</p>
                        @endif
                    </a>
                @endforeach
            </div>
            {{ $posts->links() }}
        @endif
    </div>
</x-app-layout>
