<x-admin.layout title="Affiliate Management" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'Affiliate Management']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Partner operations</p>
            <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Affiliate Management</h1>
            <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400">Review persisted affiliate accounts and their linked registered users without changing operational state.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">
            <i data-lucide="handshake" class="h-4 w-4" aria-hidden="true"></i>
            {{ number_format($totalAffiliates) }} {{ Str::plural('account', $totalAffiliates) }}
        </span>
    </div>

    <div class="rounded-2xl border border-cyan-500/20 bg-cyan-500/5 p-4 text-xs leading-5 text-cyan-800 dark:text-cyan-200" role="note">
        <div class="flex items-start gap-3">
            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true"></i>
            <p><strong>Affiliate operations are in setup.</strong> Applications and account records can be reviewed here. Enrollment, approvals, referral tracking, commissions, and payouts are not yet enabled.</p>
        </div>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Affiliate record summary">
        @foreach([
            ['label' => 'Applications', 'value' => $totalApplications, 'icon' => 'clipboard-list', 'accent' => 'text-indigo-500'],
            ['label' => 'Pending applications', 'value' => $pendingApplications, 'icon' => 'clock-3', 'accent' => 'text-amber-500'],
            ['label' => 'Affiliate accounts', 'value' => $totalAffiliates, 'icon' => 'handshake', 'accent' => 'text-cyan-500'],
            ['label' => 'Active accounts', 'value' => $activeAffiliates, 'icon' => 'badge-check', 'accent' => 'text-emerald-500'],
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

    <div class="flex flex-wrap gap-2" aria-label="Affiliate management sections">
        <a href="{{ route('admin.affiliates.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" aria-current="page">
            <i data-lucide="users-round" class="h-4 w-4" aria-hidden="true"></i>Affiliate accounts
        </a>
        <a href="{{ route('admin.affiliate-applications.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300">
            <i data-lucide="clipboard-list" class="h-4 w-4" aria-hidden="true"></i>Applications
        </a>
    </div>

    <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="affiliate-directory-heading">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="affiliate-directory-heading" class="font-heading text-lg font-bold">Affiliate accounts directory</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Search registered owners, affiliate IDs, or existing referral codes.</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($affiliates->total()) }} matching</span>
            </div>

            <form method="GET" action="{{ route('admin.affiliates.index') }}" class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-12" role="search">
                <div class="relative lg:col-span-4">
                    <label for="affiliate-search" class="sr-only">Search affiliate accounts</label>
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input id="affiliate-search" name="search" type="search" maxlength="100" value="{{ $filters['search'] }}" placeholder="Owner, email, ID, or code" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </div>
                <div class="lg:col-span-3">
                    <label for="affiliate-status" class="sr-only">Affiliate status</label>
                    <select id="affiliate-status" name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        <option value="all">All account statuses</option>
                        @foreach($affiliateStatuses as $status)
                            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ Str::headline($status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="affiliate-sort" class="sr-only">Sort affiliates</label>
                    <select id="affiliate-sort" name="sort" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                        <option value="status" @selected($filters['sort'] === 'status')>Status A–Z</option>
                        <option value="id" @selected($filters['sort'] === 'id')>Highest ID</option>
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <label for="affiliate-per-page" class="sr-only">Results per page</label>
                    <select id="affiliate-per-page" name="per_page" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        @foreach([10, 25, 50] as $perPage)
                            <option value="{{ $perPage }}" @selected($filters['per_page'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 lg:col-span-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <i data-lucide="list-filter" class="h-4 w-4" aria-hidden="true"></i>Apply
                    </button>
                    @if($filters['search'] !== '' || $filters['status'] !== 'all' || $filters['sort'] !== 'newest' || $filters['per_page'] !== 10)
                        <a href="{{ route('admin.affiliates.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-slate-500 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-400" aria-label="Clear affiliate filters"><i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i></a>
                    @endif
                </div>
            </form>
        </div>

        @if($affiliates->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800"><i data-lucide="users-round" class="h-6 w-6" aria-hidden="true"></i></span>
                <h3 class="mt-4 font-heading text-base font-bold">No affiliate accounts found</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No account records match the selected filters. Accounts are never generated from this screen.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[70rem] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-7">Owner</th>
                            <th scope="col" class="px-5 py-3 font-bold">Status</th>
                            <th scope="col" class="px-5 py-3 font-bold">Referral code</th>
                            <th scope="col" class="px-5 py-3 font-bold">Application</th>
                            <th scope="col" class="px-5 py-3 font-bold">Lifecycle dates</th>
                            <th scope="col" class="px-5 py-3 font-bold">Created</th>
                            <th scope="col" class="px-5 py-3 text-right font-bold sm:px-7">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach($affiliates as $affiliate)
                            <tr class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                <th scope="row" class="px-5 py-4 sm:px-7">
                                    <span class="block max-w-56 truncate font-bold text-slate-800 dark:text-slate-200">{{ $affiliate->user->name }}</span>
                                    <span class="block max-w-64 truncate text-[10px] font-normal text-slate-400">{{ $affiliate->user->email }} · User #{{ $affiliate->user_id }}</span>
                                </th>
                                <td class="px-5 py-4">@include('admin.affiliates.partials.status-badge', ['status' => $affiliate->getRawOriginal('status')])</td>
                                <td class="px-5 py-4"><span class="block max-w-56 truncate font-mono text-slate-600 dark:text-slate-300">{{ $affiliate->referral_code ?: 'Not assigned' }}</span></td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">{{ $affiliate->application_id ? '#'.$affiliate->application_id : 'Not linked' }}</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">
                                    @if($affiliate->approved_at)
                                        <span class="block">Approved {{ $affiliate->approved_at->format('M j, Y') }}</span>
                                    @endif
                                    @if($affiliate->suspended_at)
                                        <span class="block">Suspended {{ $affiliate->suspended_at->format('M j, Y') }}</span>
                                    @endif
                                    @if(! $affiliate->approved_at && ! $affiliate->suspended_at)
                                        <span>No lifecycle event</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400"><time datetime="{{ $affiliate->created_at?->toAtomString() }}">{{ $affiliate->created_at?->format('M j, Y') ?? 'Unavailable' }}</time></td>
                                <td class="px-5 py-4 text-right sm:px-7"><a href="{{ route('admin.affiliates.show', $affiliate) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-bold text-indigo-600 transition hover:bg-indigo-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400">View <i data-lucide="arrow-up-right" class="h-3.5 w-3.5" aria-hidden="true"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-7 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-xs text-slate-500 dark:text-slate-400">Showing {{ number_format($affiliates->firstItem()) }}–{{ number_format($affiliates->lastItem()) }} of {{ number_format($affiliates->total()) }}</p>
                <div class="min-w-0">{{ $affiliates->links() }}</div>
            </div>
        @endif
    </section>
</x-admin.layout>
