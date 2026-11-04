@props([
    'paginator' => null,
])

@if ($paginator && $paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 transition-colors duration-200']) }}>
        <div>
            Menampilkan <span class="font-semibold text-slate-900 dark:text-white">{{ $paginator->firstItem() }}</span> - <span class="font-semibold text-slate-900 dark:text-white">{{ $paginator->lastItem() }}</span> dari <span class="font-semibold text-slate-900 dark:text-white">{{ $paginator->total() }}</span> data
        </div>

        <div class="flex items-center space-x-2">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-900 text-slate-400 dark:text-slate-600 border border-slate-200 dark:border-slate-800 cursor-not-allowed">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition-colors">Sebelumnya</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition-colors">Berikutnya</a>
            @else
                <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-900 text-slate-400 dark:text-slate-600 border border-slate-200 dark:border-slate-800 cursor-not-allowed">Berikutnya</span>
            @endif
        </div>
    </div>
@endif
