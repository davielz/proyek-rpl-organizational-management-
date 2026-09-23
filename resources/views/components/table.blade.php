<div class="w-full overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 shadow-xl">
    <table {{ $attributes->merge(['class' => 'w-full text-left text-sm text-slate-300 border-collapse']) }}>
        {{ $slot }}
    </table>
</div>
