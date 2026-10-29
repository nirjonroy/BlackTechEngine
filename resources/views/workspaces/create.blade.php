<x-app-layout>
    <x-slot name="header"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">{{ $onboarding ? 'Onboarding' : 'Workspace settings' }}</p><h1 class="font-heading text-xl font-black sm:text-2xl">Create a workspace</h1></div></x-slot>
    <div class="mx-auto max-w-2xl">
        @if ($onboarding) <x-customer.onboarding-progress :current="1" /> @endif
        <section class="bte-card rounded-3xl p-5 sm:p-8">
            <h2 class="font-heading text-2xl font-black">Give your work a secure home</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">Workspaces isolate brands and future content workflows. You can add team members in a later phase.</p>
            <div class="mt-7">@include('workspaces.partials.form', ['action' => $onboarding ? route('onboarding.workspace.store') : route('workspaces.store'), 'method' => 'POST', 'submitLabel' => $onboarding ? 'Create & add brand' : 'Create workspace'])</div>
        </section>
    </div>
</x-app-layout>
