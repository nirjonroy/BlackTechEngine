<x-admin.layout title="Registered Users" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'Registered Users']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Account directory</p>
            <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Registered Users</h1>
            <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400">A read-only directory of web-user accounts. Workspace, plan, billing, and usage data are not available in the current schema.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">
            <i data-lucide="users" class="h-4 w-4" aria-hidden="true"></i>
            {{ number_format($totalUsers) }} {{ Str::plural('account', $totalUsers) }}
        </span>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="User account summary">
        @foreach([
            ['label' => 'Total accounts', 'value' => $totalUsers, 'icon' => 'users', 'accent' => 'text-indigo-500'],
            ['label' => 'Verified emails', 'value' => $verifiedUsers, 'icon' => 'badge-check', 'accent' => 'text-emerald-500'],
            ['label' => 'Awaiting verification', 'value' => $unverifiedUsers, 'icon' => 'mail-warning', 'accent' => 'text-amber-500'],
        ] as $summary)
            <article class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800">
                <div class="flex items-center justify-between gap-3 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <span>{{ $summary['label'] }}</span>
                    <i data-lucide="{{ $summary['icon'] }}" class="h-4 w-4 {{ $summary['accent'] }}" aria-hidden="true"></i>
                </div>
                <p class="mt-3 font-heading text-3xl font-black text-slate-900 dark:text-white">{{ number_format($summary['value']) }}</p>
            </article>
        @endforeach
    </section>

    <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="user-directory-heading">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="user-directory-heading" class="font-heading text-lg font-bold">User directory</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Search by account ID, name, or email address.</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($users->total()) }} matching</span>
            </div>

            <form method="GET" action="{{ route('admin.users.index') }}" class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-12" role="search">
                <div class="relative lg:col-span-5">
                    <label for="user-search" class="sr-only">Search users</label>
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input id="user-search" name="search" type="search" maxlength="100" value="{{ $filters['search'] }}" placeholder="Search ID, name, or email" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </div>
                <div class="lg:col-span-3">
                    <label for="verification-filter" class="sr-only">Email verification</label>
                    <select id="verification-filter" name="verification" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        <option value="all" @selected($filters['verification'] === 'all')>All verification states</option>
                        <option value="verified" @selected($filters['verification'] === 'verified')>Verified only</option>
                        <option value="unverified" @selected($filters['verification'] === 'unverified')>Unverified only</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="sort-users" class="sr-only">Sort users</label>
                    <select id="sort-users" name="sort" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                        <option value="name" @selected($filters['sort'] === 'name')>Name A–Z</option>
                        <option value="email" @selected($filters['sort'] === 'email')>Email A–Z</option>
                        <option value="id" @selected($filters['sort'] === 'id')>Highest ID</option>
                    </select>
                </div>
                <div class="flex gap-2 lg:col-span-2">
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <i data-lucide="list-filter" class="h-4 w-4" aria-hidden="true"></i>
                        Apply
                    </button>
                    @if($filters['search'] !== '' || $filters['verification'] !== 'all' || $filters['sort'] !== 'newest' || $filters['per_page'] !== 10)
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-slate-500 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-400" aria-label="Clear user filters">
                            <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if($users->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                    <i data-lucide="user-search" class="h-6 w-6" aria-hidden="true"></i>
                </span>
                <h3 class="mt-4 font-heading text-base font-bold">No registered users found</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Try a different search or verification filter.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-7">User</th>
                            <th scope="col" class="px-5 py-3 font-bold">Email</th>
                            <th scope="col" class="px-5 py-3 font-bold">Verification</th>
                            <th scope="col" class="px-5 py-3 font-bold">Registered</th>
                            <th scope="col" class="px-5 py-3 text-right font-bold sm:px-7">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach($users as $user)
                            <tr class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                <th scope="row" class="px-5 py-4 sm:px-7">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 font-heading text-xs font-black text-indigo-600 dark:text-indigo-400">{{ Str::upper(Str::substr($user->name, 0, 2)) }}</span>
                                        <span class="min-w-0">
                                            <span class="block max-w-56 truncate font-bold text-slate-800 dark:text-slate-200">{{ $user->name }}</span>
                                            <span class="block font-mono text-[10px] text-slate-400">ID #{{ $user->id }}</span>
                                        </span>
                                    </div>
                                </th>
                                <td class="px-5 py-4"><span class="block max-w-64 truncate text-slate-600 dark:text-slate-300">{{ $user->email }}</span></td>
                                <td class="px-5 py-4">
                                    @if($user->email_verified_at)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 font-bold text-emerald-600 dark:text-emerald-400"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Verified</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 font-bold text-amber-600 dark:text-amber-400"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Pending</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">
                                    <time datetime="{{ $user->created_at?->toAtomString() }}">{{ $user->created_at?->format('M j, Y') ?? 'Unavailable' }}</time>
                                </td>
                                <td class="px-5 py-4 text-right sm:px-7">
                                    <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-bold text-indigo-600 transition hover:bg-indigo-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400">
                                        View <i data-lucide="arrow-up-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-7 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-xs text-slate-500 dark:text-slate-400">Showing {{ number_format($users->firstItem()) }}–{{ number_format($users->lastItem()) }} of {{ number_format($users->total()) }}</p>
                <div class="min-w-0">{{ $users->links() }}</div>
            </div>
        @endif
    </section>
</x-admin.layout>
