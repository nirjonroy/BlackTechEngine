<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">{{ $workspace->name }}</p>
            <h1 class="font-heading text-xl font-black sm:text-2xl">{{ $brand->name }}</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        @if(session('status'))
            <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                <p class="font-black">The action could not be completed.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('workspaces.brands.index', $workspace) }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-indigo-600 dark:text-slate-300"><i data-lucide="arrow-left" class="h-4 w-4"></i>All brands</a>
            <div class="flex items-center gap-2">
                <a href="{{ route('workspaces.brands.brand-dna.show', [$workspace, $brand]) }}" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-black text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300"><i data-lucide="dna" class="h-4 w-4"></i>Brand DNA</a>
                <a href="{{ route('workspaces.brands.keywords.index', [$workspace, $brand]) }}" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-black text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300"><i data-lucide="key-round" class="h-4 w-4"></i>Keywords &amp; Clusters</a>
                <a href="{{ route('workspaces.brands.edit', [$workspace, $brand]) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white"><i data-lucide="pencil" class="h-4 w-4"></i>Edit profile</a>
            </div>
        </div>

        <section class="bte-card rounded-3xl p-5 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black {{ $brand->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span>
                    <h2 class="mt-3 font-heading text-3xl font-black">{{ $brand->name }}</h2>
                    <a href="{{ $brand->website_url }}" rel="noopener noreferrer" target="_blank" class="mt-2 inline-flex items-center gap-1 text-sm font-bold text-indigo-600 dark:text-indigo-300">{{ $brand->website_url }}<i data-lucide="external-link" class="h-3.5 w-3.5"></i></a>
                </div>
                <div class="rounded-xl bg-indigo-50 px-4 py-3 text-sm dark:bg-indigo-950/50">
                    <p class="text-xs font-bold uppercase text-indigo-500">Market</p>
                    <p class="mt-1 font-bold">{{ $brand->industry }} &middot; {{ $brand->primary_market }}</p>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div><h3 class="text-sm font-black uppercase tracking-wide text-slate-500">Business description</h3><p class="mt-2 whitespace-pre-line text-sm leading-7">{{ $brand->business_description }}</p></div>
                <div><h3 class="text-sm font-black uppercase tracking-wide text-slate-500">Target audience</h3><p class="mt-2 whitespace-pre-line text-sm leading-7">{{ $brand->target_audience }}</p></div>
                <div><h3 class="text-sm font-black uppercase tracking-wide text-slate-500">Voice and language</h3><p class="mt-2 text-sm leading-7"><strong>{{ $brand->preferred_language }}</strong><br>{{ $brand->voice_tone }}</p></div>
                <div>
                    <h3 class="text-sm font-black uppercase tracking-wide text-slate-500">Content objectives</h3>
                    <ul class="mt-2 space-y-2">
                        @foreach($brand->content_objectives as $objective)
                            <li class="flex gap-2 text-sm"><i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"></i>{{ $objective }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="mt-7">
                <h3 class="text-sm font-black uppercase tracking-wide text-slate-500">Main SEO topic categories</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($brand->seo_topic_categories as $topic)
                        <span class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ $topic }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        @php
            $brandDna = $brand->brandDna;
        @endphp
        <section class="bte-card rounded-3xl p-5 sm:p-8" aria-labelledby="brand-dna-heading">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-500">Identity Intelligence</span>
                        @if($brandDna && $brandDna->is_approved)
                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Approved</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-500 dark:bg-slate-800">Not Approved</span>
                        @endif
                    </div>
                    <h2 id="brand-dna-heading" class="mt-1 font-heading text-2xl font-black">Brand DNA Profile</h2>
                    <p class="mt-1 text-sm text-slate-500">Automated extraction from your website informing SEO prompts, topic themes, and image generation.</p>
                </div>
                <a href="{{ route('workspaces.brands.brand-dna.show', [$workspace, $brand]) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    <i data-lucide="dna" class="h-4 w-4"></i> {{ $brandDna ? 'Manage Brand DNA' : 'Extract Brand DNA' }}
                </a>
            </div>
        </section>

        <section class="bte-card rounded-3xl p-5 sm:p-8" aria-labelledby="keywords-explorer-heading">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-500">SEO Strategy</span>
                    <h2 id="keywords-explorer-heading" class="mt-1 font-heading text-2xl font-black">Keywords &amp; Topic Clusters</h2>
                    <p class="mt-1 text-sm text-slate-500">Discover search queries, group topics semantically, and turn keywords into article briefs.</p>
                </div>
                <a href="{{ route('workspaces.brands.keywords.index', [$workspace, $brand]) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                    <i data-lucide="compass" class="h-4 w-4"></i> Open Keyword Explorer
                </a>
            </div>
        </section>

        @php
            $wpConnection = $brand->wordPressConnection;
        @endphp
        <section class="bte-card rounded-3xl p-5 sm:p-8" aria-labelledby="wordpress-integration-heading">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-500">CMS Integration</span>
                    <h2 id="wordpress-integration-heading" class="mt-1 font-heading text-2xl font-black">WordPress Publishing</h2>
                    <p class="mt-1 text-sm text-slate-500">Connect your WordPress site via REST API and Application Password for direct article publishing.</p>
                </div>
                @if($wpConnection && $wpConnection->isConnected())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Connected
                    </span>
                @elseif($wpConnection && $wpConnection->status === \App\Enums\WordPressConnectionStatus::Error)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                        <span class="h-2 w-2 rounded-full bg-rose-500"></span> Connection Error
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        Not Connected
                    </span>
                @endif
            </div>

            @if($wpConnection && $wpConnection->status !== \App\Enums\WordPressConnectionStatus::Disconnected)
                <div class="mt-6 rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Site Name</p>
                            <p class="mt-1 text-sm font-bold">{{ $wpConnection->site_name ?: 'WordPress Site' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Site URL</p>
                            <p class="mt-1 truncate text-sm font-bold">{{ $wpConnection->site_url }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Authenticated User</p>
                            <p class="mt-1 text-sm font-bold">{{ $wpConnection->username }}</p>
                        </div>
                    </div>

                    @if($wpConnection->last_verified_at)
                        <p class="mt-4 text-xs text-slate-500">Last verified: {{ $wpConnection->last_verified_at->toDayDateTimeString() }}</p>
                    @endif

                    @if($wpConnection->last_error)
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                            <strong>Error:</strong> {{ $wpConnection->last_error }}
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <a href="{{ route('workspaces.brands.internal-links.index', [$workspace, $brand]) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-3.5 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:bg-indigo-950/60 dark:text-indigo-300">
                            <i data-lucide="link" class="h-3.5 w-3.5"></i> Internal Linking Index
                        </a>
                        <form method="POST" action="{{ route('workspaces.brands.wordpress.verify', [$workspace, $brand]) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 px-3.5 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-950/50">
                                <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i> Re-verify Connection
                            </button>
                        </form>
                        <form method="POST" action="{{ route('workspaces.brands.wordpress.disconnect', [$workspace, $brand]) }}" onsubmit="return confirm('Disconnect this WordPress site? Direct publishing will be disabled until reconnected.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 px-3.5 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-950/50">
                                <i data-lucide="unlink" class="h-3.5 w-3.5"></i> Disconnect
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('workspaces.brands.wordpress.connect', [$workspace, $brand]) }}" class="mt-6 space-y-4 rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="wp_site_url" class="block text-xs font-bold uppercase tracking-wider text-slate-500">WordPress Site URL</label>
                            <input type="url" id="wp_site_url" name="site_url" required value="{{ old('site_url', $brand->website_url) }}" placeholder="https://example.com" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                            <p class="mt-1 text-[11px] text-slate-400">Must start with https://. SSRF protections apply.</p>
                        </div>
                        <div>
                            <label for="wp_username" class="block text-xs font-bold uppercase tracking-wider text-slate-500">WordPress Username</label>
                            <input type="text" id="wp_username" name="username" required value="{{ old('username') }}" placeholder="admin_user" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                        </div>
                        <div>
                            <label for="wp_application_password" class="block text-xs font-bold uppercase tracking-wider text-slate-500">Application Password</label>
                            <input type="password" id="wp_application_password" name="application_password" required autocomplete="new-password" placeholder="xxxx xxxx xxxx xxxx" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm dark:border-slate-700">
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500 dark:bg-slate-900/50 dark:text-slate-400">
                        <p class="font-bold">How to generate an Application Password:</p>
                        <p class="mt-1">In your WordPress admin dashboard, navigate to <strong>Users &rarr; Profile</strong>, scroll down to <strong>Application Passwords</strong>, enter a name (e.g. &quot;BlackTechEngine&quot;), click <em>Add New Application Password</em>, and copy the generated key.</p>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <i data-lucide="plug-2" class="h-4 w-4"></i> Connect &amp; Verify WordPress Site
                    </button>
                </form>
            @endif
        </section>
    </div>
</x-app-layout>
