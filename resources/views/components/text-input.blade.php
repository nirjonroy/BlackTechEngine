@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bte-surface rounded-xl border-slate-300 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:text-white']) }}>
