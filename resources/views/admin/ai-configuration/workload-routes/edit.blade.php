<x-admin.layout title="Edit AI Workload Draft" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'AI Models & Routing', 'route' => 'admin.ai-configuration.index'], ['label' => 'Workload routes', 'route' => 'admin.ai-configuration.workload-routes'], ['label' => 'Edit draft']]">
    @include('admin.ai-configuration.partials.header')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-8" aria-labelledby="edit-draft-heading">
            <h2 id="edit-draft-heading" class="font-heading text-lg font-bold">Edit workload draft</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Update the disabled configuration using optimistic concurrency protection.</p>
            <div class="mt-6">@include('admin.ai-configuration.workload-routes.partials.form', ['action' => route('admin.ai-configuration.workload-routes.update', $aiWorkloadRoute), 'method' => 'PATCH', 'submitLabel' => 'Save draft changes'])</div>
        </section>
        <aside class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-4" aria-labelledby="saved-draft-heading">
            <i data-lucide="route" class="h-6 w-6 text-indigo-500" aria-hidden="true"></i>
            <h2 id="saved-draft-heading" class="mt-4 font-heading text-lg font-bold">Saved draft configuration</h2>
            <dl class="mt-5 flex flex-col gap-4 text-xs">
                <div><dt class="font-bold uppercase tracking-wider text-slate-400">State</dt><dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">Disabled draft</dd></div>
                <div><dt class="font-bold uppercase tracking-wider text-slate-400">Version</dt><dd class="mt-1 font-mono text-slate-700 dark:text-slate-300">{{ $aiWorkloadRoute->lock_version }}</dd></div>
                <div><dt class="font-bold uppercase tracking-wider text-slate-400">Settings</dt><dd class="mt-1 text-slate-700 dark:text-slate-300">No runtime settings configured</dd></div>
            </dl>
        </aside>
    </div>
</x-admin.layout>
