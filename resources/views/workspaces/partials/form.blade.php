<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div>
        <label for="name" class="mb-2 block text-sm font-bold">Workspace name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $workspace->name ?? '') }}" required maxlength="120" autocomplete="organization" class="bte-surface w-full rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name') <p id="name-error" class="mt-2 text-sm font-semibold text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="timezone" class="mb-2 block text-sm font-bold">Reporting timezone</label>
        <select id="timezone" name="timezone" required class="bte-surface w-full rounded-xl border border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700">
            @foreach ($timezones as $timezone)
                <option value="{{ $timezone }}" @selected(old('timezone', $workspace->settings['timezone'] ?? 'UTC') === $timezone)>{{ str_replace('_', ' ', $timezone) }}</option>
            @endforeach
        </select>
        @error('timezone') <p class="mt-2 text-sm font-semibold text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-wrap items-center gap-3 pt-2">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
            {{ $submitLabel }} <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
        </button>
        @unless ($onboarding ?? false)
            <a href="{{ route('workspaces.index') }}" class="rounded-xl px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</a>
        @endunless
    </div>
</form>
