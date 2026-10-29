<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">Customer studio</p><h1 class="font-heading text-xl font-black sm:text-2xl">Workspaces &amp; brands</h1></div></x-slot>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3"><p class="max-w-2xl text-sm text-slate-600 dark:text-slate-400">Keep brand context separated and choose which workspace is active in your dashboard.</p><a href="{{ route('workspaces.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i> New workspace</a></div>
        @if ($workspaces->isEmpty())
            <section class="bte-card rounded-2xl border-dashed p-8 text-center"><h2 class="font-heading text-xl font-black">No workspaces yet</h2><p class="mt-2 text-sm text-slate-500">Start onboarding to create your first workspace and brand.</p><a href="{{ route('onboarding.start') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">Start onboarding</a></section>
        @else
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($workspaces as $workspace)
                    <article class="bte-card bte-card-hover rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300"><i data-lucide="panels-top-left" class="h-5 w-5" aria-hidden="true"></i></span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $workspace->brands_count }} {{ str('brand')->plural($workspace->brands_count) }}</span></div>
                        <h2 class="mt-4 truncate font-heading text-xl font-black">{{ $workspace->name }}</h2><p class="mt-1 text-xs text-slate-500">{{ $workspace->settings['timezone'] ?? 'UTC' }}</p>
                        <div class="mt-5 flex flex-wrap gap-2"><a href="{{ route('workspaces.brands.index', $workspace) }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white">Manage brands</a><a href="{{ route('workspaces.edit', $workspace) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Settings</a></div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
