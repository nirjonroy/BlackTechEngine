<x-admin.layout title="Affiliate Application #{{ $affiliateApplication->id }}" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'Affiliate Management', 'route' => 'admin.affiliates.index'], ['label' => 'Applications', 'route' => 'admin.affiliate-applications.index'], ['label' => 'Application #'.$affiliateApplication->id]]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Read-only application record</p>
            <h1 class="mt-1 truncate font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Affiliate Application #{{ $affiliateApplication->id }}</h1>
            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">Submitted by {{ $affiliateApplication->user->name }} · Registered user #{{ $affiliateApplication->user_id }}</p>
        </div>
        <a href="{{ route('admin.affiliate-applications.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300"><i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>Back to applications</a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-8" aria-labelledby="application-details-heading">
            <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="application-details-heading" class="font-heading text-lg font-bold">Application details</h2>
                    <p class="mt-1 max-w-xl break-all text-xs text-slate-500 dark:text-slate-400">{{ $affiliateApplication->user->email }}</p>
                </div>
                @include('admin.affiliates.partials.status-badge', ['status' => $affiliateApplication->getRawOriginal('status')])
            </div>

            <dl class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                @foreach([
                    ['label' => 'Application ID', 'value' => '#'.$affiliateApplication->id],
                    ['label' => 'Registered user', 'value' => $affiliateApplication->user->name.' (#'.$affiliateApplication->user_id.')'],
                    ['label' => 'Submitted', 'value' => $affiliateApplication->submitted_at?->format('M j, Y, g:i A') ?? 'Not submitted'],
                    ['label' => 'Reviewed', 'value' => $affiliateApplication->reviewed_at?->format('M j, Y, g:i A') ?? 'Not reviewed'],
                    ['label' => 'Reviewing Admin', 'value' => $affiliateApplication->reviewedByAdmin?->name ?? 'Not assigned'],
                    ['label' => 'Created', 'value' => $affiliateApplication->created_at?->format('M j, Y, g:i A') ?? 'Unavailable'],
                ] as $detail)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $detail['label'] }}</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $detail['value'] }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Review reason</h3>
                <p class="mt-2 whitespace-pre-line break-words text-sm text-slate-700 dark:text-slate-300">{{ $affiliateApplication->review_reason ?: 'No review reason recorded.' }}</p>
            </div>
        </section>

        <aside class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-4" aria-labelledby="application-link-heading">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"><i data-lucide="link-2" class="h-5 w-5" aria-hidden="true"></i></span>
            <h2 id="application-link-heading" class="mt-4 font-heading text-lg font-bold">Associated account</h2>
            @if($affiliateApplication->affiliate)
                <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">This application is linked to affiliate account #{{ $affiliateApplication->affiliate->id }}.</p>
                <div class="mt-4">@include('admin.affiliates.partials.status-badge', ['status' => $affiliateApplication->affiliate->getRawOriginal('status')])</div>
                <a href="{{ route('admin.affiliates.show', $affiliateApplication->affiliate) }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>View affiliate account</a>
            @else
                <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">No affiliate account is linked to this application. This read-only screen does not create one.</p>
            @endif
            <div class="mt-5 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs leading-5 text-amber-700 dark:text-amber-300">Approve, reject, and enrollment actions are intentionally unavailable.</div>
        </aside>
    </div>
</x-admin.layout>
