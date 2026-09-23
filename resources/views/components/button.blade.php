@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
    'icon' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950 disabled:opacity-50 disabled:cursor-not-allowed select-none active:scale-[0.98] shadow-sm';

    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'md' => 'px-4 py-2 text-sm gap-2',
        'lg' => 'px-5 py-2.5 text-base gap-2.5',
    ][$size] ?? 'px-4 py-2 text-sm gap-2';

    $variantClasses = [
        'primary' => 'bg-indigo-600 hover:bg-indigo-500 text-white focus:ring-indigo-500 shadow-indigo-600/20',
        'secondary' => 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 focus:ring-slate-500',
        'success' => 'bg-emerald-600 hover:bg-emerald-500 text-white focus:ring-emerald-500 shadow-emerald-600/20',
        'danger' => 'bg-rose-600 hover:bg-rose-500 text-white focus:ring-rose-500 shadow-rose-600/20',
        'warning' => 'bg-amber-600 hover:bg-amber-500 text-white focus:ring-amber-500 shadow-amber-600/20',
        'outline' => 'bg-transparent border border-slate-700 hover:bg-slate-800/80 text-slate-300 hover:text-white focus:ring-slate-500',
        'ghost' => 'bg-transparent hover:bg-slate-800/60 text-slate-400 hover:text-white focus:ring-slate-500 shadow-none',
    ][$variant] ?? 'bg-indigo-600 hover:bg-indigo-500 text-white focus:ring-indigo-500';

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => $classes, 'disabled' => $disabled]) }}>
    @if ($icon)
        <span class="shrink-0">{!! $icon !!}</span>
    @endif
    <span>{{ $slot }}</span>
</button>
