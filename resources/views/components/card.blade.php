@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm dark:shadow-2xl overflow-hidden text-slate-800 dark:text-slate-100 transition-colors duration-200']) }}>
    {{ $slot }}
</div>
