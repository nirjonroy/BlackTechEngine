<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">{{ $workspace->name }}</p><h1 class="font-heading text-xl font-black sm:text-2xl">Edit {{ $brand->name }}</h1></div></x-slot>
    <section class="bte-card mx-auto max-w-4xl rounded-3xl p-5 sm:p-8">@include('brands.partials.form', ['action' => route('workspaces.brands.update', [$workspace, $brand]), 'method' => 'PATCH', 'submitLabel' => 'Save brand profile', 'onboarding' => false])</section>
</x-app-layout>
