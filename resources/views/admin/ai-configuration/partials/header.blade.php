<div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">AI configuration foundation</p>
        <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">AI Models &amp; Workload Routing</h1>
        <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500 dark:text-slate-400">Review persisted provider, model, routing, and audit metadata. Runtime inference and credential management are not connected.</p>
    </div>
    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-700 dark:text-amber-300">
        <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>Read only
    </span>
</div>

<div class="rounded-2xl border border-cyan-500/20 bg-cyan-500/5 p-4 text-xs leading-5 text-cyan-900 dark:text-cyan-100" role="note">
    <div class="flex items-start gap-3">
        <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
        <p><strong>Metadata only.</strong> This area does not store provider credentials, execute AI requests, estimate spend, edit prompts, or dispatch background jobs.</p>
    </div>
</div>

@include('admin.ai-configuration.partials.section-nav')
