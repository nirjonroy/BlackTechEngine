<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-500">{{ $workspace->name }} &middot; {{ $brand->name }}</p>
            <h1 class="truncate font-heading text-xl font-black sm:text-2xl">{{ $aiContentDraft->seo_title ?: $aiContentDraft->topic }}</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
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
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black uppercase text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ $aiContentDraft->status->label() }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">Unpublished draft &middot; Private</span>
                @if($reviewEnabled)<span class="rounded-full bg-slate-100 px-3 py-1 font-mono text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">v{{ $aiContentDraft->lock_version }} / r{{ $aiContentDraft->current_revision }}</span>@endif
            </div>
            <a href="{{ route('content-studio.drafts.edit', [$workspace, $brand, $aiContentDraft]) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                Edit draft
            </a>
        </div>

        @if($reviewEnabled)
            <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                @if($aiContentDraft->status === \App\Enums\AiContentDraftStatus::Draft)
                    <form method="POST" action="{{ route('content-studio.drafts.review-requests.store', [$workspace, $brand, $aiContentDraft]) }}" class="bte-card rounded-2xl p-5 sm:p-6">
                        @csrf
                        <input type="hidden" name="lock_version" value="{{ $aiContentDraft->lock_version }}">
                        <h2 class="font-heading text-lg font-black">Request owner review</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Submit the current revision for review. Any later edit requires a new review.</p>
                        <label for="review-notes" class="mt-4 block text-sm font-bold">Reviewer context <span class="font-normal text-slate-500">(optional)</span></label>
                        <textarea id="review-notes" name="notes" rows="3" maxlength="2000" class="bte-surface mt-2 w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></textarea>
                        <button type="submit" class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white">Request review</button>
                    </form>
                @endif

                @can('approve', $aiContentDraft)
                    @if($aiContentDraft->status === \App\Enums\AiContentDraftStatus::NeedsReview)
                        <div class="bte-card rounded-2xl p-5 sm:p-6">
                            <h2 class="font-heading text-lg font-black">Owner review decision</h2>
                            <form method="POST" action="{{ route('content-studio.drafts.approval.store', [$workspace, $brand, $aiContentDraft]) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="lock_version" value="{{ $aiContentDraft->lock_version }}">
                                <label for="approval-notes" class="block text-sm font-bold">Approval notes <span class="font-normal text-slate-500">(optional)</span></label>
                                <textarea id="approval-notes" name="notes" rows="3" maxlength="2000" class="bte-surface mt-2 w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></textarea>
                                <button type="submit" class="mt-3 inline-flex rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Approve for export</button>
                            </form>
                            <form method="POST" action="{{ route('content-studio.drafts.change-requests.store', [$workspace, $brand, $aiContentDraft]) }}" class="mt-5 border-t border-slate-200 pt-5 dark:border-slate-700">
                                @csrf
                                <input type="hidden" name="lock_version" value="{{ $aiContentDraft->lock_version }}">
                                <label for="change-notes" class="block text-sm font-bold">Required changes</label>
                                <textarea id="change-notes" name="notes" rows="3" required maxlength="2000" class="bte-surface mt-2 w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></textarea>
                                <button type="submit" class="mt-3 inline-flex rounded-xl border border-amber-400 px-4 py-2.5 text-sm font-black text-amber-700 dark:text-amber-300">Request changes</button>
                            </form>
                        </div>
                    @endif
                @endcan

                @if(in_array($aiContentDraft->status, [\App\Enums\AiContentDraftStatus::Approved, \App\Enums\AiContentDraftStatus::ExportReady], true))
                    <form method="POST" action="{{ route('content-studio.drafts.exports.store', [$workspace, $brand, $aiContentDraft]) }}" class="bte-card rounded-2xl p-5 sm:p-6">
                        @csrf
                        <input type="hidden" name="lock_version" value="{{ $aiContentDraft->lock_version }}">
                        <h2 class="font-heading text-lg font-black">Manual export</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Download a copy-ready file. Export does not publish this content or contact an external CMS.</p>
                        <label for="export-format" class="mt-4 block text-sm font-bold">Format</label>
                        <select id="export-format" name="format" class="bte-surface mt-2 w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">
                            <option value="text">Plain text</option>
                            <option value="markdown">Markdown</option>
                            <option value="html">Sanitized HTML</option>
                        </select>
                        <button type="submit" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white"><i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>Download export</button>
                    </form>

                    @php
                        $wpConn = $brand->wordPressConnection;
                        $latestWpPost = $aiContentDraft->latestWordPressPublication;
                    @endphp
                    <div class="bte-card rounded-2xl p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="font-heading text-lg font-black">WordPress Publishing</h2>
                            @if($wpConn && $wpConn->isConnected())
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Connected
                                </span>
                            @endif
                        </div>

                        @if($wpConn && $wpConn->isConnected())
                            <p class="mt-2 text-sm leading-6 text-slate-500">Directly publish this approved article to <strong>{{ $wpConn->site_name ?: $wpConn->site_url }}</strong>.</p>

                            @if($latestWpPost && $latestWpPost->wordpress_post_id)
                                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs dark:border-emerald-900/50 dark:bg-emerald-950/30">
                                    <p class="font-bold text-emerald-800 dark:text-emerald-300">Published to WordPress (Post #{{ $latestWpPost->wordpress_post_id }})</p>
                                    <p class="mt-1 text-slate-600 dark:text-slate-400">Status: <span class="font-bold uppercase">{{ $latestWpPost->status->label() }}</span> &middot; {{ $latestWpPost->published_at?->toDayDateTimeString() }}</p>
                                    @if($latestWpPost->post_url)
                                        <a href="{{ $latestWpPost->post_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 font-bold text-indigo-600 hover:underline dark:text-indigo-400">
                                            View on WordPress <i data-lucide="external-link" class="h-3 w-3"></i>
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <form method="POST" action="{{ route('content-studio.drafts.wordpress.publish', [$workspace, $brand, $aiContentDraft]) }}" class="mt-4 space-y-3">
                                @csrf
                                <div>
                                    <label for="target_status" class="block text-sm font-bold">Publish status</label>
                                    <select id="target_status" name="target_status" class="bte-surface mt-1.5 w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">
                                        <option value="draft">Save as WordPress Draft (Recommended)</option>
                                        <option value="publish">Publish Live</option>
                                    </select>
                                </div>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                    <i data-lucide="send" class="h-4 w-4"></i>
                                    {{ $latestWpPost && $latestWpPost->wordpress_post_id ? 'Update on WordPress' : 'Publish to WordPress' }}
                                </button>
                            </form>
                        @else
                            <div class="mt-4 rounded-xl border border-dashed border-slate-300 p-4 text-center dark:border-slate-700">
                                <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">No active WordPress connection found for this brand.</p>
                                <a href="{{ route('workspaces.brands.show', [$workspace, $brand]) }}" class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-400">
                                    Connect WordPress in Brand Settings <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </section>

            @if($aiContentDraft->review_feedback)
                <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/40">
                    <h2 class="font-heading text-lg font-black text-amber-900 dark:text-amber-200">Latest reviewer feedback</h2>
                    <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-amber-900 dark:text-amber-200">{{ $aiContentDraft->review_feedback }}</p>
                </section>
            @endif
        @endif

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="bte-card rounded-2xl p-5 lg:col-span-2">
                <h2 class="font-heading text-lg font-black">Metadata</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div><dt class="font-bold text-slate-500">SEO title</dt><dd class="mt-1 break-words">{{ $aiContentDraft->seo_title ?: 'Not set' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Meta description</dt><dd class="mt-1 break-words">{{ $aiContentDraft->meta_description ?: 'Not set' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Suggested slug</dt><dd class="mt-1 break-all font-mono">{{ $aiContentDraft->suggested_slug ?: 'Not set' }}</dd></div>
                </dl>
            </div>
            <aside class="bte-card rounded-2xl p-5">
                <h2 class="font-heading text-lg font-black">Provenance</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Created by</dt><dd class="font-bold">{{ $aiContentDraft->creator?->name ?? 'Former customer' }}</dd></div>
                    <div><dt class="text-slate-500">Provider/model</dt><dd class="break-words font-bold">{{ $aiContentDraft->model?->provider?->display_name ?? 'Manual draft' }}{{ $aiContentDraft->model ? ' · '.$aiContentDraft->model->display_name : '' }}</dd></div>
                    <div><dt class="text-slate-500">Updated</dt><dd class="font-bold">{{ $aiContentDraft->updated_at->format('M j, Y g:i A') }}</dd></div>
                    @if($reviewEnabled && $aiContentDraft->approved_at)<div><dt class="text-slate-500">Approved</dt><dd class="font-bold">{{ $aiContentDraft->approved_at->format('M j, Y g:i A') }} by {{ $aiContentDraft->approver?->name ?? 'Former customer' }}</dd></div>@endif
                </dl>
            </aside>
        </section>

        <section class="bte-card min-w-0 rounded-2xl p-5 sm:p-7">
            <h2 class="font-heading text-lg font-black">{{ $reviewEnabled && in_array($aiContentDraft->status, [\App\Enums\AiContentDraftStatus::Approved, \App\Enums\AiContentDraftStatus::ExportReady], true) ? 'Approved content preview' : 'Content preview' }}</h2>
            @if($aiContentDraft->content)
                @if($reviewEnabled)
                    <div class="mt-5 break-words text-sm leading-7 text-slate-700 dark:text-slate-300 [&_a]:text-indigo-600 [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-slate-300 [&_blockquote]:pl-4 [&_h1]:mt-6 [&_h1]:text-2xl [&_h1]:font-black [&_h2]:mt-6 [&_h2]:text-xl [&_h2]:font-black [&_h3]:mt-5 [&_h3]:text-lg [&_h3]:font-bold [&_li]:ml-5 [&_ol]:list-decimal [&_p]:mt-3 [&_ul]:list-disc">{!! $previewHtml !!}</div>
                @else
                    <div class="mt-5 whitespace-pre-wrap break-words text-sm leading-7 text-slate-700 dark:text-slate-300">{{ $aiContentDraft->content }}</div>
                @endif
            @else
                <p class="mt-4 text-sm text-slate-500">This brief does not contain generated or written content yet.</p>
            @endif
        </section>

        <!-- Contextual Internal Linking Section -->
        <section class="bte-card rounded-2xl p-5 sm:p-7">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-heading text-lg font-black">Contextual Internal Link Suggestions</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Automatically matched relevant pages from your indexed site. Select suggestions to insert natural anchor links.
                    </p>
                </div>
                <a href="{{ route('workspaces.brands.internal-links.index', [$workspace, $brand]) }}" class="text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-400">
                    Manage Index ({{ $brand->indexedPages()->count() }} pages) &rarr;
                </a>
            </div>

            @if(empty($internalLinkSuggestions))
                <div class="mt-4 rounded-xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-500 dark:border-slate-800">
                    No unmatched internal link opportunities detected in this draft.
                </div>
            @else
                @php
                    $isEditable = ! in_array($aiContentDraft->status, [\App\Enums\AiContentDraftStatus::Approved, \App\Enums\AiContentDraftStatus::ExportReady], true);
                @endphp

                @if(! $isEditable)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                        <strong>Note:</strong> Internal links cannot modify approved or export-ready content automatically. Return to review or edit draft to apply new links.
                    </div>
                @endif

                <form method="POST" action="{{ route('content-studio.drafts.internal-links.apply', [$workspace, $brand, $aiContentDraft]) }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="space-y-2">
                        @foreach($internalLinkSuggestions as $idx => $sug)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-3.5 text-xs dark:border-slate-800">
                                <div class="flex items-start gap-3">
                                    @if($isEditable)
                                        <input type="checkbox" name="links[{{ $idx }}][anchor]" value="{{ $sug['anchor'] }}" checked class="mt-1 rounded text-indigo-600">
                                        <input type="hidden" name="links[{{ $idx }}][url]" value="{{ $sug['url'] }}">
                                    @endif
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 dark:text-white">Anchor: "{{ $sug['anchor'] }}"</span>
                                            <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                                Relevance {{ $sug['relevance_score'] }}%
                                            </span>
                                        </div>
                                        <p class="text-slate-500 mt-0.5">Target: <a href="{{ $sug['url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-indigo-600 hover:underline">{{ $sug['title'] }}</a></p>
                                        <p class="text-slate-400 font-mono text-[11px] mt-1">{{ $sug['context'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($isEditable)
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white hover:bg-indigo-700">
                                Insert Selected Internal Links
                            </button>
                        </div>
                    @endif
                </form>
            @endif
        </section>

        @if($aiContentDraft->faq_entries !== [])
            <section class="bte-card rounded-2xl p-5 sm:p-7">
                <h2 class="font-heading text-lg font-black">FAQ content</h2>
                <div class="mt-4 space-y-4">@foreach($aiContentDraft->faq_entries as $faq)<div><h3 class="break-words font-bold">{{ $faq['question'] }}</h3><p class="mt-1 whitespace-pre-wrap break-words text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $faq['answer'] }}</p></div>@endforeach</div>
            </section>
        @endif

        @if($aiContentDraft->verification_notes !== [])
            <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/40">
                <h2 class="font-heading text-lg font-black text-amber-900 dark:text-amber-200">Human verification required</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-amber-900 dark:text-amber-200">@foreach($aiContentDraft->verification_notes as $note)<li class="break-words">{{ $note }}</li>@endforeach</ul>
            </section>
        @endif

        @if($reviewEnabled)
            <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="bte-card rounded-2xl p-5 sm:p-6">
                    <h2 class="font-heading text-lg font-black">Review history</h2>
                    <ol class="mt-4 space-y-4">
                        @forelse($aiContentDraft->editorialEvents as $event)
                            <li class="border-l-2 border-indigo-200 pl-4 dark:border-indigo-900">
                                <div class="flex flex-wrap items-baseline justify-between gap-2"><p class="font-bold">{{ str($event->action)->replace('_', ' ')->title() }}</p><time class="text-xs text-slate-500">{{ $event->created_at->format('M j, Y g:i A') }}</time></div>
                                <p class="mt-1 text-xs text-slate-500">{{ $event->actor?->name ?? 'Former customer' }} &middot; version {{ $event->lock_version }}</p>
                                @if($event->notes)<p class="mt-2 break-words text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $event->notes }}</p>@endif
                            </li>
                        @empty
                            <li class="text-sm text-slate-500">No editorial actions recorded yet.</li>
                        @endforelse
                    </ol>
                </div>
                <div class="bte-card rounded-2xl p-5 sm:p-6">
                    <h2 class="font-heading text-lg font-black">Revision history</h2>
                    <ol class="mt-4 space-y-4">
                        @forelse($aiContentDraft->revisions as $revision)
                            <li class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                <div class="flex flex-wrap items-baseline justify-between gap-2"><p class="font-bold">Revision {{ $revision->revision_number }}</p><time class="text-xs text-slate-500">{{ $revision->created_at->format('M j, Y g:i A') }}</time></div>
                                <p class="mt-1 text-xs text-slate-500">{{ $revision->editor?->name ?? 'Former customer' }}</p>
                                <p class="mt-2 break-words text-sm text-slate-600 dark:text-slate-400">Changed: {{ collect($revision->changed_fields)->map(fn ($field) => str($field)->replace('_', ' '))->join(', ') }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500">No revisions recorded yet.</li>
                        @endforelse
                    </ol>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
