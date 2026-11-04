@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'icon' => null,
])

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if ($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500 dark:text-rose-400">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl shadow-sm">
        @if ($icon)
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                {!! $icon !!}
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->except('class')->merge([
                'class' => 'w-full bg-slate-50 dark:bg-[#162033] border text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 rounded-xl transition-all duration-200 outline-none ' .
                    ($icon ? 'pl-10 ' : 'px-4 ') . 'py-2.5 ' .
                    ($error ? 'border-rose-500/80 focus:border-rose-500 focus:ring-1 focus:ring-rose-500' : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600 focus:bg-white dark:focus:bg-[#1a2840] focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500') .
                    ($disabled ? ' opacity-50 cursor-not-allowed bg-slate-100 dark:bg-[#0a1020]' : '')
            ]) }}
        />
    </div>

    @if ($error)
        <p class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $error }}</span>
        </p>
    @elseif ($hint)
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">{{ $hint }}</p>
    @endif
</div>
