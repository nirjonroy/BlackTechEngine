@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-bold text-slate-700 dark:text-slate-300']) }}>
    {{ $value ?? $slot }}
</label>
