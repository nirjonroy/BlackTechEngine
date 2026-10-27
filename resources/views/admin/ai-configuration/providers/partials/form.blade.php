@php
    $provider = $aiProvider ?? null;
@endphp

@if($errors->any())
    <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-700 dark:text-rose-300" role="alert">
        <p class="font-bold">The provider metadata could not be saved.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="flex flex-col gap-6">
    @csrf
    @if($method === 'PATCH')
        @method('PATCH')
        <input type="hidden" name="lock_version" value="{{ old('lock_version', $provider->lock_version) }}">
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="key" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Provider key</label>
            <input id="key" name="key" type="text" maxlength="64" required autocomplete="off" value="{{ old('key', $provider?->key) }}" aria-describedby="key-help @error('key') key-error @enderror" @error('key') aria-invalid="true" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 font-mono text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            <p id="key-help" class="mt-2 text-xs text-slate-500 dark:text-slate-400">Normalized to a lowercase hyphenated identifier and limited to 64 characters.</p>
            @error('key')<p id="key-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="display_name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Display name</label>
            <input id="display_name" name="display_name" type="text" maxlength="120" required autocomplete="off" value="{{ old('display_name', $provider?->display_name) }}" @error('display_name') aria-invalid="true" aria-describedby="display-name-error" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            @error('display_name')<p id="display-name-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Description <span class="font-normal normal-case tracking-normal">(optional)</span></label>
        <textarea id="description" name="description" rows="5" maxlength="2000" aria-describedby="description-help @error('description') description-error @enderror" @error('description') aria-invalid="true" @enderror class="mt-2 w-full resize-y rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('description', $provider?->description) }}</textarea>
        <p id="description-help" class="mt-2 text-xs text-slate-500 dark:text-slate-400">Operationally neutral catalog context only. Do not enter credentials, endpoints, or secrets.</p>
        @error('description')<p id="description-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="request_timeout_seconds" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Request timeout</label>
            <div class="relative mt-2"><input id="request_timeout_seconds" name="request_timeout_seconds" type="number" min="1" max="120" required value="{{ old('request_timeout_seconds', $provider?->request_timeout_seconds ?? 30) }}" @error('request_timeout_seconds') aria-invalid="true" aria-describedby="timeout-error" @enderror class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-20 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"><span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs text-slate-400">seconds</span></div>
            @error('request_timeout_seconds')<p id="timeout-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="retry_limit" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Retry limit</label>
            <input id="retry_limit" name="retry_limit" type="number" min="0" max="5" required value="{{ old('retry_limit', $provider?->retry_limit ?? 0) }}" @error('retry_limit') aria-invalid="true" aria-describedby="retry-error" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            @error('retry_limit')<p id="retry-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.ai-configuration.providers') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300">Cancel</a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>{{ $submitLabel }}</button>
    </div>
</form>
