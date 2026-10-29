<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">Workspace settings</p><h1 class="font-heading text-xl font-black sm:text-2xl">Edit {{ $workspace->name }}</h1></div></x-slot>
    <section class="bte-card mx-auto max-w-2xl rounded-3xl p-5 sm:p-8">
        @include('workspaces.partials.form', ['action' => route('workspaces.update', $workspace), 'method' => 'PATCH', 'submitLabel' => 'Save settings', 'onboarding' => false])
    </section>
</x-app-layout>
