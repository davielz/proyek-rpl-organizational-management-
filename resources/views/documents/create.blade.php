<x-app-layout title="Buat Dokumen Proposal / LPJ">
    <x-slot:header>
        <x-page-header
            title="Buat Dokumen Baru"
            description="Pilih kegiatan dan susun draf proposal atau laporan pertanggungjawaban."
        >
            <x-slot:actions>
                <a href="{{ route('documents.index') }}">
                    <x-button variant="secondary">
                        Kembali ke Daftar
                    </x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-4xl mx-auto space-y-6" x-data="documentForm()">
        @if ($errors->any())
            <x-alert type="error" title="Terdapat Kesalahan Validasi">
                <ul class="list-disc list-inside text-xs space-y-1 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <!-- AI Banner Status Notification -->
        <template x-if="aiNotice.message">
            <div :class="aiNotice.isFallback ? 'bg-amber-950/40 border-amber-500/30 text-amber-300' : 'bg-indigo-950/50 border-indigo-500/40 text-indigo-200'" class="p-4 rounded-xl border flex items-start justify-between gap-3 text-xs shadow-lg transition-all">
                <div class="flex items-start space-x-3">
                    <span class="p-1 rounded-lg bg-indigo-500/20 text-indigo-400 font-bold shrink-0 mt-0.5" x-text="aiNotice.isFallback ? '⚡' : '✨'"></span>
                    <div>
                        <strong class="font-bold block" x-text="aiNotice.isFallback ? 'Draf AI (Fallback Template):' : 'AI Assistant Success:'"></strong>
                        <span x-text="aiNotice.message"></span>
                    </div>
                </div>
                <button type="button" @click="aiNotice.message = ''" class="text-slate-400 hover:text-white">&times;</button>
            </div>
        </template>

        <x-card class="p-6">
            <form action="{{ route('documents.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Event Select -->
                    <div>
                        <x-select
                            name="event_id"
                            label="Pilih Kegiatan *"
                            x-model="eventId"
                            @change="updateTitle()"
                            required
                        >
                            <option value="">-- Pilih Kegiatan --</option>
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}" {{ (old('event_id', $selectedEventId) == $event->id) ? 'selected' : '' }} data-title="{{ $event->title }}">
                                    {{ $event->title }} ({{ $event->organization->name }})
                                </option>
                            @endforeach
                        </x-select>
                        @error('event_id')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Document Type -->
                    <div>
                        <x-select
                            name="type"
                            label="Jenis Dokumen *"
                            x-model="docType"
                            @change="updateTitle()"
                            required
                        >
                            <option value="proposal" {{ old('type', $selectedType) === 'proposal' ? 'selected' : '' }}>Proposal Kegiatan</option>
                            <option value="lpj" {{ old('type', $selectedType) === 'lpj' ? 'selected' : '' }}>Laporan Pertanggungjawaban (LPJ)</option>
                        </x-select>
                        @error('type')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Document Title -->
                <div>
                    <x-input
                        name="title"
                        label="Judul Dokumen *"
                        placeholder="Contoh: Proposal Pengajuan Kegiatan Workshop UI/UX Design 2026"
                        x-model="title"
                        required
                    />
                    @error('title')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- AI Draft Generator Card & Template Helper -->
                <div class="p-4 bg-gradient-to-r from-indigo-950/60 via-slate-900 to-purple-950/40 border border-indigo-500/30 rounded-2xl space-y-3 shadow-inner">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-0.5">
                            <div class="flex items-center space-x-2 text-indigo-300 font-bold text-xs">
                                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span>AI Assistant Helper</span>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                Buat draf awal dokumen otomatis berdasarkan data kegiatan yang dipilih. Draf dapat Anda periksa dan sesuaikan sebelum diajukan.
                            </p>
                        </div>

                        <div class="flex items-center space-x-2 shrink-0">
                            <button
                                type="button"
                                @click="generateAiDraft()"
                                :disabled="isGeneratingAi || !eventId"
                                class="px-4 py-2 text-xs font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 disabled:opacity-50 text-white rounded-xl transition-all shadow-md shadow-indigo-600/30 flex items-center space-x-2"
                            >
                                <template x-if="isGeneratingAi">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <span x-text="isGeneratingAi ? 'Menyusun Draf AI...' : '✨ Generate Draf dengan AI'"></span>
                            </button>

                            <button
                                type="button"
                                @click="loadTemplate()"
                                class="px-3 py-2 text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl transition-colors border border-slate-700"
                                title="Gunakan template standar manual"
                            >
                                Template Standar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Background -->
                <div>
                    <x-textarea
                        name="background"
                        label="1. Latar Belakang / Pendahuluan *"
                        rows="5"
                        placeholder="Jelaskan alasan dan latar belakang diselenggarakannya kegiatan ini..."
                        x-model="background"
                        required
                    />
                    @error('background')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Objectives -->
                <div>
                    <x-textarea
                        name="objectives"
                        label="2. Maksud & Tujuan Kegiatan *"
                        rows="4"
                        placeholder="Sebutkan sasaran dan tujuan utama kegiatan..."
                        x-model="objectives"
                        required
                    />
                    @error('objectives')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Budget Plan / Summary -->
                <div>
                    <x-textarea
                        name="budget_plan"
                        label="3. Rencana / Realisasi Anggaran Biaya"
                        rows="4"
                        placeholder="Rincian estimasi pengeluaran atau laporan alokasi dana..."
                        x-model="budgetPlan"
                    />
                </div>

                <div>
                    <x-textarea
                        name="summary_content"
                        label="4. Ringkasan Pelaksanaan / Catatan Penutup"
                        rows="4"
                        placeholder="Ringkasan agenda, hasil yang diharapkan (Proposal), atau capaian realisasi (LPJ)..."
                        x-model="summaryContent"
                    />
                </div>

                <!-- Form Submit Actions -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                    <a href="{{ route('documents.index') }}">
                        <x-button type="button" variant="secondary">Batal</x-button>
                    </a>
                    <x-button type="submit" variant="primary">Simpan Draf Dokumen</x-button>
                </div>
            </form>
        </x-card>
    </div>

    <script>
        function documentForm() {
            return {
                eventId: '{{ old('event_id', $selectedEventId) }}',
                docType: '{{ old('type', $selectedType) }}',
                title: '{{ old('title') }}',
                background: '{{ old('background') }}',
                objectives: '{{ old('objectives') }}',
                budgetPlan: '{{ old('budget_plan') }}',
                summaryContent: '{{ old('summary_content') }}',

                isGeneratingAi: false,
                aiNotice: {
                    message: '',
                    isFallback: false
                },

                updateTitle() {
                    const select = document.querySelector('select[name="event_id"]');
                    const selectedOption = select ? select.options[select.selectedIndex] : null;
                    const eventTitle = selectedOption ? selectedOption.getAttribute('data-title') : '';

                    if (eventTitle && !this.title) {
                        const prefix = this.docType === 'proposal' ? 'Proposal Kegiatan' : 'Laporan Pertanggungjawaban (LPJ)';
                        this.title = `${prefix}: ${eventTitle}`;
                    }
                },

                async generateAiDraft() {
                    if (!this.eventId) {
                        alert('Silakan pilih kegiatan terlebih dahulu.');
                        return;
                    }

                    this.isGeneratingAi = true;
                    this.aiNotice.message = '';

                    try {
                        const response = await fetch('{{ route('documents.generate-ai-draft') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                event_id: this.eventId,
                                type: this.docType
                            })
                        });

                        const result = await response.json();

                        if (response.ok && result.success && result.data) {
                            this.title = result.data.title || this.title;
                            this.background = result.data.background || '';
                            this.objectives = result.data.objectives || '';
                            this.budgetPlan = result.data.budget_plan || '';
                            this.summaryContent = result.data.summary_content || '';

                            this.aiNotice.message = result.message || 'Draf berhasil dimuat ke editor.';
                            this.aiNotice.isFallback = result.is_fallback || false;
                        } else {
                            alert(result.message || 'Gagal menghasilkan draf AI.');
                        }
                    } catch (error) {
                        console.error('AI Draft Error:', error);
                        alert('Terjadi kesalahan jaringan saat meminta draf AI.');
                    } finally {
                        this.isGeneratingAi = false;
                    }
                },

                loadTemplate() {
                    if (this.docType === 'proposal') {
                        this.background = "Dalam rangka meningkatkan softskill dan kepemimpinan mahasiswa di lingkungan kampus, organisasi memerlukan wadah pengembangan diri yang terstruktur dan berdampak luas. Kegiatan ini dirancang untuk menjawab tantangan tersebut melalui pelatihan interaktif dan kolaboratif.";
                        this.objectives = "1. Meningkatkan keterampilan teknis dan manajerial peserta.\n2. Mempererat tali silaturahmi antar pengurus dan anggota organisasi.\n3. Memberikan pengalaman praktis dalam pengelolaan program kerja unggulan.";
                        this.budgetPlan = "- Consumables & Konsumsi Peserta: Rp 1.500.000\n- Perlengkapan & Spanduk Acara: Rp 750.000\n- Honorarium Pemateri / Fasilitator: Rp 1.000.000\nTotal Estimasi: Rp 3.250.000";
                        this.summaryContent = "Kegiatan akan dilaksanakan dalam waktu 1 hari penuh mencakup sesi pematerian, workshop kelompok, dan evaluasi hasil belajar.";
                    } else {
                        this.background = "Kegiatan telah dilaksanakan secara lancar sesuai target yang ditetapkan. Dokumen LPJ ini disusun sebagai bentuk transparansi dan akuntabilitas pelaksanaan program kerja organisasi kepada kampus dan pengurus.";
                        this.objectives = "1. Melaporkan realisasi ketercapaian target peserta.\n2. Menyajikan rincian pengeluaran dana kegiatan secara akurat.\n3. Menyampaikan evaluasi dan kendala selama acara sebagai bahan perbaikan kegiatan mendatang.";
                        this.budgetPlan = "- Total Penerimaan Dana: Rp 3.250.000\n- Total Realisasi Pengeluaran: Rp 3.100.000\n- Sisa Dana / Saldo: Rp 150.000 (Dikembalikan ke Bendahara Organisasi)";
                        this.summaryContent = "Acara dihadiri oleh 85 peserta terdaftar dengan tingkat kepuasan tinggi. Evaluasi menunjukkan perlu koordinasi lebih awal untuk publikasi media sosial.";
                    }
                }
            }
        }
    </script>
</x-app-layout>
