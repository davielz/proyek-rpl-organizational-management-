@props([
    'title' => null,
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-800']) }}>
    <div class="space-y-1">
        @if (is_array($breadcrumbs) && count($breadcrumbs) > 0)
            <nav class="flex items-center space-x-1.5 text-xs text-slate-400 mb-1">
                @foreach ($breadcrumbs as $label => $url)
                    @if (!$loop->first)
                        <svg class="w-3 h-3 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    @endif
                    @if ($url && !$loop->last)
                        <a href="{{ $url }}" class="hover:text-white transition-colors">{{ $label }}</a>
                    @else
                        <span class="text-slate-300 font-medium">{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        @if ($title)
            <h1 class="text-2xl font-bold text-white tracking-tight">{{ $title }}</h1>
        @endif

        @if ($subtitle)
            <p class="text-xs text-slate-400 max-w-3xl leading-relaxed">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex items-center gap-3 shrink-0">
            {{ $slot }}
        </div>
    @endif
</div>
