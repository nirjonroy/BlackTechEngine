<x-guest-layout>
    <x-slot:title>Sign In</x-slot:title>
    <x-slot:secondaryUrl>{{ route('register') }}</x-slot:secondaryUrl>
    <x-slot:secondaryPrompt>Need an account?</x-slot:secondaryPrompt>
    <x-slot:secondaryText>Sign Up Free</x-slot:secondaryText>

    <div class="grid w-full max-w-5xl grid-cols-1 items-center gap-8 lg:grid-cols-12 lg:gap-12">
        <section class="bte-card rounded-3xl border border-slate-200 p-6 shadow-xl sm:p-10 lg:col-span-6 dark:border-slate-800" aria-labelledby="login-heading">
            <div class="mb-8">
                <p class="mb-2 flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.18em] text-indigo-500">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Secure account access
                </p>
                <h1 id="login-heading" class="font-heading text-3xl font-black tracking-tight text-slate-900 dark:text-white">Welcome Back</h1>
                <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">Sign in to manage your autopilot SEO schedule and Brand DNA.</p>
            </div>

            @if (session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ submitting: false }" x-on:submit="submitting = true">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="bte-surface w-full rounded-xl border border-slate-300 py-3 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    </div>
                    @error('email')
                        <p id="email-error" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div x-data="{ showPassword: false }">
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="rounded text-[11px] font-semibold text-indigo-500 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Forgot password?</a>
                        @endif
                    </div>
                    <div class="relative">
                        <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                        <input id="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" class="bte-surface w-full rounded-xl border border-slate-300 py-3 pl-10 pr-12 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 transition-colors hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" x-on:click="showPassword = ! showPassword" x-bind:aria-pressed="showPassword" x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'">
                            <i data-lucide="eye" class="h-4 w-4" x-show="! showPassword" aria-hidden="true"></i>
                            <i data-lucide="eye-off" class="h-4 w-4" x-show="showPassword" x-cloak aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')
                        <p id="password-error" class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <label for="remember" class="flex w-fit cursor-pointer items-center gap-2 text-xs">
                    <input id="remember" type="checkbox" name="remember" class="rounded border-slate-400 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800">
                    <span class="text-slate-600 dark:text-slate-400">Keep me logged in</span>
                </label>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition-all hover:scale-[1.01] focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70 motion-reduce:transform-none dark:focus-visible:ring-offset-[#131C31]" x-bind:disabled="submitting">
                    <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" x-show="submitting" x-cloak aria-hidden="true"></i>
                    <span x-text="submitting ? 'Signing In…' : 'Sign In to Dashboard'">Sign In to Dashboard</span>
                </button>
            </form>
        </section>

        <aside class="hidden space-y-6 lg:col-span-6 lg:block" aria-label="Account benefits">
            <div class="bte-card glow-indigo rounded-3xl border border-slate-200 p-6 dark:border-slate-800">
                <div class="mb-5 flex items-center justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="h-3 w-3 animate-pulse rounded-full bg-emerald-400 motion-reduce:animate-none"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Engine ready</span>
                    </div>
                    <span class="rounded-full bg-indigo-500/10 px-2.5 py-1 text-[10px] font-bold text-indigo-500">Secure session</span>
                </div>
                <div class="space-y-3 text-xs">
                    <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <i data-lucide="calendar-check" class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" aria-hidden="true"></i>
                        <div><div class="font-bold text-slate-800 dark:text-slate-200">Publishing schedule</div><div class="mt-1 leading-5 text-slate-500 dark:text-slate-400">Return to your planned articles, distribution channels, and content calendar.</div></div>
                    </div>
                    <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <i data-lucide="fingerprint" class="mt-0.5 h-5 w-5 shrink-0 text-cyan-500" aria-hidden="true"></i>
                        <div><div class="font-bold text-slate-800 dark:text-slate-200">Brand DNA</div><div class="mt-1 leading-5 text-slate-500 dark:text-slate-400">Keep your voice, audience, and search strategy together in one workspace.</div></div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-indigo-500/20 bg-gradient-to-br from-indigo-950 to-slate-900 p-6 text-white">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-500/20 text-indigo-300"><i data-lucide="shield-check" class="h-5 w-5" aria-hidden="true"></i></div>
                <h2 class="font-heading text-lg font-bold">Your authentication stays protected</h2>
                <p class="mt-2 text-xs leading-5 text-slate-300">Rate-limited sign-in, CSRF protection, and regenerated sessions work together behind this interface.</p>
            </div>
        </aside>
    </div>
</x-guest-layout>
