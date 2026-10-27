<x-admin.layout title="Affiliate Applications" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'Affiliate Management', 'route' => 'admin.affiliates.index'], ['label' => 'Applications']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Application records</p>
            <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Affiliate Applications</h1>
            <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400">A read-only queue of persisted application records. Review actions and enrollment remain disabled.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">
            <i data-lucide="clipboard-list" class="h-4 w-4" aria-hidden="true"></i>
            {{ number_format($applications->total()) }} matching
        </span>
    </div>

    <div class="flex flex-wrap gap-2" aria-label="Affiliate management sections">
        <a href="{{ route('admin.affiliates.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300"><i data-lucide="users-round" class="h-4 w-4" aria-hidden="true"></i>Affiliate accounts</a>
        <a href="{{ route('admin.affiliate-applications.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" aria-current="page"><i data-lucide="clipboard-list" class="h-4 w-4" aria-hidden="true"></i>Applications</a>
    </div>

    <section class="bte-card overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="affiliate-applications-heading">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-7">
            <div>
                <h2 id="affiliate-applications-heading" class="font-heading text-lg font-bold">Applications directory</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Search by application ID or registered-user ID, name, and email address.</p>
            </div>

            <form method="GET" action="{{ route('admin.affiliate-applications.index') }}" class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-12" role="search">
                <div class="relative lg:col-span-4">
                    <label for="application-search" class="sr-only">Search applications</label>
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input id="application-search" name="search" type="search" maxlength="100" value="{{ $filters['search'] }}" placeholder="Application or user identifier" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </div>
                <div class="lg:col-span-3">
                    <label for="application-status" class="sr-only">Application status</label>
                    <select id="application-status" name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        <option value="all">All application statuses</option>
                        @foreach($applicationStatuses as $status)
                            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ Str::headline($status->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="application-sort" class="sr-only">Sort applications</label>
                    <select id="application-sort" name="sort" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                        <option value="submitted" @selected($filters['sort'] === 'submitted')>Recently submitted</option>
                        <option value="status" @selected($filters['sort'] === 'status')>Status A–Z</option>
                        <option value="id" @selected($filters['sort'] === 'id')>Highest ID</option>
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <label for="application-per-page" class="sr-only">Results per page</label>
                    <select id="application-per-page" name="per_page" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                        @foreach([10, 25, 50] as $perPage)
                            <option value="{{ $perPage }}" @selected($filters['per_page'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 lg:col-span-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><i data-lucide="list-filter" class="h-4 w-4" aria-hidden="true"></i>Apply</button>
                    @if($filters['search'] !== '' || $filters['status'] !== 'all' || $filters['sort'] !== 'newest' || $filters['per_page'] !== 10)
                        <a href="{{ route('admin.affiliate-applications.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2.5 text-slate-500 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-400" aria-label="Clear application filters"><i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i></a>
                    @endif
                </div>
            </form>
        </div>

        @if($applications->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800"><i data-lucide="clipboard-x" class="h-6 w-6" aria-hidden="true"></i></span>
                <h3 class="mt-4 font-heading text-base font-bold">No affiliate applications found</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No application records match the selected filters. Public enrollment is not enabled.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[66rem] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-bold sm:px-7">Application</th>
                            <th scope="col" class="px-5 py-3 font-bold">Registered user</th>
                            <th scope="col" class="px-5 py-3 font-bold">Status</th>
                            <th scope="col" class="px-5 py-3 font-bold">Submitted</th>
                            <th scope="col" class="px-5 py-3 font-bold">Review</th>
                            <th scope="col" class="px-5 py-3 font-bold">Created</th>
                            <th scope="col" class="px-5 py-3 text-right font-bold sm:px-7">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach($applications as $application)
                            <tr class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/30">
                                <th scope="row" class="px-5 py-4 font-mono font-bold text-slate-800 dark:text-slate-200 sm:px-7">#{{ $application->id }}</th>
                                <td class="px-5 py-4">
                                    <span class="block max-w-56 truncate font-bold text-slate-800 dark:text-slate-200">{{ $application->user->name }}</span>
                                    <span class="block max-w-64 truncate text-[10px] text-slate-400">{{ $application->user->email }} · User #{{ $application->user_id }}</span>
                                </td>
                                <td class="px-5 py-4">@include('admin.affiliates.partials.status-badge', ['status' => $application->getRawOriginal('status')])</td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400"><time datetime="{{ $application->submitted_at?->toAtomString() }}">{{ $application->submitted_at?->format('M j, Y') ?? 'Not submitted' }}</time></td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400">
                                    <span class="block">{{ $application->reviewedByAdmin?->name ?? 'Not reviewed' }}</span>
                                    @if($application->reviewed_at)
                                        <time datetime="{{ $application->reviewed_at->toAtomString() }}" class="mt-0.5 block text-[10px] text-slate-400">{{ $application->reviewed_at->format('M j, Y') }}</time>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-500 dark:text-slate-400"><time datetime="{{ $application->created_at?->toAtomString() }}">{{ $application->created_at?->format('M j, Y') ?? 'Unavailable' }}</time></td>
                                <td class="px-5 py-4 text-right sm:px-7"><a href="{{ route('admin.affiliate-applications.show', $application) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 font-bold text-indigo-600 transition hover:bg-indigo-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400">View <i data-lucide="arrow-up-right" class="h-3.5 w-3.5" aria-hidden="true"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-7 lg:flex-row lg:items-center lg:justify-between">
                <p class="text-xs text-slate-500 dark:text-slate-400">Showing {{ number_format($applications->firstItem()) }}–{{ number_format($applications->lastItem()) }} of {{ number_format($applications->total()) }}</p>
                <div class="min-w-0">{{ $applications->links() }}</div>
            </div>
        @endif
    </section>
</x-admin.layout>
