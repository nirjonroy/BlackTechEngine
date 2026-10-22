<x-guest-layout>
    <x-slot:title>Create Account</x-slot:title>
    <x-slot:secondaryUrl>{{ route('login') }}</x-slot:secondaryUrl>
    <x-slot:secondaryPrompt>Already have an account?</x-slot:secondaryPrompt>
    <x-slot:secondaryText>Sign In</x-slot:secondaryText>

    <div class="w-full max-w-3xl">
        <div class="mb-7" aria-label="Account setup progress">
            <div class="mb-3 flex items-center justify-between text-xs font-bold">
                <span class="flex items-center gap-1.5 text-indigo-500"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] text-white">1</span> Account</span>
                <span class="flex items-center gap-1.5 text-slate-400"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-300 text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">2</span> Verify Email</span>
                <span class="flex items-center gap-1.5 text-slate-400"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-300 text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">3</span> Launch</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"><div class="h-full w-1/3 rounded-full bg-gradient-to-r from-indigo-500 to-cyan-400"></div></div>
        </div>

        <section class="bte-card rounded-3xl border border-slate-200 p-6 shadow-2xl sm:p-10 dark:border-slate-800" aria-labelledby="register-heading">
            <div class="mb-7">
                <h1 id="register-heading" class="font-heading text-2xl font-black sm:text-3xl">Create Your Free Autopilot Account</h1>
                <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">Set up secure access to your BlackTechEngine workspace.</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
                @csrf

                <div>
                    <label for="name" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">Full Name</label>
                    <div class="relative">
                        <i data-lucide="user" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="bte-surface w-full rounded-xl border border-slate-300 py-3 pl-10 pr-4 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    </div>
                    @error('name')<p id="name-error" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">Work Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="bte-surface w-full rounded-xl border border-slate-300 py-3 pl-10 pr-4 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    </div>
                    @error('email')<p id="email-error" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div x-data="{ showPassword: false }">
                        <label for="password" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">Choose Password</label>
                        <div class="relative">
                            <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                            <input id="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" class="bte-surface w-full rounded-xl border border-slate-300 py-3 pl-10 pr-12 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                            <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" x-on:click="showPassword = ! showPassword" x-bind:aria-pressed="showPassword" x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"><i data-lucide="eye" class="h-4 w-4" x-show="! showPassword" aria-hidden="true"></i><i data-lucide="eye-off" class="h-4 w-4" x-show="showPassword" x-cloak aria-hidden="true"></i></button>
                        </div>
                        @error('password')<p id="password-error" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div x-data="{ showPassword: false }">
                        <label for="password_confirmation" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">Confirm Password</label>
                        <div class="relative">
                            <i data-lucide="shield-check" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                            <input id="password_confirmation" type="password" x-bind:type="showPassword ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" class="bte-surface w-full rounded-xl border border-slate-300 py-3 pl-10 pr-12 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white">
                            <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" x-on:click="showPassword = ! showPassword" x-bind:aria-pressed="showPassword" x-bind:aria-label="showPassword ? 'Hide password confirmation' : 'Show password confirmation'"><i data-lucide="eye" class="h-4 w-4" x-show="! showPassword" aria-hidden="true"></i><i data-lucide="eye-off" class="h-4 w-4" x-show="showPassword" x-cloak aria-hidden="true"></i></button>
                        </div>
                        @error('password_confirmation')<p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <p class="flex items-start gap-2 rounded-xl border border-indigo-200 bg-indigo-50/80 p-3 text-[11px] leading-5 text-slate-600 dark:border-indigo-800/50 dark:bg-indigo-950/40 dark:text-slate-300"><i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500" aria-hidden="true"></i><span>Use a strong password. Your password fields are never repopulated after a validation error.</span></p>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition-all hover:scale-[1.01] focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70 motion-reduce:transform-none dark:focus-visible:ring-offset-[#131C31]" x-bind:disabled="submitting">
                    <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" x-show="submitting" x-cloak aria-hidden="true"></i>
                    <span x-text="submitting ? 'Creating Account…' : 'Create Account & Continue'">Create Account &amp; Continue</span>
                    <i data-lucide="arrow-right" class="h-4 w-4" x-show="! submitting" aria-hidden="true"></i>
                </button>
            </form>
        </section>
    </div>
</x-guest-layout>
