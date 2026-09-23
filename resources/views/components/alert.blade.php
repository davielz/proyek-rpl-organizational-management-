@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => true,
])

@php
    $typeClasses = [
        'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
        'error' => 'bg-rose-500/10 border-rose-500/30 text-rose-400',
        'warning' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
        'info' => 'bg-sky-500/10 border-sky-500/30 text-sky-400',
    ][$type] ?? 'bg-sky-500/10 border-sky-500/30 text-sky-400';

    $icons = [
        'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ];
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    {{ $attributes->merge(['class' => "p-4 border rounded-2xl flex items-start gap-3 shadow-lg {$typeClasses}"]) }}
>
    <div class="shrink-0 mt-0.5">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {!! $icons[$type] ?? $icons['info'] !!}
        </svg>
    </div>

    <div class="flex-1 text-sm space-y-0.5">
        @if ($title)
            <h4 class="font-semibold text-white tracking-tight">{{ $title }}</h4>
        @endif
        <div class="leading-relaxed">
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            @click="show = false"
            class="shrink-0 text-current opacity-60 hover:opacity-100 transition-opacity p-0.5 rounded-lg hover:bg-white/10"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    @endif
</div>
