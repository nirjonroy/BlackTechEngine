@props(['current'])

@php
    $steps = ['Workspace', 'Brand profile', 'Confirm'];
@endphp

<div {{ $attributes->merge(['class' => 'mb-6']) }} aria-label="Onboarding progress">
    <div class="grid grid-cols-3 gap-2">
        @foreach ($steps as $number => $label)
            @php $step = $number + 1; @endphp
            <div class="flex min-w-0 items-center gap-2 text-xs font-bold {{ $step <= $current ? 'text-indigo-600 dark:text-indigo-300' : 'text-slate-400' }}">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $step <= $current ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">{{ $step }}</span>
                <span class="hidden truncate sm:block">{{ $label }}</span>
            </div>
        @endforeach
    </div>
    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"><div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-cyan-400 {{ match ((int) $current) { 1 => 'w-1/3', 2 => 'w-2/3', default => 'w-full' } }}"></div></div>
</div>
