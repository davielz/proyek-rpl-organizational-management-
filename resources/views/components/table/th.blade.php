@props([
    'align' => 'left',
])

@php
    $alignClasses = [
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
    ][$align] ?? 'text-left';
@endphp

<th {{ $attributes->merge(['class' => "px-6 py-3.5 bg-slate-50 dark:bg-[#06091a] text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 {$alignClasses} transition-colors duration-200"]) }}>
    {{ $slot }}
</th>
