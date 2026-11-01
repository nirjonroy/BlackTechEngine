@php($nextState = $enabled ? 'disabled' : 'enabled')

<div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Operational state</p>
            <p class="mt-1 text-sm font-black {{ $enabled ? 'text-emerald-600 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-300' }}">{{ $enabled ? 'Enabled' : 'Disabled' }}</p>
        </div>
        <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $enabled ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-slate-500/10 text-slate-600 dark:text-slate-300' }}">Version {{ $lockVersion }}</span>
    </div>

    <form method="POST" action="{{ $action }}" class="mt-4 flex flex-col gap-3">
        @csrf
        @method('PATCH')
        <input type="hidden" name="state" value="{{ $nextState }}">
        <input type="hidden" name="lock_version" value="{{ $lockVersion }}">
        <label class="flex items-start gap-3 text-xs leading-5 text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="confirm" value="1" required class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900">
            <span>I confirm this {{ $subjectLabel }} should be {{ $nextState }}. Activation is allowed only while customer generation is disabled.</span>
        </label>
        <button type="submit" class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-black text-white focus:outline-none focus-visible:ring-2 {{ $enabled ? 'bg-rose-600 hover:bg-rose-500 focus-visible:ring-rose-500' : 'bg-emerald-600 hover:bg-emerald-500 focus-visible:ring-emerald-500' }}">
            {{ $enabled ? 'Safely deactivate' : 'Approve and activate' }}
        </button>
    </form>
</div>
