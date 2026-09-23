@props([
    'message' => 'Memuat data...',
])

<div {{ $attributes->merge(['class' => 'p-8 text-center flex flex-col items-center justify-center space-y-3']) }}>
    <div class="relative w-10 h-10">
        <div class="absolute inset-0 rounded-full border-2 border-indigo-500/20"></div>
        <div class="absolute inset-0 rounded-full border-2 border-indigo-500 border-t-transparent animate-spin"></div>
    </div>
    <span class="text-xs font-medium text-slate-400">{{ $message }}</span>
</div>
