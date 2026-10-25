<x-admin.layout title="Create Pricing Plan" :breadcrumbs="[['label' => 'Plans & Pricing Tiers', 'url' => route('admin.pricing.index')], ['label' => 'Create Plan']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Pricing catalog</p>
            <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Create Pricing Plan</h1>
            <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500 dark:text-slate-400">Add a marketing plan for the public Pricing CMS. This does not create a subscription, payment-provider product, entitlement, or usage quota.</p>
        </div>
        <a href="{{ route('admin.pricing.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300 dark:hover:text-cyan-400">
            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
            Back to plans
        </a>
    </div>

    <form action="{{ route('admin.pricing.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('admin.pricing.partials.form-fields', ['submitLabel' => 'Create pricing plan'])
    </form>
</x-admin.layout>
