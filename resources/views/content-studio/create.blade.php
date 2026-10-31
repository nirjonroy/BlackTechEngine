<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">{{ $workspace->name }} · {{ $brand->name }}</p><h1 class="font-heading text-xl font-black sm:text-2xl">Create content draft</h1></div></x-slot>
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200"><strong>Human review required.</strong> AI output may be inaccurate and is never published automatically.</div>
        @include('content-studio.partials.form', ['action' => route('content-studio.drafts.store', [$workspace, $brand]), 'method' => 'POST', 'mode' => 'create', 'aiContentDraft' => null])
    </div>
</x-app-layout>
