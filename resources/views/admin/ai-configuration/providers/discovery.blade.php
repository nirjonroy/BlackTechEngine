<x-admin.layout title="Gemini Model Discovery" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'AI Models & Routing', 'route' => 'admin.ai-configuration.index'], ['label' => 'Providers', 'route' => 'admin.ai-configuration.providers'], ['label' => 'Discovery preview']]">
    @include('admin.ai-configuration.partials.header')

    <div class="rounded-2xl border p-4 text-xs leading-5 {{ $discovery->complete ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200' : 'border-amber-500/20 bg-amber-500/10 text-amber-800 dark:text-amber-200' }}" role="status">
        <strong>{{ $discovery->complete ? 'Catalog preview complete.' : 'Catalog preview is partial.' }}</strong>
        {{ number_format(count($discovery->models)) }} models were returned across {{ number_format($discovery->pagesFetched) }} page(s).
        @unless($discovery->complete)
            Do not treat this preview as the complete Gemini catalog. Reason: {{ Str::headline($discovery->incompleteReason ?? 'unknown') }}.
        @endunless
    </div>

    <section class="bte-card mt-6 overflow-hidden rounded-3xl border border-slate-200 shadow-xl dark:border-slate-800" aria-labelledby="discovered-models-heading">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between sm:p-7">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-violet-500">Read-only provider response</p>
                <h1 id="discovered-models-heading" class="mt-1 font-heading text-xl font-black">{{ $aiProvider->display_name }} model catalog</h1>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No models were created, updated, enabled, or removed by this preview.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.ai-configuration.providers') }}" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-indigo-400 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:text-slate-300">Back to providers</a>
                <a href="{{ route('admin.ai-configuration.models') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Review saved models</a>
            </div>
        </div>

        @if($discovery->models === [])
            <div class="px-6 py-16 text-center">
                <i data-lucide="scan-search" class="mx-auto h-8 w-8 text-slate-400" aria-hidden="true"></i>
                <h2 class="mt-4 font-heading text-base font-bold">No models returned</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">The trusted Models endpoint returned an empty catalog.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 p-5 sm:p-7 lg:grid-cols-2">
                @foreach($discovery->models as $model)
                    @php($supportsGeneration = in_array('generateContent', $model->supportedGenerationMethods, true))
                    <article class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h2 class="truncate font-heading text-base font-bold">{{ $model->displayName ?: $model->providerModelId }}</h2>
                                <p class="mt-1 break-all font-mono text-[10px] text-slate-400">{{ $model->resourceName }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $supportsGeneration ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-slate-500/10 text-slate-600 dark:text-slate-400' }}">
                                {{ $supportsGeneration ? 'generateContent supported' : ($model->generationMethodsKnown ? 'Not generation-capable' : 'Capability unknown') }}
                            </span>
                        </div>
                        @if($model->description)
                            <p class="mt-3 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $model->description }}</p>
                        @endif
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
                            <div><dt class="text-slate-400">Version</dt><dd class="mt-1 font-bold">{{ $model->version }}</dd></div>
                            <div><dt class="text-slate-400">Thinking metadata</dt><dd class="mt-1 font-bold">{{ $model->supportsThinking === null ? 'Not reported' : ($model->supportsThinking ? 'Supported' : 'Not supported') }}</dd></div>
                            <div><dt class="text-slate-400">Input limit</dt><dd class="mt-1 font-bold">{{ $model->inputTokenLimit === null ? 'Not reported' : number_format($model->inputTokenLimit).' tokens' }}</dd></div>
                            <div><dt class="text-slate-400">Output limit</dt><dd class="mt-1 font-bold">{{ $model->outputTokenLimit === null ? 'Not reported' : number_format($model->outputTokenLimit).' tokens' }}</dd></div>
                        </dl>
                        <div class="mt-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Generation methods</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse($model->supportedGenerationMethods as $method)
                                    <span class="rounded-lg bg-slate-100 px-2 py-1 font-mono text-[10px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $method }}</span>
                                @empty
                                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $model->generationMethodsKnown ? 'None reported' : 'Not reported' }}</span>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-admin.layout>
