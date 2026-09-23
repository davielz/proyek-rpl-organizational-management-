@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden']) }}>
    {{ $slot }}
</div>
