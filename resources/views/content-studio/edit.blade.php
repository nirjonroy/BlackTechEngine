<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">{{ $brand->name }}</p><h1 class="font-heading text-xl font-black sm:text-2xl">Edit content draft</h1></div></x-slot>
    <div class="mx-auto max-w-6xl">@include('content-studio.partials.form', ['action' => route('content-studio.drafts.update', [$workspace, $brand, $aiContentDraft]), 'method' => 'PATCH', 'mode' => 'edit'])</div>
</x-app-layout>
