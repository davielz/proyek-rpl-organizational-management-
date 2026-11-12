@php
    $currentUser = auth()->user();
    $initialAiMessages = $currentUser
        ? \App\Models\AiChatMessage::where('user_id', $currentUser->id)
            ->latest('created_at')
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content
            ])
        : collect();
@endphp

<script>
// Clean any legacy cross-account client-side storage from previous versions
try {
    localStorage.removeItem('campus_ai_chat_history');
    sessionStorage.removeItem('campus_ai_chat_history');
    for (let i = sessionStorage.length - 1; i >= 0; i--) {
        const k = sessionStorage.key(i);
        if (k && k.startsWith('campus_ai_chat_history')) {
            sessionStorage.removeItem(k);
        }
    }
    for (let i = localStorage.length - 1; i >= 0; i--) {
        const k = localStorage.key(i);
        if (k && k.startsWith('campus_ai_chat_history')) {
            localStorage.removeItem(k);
        }
    }
} catch(e) {}

// Global AI Assistant State (Strictly scoped to current authenticated user's database messages)
window.aiChatState = {
    userId: @json(auth()->id()),
    messages: @json($initialAiMessages),
    isLoading: false
};

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

window.clearAiChatMessages = async function() {
    if (!confirm('Apakah Anda yakin ingin menghapus seluruh riwayat percakapan AI Assistant Anda?')) {
        return;
    }

    try {
        const clearUrl = @json(route('dashboard.chat.clear'));
        const csrf = @json(csrf_token());

        await fetch(clearUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
    } catch(err) {
        console.warn('[AI Assistant] Clear error:', err);
    }

    window.aiChatState.messages = [];
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

    // Push user message to UI state immediately
    window.aiChatState.messages.push({ role: 'user', content: text });
    if (input) input.value = '';
    window.renderAiMessages();
    window.scrollAiChatToBottom();

    window.setAiLoading(true);

    try {
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
                message: text
            })
        });

        const data = await response.json();

        if (data.success && data.reply) {
            window.aiChatState.messages.push({ role: 'assistant', content: data.reply });
        } else {
            window.aiChatState.messages.push({
                role: 'assistant',
                content: data.reply || 'Maaf, AI Assistant sedang mengalami antrean pemrosesan data. Silakan coba beberapa saat lagi.'
            });
        }
    } catch(err) {
        window.aiChatState.messages.push({
            role: 'assistant',
            content: 'Maaf, terjadi kendala koneksi saat menghubungi server AI Assistant. Silakan coba kirim kembali beberapa saat lagi.'
        });
    } finally {
        window.setAiLoading(false);
        window.renderAiMessages();
        window.scrollAiChatToBottom();
    }
};

let aiLoadingInterval = null;

