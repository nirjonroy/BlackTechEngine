<x-admin.layout title="User Details" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'Registered Users', 'route' => 'admin.users.index'], ['label' => 'User #'.$user->id]]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Read-only account record</p>
            <h1 class="mt-1 truncate font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $user->name }}</h1>
            <p class="mt-1 font-mono text-xs text-slate-500 dark:text-slate-400">Registered user ID #{{ $user->id }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if(auth('admin')->user()?->can('edit users'))
                <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex w-fit items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                    Edit Profile
                </a>
            @endif
            <a href="{{ route('admin.users.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300">
                <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                Back to users
            </a>
        </div>
    </div>

    @if(session('status') === 'user-name-updated')
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300" role="status">
            The user's name was updated successfully.
        </div>
    @elseif(session('status') === 'user-name-unchanged')
        <div class="rounded-2xl border border-cyan-500/20 bg-cyan-500/10 px-4 py-3 text-sm font-semibold text-cyan-700 dark:text-cyan-300" role="status">
            No changes were needed.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-7" aria-labelledby="account-details-heading">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-5 dark:border-slate-800">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-500/10 font-heading text-sm font-black text-indigo-600 dark:text-indigo-400">{{ Str::upper(Str::substr($user->name, 0, 2)) }}</span>
                <div class="min-w-0">
                    <h2 id="account-details-heading" class="truncate font-heading text-lg font-bold">Account details</h2>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                @foreach([
                    ['label' => 'User ID', 'value' => '#'.$user->id],
                    ['label' => 'Name', 'value' => $user->name],
                    ['label' => 'Email address', 'value' => $user->email],
                    ['label' => 'Email status', 'value' => $user->email_verified_at ? 'Verified' : 'Awaiting verification'],
                    ['label' => 'Registered', 'value' => $user->created_at?->format('M j, Y, g:i A') ?? 'Unavailable'],
                    ['label' => 'Last updated', 'value' => $user->updated_at?->format('M j, Y, g:i A') ?? 'Unavailable'],
                ] as $detail)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $detail['label'] }}</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $detail['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <aside class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-5" aria-labelledby="account-scope-heading">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <i data-lucide="database-zap" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <h2 id="account-scope-heading" class="mt-4 font-heading text-lg font-bold">Current data scope</h2>
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">The application currently stores identity and email-verification data for registered users. Workspace membership, subscription plan, billing, quotas, usage telemetry, account status, and last-login activity are not available.</p>
            <div class="mt-5 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs leading-5 text-amber-700 dark:text-amber-300">
                No edit, delete, suspend, impersonate, or role-management actions are exposed on this screen.
            </div>
        </aside>
    </div>
</x-admin.layout>
