<x-guest-layout>
    <x-slot:title>Confirm Password</x-slot:title>

    <section class="bte-card w-full max-w-lg rounded-3xl border border-slate-200 p-6 shadow-xl sm:p-10 dark:border-slate-800" aria-labelledby="confirm-password-heading">
        <div class="mb-7">
            <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-500"><i data-lucide="shield-check" class="h-6 w-6" aria-hidden="true"></i></span>
            <h1 id="confirm-password-heading" class="font-heading text-2xl font-black sm:text-3xl">Confirm It’s You</h1>
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">This is a secure area. Confirm your password before continuing.</p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5" x-data="{ submitting: false, showPassword: false }" x-on:submit="submitting = true">
            @csrf

            <div>
                <label for="password" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">Password</label>
                <div class="relative">
                    <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input id="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autofocus autocomplete="current-password" class="bte-surface w-full rounded-xl border border-slate-300 py-3 pl-10 pr-12 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" x-on:click="showPassword = ! showPassword" x-bind:aria-pressed="showPassword" x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"><i data-lucide="eye" class="h-4 w-4" x-show="! showPassword" aria-hidden="true"></i><i data-lucide="eye-off" class="h-4 w-4" x-show="showPassword" x-cloak aria-hidden="true"></i></button>
                </div>
                @error('password')<p id="password-error" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-[#131C31]" x-bind:disabled="submitting"><i data-lucide="loader-circle" class="h-4 w-4 animate-spin" x-show="submitting" x-cloak aria-hidden="true"></i><span x-text="submitting ? 'Confirming…' : 'Confirm Password'">Confirm Password</span></button>
        </form>
    </section>
</x-guest-layout>
