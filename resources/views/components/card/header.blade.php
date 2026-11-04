@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4 transition-colors duration-200']) }}>
    <div>
        @if ($title)
            <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">{{ $title }}</h3>
        @endif
        @if ($subtitle)
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
    @if (isset($action))
        <div class="shrink-0 flex items-center gap-2">
            {{ $action }}
        </div>
    @endif
</div>
