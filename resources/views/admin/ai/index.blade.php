<x-app-layout title="Konfigurasi AI Assistant">
    <x-slot:header>
        <x-page-header
            title="Konfigurasi AI Assistant"
            description="Kelola penyedia model kecerdasan buatan (LLM), kunci API, model inferensi aktif, serta parameter sistem AI Assistant."
        >
            <x-slot:actions>
                <div class="flex items-center space-x-3">
                    <button
                        type="button"
                        x-data=""
                        @click="$dispatch('open-modal', 'create-provider-modal')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-xl shadow-sm shadow-indigo-600/30 transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Provider Baru</span>
                    </button>
                    <x-badge status="admin" size="md">Khusus Admin</x-badge>
                </div>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6">
        @if (session('success'))
            <x-alert type="success" title="Berhasil">
                {{ session('success') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert type="error" title="Gagal">
                {{ session('error') }}
            </x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" title="Terdapat Kesalahan Input">
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <!-- Active Provider Banner / Hero -->
        @if ($activeConfig)
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-900/40 via-slate-900/80 to-slate-950 border border-indigo-500/30 p-6 shadow-xl backdrop-blur-sm text-slate-100">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2.5">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse mr-2"></span>
                                Provider AI Sedang Aktif
                            </span>
                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700 uppercase tracking-wider">
                                {{ strtoupper($activeConfig->provider) }}
                            </span>
                        </div>

                        <div>
                            <h2 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                                {{ $activeConfig->name }}
                                <span class="text-sm font-normal text-indigo-300">({{ $activeConfig->model }})</span>
                            </h2>
                            <p class="text-sm text-slate-300 mt-1 max-w-2xl">
                                {{ $activeConfig->description ?: 'Semua percakapan pengguna dan pembuatan draf proposal akan diproses melalui provider ini.' }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-300 pt-2 border-t border-slate-800/80">
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">Endpoint:</span>
                                <code class="px-2 py-0.5 rounded bg-slate-950/60 font-mono text-slate-200">{{ Str::limit($activeConfig->base_url, 45) }}</code>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">API Key:</span>
                                <code class="px-2 py-0.5 rounded bg-slate-950/60 font-mono text-slate-200">{{ $activeConfig->masked_api_key }}</code>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">Timeout:</span>
                                <span class="font-semibold text-white">{{ $activeConfig->timeout }}s</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">Max Tokens:</span>
                                <span class="font-semibold text-white">{{ $activeConfig->max_tokens }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">Temperature:</span>
                                <span class="font-semibold text-white">{{ $activeConfig->temperature }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Active Test Button -->
                    <div x-data="{
                        testing: false,
                        status: null,
                        message: null,
                        latency: null,
                        reply: null,
                        testActive() {
                            this.testing = true;
                            this.status = null;
                            this.message = null;
                            fetch('{{ route('admin.ai.test', $activeConfig) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            })
                            .then(res => res.json())
                            .then(data => {
                                this.testing = false;
                                this.status = data.success ? 'success' : 'error';
                                this.message = data.message;
                                this.latency = data.latency_ms;
                                this.reply = data.reply;
                            })
                            .catch(err => {
                                this.testing = false;
                                this.status = 'error';
                                this.message = 'Gagal menghubungi server aplikasi.';
                            });
                        }
                    }" class="flex flex-col items-start lg:items-end justify-center gap-2 shrink-0">
                        <button
                            type="button"
                            @click="testActive()"
                            :disabled="testing"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-md cursor-pointer disabled:opacity-50"
                            :class="testing ? 'bg-slate-700 text-slate-300' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/30'"
                        >
                            <svg x-show="testing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg x-show="!testing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span x-text="testing ? 'Menguji Sambungan...' : 'Uji Koneksi Aktif'"></span>
                        </button>

                        <div x-show="status" x-cloak class="mt-2 text-xs">
                            <div x-show="status === 'success'" class="p-2.5 rounded-lg bg-emerald-950/80 border border-emerald-500/40 text-emerald-200">
                                <div class="font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="message"></span> (<span x-text="latency + ' ms'"></span>)
                                </div>
                                <div x-show="reply" class="text-[11px] text-slate-300 mt-1 italic" x-text="'Respon AI: ' + reply"></div>
                            </div>
                            <div x-show="status === 'error'" class="p-2.5 rounded-lg bg-rose-950/80 border border-rose-500/40 text-rose-200">
                                <div class="font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span x-text="message"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security & Context Policy Reminder -->
                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-start gap-2.5 text-xs text-indigo-200/90 bg-indigo-950/30 -mx-6 -mb-6 p-4 rounded-b-2xl">
                    <svg class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <div>
                        <strong class="font-semibold text-white">Prinsip Keamanan & Konteks AI Tetap Berlaku:</strong>
                        Pergantian provider di atas tidak akan merubah isolasi data hak akses (Admin, Pengurus, Anggota), penolakan pertanyaan di luar konteks sistem organisasi kampus, pembedaan terminologi total Anggota vs total Pengguna, serta pemahaman database real-time.
                    </div>
                </div>
            </div>
        @endif

        <!-- List / Cards of All AI Configurations -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Daftar Provider Model AI</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pilih salah satu provider untuk diaktifkan atau kelola parameter kunci API dan model.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                    {{ $configurations->count() }} Provider Terdaftar
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach ($configurations as $config)
                    <x-card class="flex flex-col justify-between border-2 {{ $config->is_active ? 'border-indigo-500 dark:border-indigo-500/60 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800' }}">
                        <!-- Card Header -->
                        <div class="p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 flex items-center justify-between">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white shrink-0 {{ $config->provider === 'nvidia' ? 'bg-[#76B900]' : ($config->provider === 'gemini' ? 'bg-gradient-to-tr from-blue-500 to-indigo-600' : ($config->provider === 'openai' ? 'bg-emerald-600' : 'bg-slate-700')) }}">
                                    @if ($config->provider === 'nvidia')
                                        <span class="text-xs tracking-tighter font-extrabold">NV</span>
                                    @elseif ($config->provider === 'gemini')
                                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2L14.4 7.6L20 10L14.4 12.4L12 18L9.6 12.4L4 10L9.6 7.6L12 2Z"/>
                                        </svg>
                                    @elseif ($config->provider === 'openai')
                                        <span class="text-xs tracking-tighter font-extrabold">OA</span>
                                    @else
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-900 dark:text-white truncate text-base">{{ $config->name }}</h4>
                                        @if ($config->is_default)
                                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Bawaan</span>
                                        @endif
                                    </div>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">{{ $config->provider }}</span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if ($config->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Aktif
                                    </span>
                                @else
                                    <form action="{{ route('admin.ai.activate', $config) }}" method="POST">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30 transition-colors cursor-pointer"
                                            title="Jadikan provider aktif untuk seluruh aplikasi"
                                        >
                                            Aktifkan
                                        </button>
                                    </form>

                                    @if (!$config->is_default)
                                        <form action="{{ route('admin.ai.destroy', $config) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus provider {{ $config->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                                title="Hapus Provider"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- Card Body: Settings Form -->
                        <div class="p-5 space-y-4 flex-1">
                            <form id="form-config-{{ $config->id }}" action="{{ route('admin.ai.update', $config) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Provider</label>
                                        <input
                                            type="text"
                                            name="name"
                                            value="{{ old('name', $config->name) }}"
                                            required
                                            class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tipe Provider</label>
                                        <select
                                            name="provider"
                                            class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="nvidia" @selected($config->provider === 'nvidia')>NVIDIA NIM API</option>
                                            <option value="gemini" @selected($config->provider === 'gemini')>Google Gemini API</option>
                                            <option value="openai" @selected($config->provider === 'openai')>OpenAI API</option>
                                            <option value="custom" @selected($config->provider === 'custom')>Custom OpenAI-Compatible</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Model Identifier</label>
                                        <span class="text-[10px] text-slate-400">Contoh: {{ $config->provider === 'gemini' ? 'gemini-1.5-flash, gemini-1.5-pro' : ($config->provider === 'nvidia' ? 'nvidia/nemotron-3.5-bolt-e4b' : 'gpt-4o-mini') }}</span>
                                    </div>
                                    <input
                                        type="text"
                                        name="model"
                                        value="{{ old('model', $config->model) }}"
                                        required
                                        class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <div x-data="{ showKey: false }">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">API Key</label>
                                        <span class="text-[10px] text-slate-400">Saat ini: <code class="font-mono text-indigo-400">{{ $config->masked_api_key }}</code></span>
                                    </div>
                                    <div class="relative">
                                        <input
                                            :type="showKey ? 'text' : 'password'"
                                            name="api_key"
                                            placeholder="Kosongkan jika tidak ingin mengubah kunci API saat ini"
                                            class="w-full text-xs font-mono pr-10 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                        <button
                                            type="button"
                                            @click="showKey = !showKey"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                                        >
                                            <svg x-show="!showKey" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="showKey" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Endpoint Base URL</label>
                                    <input
                                        type="url"
                                        name="base_url"
                                        value="{{ old('base_url', $config->base_url) }}"
                                        required
                                        class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Timeout (s)</label>
                                        <input
                                            type="number"
                                            name="timeout"
                                            min="5"
                                            max="180"
                                            value="{{ old('timeout', $config->timeout) }}"
                                            class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Tokens</label>
                                        <input
                                            type="number"
                                            name="max_tokens"
                                            min="50"
                                            max="8192"
                                            value="{{ old('max_tokens', $config->max_tokens) }}"
                                            class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Temperature</label>
                                        <input
                                            type="number"
                                            step="0.05"
                                            min="0"
                                            max="2.0"
                                            name="temperature"
                                            value="{{ old('temperature', $config->temperature) }}"
                                            class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi / Catatan</label>
                                    <textarea
                                        name="description"
                                        rows="2"
                                        class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >{{ old('description', $config->description) }}</textarea>
                                </div>
                            </form>
                        </div>

                        <!-- Card Footer: Actions & Live Test -->
                        <div
                            x-data="{
                                testing: false,
                                status: null,
                                message: null,
                                latency: null,
                                reply: null,
                                testThis() {
                                    this.testing = true;
                                    this.status = null;
                                    this.message = null;
                                    fetch('{{ route('admin.ai.test', $config) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        this.testing = false;
                                        this.status = data.success ? 'success' : 'error';
                                        this.message = data.message;
                                        this.latency = data.latency_ms;
                                        this.reply = data.reply;
                                    })
                                    .catch(err => {
                                        this.testing = false;
                                        this.status = 'error';
                                        this.message = 'Gagal menghubungi endpoint.';
                                    });
                                }
                            }"
                            class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 rounded-b-2xl space-y-3"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <button
                                    type="button"
                                    @click="testThis()"
                                    :disabled="testing"
                                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors cursor-pointer disabled:opacity-50"
                                >
                                    <svg x-show="testing" class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-current" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <svg x-show="!testing" class="w-3.5 h-3.5 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span x-text="testing ? 'Menguji...' : 'Tes Koneksi'"></span>
                                </button>

                                <button
                                    type="submit"
                                    form="form-config-{{ $config->id }}"
                                    class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm shadow-indigo-600/30 transition-colors cursor-pointer"
                                >
                                    <span>Simpan Perubahan</span>
                                </button>
                            </div>

                            <div x-show="status" x-cloak class="text-xs">
                                <div x-show="status === 'success'" class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                                    <div class="font-semibold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span x-text="message"></span> (<span x-text="latency + ' ms'"></span>)
                                    </div>
                                    <div x-show="reply" class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 italic" x-text="'Respon: ' + reply"></div>
                                </div>
                                <div x-show="status === 'error'" class="p-2 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400">
                                    <div class="font-semibold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span x-text="message"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal Tambah Provider Baru -->
    <x-modal name="create-provider-modal" title="Tambah Provider Model AI Baru" size="lg">
        <form
            action="{{ route('admin.ai.store') }}"
            method="POST"
            x-data="{
                provider: 'nvidia',
                baseUrl: 'https://integrate.api.nvidia.com/v1',
                model: 'nvidia/nemotron-3.5-bolt-e4b',
                onProviderChange() {
                    if (this.provider === 'nvidia') {
                        this.baseUrl = 'https://integrate.api.nvidia.com/v1';
                        this.model = 'nvidia/nemotron-3.5-bolt-e4b';
                    } else if (this.provider === 'gemini') {
                        this.baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
                        this.model = 'gemini-1.5-flash';
                    } else if (this.provider === 'openai') {
                        this.baseUrl = 'https://api.openai.com/v1';
                        this.model = 'gpt-4o-mini';
                    } else {
                        this.baseUrl = '';
                        this.model = '';
                    }
                }
            }"
            class="p-6 space-y-4"
        >
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Provider / Alias <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        required
                        placeholder="Contoh: Groq Llama 3, OpenRouter, dll"
                        class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tipe Provider <span class="text-rose-500">*</span></label>
                    <select
                        name="provider"
                        x-model="provider"
                        @change="onProviderChange()"
                        class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="nvidia">NVIDIA NIM (OpenAI Compatible)</option>
                        <option value="gemini">Google Gemini API</option>
                        <option value="openai">OpenAI API</option>
                        <option value="custom">Custom (OpenAI-Compatible endpoint)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Model Identifier <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="model"
                        x-model="model"
                        required
                        placeholder="Contoh: gemini-1.5-pro atau meta/llama-3.1-70b"
                        class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Endpoint Base URL</label>
                    <input
                        type="url"
                        name="base_url"
                        x-model="baseUrl"
                        placeholder="https://..."
                        class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kunci API (API Key)</label>
                <input
                    type="password"
                    name="api_key"
                    placeholder="Masukkan API Key untuk provider ini..."
                    class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Timeout (s)</label>
                    <input
                        type="number"
                        name="timeout"
                        min="5"
                        max="180"
                        value="60"
                        class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Tokens</label>
                    <input
                        type="number"
                        name="max_tokens"
                        min="50"
                        max="8192"
                        value="1000"
                        class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Temperature</label>
                    <input
                        type="number"
                        step="0.05"
                        min="0"
                        max="2.0"
                        value="0.70"
                        name="temperature"
                        class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Singkat</label>
                <textarea
                    name="description"
                    rows="2"
                    placeholder="Catatan mengenai provider atau model ini..."
                    class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
            </div>

            <div class="flex items-center space-x-2 pt-2">
                <input
                    type="checkbox"
                    id="is_active_new"
                    name="is_active"
                    value="1"
                    class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500"
                >
                <label for="is_active_new" class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                    Langsung jadikan provider ini sebagai model aktif AI Assistant
                </label>
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end space-x-3">
                <button
                    type="button"
                    @click="$dispatch('close-modal', 'create-provider-modal')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm shadow-indigo-600/30 transition-colors cursor-pointer"
                >
                    Simpan Provider
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>

