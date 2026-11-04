<div class="w-full overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] shadow-sm dark:shadow-2xl transition-colors duration-200">
    <table {{ $attributes->merge(['class' => 'w-full text-left text-sm text-slate-700 dark:text-slate-300 border-collapse']) }}>
        {{ $slot }}
    </table>
</div>
