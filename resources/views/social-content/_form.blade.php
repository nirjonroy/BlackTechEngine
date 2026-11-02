@php
    $existing = isset($socialPost) ? $socialPost->variants->keyBy(fn ($variant) => $variant->platform->value) : collect();
@endphp
<div class="space-y-6">
    @if (! isset($socialPost))
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
    @endif
    <section class="bte-card rounded-2xl p-5 sm:p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <label class="block"><span class="text-sm font-bold">Internal title</span><input name="title" value="{{ old('title', $socialPost->title ?? '') }}" required maxlength="180" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"></label>
            <label class="block"><span class="text-sm font-bold">Campaign (optional)</span><input name="campaign" value="{{ old('campaign', $socialPost->campaign ?? '') }}" maxlength="120" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"></label>
        </div>
        @if (! isset($socialPost) && $sources->isNotEmpty())
            <label class="mt-5 block"><span class="text-sm font-bold">Repurpose an approved Content Studio article (optional)</span><select name="source_ai_content_draft_id" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"><option value="">Start without an article</option>@foreach ($sources as $source)<option value="{{ $source->id }}" @selected(old('source_ai_content_draft_id') == $source->id)>{{ $source->seo_title ?: $source->topic }} · revision {{ $source->current_revision }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">A revision snapshot is retained; later article changes are shown as stale and never overwrite social copy.</span></label>
        @endif
    </section>

    <section class="space-y-4" aria-labelledby="platform-variants-heading">
        <div><h2 id="platform-variants-heading" class="font-heading text-xl font-black">Platform variants</h2><p class="mt-1 text-sm text-slate-500">Add at least one variant. Limits are advisory; review each platform before manual publishing.</p></div>
        @foreach ($platforms as $index => $platform)
            @php $variant = $existing->get($platform->value); @endphp
            <article class="bte-card rounded-2xl p-5 sm:p-6" x-data="{ enabled: {{ old("variants.$index.platform", $variant?->platform?->value) ? 'true' : ($index === 0 ? 'true' : 'false') }} }">
                <label class="flex items-center gap-3 font-bold"><input type="checkbox" x-model="enabled" class="rounded border-slate-300 text-indigo-600"> {{ $platform->label() }} <span class="text-xs font-medium text-slate-500">Advisory {{ number_format($platform->advisoryLimit()) }} characters</span></label>
                <div x-show="enabled" class="mt-5 grid gap-4" x-cloak>
                    <input type="hidden" name="variants[{{ $index }}][platform]" value="{{ $platform->value }}" :disabled="!enabled">
                    <label><span class="text-sm font-bold">Caption</span><textarea name="variants[{{ $index }}][caption]" rows="5" maxlength="10000" :required="enabled" :disabled="!enabled" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">{{ old("variants.$index.caption", $variant?->caption) }}</textarea></label>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label><span class="text-sm font-bold">Hashtags</span><input name="variants[{{ $index }}][hashtags]" value="{{ old("variants.$index.hashtags", implode(' ', $variant?->hashtags ?? [])) }}" :disabled="!enabled" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"></label>
                        <label><span class="text-sm font-bold">CTA text</span><input name="variants[{{ $index }}][cta_text]" value="{{ old("variants.$index.cta_text", $variant?->cta_text) }}" maxlength="160" :disabled="!enabled" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"></label>
                        <label><span class="text-sm font-bold">Target URL</span><input type="url" name="variants[{{ $index }}][target_url]" value="{{ old("variants.$index.target_url", $variant?->target_url) }}" :disabled="!enabled" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"></label>
                        <label><span class="text-sm font-bold">Existing media reference</span><input name="variants[{{ $index }}][media_reference]" value="{{ old("variants.$index.media_reference", $variant?->media_reference) }}" placeholder="social/example-image.jpg" :disabled="!enabled" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"><span class="mt-1 block text-xs text-slate-500">References only; uploads and external credentials are not part of this phase.</span></label>
                    </div>
                    <label><span class="text-sm font-bold">Image alt text</span><input name="variants[{{ $index }}][image_alt_text]" value="{{ old("variants.$index.image_alt_text", $variant?->image_alt_text) }}" maxlength="500" :disabled="!enabled" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900"></label>
                </div>
            </article>
        @endforeach
    </section>
    @if (isset($socialPost))<input type="hidden" name="lock_version" value="{{ $socialPost->lock_version }}">@endif
    <div class="flex flex-wrap gap-3"><button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Save social draft</button><a href="{{ route('social-content.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold dark:border-slate-700">Cancel</a></div>
</div>
