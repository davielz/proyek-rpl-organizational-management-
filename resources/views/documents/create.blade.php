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

    <div class="max-w-4xl mx-auto space-y-6">
        @if ($errors->any())
            <x-alert type="error" title="Terdapat Kesalahan Validasi">
                <ul class="list-disc list-inside text-xs space-y-1 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <x-card class="p-6">
            <form action="{{ route('documents.store') }}" method="POST" class="space-y-6" x-data="documentForm()">
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

                <!-- Template Helper Buttons -->
                <div class="p-4 bg-indigo-950/40 border border-indigo-500/20 rounded-xl flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center space-x-2 text-indigo-300 text-xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Gunakan template konten otomatis untuk membantu pengisian awal:</span>
                    </div>
                    <button type="button" @click="loadTemplate()" class="px-3 py-1.5 text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                        Isi Template Standar
                    </button>
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

                updateTitle() {
                    const select = document.querySelector('select[name="event_id"]');
                    const selectedOption = select ? select.options[select.selectedIndex] : null;
                    const eventTitle = selectedOption ? selectedOption.getAttribute('data-title') : '';

                    if (eventTitle && !this.title) {
                        const prefix = this.docType === 'proposal' ? 'Proposal Kegiatan' : 'Laporan Pertanggungjawaban (LPJ)';
                        this.title = `${prefix}: ${eventTitle}`;
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
