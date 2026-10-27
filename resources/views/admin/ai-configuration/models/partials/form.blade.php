@php
    $model = $aiModel ?? null;
    $oldCapabilities = old('capabilities', $model?->capabilities ?? []);
    $selectedCapabilities = is_array($oldCapabilities) ? array_values($oldCapabilities) : [];
@endphp

@if($errors->any())
    <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-700 dark:text-rose-300" role="alert"><p class="font-bold">The model metadata could not be saved.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $action }}" class="flex flex-col gap-6">
    @csrf
    @if($method === 'PATCH')
        @method('PATCH')
        <input type="hidden" name="lock_version" value="{{ old('lock_version', $model->lock_version) }}">
    @endif

    <div>
        <label for="ai_provider_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Provider</label>
        <select id="ai_provider_id" name="ai_provider_id" required @error('ai_provider_id') aria-invalid="true" aria-describedby="provider-error" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            <option value="">Select a persisted provider</option>
            @foreach($providers as $provider)
                <option value="{{ $provider->id }}" @selected((int) old('ai_provider_id', $model?->ai_provider_id) === $provider->id)>{{ $provider->display_name }} ({{ $provider->key }})</option>
            @endforeach
        </select>
        @error('ai_provider_id')<p id="provider-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div class="min-w-0">
            <label for="provider_model_id" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Provider-native model identifier</label>
            <input id="provider_model_id" name="provider_model_id" type="text" maxlength="191" required autocomplete="off" value="{{ old('provider_model_id', $model?->provider_model_id) }}" aria-describedby="model-id-help @error('provider_model_id') model-id-error @enderror" @error('provider_model_id') aria-invalid="true" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 font-mono text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            <p id="model-id-help" class="mt-2 break-words text-xs text-slate-500 dark:text-slate-400">Stored as supplied after trimming outer whitespace. Matching is case-insensitive within each provider.</p>
            @error('provider_model_id')<p id="model-id-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="display_name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Display name</label>
            <input id="display_name" name="display_name" type="text" maxlength="120" required autocomplete="off" value="{{ old('display_name', $model?->display_name) }}" @error('display_name') aria-invalid="true" aria-describedby="display-name-error" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            @error('display_name')<p id="display-name-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="modality" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Modality</label>
        <select id="modality" name="modality" required @error('modality') aria-invalid="true" aria-describedby="modality-error" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="">Select a supported modality</option>@foreach($modalities as $modality)<option value="{{ $modality->value }}" @selected(old('modality', $model?->getRawOriginal('modality')) === $modality->value)>{{ Str::headline($modality->value) }}</option>@endforeach</select>
        @error('modality')<p id="modality-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
    </div>

    <fieldset>
        <legend class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Capabilities</legend>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Select only capabilities declared by the current application allowlist.</p>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach($capabilities as $capability)
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-cyan-500 focus-within:ring-2 focus-within:ring-cyan-500 dark:border-slate-700"><input type="checkbox" name="capabilities[]" value="{{ $capability->value }}" @checked(in_array($capability->value, $selectedCapabilities, true)) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900"><span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ Str::headline($capability->value) }}</span></label>
            @endforeach
        </div>
        @error('capabilities')<p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        @error('capabilities.*')<p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
    </fieldset>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end"><a href="{{ route('admin.ai-configuration.models') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300">Cancel</a><button type="submit" @disabled($providers->isEmpty()) class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"><i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>{{ $submitLabel }}</button></div>
</form>
