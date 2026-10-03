<script>
// Global AI Chatbot Controller (Declared first so it is available to any button on the page)
window.aiChatState = window.aiChatState || {
    messages: [],
    isLoading: false
};

// Restore chat history from sessionStorage
try {
    const saved = sessionStorage.getItem('campus_ai_chat_history');
    if (saved) {
        window.aiChatState.messages = JSON.parse(saved) || [];
    }
} catch(e) {}

window.toggleAiChatbot = function(forceState) {
    const win = document.getElementById('ai-chatbot-window');
    if (!win) return;

    if (typeof forceState === 'boolean') {
        if (forceState) {
            win.classList.remove('hidden');
        } else {
            win.classList.add('hidden');
        }
    } else {
        win.classList.toggle('hidden');
    }

    if (!win.classList.contains('hidden')) {
        window.renderAiMessages();
        window.scrollAiChatToBottom();
        const input = document.getElementById('ai-chatbot-input');
        if (input) input.focus();
    }
};

window.clearAiChatMessages = function() {
    window.aiChatState.messages = [];
    sessionStorage.removeItem('campus_ai_chat_history');
    window.renderAiMessages();
};

window.sendAiQuickPrompt = function(promptText) {
    window.toggleAiChatbot(true);
    const input = document.getElementById('ai-chatbot-input');
    if (input) {
        input.value = promptText;
        window.submitAiChatMessage(new Event('submit'));
    }
};

window.submitAiChatMessage = async function(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (window.aiChatState.isLoading) return;

    const input = document.getElementById('ai-chatbot-input');
    const text = input ? input.value.trim() : '';
    if (!text) return;

    // Push user message
    window.aiChatState.messages.push({ role: 'user', content: text });
    if (input) input.value = '';
    window.renderAiMessages();
    window.scrollAiChatToBottom();

    window.setAiLoading(true);

    try {
        const history = window.aiChatState.messages.slice(0, -1).map(m => ({
            role: m.role,
            content: m.content
        }));

        const endpoint = @json(route('dashboard.chat'));
        const csrfToken = @json(csrf_token());

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                message: text,
                history: history
            })
        });

        const data = await response.json();
        if (data.success && data.reply) {
            window.aiChatState.messages.push({ role: 'assistant', content: data.reply });
        } else {
            window.aiChatState.messages.push({
                role: 'assistant',
                content: data.reply || 'Maaf, terjadi kendala saat menghubungkan ke NVIDIA AI.'
            });
        }
    } catch(err) {
        window.aiChatState.messages.push({
            role: 'assistant',
            content: 'Maaf, gagal terhubung ke server. Silakan periksa koneksi internet Anda.'
        });
    } finally {
        window.setAiLoading(false);
        try { sessionStorage.setItem('campus_ai_chat_history', JSON.stringify(window.aiChatState.messages)); } catch(e) {}
        window.renderAiMessages();
        window.scrollAiChatToBottom();
    }
};

window.setAiLoading = function(loading) {
    window.aiChatState.isLoading = loading;
    const loader = document.getElementById('ai-chatbot-loading');
    const btn = document.getElementById('ai-chatbot-submit-btn');
    const input = document.getElementById('ai-chatbot-input');

    if (loader) {
        if (loading) loader.classList.remove('hidden');
        else loader.classList.add('hidden');
    }
    if (btn) btn.disabled = loading;
    if (input) input.disabled = loading;
};

window.renderAiMessages = function() {
    const list = document.getElementById('ai-chatbot-dynamic-list');
    if (!list) return;

    list.innerHTML = '';
    window.aiChatState.messages.forEach(msg => {
        const wrap = document.createElement('div');
        wrap.className = msg.role === 'user' ? 'flex justify-end' : 'flex justify-start';

        const bubble = document.createElement('div');
        if (msg.role === 'user') {
            bubble.className = 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl rounded-tr-none px-3.5 py-2 text-xs max-w-[85%] shadow-md';
        } else {
            bubble.className = 'bg-slate-950 border border-slate-800 text-slate-200 rounded-2xl rounded-tl-none px-3.5 py-2.5 text-xs max-w-[88%] space-y-1 shadow-md';
        }

        bubble.innerHTML = window.formatAiMarkdownText(msg.content);
        wrap.appendChild(bubble);
        list.appendChild(wrap);
    });
};

window.scrollAiChatToBottom = function() {
    setTimeout(() => {
        const container = document.getElementById('ai-chatbot-messages');
        if (container) container.scrollTop = container.scrollHeight;
    }, 50);
};

window.formatAiMarkdownText = function(text) {
    if (!text) return '';
    let html = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    html = html.replace(/`(.*?)`/g, '<code class="bg-slate-900 px-1 py-0.5 rounded text-[11px] text-indigo-300 font-mono">$1</code>');
    html = html.replace(/\n/g, '<br>');
    return html;
};

// Initial render if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.renderAiMessages());
} else {
    window.renderAiMessages();
}
</script>

