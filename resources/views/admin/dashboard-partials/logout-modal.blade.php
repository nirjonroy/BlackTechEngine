<div x-cloak x-show="logoutOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="admin-logout-heading">
    <button type="button" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" x-on:click="logoutOpen = false; $nextTick(() => $refs.logoutTrigger.focus())" aria-label="Close logout confirmation"></button>
    <div class="bte-card relative z-10 w-full max-w-sm rounded-3xl border border-slate-200 p-6 text-center shadow-2xl dark:border-slate-800">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-500">
            <i data-lucide="log-out" class="h-6 w-6" aria-hidden="true"></i>
        </span>
        <h2 id="admin-logout-heading" class="mt-3 font-heading text-lg font-bold">Exit Admin Session?</h2>
        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">You will need to authenticate again to access protected Admin routes.</p>
        <div class="mt-6 flex gap-2">
            <button type="button" x-ref="logoutCancel" x-on:click="logoutOpen = false; $nextTick(() => $refs.logoutTrigger.focus())" class="w-1/2 rounded-xl border border-slate-300 py-2.5 text-xs font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700">Stay signed in</button>
            <form action="{{ route('admin.logout') }}" method="POST" class="w-1/2">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-rose-600 py-2.5 text-xs font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#131C31]">Log out</button>
            </form>
        </div>
    </div>
</div>
