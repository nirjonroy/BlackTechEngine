<x-admin.layout title="Affiliate Account #{{ $affiliate->id }}" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'Affiliate Management', 'route' => 'admin.affiliates.index'], ['label' => 'Account #'.$affiliate->id]]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Read-only affiliate record</p>
            <h1 class="mt-1 truncate font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Affiliate Account #{{ $affiliate->id }}</h1>
            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">Owned by {{ $affiliate->user->name }} · Registered user #{{ $affiliate->user_id }}</p>
        </div>
        <a href="{{ route('admin.affiliates.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300"><i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>Back to accounts</a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-7" aria-labelledby="affiliate-account-details">
            <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="affiliate-account-details" class="font-heading text-lg font-bold">Account details</h2>
                    <p class="mt-1 max-w-xl break-all text-xs text-slate-500 dark:text-slate-400">{{ $affiliate->user->email }}</p>
                </div>
                @include('admin.affiliates.partials.status-badge', ['status' => $affiliate->getRawOriginal('status')])
            </div>

            <dl class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                @foreach([
                    ['label' => 'Affiliate ID', 'value' => '#'.$affiliate->id],
                    ['label' => 'Registered user', 'value' => $affiliate->user->name.' (#'.$affiliate->user_id.')'],
                    ['label' => 'Referral code', 'value' => $affiliate->referral_code ?: 'Not assigned'],
                    ['label' => 'Linked application', 'value' => $affiliate->application_id ? '#'.$affiliate->application_id : 'Not linked'],
                    ['label' => 'Approved', 'value' => $affiliate->approved_at?->format('M j, Y, g:i A') ?? 'Not approved'],
                    ['label' => 'Suspended', 'value' => $affiliate->suspended_at?->format('M j, Y, g:i A') ?? 'Not suspended'],
                    ['label' => 'Created', 'value' => $affiliate->created_at?->format('M j, Y, g:i A') ?? 'Unavailable'],
                ] as $detail)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $detail['label'] }}</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $detail['value'] }}</dd>
                    </div>
                @endforeach
            </dl>

            @if($affiliate->application)
                <a href="{{ route('admin.affiliate-applications.show', $affiliate->application) }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><i data-lucide="clipboard-list" class="h-4 w-4" aria-hidden="true"></i>View linked application</a>
            @endif
        </section>

        <aside class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-5" aria-labelledby="affiliate-account-scope">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400"><i data-lucide="shield-alert" class="h-5 w-5" aria-hidden="true"></i></span>
            <h2 id="affiliate-account-scope" class="mt-4 font-heading text-lg font-bold">Read-only operational scope</h2>
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">This page exposes only persisted ownership, lifecycle, referral-code, and timestamp fields. It cannot activate, suspend, approve, or otherwise mutate this account.</p>
            <div class="mt-5 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-xs leading-5 text-amber-700 dark:text-amber-300">Referral tracking, commissions, balances, payouts, and subscription data do not exist in the current affiliate schema.</div>
        </aside>
    </div>
</x-admin.layout>
