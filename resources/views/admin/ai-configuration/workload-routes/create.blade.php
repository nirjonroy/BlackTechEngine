<x-admin.layout title="Create AI Workload Draft" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'AI Models & Routing', 'route' => 'admin.ai-configuration.index'], ['label' => 'Workload routes', 'route' => 'admin.ai-configuration.workload-routes'], ['label' => 'Create draft']]">
    @include('admin.ai-configuration.partials.header')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-8" aria-labelledby="create-draft-heading">
            <h2 id="create-draft-heading" class="font-heading text-lg font-bold">Create workload draft</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Define a future workload identifier and optional persisted model assignments.</p>
            <div class="mt-6">@include('admin.ai-configuration.workload-routes.partials.form', ['action' => route('admin.ai-configuration.workload-routes.store'), 'method' => 'POST', 'submitLabel' => 'Save disabled draft'])</div>
        </section>
        <aside class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-4">
            <i data-lucide="shield-check" class="h-6 w-6 text-amber-500" aria-hidden="true"></i>
            <h2 class="mt-4 font-heading text-lg font-bold">Draft-only boundary</h2>
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">Every new record is forced disabled. This interface has no activation, execution, credential, health-check, billing, or deletion controls.</p>
        </aside>
    </div>
</x-admin.layout>