<div class="relative z-[9999]">
    <!-- Floating Chat Trigger Button -->
    <button
        id="ai-chatbot-trigger-btn"
        onclick="window.toggleAiChatbot()"
        type="button"
        class="fixed bottom-6 right-6 z-[9999] flex items-center space-x-2.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 hover:from-indigo-500 hover:to-purple-500 text-white px-4 py-3 rounded-full shadow-2xl shadow-indigo-600/50 hover:scale-105 active:scale-95 transition-all duration-200 group border border-indigo-400/40 cursor-pointer"
        title="Buka Asisten AI Kampus"
    >
        <div class="relative flex items-center justify-center">
            <svg class="w-6 h-6 transform group-hover:rotate-12 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
        </div>
        <span class="font-bold text-sm tracking-tight hidden sm:inline-block">AI Assistant</span>
    </button>

    <!-- Chat Window Container -->
    <div
        id="ai-chatbot-window"
        class="hidden fixed bottom-24 right-4 sm:right-6 z-[9999] w-[calc(100vw-2rem)] sm:w-96 h-[520px] max-h-[80vh] bg-slate-900/95 border border-slate-700/80 rounded-2xl shadow-2xl backdrop-blur-xl flex flex-col overflow-hidden transition-all duration-200"
    >
        <!-- Header -->
        <div class="p-4 bg-slate-950/90 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 font-bold text-xs border border-indigo-400/30">
                    ✨ AI
                </div>
                <div>
                    <div class="flex items-center space-x-1.5">
                        <h3 class="font-bold text-sm text-white leading-none">Asisten AI Kampus</h3>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">NVIDIA</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">Bertenaga Llama 3.2 (NVIDIA AI)</p>
                </div>
            </div>
            <div class="flex items-center space-x-1">
                <button onclick="window.clearAiChatMessages()" class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 text-xs transition-colors" title="Bersihkan Chat">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <button onclick="window.toggleAiChatbot(false)" class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-800 transition-colors" title="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div id="ai-chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-3 scroll-smooth">
            <!-- Welcome Card -->
            <div class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl space-y-2" id="ai-chatbot-welcome">
                <p class="text-xs text-slate-300 leading-relaxed">
                    👋 Halo <strong>{{ auth()->user()->name ?? 'Mahasiswa' }}</strong>! Saya <strong>Asisten AI Kampus</strong>. Tanyakan apa saja seputar kegiatan, proposal, atau sistem ulasan!
                </p>
                <div class="text-[11px] text-slate-400 font-semibold pt-1">Rekomendasi Pertanyaan:</div>
                <div class="flex flex-wrap gap-1.5">
                    <button onclick="window.sendAiQuickPrompt('Bagaimana cara membuat proposal kegiatan?')" class="text-[11px] bg-slate-800 hover:bg-indigo-600/30 text-indigo-300 hover:text-indigo-200 px-2.5 py-1 rounded-lg border border-slate-700 transition-colors text-left cursor-pointer">
                        📝 Cara menyusun proposal
                    </button>
                    <button onclick="window.sendAiQuickPrompt('Berikan ide kegiatan seru untuk organisasi mahasiswa')" class="text-[11px] bg-slate-800 hover:bg-indigo-600/30 text-indigo-300 hover:text-indigo-200 px-2.5 py-1 rounded-lg border border-slate-700 transition-colors text-left cursor-pointer">
                        💡 Ide kegiatan organisasi
                    </button>
                    <button onclick="window.sendAiQuickPrompt('Bagaimana fitur analisis sentimen ulasan bekerja?')" class="text-[11px] bg-slate-800 hover:bg-indigo-600/30 text-indigo-300 hover:text-indigo-200 px-2.5 py-1 rounded-lg border border-slate-700 transition-colors text-left cursor-pointer">
                        ⭐ Analisis sentimen ulasan
                    </button>
                </div>
            </div>

            <!-- Dynamic Messages will be injected here -->
            <div id="ai-chatbot-dynamic-list" class="space-y-3"></div>

            <!-- Loading Indicator -->
            <div id="ai-chatbot-loading" class="hidden flex justify-start">
                <div class="bg-slate-950 border border-slate-800 text-slate-400 rounded-2xl rounded-tl-none px-4 py-2.5 text-xs flex items-center space-x-2">
                    <span class="font-medium text-indigo-300">NVIDIA AI mengetik</span>
                    <span class="flex space-x-1">
                        <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                        <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Footer Input Form -->
        <form id="ai-chatbot-form" onsubmit="window.submitAiChatMessage(event)" class="p-3 bg-slate-950/90 border-t border-slate-800 flex items-center space-x-2">
            <input
                id="ai-chatbot-input"
                type="text"
                placeholder="Tanyakan ke NVIDIA AI..."
                class="flex-1 bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 outline-none transition-all"
            />
            <button
                id="ai-chatbot-submit-btn"
                type="submit"
                class="bg-indigo-600 hover:bg-indigo-500 text-white p-2 rounded-xl transition-all shadow-md shadow-indigo-600/30 flex-shrink-0 cursor-pointer"
                title="Kirim Pesan"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>
    </div>
</div>
