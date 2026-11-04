@props([
    'showLabel' => false,
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => 'p-1.5 text-xs',
        'md' => 'p-2 text-sm',
        'lg' => 'p-2.5 text-base',
    ][$size] ?? 'p-2 text-sm';

    $iconSizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
    ][$size] ?? 'w-5 h-5';
@endphp

<div
    x-data="{
        isDark: document.documentElement.classList.contains('dark'),
        init() {
            // Watch for changes in classList or storage
            this.isDark = document.documentElement.classList.contains('dark');
        },
        toggle() {
            if (window.themeManager) {
                window.themeManager.toggle();
                this.isDark = document.documentElement.classList.contains('dark');
            } else {
                this.isDark = !this.isDark;
                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
            }
        }
    }"
    @theme-changed.window="isDark = ($event.detail.theme === 'dark')"
    class="inline-flex items-center"
>
    <button
        type="button"
        @click="toggle()"
        :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
        :aria-label="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
        {{ $attributes->merge([
            'class' => "relative inline-flex items-center gap-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 cursor-pointer {$sizeClasses}"
        ]) }}
    >
        <span class="relative flex items-center justify-center {{ $iconSizes }}">
            <!-- Sun Icon (Active when dark mode is ON, click to switch to light) -->
            <svg
                x-show="isDark"
                x-cloak
                class="{{ $iconSizes }} text-amber-400 transform transition-transform duration-300 hover:rotate-45"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>

            <!-- Moon Icon (Active when light mode is ON, click to switch to dark) -->
            <svg
                x-show="!isDark"
                class="{{ $iconSizes }} text-indigo-600 dark:text-indigo-400 transform transition-transform duration-300 hover:-rotate-12"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </span>

        @if ($showLabel)
            <span
                x-text="isDark ? 'Mode Terang' : 'Mode Gelap'"
                class="text-xs font-medium text-slate-600 dark:text-slate-300 select-none"
            >
                Tema
            </span>
        @endif
    </button>
</div>
