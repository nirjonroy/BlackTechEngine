<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    @if ($mode === 'create')
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
        <input type="hidden" name="status" value="draft">
    @endif

    @error('generation')
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200" role="alert">{{ $message }}</div>
    @enderror

    <section class="bte-card rounded-2xl p-5 sm:p-6">
        <div class="mb-5"><h2 class="font-heading text-lg font-black">Content brief</h2><p class="mt-1 text-sm text-slate-500">Brand context is included automatically. Review every generated claim before use.</p></div>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div><label for="content_type" class="mb-2 block text-sm font-bold">Content type</label><select id="content_type" name="content_type" required class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">@foreach($contentTypes as $type)<option value="{{ $type->value }}" @selected(old('content_type', $aiContentDraft->content_type->value ?? '') === $type->value)>{{ $type->label() }}</option>@endforeach</select>@error('content_type')<p class="mt-2 text-sm text-rose-500" role="alert">{{ $message }}</p>@enderror</div>
            <div><label for="content_length" class="mb-2 block text-sm font-bold">Content length</label><select id="content_length" name="content_length" required class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">@foreach($contentLengths as $length)<option value="{{ $length->value }}" @selected(old('content_length', $aiContentDraft->content_length->value ?? 'standard') === $length->value)>{{ $length->label() }}</option>@endforeach</select>@error('content_length')<p class="mt-2 text-sm text-rose-500" role="alert">{{ $message }}</p>@enderror</div>
        </div>
        <div class="mt-5"><label for="topic" class="mb-2 block text-sm font-bold">Primary topic</label><input id="topic" name="topic" value="{{ old('topic', $aiContentDraft->topic ?? '') }}" required maxlength="240" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">@error('topic')<p class="mt-2 text-sm text-rose-500" role="alert">{{ $message }}</p>@enderror</div>
        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
            <div><label for="primary_keyword" class="mb-2 block text-sm font-bold">Primary keyword</label><input id="primary_keyword" name="primary_keyword" value="{{ old('primary_keyword', $aiContentDraft->primary_keyword ?? '') }}" required maxlength="160" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">@error('primary_keyword')<p class="mt-2 text-sm text-rose-500" role="alert">{{ $message }}</p>@enderror</div>
            <div><label for="secondary_keywords" class="mb-2 block text-sm font-bold">Secondary keywords</label><textarea id="secondary_keywords" name="secondary_keywords" rows="3" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700" placeholder="One keyword per line">{{ implode("\n", (array) old('secondary_keywords', $aiContentDraft->secondary_keywords ?? [])) }}</textarea>@error('secondary_keywords.*')<p class="mt-2 text-sm text-rose-500" role="alert">{{ $message }}</p>@enderror</div>
        </div>
        <div class="mt-5"><label for="target_audience" class="mb-2 block text-sm font-bold">Target audience</label><textarea id="target_audience" name="target_audience" rows="3" required maxlength="2000" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">{{ old('target_audience', $aiContentDraft->target_audience ?? $brand->target_audience) }}</textarea>@error('target_audience')<p class="mt-2 text-sm text-rose-500" role="alert">{{ $message }}</p>@enderror</div>
        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div><label for="language" class="mb-2 block text-sm font-bold">Language</label><input id="language" name="language" value="{{ old('language', $aiContentDraft->language ?? $brand->preferred_language) }}" required maxlength="64" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></div>
            <div><label for="region" class="mb-2 block text-sm font-bold">Region</label><input id="region" name="region" value="{{ old('region', $aiContentDraft->region ?? $brand->primary_market) }}" required maxlength="120" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></div>
            <div><label for="tone" class="mb-2 block text-sm font-bold">Tone</label><input id="tone" name="tone" value="{{ old('tone', $aiContentDraft->tone ?? $brand->voice_tone) }}" required maxlength="500" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></div>
        </div>
    </section>

    @if ($mode === 'edit')
        <section class="bte-card rounded-2xl p-5 sm:p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-heading text-lg font-black">Draft editor</h2><p class="mt-1 text-sm text-slate-500">Content is stored as text and remains unpublished.</p></div><select name="status" aria-label="Draft status" class="bte-surface rounded-xl border border-slate-300 text-sm dark:border-slate-700">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $aiContentDraft->status->value) === $status->value)>{{ str($status->value)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div><label for="seo_title" class="mb-2 block text-sm font-bold">SEO title</label><input id="seo_title" name="seo_title" value="{{ old('seo_title', $aiContentDraft->seo_title) }}" maxlength="120" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></div>
                <div><label for="suggested_slug" class="mb-2 block text-sm font-bold">Suggested slug</label><input id="suggested_slug" name="suggested_slug" value="{{ old('suggested_slug', $aiContentDraft->suggested_slug) }}" maxlength="191" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></div>
            </div>
            <div class="mt-5"><label for="meta_description" class="mb-2 block text-sm font-bold">Meta description</label><textarea id="meta_description" name="meta_description" rows="3" maxlength="320" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">{{ old('meta_description', $aiContentDraft->meta_description) }}</textarea></div>
            <div class="mt-5"><label for="outline" class="mb-2 block text-sm font-bold">Heading outline</label><textarea id="outline" name="outline" rows="6" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">{{ implode("\n", (array) old('outline', $aiContentDraft->outline)) }}</textarea></div>
            <div class="mt-5"><label for="content" class="mb-2 block text-sm font-bold">Main content (Markdown)</label><textarea id="content" name="content" rows="24" maxlength="100000" class="bte-surface w-full rounded-xl border border-slate-300 font-mono text-sm leading-6 dark:border-slate-700">{{ old('content', $aiContentDraft->content) }}</textarea>@error('content')<p class="mt-2 text-sm text-rose-500" role="alert">{{ $message }}</p>@enderror</div>
            <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                @foreach(['internal_link_opportunities' => 'Internal-link opportunities', 'external_research_topics' => 'External research topics', 'verification_notes' => 'Human verification notes'] as $field => $label)
                    <div><label for="{{ $field }}" class="mb-2 block text-sm font-bold">{{ $label }}</label><textarea id="{{ $field }}" name="{{ $field }}" rows="7" class="bte-surface w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700">{{ implode("\n", (array) old($field, $aiContentDraft->{$field})) }}</textarea></div>
                @endforeach
            </div>
            <div class="mt-5" x-data="{ faqs: {{ Js::from(old('faq_entries', $aiContentDraft->faq_entries)) }} }">
                <div class="flex items-center justify-between gap-3"><label class="text-sm font-bold">FAQ entries</label><button type="button" x-on:click="faqs.push({ question: '', answer: '' })" class="rounded-lg px-3 py-2 text-sm font-bold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950">Add FAQ</button></div>
                <div class="mt-3 space-y-3"><template x-for="(faq, index) in faqs" x-bind:key="index"><div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><div class="flex justify-end"><button type="button" x-on:click="faqs.splice(index, 1)" class="text-xs font-bold text-rose-500">Remove</button></div><input x-bind:name="`faq_entries[${index}][question]`" x-model="faq.question" placeholder="Question" maxlength="500" class="bte-surface mt-2 w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"><textarea x-bind:name="`faq_entries[${index}][answer]`" x-model="faq.answer" placeholder="Answer" rows="3" maxlength="2000" class="bte-surface mt-3 w-full rounded-xl border border-slate-300 text-sm dark:border-slate-700"></textarea></div></template></div>
            </div>
        </section>
    @endif

    <div class="flex flex-wrap gap-3">
        @if ($mode === 'create')
            <button type="submit" formaction="{{ route('content-studio.generations.store', [$workspace, $brand]) }}" @disabled(! config('ai.content_generation_enabled')) class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white disabled:cursor-not-allowed disabled:opacity-50"><i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>Generate with AI</button>
            <button type="submit" formaction="{{ route('content-studio.drafts.store', [$workspace, $brand]) }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-black hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Save brief as draft</button>
            @unless(config('ai.content_generation_enabled'))<p class="basis-full text-xs font-semibold text-amber-600 dark:text-amber-300">AI generation is disabled. You can save the brief without generating content.</p>@endunless
        @else
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white"><i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>Save draft</button>
        @endif
        <a href="{{ route('content-studio.index', ['workspace' => $workspace->id, 'brand' => $brand->id]) }}" class="rounded-xl px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</a>
    </div>
</form>
