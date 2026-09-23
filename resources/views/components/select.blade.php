@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
])

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if ($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-rose-400">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl shadow-sm">
        <select
            name="{{ $name }}"
            id="{{ $name }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->except('class')->merge([
                'class' => 'w-full bg-slate-900 border text-sm text-white rounded-xl px-4 py-2.5 transition-all duration-200 outline-none appearance-none pr-10 ' .
                    ($error ? 'border-rose-500/80 focus:border-rose-500 focus:ring-1 focus:ring-rose-500' : 'border-slate-800 hover:border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500') .
                    ($disabled ? ' opacity-50 cursor-not-allowed bg-slate-950/60' : '')
            ]) }}
        >
            @if ($placeholder)
                <option value="" disabled @selected(old($name, $selected) === null || old($name, $selected) === '')>{{ $placeholder }}</option>
            @endif

            @if (is_array($options) && count($options) > 0)
                @foreach ($options as $key => $value)
                    @php
                        $optValue = is_numeric($key) && !is_string($key) ? $value : $key;
                        $optLabel = $value;
                    @endphp
                    <option value="{{ $optValue }}" @selected((string) old($name, $selected) === (string) $optValue)>
                        {{ $optLabel }}
                    </option>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>

        <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
    </div>

    @if ($error)
        <p class="text-xs text-rose-400 mt-1.5 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $error }}</span>
        </p>
    @elseif ($hint)
        <p class="text-xs text-slate-400 mt-1.5">{{ $hint }}</p>
    @endif
</div>
