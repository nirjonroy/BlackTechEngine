<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-500">Automated Content Engine</p>
                <h1 class="font-heading text-xl font-black sm:text-2xl">Article Calendar & Autopilot</h1>
            </div>
            <div class="flex items-center gap-2">
                @if($workspace && $brand)
                    <a href="{{ route('workspaces.brands.keywords.index', [$workspace, $brand]) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700">
                        <i data-lucide="key" class="inline-block h-4 w-4 mr-1"></i> Keywords
                    </a>
                @endif
                <a href="{{ route('content-studio.index', request()->only(['workspace', 'brand'])) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold dark:border-slate-700">
                    All Drafts
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter & Navigation Bar -->
        <section class="bte-card rounded-2xl p-4 sm:p-5">
            <form method="GET" action="{{ route('content-studio.calendar') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 items-end">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Workspace</label>
                    <select name="workspace" onchange="this.form.submit()" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach ($workspaces as $item)
                            <option value="{{ $item->id }}" @selected($workspace?->is($item))>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Brand</label>
                    <select name="brand" onchange="this.form.submit()" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <option value="">All Brands</option>
                        @foreach ($workspace?->brands ?? [] as $item)
                            <option value="{{ $item->id }}" @selected($brand?->is($item))>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Date</label>
                    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">View Mode</label>
                    <select name="view" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach (['month' => 'Month View', 'week' => 'Week View', 'list' => 'List View (30 Days)'] as $key => $lbl)
                            <option value="{{ $key }}" @selected($view === $key)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Timezone</label>
                    <select name="timezone" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach (['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo', 'Asia/Dhaka', 'Australia/Sydney'] as $tz)
                            <option value="{{ $tz }}" @selected($timezone === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">
                        Update View
                    </button>
                </div>
            </form>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3 text-xs dark:border-slate-800">
                <span class="text-slate-500">
                    Displaying times in <strong>{{ $timezone }}</strong>. Autopilot defaults to human-approved drafts.
                </span>
                <nav class="flex items-center gap-2" aria-label="Calendar date navigation">
                    <a href="{{ route('content-studio.calendar', array_merge(request()->query(), ['date' => match ($view) { 'week' => $date->subWeek()->format('Y-m-d'), 'list' => $date->subDays(30)->format('Y-m-d'), default => $date->subMonthNoOverflow()->format('Y-m-d') }])) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 font-bold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
                        &larr; Previous
                    </a>
                    <a href="{{ route('content-studio.calendar', array_merge(request()->query(), ['date' => now($timezone)->format('Y-m-d')])) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 font-bold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
                        Today
                    </a>
                    <a href="{{ route('content-studio.calendar', array_merge(request()->query(), ['date' => match ($view) { 'week' => $date->addWeek()->format('Y-m-d'), 'list' => $date->addDays(30)->format('Y-m-d'), default => $date->addMonthNoOverflow()->format('Y-m-d') }])) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 font-bold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
                        Next &rarr;
                    </a>
                </nav>
            </div>
        </section>

        <!-- Autopilot Controls & Schedule List -->
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Left: Schedule Creator Form -->
            <section class="bte-card rounded-2xl p-5 lg:col-span-1">
                <div class="mb-4">
                    <h2 class="font-heading text-lg font-black">Schedule New Article</h2>
                    <p class="text-xs text-slate-500">Configure one-time or recurring automated SEO generation.</p>
                </div>

                @if(!$workspace || !$brand)
                    <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
                        <p class="text-sm font-bold text-slate-500">Please select a specific workspace and brand above to create schedules.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('workspaces.brands.schedules.store', [$workspace, $brand]) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="timezone" value="{{ $timezone }}">

                        <!-- Saved Keyword / Cluster Selector -->
                        @if($keywords->isNotEmpty() || $clusters->isNotEmpty())
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Pick from Saved Keywords</label>
                                <select id="keyword_picker" onchange="populateKeyword(this)" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                                    <option value="">-- Choose saved keyword or cluster --</option>
                                    @if($clusters->isNotEmpty())
                                        <optgroup label="Topic Clusters">
                                            @foreach($clusters as $cluster)
                                                <option value="cluster:{{ $cluster->id }}" data-topic="{{ $cluster->name }}" data-kw="{{ $cluster->pillar_keyword }}">Cluster: {{ $cluster->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($keywords->isNotEmpty())
                                        <optgroup label="Saved Brand Keywords">
                                            @foreach($keywords as $kw)
                                                <option value="kw:{{ $kw->id }}" data-topic="{{ $kw->keyword }}" data-kw="{{ $kw->keyword }}" data-cluster="{{ $kw->brand_topic_cluster_id }}">
                                                    {{ $kw->keyword }} ({{ ucfirst($kw->search_intent?->value ?? 'info') }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                            </div>
                        @endif

                        <input type="hidden" id="brand_keyword_id" name="brand_keyword_id" value="">
                        <input type="hidden" id="brand_topic_cluster_id" name="brand_topic_cluster_id" value="">

                        <div>
                            <label for="title" class="mb-1 block text-xs font-bold uppercase text-slate-500">Article Topic / Title *</label>
                            <input type="text" id="schedule_title" name="title" required placeholder="e.g. 10 Essential Strategies for B2B Growth" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        </div>

                        <div>
                            <label for="primary_keyword" class="mb-1 block text-xs font-bold uppercase text-slate-500">Primary Keyword *</label>
                            <input type="text" id="schedule_primary_keyword" name="primary_keyword" required placeholder="e.g. b2b growth strategies" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        </div>

                        <div>
                            <label for="secondary_keywords" class="mb-1 block text-xs font-bold uppercase text-slate-500">Secondary Keywords (comma separated)</label>
                            <input type="text" name="secondary_keywords" placeholder="e.g. pipeline generation, demand generation" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Content Type</label>
                                <select name="content_type" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                                    @foreach($contentTypes as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Length</label>
                                <select name="content_length" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                                    @foreach($contentLengths as $len)
                                        <option value="{{ $len->value }}">{{ $len->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Schedule Frequency -->
                        <div class="border-t border-slate-200 pt-3 dark:border-slate-800">
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Schedule Frequency *</label>
                            <select id="frequency_select" name="frequency" onchange="toggleFrequencyFields(this.value)" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                                @foreach($frequencies as $freq)
                                    <option value="{{ $freq->value }}">{{ $freq->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="date_field_wrapper" class="hidden">
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Scheduled Date</label>
                            <input type="date" name="scheduled_date" value="{{ now($timezone)->addDay()->format('Y-m-d') }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Execution Time ({{ $timezone }}) *</label>
                            <input type="time" name="scheduled_time" value="09:00" required class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                        </div>

                        <div id="days_field_wrapper" class="space-y-1">
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Days of the Week (for weekly)</label>
                            <div class="flex flex-wrap gap-2 text-xs">
                                @foreach([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $dNum => $dName)
                                    <label class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 dark:border-slate-800">
                                        <input type="checkbox" name="days_of_week[]" value="{{ $dNum }}" @checked(in_array($dNum, [1, 3, 5])) class="rounded text-indigo-600">
                                        {{ $dName }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Publishing Target & WordPress Opt-In -->
                        <div class="border-t border-slate-200 pt-3 dark:border-slate-800 space-y-3">
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Target Editorial Action *</label>
                                <select id="target_status_select" name="target_status" onchange="toggleWpOptions(this.value)" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-900">
                                    <option value="draft">Save as Draft (Safe default)</option>
                                    <option value="needs_review">Submit for Human Review</option>
                                    <option value="wordpress_publish" @disabled(!$hasWordPress)>
                                        Auto-Publish to WordPress {{ $hasWordPress ? '' : '(WordPress not connected)' }}
                                    </option>
                                </select>
                            </div>

                            <div id="wp_optin_box" class="hidden rounded-xl border border-indigo-200 bg-indigo-50/50 p-3 text-xs dark:border-indigo-900/50 dark:bg-indigo-950/20 space-y-2">
                                <label class="flex items-start gap-2 font-bold text-slate-800 dark:text-slate-200">
                                    <input type="checkbox" name="auto_publish_to_wordpress" value="1" class="mt-0.5 rounded text-indigo-600">
                                    <span>Explicitly opt-in to auto-publishing generated articles to WordPress.</span>
                                </label>
                                <div>
                                    <label class="block text-slate-500">WordPress Remote Post Status:</label>
                                    <select name="publish_as_status" class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-900">
                                        <option value="draft">Remote WordPress Draft</option>
                                        <option value="publish">Directly Published</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black text-white hover:bg-indigo-700">
                            Save Autopilot Schedule
                        </button>
                    </form>
                @endif
            </section>

            <!-- Right: Active Schedules & Recent Runs -->
            <section class="space-y-6 lg:col-span-2">
                <!-- Schedules List -->
                <div class="bte-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-heading text-lg font-black">Configured Schedules ({{ $schedules->count() }})</h2>
                    </div>

                    @if($schedules->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-500 dark:border-slate-800">
                            <p class="text-sm">No article autopilot schedules found for this brand.</p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($schedules as $sched)
                                <div class="py-4 first:pt-0 last:pb-0 flex flex-wrap items-center justify-between gap-3">
                                    <div class="space-y-1 max-w-md">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $sched->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                                {{ $sched->is_active ? 'Active' : 'Paused' }}
                                            </span>
                                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                                {{ $sched->frequency->label() }}
                                            </span>
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                {{ $sched->target_status->label() }}
                                            </span>
                                        </div>
                                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">{{ $sched->title }}</h3>
                                        <p class="text-xs text-slate-500">
                                            Keyword: <strong>{{ $sched->primary_keyword }}</strong>
                                            @if($sched->next_run_at)
                                                &bull; Next: {{ $sched->next_run_at->setTimezone($timezone)->format('M d, Y @ H:i') }}
                                            @endif
                                            @if($sched->total_runs > 0)
                                                &bull; Runs: {{ $sched->total_runs }}
                                            @endif
                                        </p>
                                        @if($sched->last_error)
                                            <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">
                                                Last error: {{ Str::limit($sched->last_error, 100) }} (retries: {{ $sched->failure_count }})
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <!-- Run Now Form -->
                                        <form method="POST" action="{{ route('workspaces.brands.schedules.run-now', [$workspace ?? $sched->workspace, $sched->brand, $sched]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-bold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
                                                Run Now
                                            </button>
                                        </form>

                                        <!-- Toggle Active Form -->
                                        <form method="POST" action="{{ route('workspaces.brands.schedules.toggle', [$workspace ?? $sched->workspace, $sched->brand, $sched]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-bold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">
                                                {{ $sched->is_active ? 'Pause' : 'Resume' }}
                                            </button>
                                        </form>

                                        <!-- Delete Schedule Form -->
                                        <form method="POST" action="{{ route('workspaces.brands.schedules.destroy', [$workspace ?? $sched->workspace, $sched->brand, $sched]) }}" onsubmit="return confirm('Delete this schedule?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Recent Execution History -->
                <div class="bte-card rounded-2xl p-5">
                    <h2 class="font-heading text-lg font-black mb-3">Autopilot Run History</h2>
                    @if($runs->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-500 dark:border-slate-800">
                            <p class="text-sm">No recent autopilot executions in this date range.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($runs as $run)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 p-3.5 text-xs dark:border-slate-800">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 dark:text-white">{{ $run->schedule?->title ?? 'Scheduled Task' }}</span>
                                            <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold {{ $run->status->value === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($run->status->value === 'failed' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : 'bg-amber-100 text-amber-800') }}">
                                                {{ $run->status->label() }}
                                            </span>
                                        </div>
                                        <p class="text-slate-500">
                                            Executed: {{ $run->created_at->setTimezone($timezone)->format('M d, Y @ H:i') }}
                                            @if($run->error_message)
                                                &bull; <span class="text-rose-600">{{ Str::limit($run->error_message, 80) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    @if($run->draft)
                                        <a href="{{ route('content-studio.drafts.show', [$run->workspace_id, $run->brand_id, $run->draft]) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 font-bold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300">
                                            View Draft &rarr;
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <!-- Calendar Grid / Timeline View -->
        <section class="bte-card rounded-2xl p-5">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-heading text-lg font-black">
                        {{ match($view) { 'week' => 'Week of '.$start->format('M d, Y').' - '.$end->format('M d, Y'), 'list' => 'Next 30 Days Timeline', default => $date->format('F Y') } }}
                    </h2>
                    <p class="text-xs text-slate-500">Overview of scheduled articles and drafts generated.</p>
                </div>
            </div>

            @if($view === 'month')
                <!-- Month Grid -->
                <div class="grid grid-cols-7 gap-px rounded-xl border border-slate-200 bg-slate-200 overflow-hidden dark:border-slate-800 dark:bg-slate-800">
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                        <div class="bg-slate-50 py-2 text-center text-xs font-bold text-slate-500 dark:bg-slate-900">
                            {{ $dayName }}
                        </div>
                    @endforeach

                    @php
                        $cursor = $start->copy();
                    @endphp
                    @while($cursor->lte($end))
                        @php
                            $isCurrentMonth = $cursor->month === $date->month;
                            $dayDateStr = $cursor->format('Y-m-d');
                            $daySchedules = $schedules->filter(function($s) use ($dayDateStr, $timezone) {
                                return $s->next_run_at && $s->next_run_at->setTimezone($timezone)->format('Y-m-d') === $dayDateStr;
                            });
                            $dayDrafts = $drafts->filter(function($d) use ($dayDateStr, $timezone) {
                                return $d->created_at->setTimezone($timezone)->format('Y-m-d') === $dayDateStr;
                            });
                        @endphp
                        <div class="min-h-[110px] bg-white p-2 dark:bg-slate-950 {{ $isCurrentMonth ? '' : 'bg-slate-50/50 text-slate-400 dark:bg-slate-900/50' }}">
                            <div class="text-right text-xs font-bold mb-1 {{ $cursor->isToday($timezone) ? 'text-indigo-600 font-black' : '' }}">
                                {{ $cursor->format('j') }}
                            </div>
                            <div class="space-y-1">
                                @foreach($daySchedules as $sched)
                                    <div class="truncate rounded px-1.5 py-0.5 text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300" title="{{ $sched->title }}">
                                        &#9201; {{ $sched->scheduled_time }} {{ $sched->title }}
                                    </div>
                                @endforeach
                                @foreach($dayDrafts as $dr)
                                    <a href="{{ route('content-studio.drafts.show', [$dr->workspace_id, $dr->brand_id, $dr]) }}" class="block truncate rounded px-1.5 py-0.5 text-[11px] font-medium bg-emerald-50 text-emerald-700 hover:underline dark:bg-emerald-950/60 dark:text-emerald-300" title="{{ $dr->topic }}">
                                        &#10003; Draft: {{ $dr->topic }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @php
                            $cursor = $cursor->addDay();
                        @endphp
                    @endwhile
                </div>
            @elseif($view === 'week')
                <!-- Week Grid -->
                <div class="grid grid-cols-7 gap-2">
                    @php
                        $cursor = $start->copy();
                    @endphp
                    @while($cursor->lte($end))
                        @php
                            $dayDateStr = $cursor->format('Y-m-d');
                            $daySchedules = $schedules->filter(function($s) use ($dayDateStr, $timezone) {
                                return $s->next_run_at && $s->next_run_at->setTimezone($timezone)->format('Y-m-d') === $dayDateStr;
                            });
                            $dayDrafts = $drafts->filter(function($d) use ($dayDateStr, $timezone) {
                                return $d->created_at->setTimezone($timezone)->format('Y-m-d') === $dayDateStr;
                            });
                        @endphp
                        <div class="rounded-xl border border-slate-200 p-3 min-h-[200px] dark:border-slate-800 {{ $cursor->isToday($timezone) ? 'ring-2 ring-indigo-500' : '' }}">
                            <div class="border-b border-slate-100 pb-2 mb-2 dark:border-slate-800">
                                <p class="text-xs uppercase font-bold text-slate-500">{{ $cursor->format('D') }}</p>
                                <p class="text-lg font-black">{{ $cursor->format('M j') }}</p>
                            </div>
                            <div class="space-y-1.5">
                                @foreach($daySchedules as $sched)
                                    <div class="rounded-lg p-2 text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                        <p class="text-[10px] text-indigo-500 uppercase">{{ $sched->frequency->label() }} &bull; {{ $sched->scheduled_time }}</p>
                                        <p class="truncate">{{ $sched->title }}</p>
                                    </div>
                                @endforeach
                                @foreach($dayDrafts as $dr)
                                    <a href="{{ route('content-studio.drafts.show', [$dr->workspace_id, $dr->brand_id, $dr]) }}" class="block rounded-lg p-2 text-xs bg-emerald-50 text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <p class="text-[10px] uppercase font-bold text-emerald-600">Generated Draft</p>
                                        <p class="truncate font-semibold">{{ $dr->topic }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @php
                            $cursor = $cursor->addDay();
                        @endphp
                    @endwhile
                </div>
            @else
                <!-- List View (Chronological Timeline) -->
                <div class="space-y-3">
                    @forelse($schedules as $sched)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-md bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">
                                        {{ $sched->frequency->label() }}
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        Next execution: {{ $sched->next_run_at ? $sched->next_run_at->setTimezone($timezone)->format('M d, Y @ H:i') : 'Paused' }}
                                    </span>
                                </div>
                                <h4 class="font-bold text-sm">{{ $sched->title }}</h4>
                                <p class="text-xs text-slate-500">Primary Keyword: {{ $sched->primary_keyword }} &bull; Target: {{ $sched->target_status->label() }}</p>
                            </div>
                            <form method="POST" action="{{ route('workspaces.brands.schedules.run-now', [$workspace ?? $sched->workspace, $sched->brand, $sched]) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">
                                    Execute Now
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 py-6 text-center">No upcoming schedules found.</p>
                    @endforelse
                </div>
            @endif
        </section>
    </div>

    <script>
        function toggleFrequencyFields(freq) {
            const dateWrap = document.getElementById('date_field_wrapper');
            const daysWrap = document.getElementById('days_field_wrapper');
            if (freq === 'once') {
                dateWrap.classList.remove('hidden');
                daysWrap.classList.add('hidden');
            } else if (freq === 'daily') {
                dateWrap.classList.add('hidden');
                daysWrap.classList.add('hidden');
            } else if (freq === 'weekly') {
                dateWrap.classList.add('hidden');
                daysWrap.classList.remove('hidden');
            }
        }

        function toggleWpOptions(targetStatus) {
            const wpBox = document.getElementById('wp_optin_box');
            if (targetStatus === 'wordpress_publish') {
                wpBox.classList.remove('hidden');
            } else {
                wpBox.classList.add('hidden');
            }
        }

        function populateKeyword(select) {
            const opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) return;

            const topicInput = document.getElementById('schedule_title');
            const kwInput = document.getElementById('schedule_primary_keyword');
            const kwHidden = document.getElementById('brand_keyword_id');
            const clusterHidden = document.getElementById('brand_topic_cluster_id');

            const val = opt.value;
            const topic = opt.getAttribute('data-topic') || '';
            const kw = opt.getAttribute('data-kw') || '';

            if (topic) topicInput.value = topic;
            if (kw) kwInput.value = kw;

            if (val.startsWith('kw:')) {
                kwHidden.value = val.replace('kw:', '');
                clusterHidden.value = opt.getAttribute('data-cluster') || '';
            } else if (val.startsWith('cluster:')) {
                kwHidden.value = '';
                clusterHidden.value = val.replace('cluster:', '');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const freqSelect = document.getElementById('frequency_select');
            if (freqSelect) toggleFrequencyFields(freqSelect.value);
            const targetSelect = document.getElementById('target_status_select');
            if (targetSelect) toggleWpOptions(targetSelect.value);
        });
    </script>
</x-app-layout>

