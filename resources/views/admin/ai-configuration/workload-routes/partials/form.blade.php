@php
    $workloadRoute = $aiWorkloadRoute ?? null;
    $selectedPrimary = old('primary_ai_model_id', $workloadRoute?->primary_ai_model_id);
    $selectedFallback = old('fallback_ai_model_id', $workloadRoute?->fallback_ai_model_id);
@endphp

@if($errors->any())
    <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-700 dark:text-rose-300" role="alert">
        <p class="font-bold">The workload draft could not be saved.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('status'))
    <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300" role="status">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ $action }}" class="flex flex-col gap-6">
    @csrf
    @if($method === 'PATCH')
        @method('PATCH')
        <input type="hidden" name="lock_version" value="{{ old('lock_version', $workloadRoute->lock_version) }}">
    @endif

    <div>
        <label for="workload_key" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Workload key</label>
        <input id="workload_key" name="workload_key" type="text" maxlength="64" required autocomplete="off" value="{{ old('workload_key', $workloadRoute?->workload_key) }}" aria-describedby="workload-key-help @error('workload_key') workload-key-error @enderror" @error('workload_key') aria-invalid="true" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 font-mono text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
        <p id="workload-key-help" class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">A descriptive identifier normalized to lowercase hyphens. It is configuration data only and is never treated as executable code.</p>
        @error('workload_key')<p id="workload-key-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
    </div>

    @if($models->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40" role="note">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200">No persisted models are available</p>
            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">You may save this draft without assignments. No model will be fabricated or selected automatically.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        @foreach([
            ['name' => 'primary_ai_model_id', 'label' => 'Primary model', 'selected' => $selectedPrimary],
            ['name' => 'fallback_ai_model_id', 'label' => 'Fallback model', 'selected' => $selectedFallback],
        ] as $assignment)
            <div>
                <label for="{{ $assignment['name'] }}" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $assignment['label'] }} <span class="font-normal normal-case tracking-normal">(optional)</span></label>
                <select id="{{ $assignment['name'] }}" name="{{ $assignment['name'] }}" @error($assignment['name']) aria-invalid="true" aria-describedby="{{ $assignment['name'] }}-error" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">Not assigned</option>
                    @foreach($models as $model)
                        @php($availability = $model->getRawOriginal('availability_status') ?: 'unknown')
                        <option value="{{ $model->id }}" @selected((int) $assignment['selected'] === $model->id)>{{ $model->display_name }} — {{ $model->provider?->display_name ?? 'Unknown provider' }} / {{ $model->provider_model_id }} [{{ $model->is_enabled ? 'enabled' : 'disabled' }}, {{ $availability }}]</option>
                    @endforeach
                </select>
                @error($assignment['name'])<p id="{{ $assignment['name'] }}-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-xs leading-5 text-amber-800 dark:text-amber-200" role="note">
        Saving assignments does not enable a model, activate fallback behavior, contact a provider, dispatch a job, or execute this workload. Capability compatibility must be validated in a future activation phase.
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.ai-configuration.workload-routes') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300">Cancel</a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>{{ $submitLabel }}</button>
    </div>
</form>
