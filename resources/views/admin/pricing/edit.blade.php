<x-admin.layout title="Edit Pricing Plan" :breadcrumbs="[['label' => 'Plans & Pricing Tiers', 'url' => route('admin.pricing.index')], ['label' => 'Edit Plan']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Plan #{{ $plan->id }}</p>
            <h1 class="mt-1 break-words font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Edit {{ $plan->name }}</h1>
            <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500 dark:text-slate-400">Update this marketing plan and its ordered feature list. Existing subscriptions or customer billing are not represented here.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold {{ $plan->is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $plan->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                {{ $plan->is_active ? 'Active' : 'Inactive' }}
            </span>
            <a href="{{ route('admin.pricing.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300 dark:hover:text-cyan-400">
                <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                Back to plans
            </a>
        </div>
    </div>

    <form action="{{ route('admin.pricing.update', $plan) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('admin.pricing.partials.form-fields', ['submitLabel' => 'Update pricing plan'])
    </form>
</x-admin.layout>
