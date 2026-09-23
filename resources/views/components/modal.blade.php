@props([
    'name' => 'modal',
    'title' => null,
    'size' => 'md',
    'show' => false,
])

@php
    $maxWidth = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ][$size] ?? 'max-w-md';
@endphp

<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    style="display: none;"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"
        @click="show = false"
    ></div>

    <!-- Modal Box -->
    <div
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl transform transition-all w-full {{ $maxWidth }} z-10 my-8"
    >
        @if ($title)
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-base font-bold text-white tracking-tight">{{ $title }}</h3>
                <button
                    type="button"
                    @click="show = false"
                    class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="p-6 space-y-4 text-sm text-slate-300">
            {{ $slot }}
        </div>

        @if (isset($footer))
            <div class="px-6 py-4 bg-slate-950/50 border-t border-slate-800 flex items-center justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
