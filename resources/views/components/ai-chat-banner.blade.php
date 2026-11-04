<div class="p-5 bg-gradient-to-r from-indigo-50/90 via-white to-purple-50/90 dark:from-indigo-950/80 dark:via-slate-900 dark:to-purple-950/70 border border-indigo-200 dark:border-indigo-500/30 rounded-2xl shadow-sm dark:shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 transition-colors duration-200">
    <div class="flex items-start space-x-3.5">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-extrabold text-base shadow-lg shadow-indigo-500/30 shrink-0 border border-indigo-400/40">
            ✨ AI
        </div>
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Asisten AI Kampus</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 uppercase tracking-wider">
                    NVIDIA Llama 3.2
                </span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-300 max-w-2xl leading-relaxed">
                Butuh bantuan menyusun proposal kegiatan, ide acara organisasi, atau informasi seputar sistem? Tanyakan langsung ke Asisten AI sekarang.
            </p>
        </div>
    </div>

    <div class="flex items-center space-x-2 shrink-0 w-full md:w-auto">
        <button
            onclick="if (typeof window.toggleAiChatbot === 'function') { window.toggleAiChatbot(true); if (typeof window.sendAiQuickPrompt === 'function') window.sendAiQuickPrompt('Bagaimana cara membuat proposal kegiatan?'); } else { console.warn('AI Chatbot loading...'); }"
            type="button"
            class="flex-1 md:flex-none px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:scale-[1.02] active:scale-95 transition-all duration-200 flex items-center justify-center space-x-2 cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <span>Tanya AI Sekarang</span>
        </button>
    </div>
</div>
