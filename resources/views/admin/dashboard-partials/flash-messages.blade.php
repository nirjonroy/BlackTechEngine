@if(session('success') || session('status'))
    <div class="flex items-start gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300" role="status">
        <i data-lucide="circle-check" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
        <p>{{ session('success') ?? session('status') }}</p>
    </div>
@endif

@if(session('error'))
    <div class="flex items-start gap-3 rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4 text-sm text-rose-700 dark:text-rose-300" role="alert">
        <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
        <p>{{ session('error') }}</p>
    </div>
@endif
