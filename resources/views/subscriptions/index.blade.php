<x-app-layout>
    <x-slot name="header">
        <div><p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-500">Account</p><h1 class="font-heading text-xl font-black sm:text-2xl">Subscription & usage</h1></div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
        @if(session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>@endif
        @if($workspaces->isEmpty())
            <div class="bte-card rounded-2xl p-8 text-center"><h2 class="font-heading text-xl font-black">No workspace yet</h2><p class="mt-2 text-sm text-slate-500">Complete onboarding before managing a subscription.</p></div>
        @else
            <form method="GET" class="bte-card rounded-2xl p-4"><label for="workspace" class="text-sm font-bold">Workspace</label><select id="workspace" name="workspace" onchange="this.form.submit()" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">@foreach($workspaces as $item)<option value="{{ $item->id }}" @selected($workspace?->is($item))>{{ $item->name }}</option>@endforeach</select></form>

            <section class="grid gap-5 lg:grid-cols-3">
                <div class="bte-card rounded-2xl p-6 lg:col-span-2"><p class="text-xs font-bold uppercase tracking-wider text-indigo-500">Current plan</p><h2 class="mt-2 font-heading text-2xl font-black">{{ $subscription?->plan?->name ?? 'No active subscription' }}</h2><p class="mt-2 text-sm text-slate-500">Status: {{ $subscription?->status?->label() ?? 'Not subscribed' }}</p>@if($subscription)<p class="mt-1 text-sm text-slate-500">Period ends {{ $subscription->current_period_ends_at?->format('M j, Y') ?? 'not scheduled' }}</p>@endif</div>
                <div class="bte-card rounded-2xl p-6"><h2 class="font-heading text-lg font-black">Usage</h2><div class="mt-3 space-y-2">@forelse($subscription?->usageCounters ?? [] as $counter)<div class="flex justify-between gap-3 text-sm"><span>{{ str($counter->key)->replace('_', ' ')->title() }}</span><strong>{{ $counter->consumed() }}</strong></div>@empty<p class="text-sm text-slate-500">No metered usage yet.</p>@endforelse</div></div>
            </section>

            @if($subscription)
                <section class="grid gap-5 lg:grid-cols-2">
                    <form method="POST" action="{{ route('subscriptions.change-requests.store', $workspace) }}" class="bte-card rounded-2xl p-6">@csrf<h2 class="font-heading text-lg font-black">Request a plan change</h2><p class="mt-1 text-sm text-slate-500">An Admin must review this request. No payment is collected here.</p><select name="pricing_plan_id" required class="mt-4 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach</select><select name="billing_interval" required class="mt-3 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"><option value="monthly">Monthly</option><option value="annual">Annual</option></select><button class="mt-4 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white">Submit request</button></form>
                    <form method="POST" action="{{ route('subscriptions.cancellation-requests.store', $workspace) }}" class="bte-card rounded-2xl p-6">@csrf<h2 class="font-heading text-lg font-black">Request cancellation</h2><p class="mt-1 text-sm text-slate-500">This records a request; it does not immediately cancel service.</p><textarea name="reason" required maxlength="1000" rows="4" class="mt-4 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900" placeholder="Reason for cancellation"></textarea><button class="mt-4 rounded-xl border border-rose-300 px-4 py-2.5 text-sm font-black text-rose-700 dark:border-rose-800 dark:text-rose-300">Submit cancellation request</button></form>
                </section>
                <section class="bte-card overflow-hidden rounded-2xl"><div class="border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-heading text-lg font-black">Invoices</h2></div><div class="divide-y divide-slate-200 dark:divide-slate-800">@forelse($subscription->invoices as $invoice)<a class="flex items-center justify-between gap-4 p-5 hover:bg-slate-50 dark:hover:bg-slate-800/50" href="{{ route('subscriptions.invoices.show', [$workspace, $invoice]) }}"><span><strong>{{ $invoice->number }}</strong><span class="ml-2 text-sm text-slate-500">{{ $invoice->status->value }}</span></span><span class="font-black">{{ $invoice->currency }} {{ number_format($invoice->total_minor / 100, 2) }}</span></a>@empty<p class="p-5 text-sm text-slate-500">No invoices.</p>@endforelse</div></section>
            @endif
        @endif
    </div>
</x-app-layout>