window.setAiLoading = function(loading) {
    window.aiChatState.isLoading = loading;
    const loader = document.getElementById('ai-chatbot-loading');
    const loadingText = document.getElementById('ai-chatbot-loading-text');
    const btn = document.getElementById('ai-chatbot-submit-btn');
    const input = document.getElementById('ai-chatbot-input');

    if (loader) {
        if (loading) {
            loader.classList.remove('hidden');
            if (loadingText) loadingText.textContent = 'Membaca database & merumuskan jawaban...';

            let seconds = 0;
            if (aiLoadingInterval) clearInterval(aiLoadingInterval);
            aiLoadingInterval = setInterval(() => {
                seconds += 1;
                if (!loadingText) return;
                if (seconds >= 4 && seconds < 10) {
                    loadingText.textContent = 'AI Assistant sedang menganalisis data sistem...';
                } else if (seconds >= 10) {
                    loadingText.textContent = 'Sedang menyusun respon lengkap untuk Anda...';
                }
            }, 1000);
        } else {
            loader.classList.add('hidden');
            if (aiLoadingInterval) {
                clearInterval(aiLoadingInterval);
                aiLoadingInterval = null;
            }
        }
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
        const hasTable = msg.role !== 'user' && msg.content && msg.content.includes('|');

        if (msg.role === 'user') {
            bubble.className = 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl rounded-tr-none px-3.5 py-2 text-xs max-w-[85%] shadow-md leading-relaxed break-words';
        } else {
            bubble.className = (hasTable ? 'max-w-[96%] w-full' : 'max-w-[88%]') + ' bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 rounded-2xl rounded-tl-none px-3.5 py-2.5 text-xs shadow-sm dark:shadow-md leading-relaxed break-words overflow-hidden';
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

    // 1. Extract code blocks first to protect their raw content
    const codeBlocks = [];
    let processed = text.replace(/```([a-zA-Z0-9_-]*)\r?\n([\s\S]*?)```/g, (match, lang, code) => {
        const placeholder = '___AI_CODE_BLOCK_' + codeBlocks.length + '___';
        codeBlocks.push(
            '<div class="my-2 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-900 text-slate-100 p-3 overflow-x-auto text-[11px] font-mono shadow-sm">' +
            '<code>' + escapeHtml(code.trim()) + '</code>' +
            '</div>'
        );
        return placeholder;
    });

    const lines = processed.split(/\r?\n/);
    const output = [];
    let inTable = false;
    let tableLines = [];
    let inList = false;
    let listType = null;
    let listItems = [];

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function formatInline(str) {
        let res = escapeHtml(str);
        res = res.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        res = res.replace(/\*(.*?)\*/g, '<em>$1</em>');
        res = res.replace(/`(.*?)`/g, '<code class="bg-slate-100 dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 px-1 py-0.5 rounded text-[11px] font-mono">$1</code>');
        return res;
    }

    function flushList() {
        if (!inList || listItems.length === 0) return;
        const tag = listType === 'ol' ? 'ol' : 'ul';
        const cls = listType === 'ol'
            ? 'list-decimal list-outside pl-4 space-y-1 my-1.5'
            : 'list-disc list-outside pl-4 space-y-1 my-1.5';
        let html = '<' + tag + ' class="' + cls + '">';
        listItems.forEach(item => {
            html += '<li class="leading-relaxed">' + formatInline(item) + '</li>';
        });
        html += '</' + tag + '>';
        output.push(html);
        inList = false;
        listType = null;
        listItems = [];
    }

    function flushTable() {
        if (!inTable) return;
        if (tableLines.length >= 2) {
            const headerLine = tableLines[0];
            const sepLine = tableLines[1];
            const rowLines = tableLines.slice(2);

            const parseRow = (line) => {
                let r = line.trim();
                if (r.startsWith('|')) r = r.slice(1);
                if (r.endsWith('|')) r = r.slice(0, -1);
                return r.split('|').map(c => c.trim());
            };

            const headers = parseRow(headerLine);
            const seps = parseRow(sepLine);
            const aligns = seps.map(s => {
                const l = s.startsWith(':');
                const r = s.endsWith(':');
                if (l && r) return 'text-center';
                if (r) return 'text-right';
                return 'text-left';
            });

            let html = '<div class="overflow-x-auto my-2 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-900 shadow-sm max-w-full">';
            html += '<table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-xs border-collapse">';
            html += '<thead class="bg-slate-100/90 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-semibold text-[11px] uppercase tracking-wider"><tr>';
            headers.forEach((h, i) => {
                const align = aligns[i] || 'text-left';
                html += '<th class="px-3 py-2 border-b border-slate-200 dark:border-slate-700 ' + align + ' whitespace-nowrap">' + formatInline(h) + '</th>';
            });
            html += '</tr></thead>';
            html += '<tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">';
            rowLines.forEach(row => {
                const cells = parseRow(row);
                html += '<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">';
                cells.forEach((c, i) => {
                    const align = aligns[i] || 'text-left';
                    html += '<td class="px-3 py-2 text-slate-700 dark:text-slate-300 ' + align + ' text-[11px] whitespace-nowrap">' + formatInline(c) + '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            output.push(html);
        } else {
            tableLines.forEach(l => output.push(formatInline(l)));
        }
        tableLines = [];
        inTable = false;
    }

    function isTableRow(line) {
        const trimmed = line.trim();
        return trimmed.startsWith('|') && trimmed.includes('|', 1);
    }

    for (let i = 0; i < lines.length; i++) {
        const rawLine = lines[i];
        const trimmed = rawLine.trim();

        // Check if table row
        if (isTableRow(rawLine)) {
            flushList();
            inTable = true;
            tableLines.push(rawLine);
            continue;
        } else if (inTable) {
            flushTable();
        }

        // Empty line
        if (!trimmed) {
            flushList();
            output.push('');
            continue;
        }

        // Headings
        if (trimmed.startsWith('### ')) {
            flushList();
            output.push('<h4 class="font-bold text-slate-900 dark:text-white text-xs mt-2.5 mb-1 tracking-tight">' + formatInline(trimmed.slice(4)) + '</h4>');
            continue;
        }
        if (trimmed.startsWith('## ')) {
            flushList();
            output.push('<h3 class="font-bold text-slate-900 dark:text-white text-sm mt-3 mb-1.5 tracking-tight">' + formatInline(trimmed.slice(3)) + '</h3>');
            continue;
        }

        // Blockquote
        if (trimmed.startsWith('> ')) {
            flushList();
            output.push('<blockquote class="border-l-2 border-indigo-500 pl-2.5 my-1 text-slate-600 dark:text-slate-400 italic">' + formatInline(trimmed.slice(2)) + '</blockquote>');
            continue;
        }

        // Unordered list (*, -, •)
        const ulMatch = trimmed.match(/^[\*\-•]\s+(.*)$/);
        if (ulMatch) {
            if (inList && listType !== 'ul') flushList();
            inList = true;
            listType = 'ul';
            listItems.push(ulMatch[1]);
            continue;
        }

        // Ordered list (1. , 2. )
        const olMatch = trimmed.match(/^\d+\.\s+(.*)$/);
        if (olMatch) {
            if (inList && listType !== 'ol') flushList();
            inList = true;
            listType = 'ol';
            listItems.push(olMatch[1]);
            continue;
        }

        // Normal paragraph/line
        flushList();
        output.push(formatInline(rawLine));
    }

    if (inTable) flushTable();
    if (inList) flushList();

    // Assemble lines
    let result = '';
    for (let i = 0; i < output.length; i++) {
        const curr = output[i];
        if (!curr && i > 0 && output[i - 1] === '') {
            continue;
        }
        if (curr.startsWith('<div') || curr.startsWith('<ul') || curr.startsWith('<ol') || curr.startsWith('<h3') || curr.startsWith('<h4') || curr.startsWith('<blockquote')) {
            result += curr;
        } else {
            result += curr + '<br>';
        }
    }

    let cleaned = result.replace(/(<br>)+$/g, '')
        .replace(/(<br>)(<div|<ul|<ol|<h3|<h4|<blockquote)/g, '$2')
        .replace(/(<\/div>|<\/ul>|<\/ol>|<\/h3>|<\/h4>|<\/blockquote>)<br>/g, '$1');

    // Restore code blocks
    codeBlocks.forEach((block, idx) => {
        cleaned = cleaned.replace('___AI_CODE_BLOCK_' + idx + '___', block);
    });

    return cleaned;
};

// Initial render once DOM is ready
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
        title="Buka AI Assistant"
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
        class="hidden fixed bottom-24 right-4 sm:right-6 z-[9999] w-[calc(100vw-2rem)] sm:w-[440px] md:w-[470px] h-[550px] max-h-[82vh] bg-white/95 dark:bg-slate-900/95 border border-slate-200 dark:border-slate-700/80 rounded-2xl shadow-2xl backdrop-blur-xl flex flex-col overflow-hidden transition-all duration-200"
    >
        <!-- Header -->
        <div class="p-4 bg-slate-50/90 dark:bg-slate-950/90 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 font-bold text-xs border border-indigo-400/30">
                    ✨ AI
                </div>
                <div>
                    <div class="flex items-center space-x-1.5">
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white leading-none">AI Assistant</h3>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Online
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Asisten Cerdas Sistem Organisasi</p>
                </div>
            </div>
            <div class="flex items-center space-x-1">
                <button onclick="window.clearAiChatMessages()" class="text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 p-1.5 rounded-lg hover:bg-slate-200/60 dark:hover:bg-slate-800 text-xs transition-colors cursor-pointer" title="Bersihkan Obrolan Saya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <button onclick="window.toggleAiChatbot(false)" class="text-slate-400 hover:text-slate-700 dark:hover:text-white p-1.5 rounded-lg hover:bg-slate-200/60 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div id="ai-chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-3 scroll-smooth">
            <!-- Welcome Card -->
            <div class="p-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2" id="ai-chatbot-welcome">
                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                    👋 Halo <strong>{{ auth()->user()->name ?? 'Mahasiswa' }}</strong>! Saya <strong>AI Assistant</strong>. Saya siap membantu Anda memberikan informasi kegiatan, polling, ruang diskusi, dokumen organisasi, dan data statistik kampus secara akurat.
                </p>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold pt-1">Rekomendasi Pertanyaan:</div>
                <div class="flex flex-wrap gap-1.5">
                    <button onclick="window.sendAiQuickPrompt('Kegiatan apa saja yang sedang dibuka pendaftarannya?')" class="text-[11px] bg-white hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 hover:text-indigo-800 dark:hover:text-indigo-200 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors text-left cursor-pointer shadow-sm">
                        📅 Kegiatan dibuka
                    </button>
                    <button onclick="window.sendAiQuickPrompt('Apakah ada polling atau voting yang sedang aktif?')" class="text-[11px] bg-white hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 hover:text-indigo-800 dark:hover:text-indigo-200 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors text-left cursor-pointer shadow-sm">
                        📊 Polling aktif
                    </button>
                    <button onclick="window.sendAiQuickPrompt('Apa topik diskusi terbaru di Ruang Diskusi?')" class="text-[11px] bg-white hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 hover:text-indigo-800 dark:hover:text-indigo-200 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors text-left cursor-pointer shadow-sm">
                        💬 Topik diskusi baru
                    </button>
                    <button onclick="window.sendAiQuickPrompt('Bagaimana ringkasan statistik sistem saat ini?')" class="text-[11px] bg-white hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 hover:text-indigo-800 dark:hover:text-indigo-200 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors text-left cursor-pointer shadow-sm">
                        📈 Ringkasan statistik
                    </button>
                </div>
            </div>

            <!-- Dynamic Messages will be injected here -->
            <div id="ai-chatbot-dynamic-list" class="space-y-3"></div>

            <!-- Loading Indicator -->
            <div id="ai-chatbot-loading" class="hidden flex justify-start">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl rounded-tl-none px-4 py-2.5 text-xs flex items-center space-x-2 shadow-sm">
                    <span id="ai-chatbot-loading-text" class="font-medium text-indigo-600 dark:text-indigo-300">Membaca database & merumuskan jawaban...</span>
                    <span class="flex space-x-1">
                        <span class="w-1.5 h-1.5 bg-indigo-500 dark:bg-indigo-400 rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-indigo-500 dark:bg-indigo-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                        <span class="w-1.5 h-1.5 bg-indigo-500 dark:bg-indigo-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Footer Input Form -->
        <form id="ai-chatbot-form" onsubmit="window.submitAiChatMessage(event)" class="p-3 bg-slate-50/90 dark:bg-slate-950/90 border-t border-slate-200 dark:border-slate-800 flex items-center space-x-2">
            <input
                id="ai-chatbot-input"
                type="text"
                placeholder="Tanyakan ke AI Assistant..."
                class="flex-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none transition-all"
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
