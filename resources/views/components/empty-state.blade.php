@props([
    'title' => 'Tidak ada data',
    'description' => 'Belum ada data yang tersedia untuk ditampilkan saat ini.',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'text-center p-8 sm:p-12 bg-slate-900/60 border border-slate-800 rounded-2xl flex flex-col items-center justify-center space-y-3']) }}>
    <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700/60 text-slate-400 flex items-center justify-center shadow-inner">
        @if ($icon)
            {!! $icon !!}
        @else
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        @endif
    </div>

    <div class="max-w-sm space-y-1">
        <h3 class="text-base font-bold text-white tracking-tight">{{ $title }}</h3>
        <p class="text-xs text-slate-400 leading-relaxed">{{ $description }}</p>
    </div>

    @if ($slot->isNotEmpty())
        <div class="pt-2">
            {{ $slot }}
        </div>
    @endif
</div>
