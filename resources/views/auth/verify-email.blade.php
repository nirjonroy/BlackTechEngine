<x-guest-layout>
    <x-slot:title>Verify Email</x-slot:title>

    <section class="bte-card w-full max-w-lg rounded-3xl border border-slate-200 p-6 text-center shadow-xl sm:p-10 dark:border-slate-800" aria-labelledby="verify-email-heading">
        <span class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500"><i data-lucide="mail-check" class="h-7 w-7" aria-hidden="true"></i></span>
        <h1 id="verify-email-heading" class="font-heading text-2xl font-black sm:text-3xl">Check Your Inbox</h1>
        <p class="mt-3 text-xs leading-5 text-slate-500 dark:text-slate-400">Before getting started, verify your email address using the link we sent you. If it did not arrive, you can request another.</p>

        @if (session('status') === 'verification-link-sent')
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300" role="status">A new verification link has been sent to your email address.</div>
        @endif

        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
            <form method="POST" action="{{ route('verification.send') }}" class="flex-1" x-data="{ submitting: false }" x-on:submit="submitting = true">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-[#131C31]" x-bind:disabled="submitting"><i data-lucide="loader-circle" class="h-4 w-4 animate-spin" x-show="submitting" x-cloak aria-hidden="true"></i><span x-text="submitting ? 'Sending…' : 'Resend Verification Email'">Resend Verification Email</span></button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-slate-300 px-5 py-3.5 text-sm font-bold text-slate-700 hover:border-indigo-500 hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:text-slate-200">Log Out</button>
            </form>
        </div>
    </section>
</x-guest-layout>
