<x-guest-layout>
    <x-slot:title>Reset Password</x-slot:title>
    <x-slot:secondaryUrl>{{ route('login') }}</x-slot:secondaryUrl>
    <x-slot:secondaryPrompt>Remembered your password?</x-slot:secondaryPrompt>
    <x-slot:secondaryText>Sign In</x-slot:secondaryText>

    <section class="bte-card w-full max-w-lg rounded-3xl border border-slate-200 p-6 shadow-xl sm:p-10 dark:border-slate-800" aria-labelledby="forgot-password-heading">
        <div class="mb-7">
            <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500"><i data-lucide="mail-question" class="h-6 w-6" aria-hidden="true"></i></span>
            <h1 id="forgot-password-heading" class="font-heading text-2xl font-black sm:text-3xl">Forgot Your Password?</h1>
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">Enter your account email and we will send you a secure link to choose a new password.</p>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
            @csrf

            <div>
                <label for="email" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">Email Address</label>
                <div class="relative">
                    <i data-lucide="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="bte-surface w-full rounded-xl border border-slate-300 py-3 pl-10 pr-4 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                </div>
                @error('email')<p id="email-error" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-[#131C31]" x-bind:disabled="submitting">
                <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" x-show="submitting" x-cloak aria-hidden="true"></i>
                <span x-text="submitting ? 'Sending Link…' : 'Email Password Reset Link'">Email Password Reset Link</span>
            </button>
        </form>
    </section>
</x-guest-layout>
