<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-500">Manual publishing plan</p>
                <h1 class="font-heading text-xl font-black sm:text-2xl">Social calendar</h1>
            </div>
            <a href="{{ route('social-content.index', request()->only(['workspace', 'brand', 'platform', 'status'])) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700">All posts</a>
        </div>
    </x-slot>

    <div class="space-y-5">
        <section class="bte-card rounded-2xl p-4">
            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                <select name="workspace" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    @foreach ($workspaces as $item)
                        <option value="{{ $item->id }}" @selected($workspace?->is($item))>{{ $item->name }}</option>
                    @endforeach
                </select>
                <select name="brand" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    <option value="">All brands</option>
                    @foreach ($workspace?->brands ?? [] as $item)
                        <option value="{{ $item->id }}" @selected($brand?->is($item))>{{ $item->name }}</option>
                    @endforeach
                </select>
                <select name="platform" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    <option value="">All platforms</option>
                    @foreach ($platforms as $platform)
                        <option value="{{ $platform->value }}" @selected(request('platform') === $platform->value)>{{ $platform->label() }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    <option value="">Planned and published</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                <select name="view" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                    @foreach (['month', 'week', 'list'] as $mode)
                        <option value="{{ $mode }}" @selected($view === $mode)>{{ ucfirst($mode) }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-indigo-600 px-4 py-2 font-bold text-white">Update</button>
            </form>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs">
                <p class="text-slate-500">Times shown in {{ $timezone }}. Planning never publishes automatically.</p>
                <nav class="flex gap-2" aria-label="Calendar date navigation">
                    <a href="{{ route('social-content.calendar', array_merge(request()->query(), ['date' => match ($view) { 'week' => $date->subWeek()->format('Y-m-d'), 'list' => $date->subDays(30)->format('Y-m-d'), default => $date->subMonthNoOverflow()->format('Y-m-d') }])) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 font-bold dark:border-slate-700">Previous</a>
                    <a href="{{ route('social-content.calendar', array_merge(request()->query(), ['date' => match ($view) { 'week' => $date->addWeek()->format('Y-m-d'), 'list' => $date->addDays(30)->format('Y-m-d'), default => $date->addMonthNoOverflow()->format('Y-m-d') }])) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 font-bold dark:border-slate-700">Next</a>
                </nav>
            </div>
        </section>

        @if ($posts->isEmpty())
            <section class="bte-card rounded-2xl border-dashed p-8 text-center">
                <h2 class="font-heading text-xl font-black">No social posts match this range and filters</h2>
            </section>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ route('social-content.posts.show', [$post->workspace_id, $post->brand_id, $post]) }}" class="bte-card rounded-2xl p-4">
                        <p class="text-xs font-bold uppercase text-indigo-600">{{ $post->planned_at->utc()->timezone($timezone)->format('D, M j · H:i') }}</p>
                        <h2 class="mt-2 font-heading font-black">{{ $post->title }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $post->brand->name }} · {{ $post->variants->count() }} platforms</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
