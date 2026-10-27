@php
    $statusValue = filled($status ?? null) ? (string) $status : 'unknown';
    $statusClasses = match ($statusValue) {
        'active', 'approved' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        'pending' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
        'under_review' => 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
        'suspended', 'rejected' => 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
        'withdrawn', 'closed' => 'bg-slate-500/10 text-slate-600 dark:text-slate-300',
        default => 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    };
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-bold {{ $statusClasses }}" data-status="{{ $statusValue }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ Str::headline($statusValue) }}
</span>
