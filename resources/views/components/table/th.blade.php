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

<th {{ $attributes->merge(['class' => "px-6 py-3.5 bg-slate-950/80 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800 {$alignClasses}"]) }}>
    {{ $slot }}
</th>
