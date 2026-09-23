<x-app-layout title="UI Foundation & Design System Showcase">
    <div class="space-y-10">
        <!-- 1. Page Header Component -->
        <x-page-header
            title="UI Foundation & Design System"
            subtitle="Showcase komponen antarmuka terstandarisasi untuk Campus Organization Management System."
            :breadcrumbs="['Dashboard' => route('dashboard'), 'UI Foundation' => null]"
        >
            <x-button variant="outline" size="sm" onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'demo-modal' }))">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Uji Coba Modal
            </x-button>
            <x-button variant="primary" size="sm">
                Aksi Utama
            </x-button>
        </x-page-header>

        <!-- 2. Badges & Statuses -->
        <x-card>
            <x-card.header title="1. Status Badges" subtitle="Variasi badge status dengan warna visual yang konsisten." />
            <x-card.body class="flex flex-wrap gap-3 items-center">
                <x-badge status="draft" />
                <x-badge status="submitted" />
                <x-badge status="pending" />
                <x-badge status="approved" />
                <x-badge status="active" />
                <x-badge status="revisi" />
                <x-badge status="rejected" />
                <x-badge status="cancelled" />
                <x-badge status="completed" />
                <x-badge status="attended" />
                <x-badge status="admin" />
                <x-badge status="pengurus" />
                <x-badge status="anggota" />
            </x-card.body>
        </x-card>

        <!-- 3. Buttons -->
        <x-card>
            <x-card.header title="2. Button Variants & Sizes" subtitle="Variasi tombol aksi dengan state hover dan active." />
            <x-card.body class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <x-button variant="primary" size="sm">Primary Sm</x-button>
                    <x-button variant="primary" size="md">Primary Md</x-button>
                    <x-button variant="primary" size="lg">Primary Lg</x-button>
                    <x-button variant="secondary">Secondary</x-button>
                    <x-button variant="success">Success</x-button>
                    <x-button variant="danger">Danger</x-button>
                    <x-button variant="warning">Warning</x-button>
                    <x-button variant="outline">Outline</x-button>
                    <x-button variant="ghost">Ghost</x-button>
                    <x-button variant="primary" disabled>Disabled</x-button>
                </div>
            </x-card.body>
        </x-card>

        <!-- 4. Alerts -->
        <x-card>
            <x-card.header title="3. Alert Component" subtitle="Kotak notifikasi dan pesan balikan sistem." />
            <x-card.body class="space-y-3">
                <x-alert type="success" title="Operasi Berhasil">
                    Proposal kegiatan telah disetujui oleh Administrator Kampus.
                </x-alert>
                <x-alert type="warning" title="Perlu Perhatian">
                    Pengajuan LPJ memerlukan revisi pada rincian konsumsi.
                </x-alert>
                <x-alert type="error" title="Akses Ditolak">
                    Anda tidak memiliki peranan yang cukup untuk mengakses halaman ini.
                </x-alert>
                <x-alert type="info" title="Informasi Jadwal">
                    Batas akhir pendaftaran kegiatan adalah besok pukul 23:59 WIB.
                </x-alert>
            </x-card.body>
        </x-card>

        <!-- 5. Form Elements (Input, Select, Textarea) -->
        <x-card>
            <x-card.header title="4. Form Inputs" subtitle="Komponen masukan formulir standar." />
            <x-card.body class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-input label="Nama Kegiatan" name="event_name" placeholder="Contoh: Workshop Laravel AI 2026" required />
                <x-input label="Lokasi Acara" name="location" placeholder="Gedung Utama Lab 3" error="Lokasi acara wajib diisi." />
                
                <x-select label="Kategori Organisasi" name="org_category" placeholder="Pilih Kategori..." :options="['HMTI' => 'Himpunan Mahasiswa TI', 'BEM' => 'Badan Eksekutif Mahasiswa']" required />
                <x-select label="Status Dokumen" name="doc_status" :options="['draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui']" />

                <div class="md:col-span-2">
                    <x-textarea label="Deskripsi Kegiatan" name="description" placeholder="Tuliskan latar belakang dan deskripsi acara..." hint="Maksimal 500 karakter." />
                </div>
            </x-card.body>
            <x-card.footer>
                <span class="text-xs text-slate-400">Formulir responsif siap pakai</span>
                <x-button variant="primary" size="sm">Simpan Form</x-button>
            </x-card.footer>
        </x-card>

        <!-- 6. Table & Cards Showcase -->
        <x-card>
            <x-card.header title="5. Data Table Component" subtitle="Tabel responsif dengan scroll horizontal otomatis." />
            <x-table>
                <thead>
                    <x-table.tr>
                        <x-table.th>Nama Kegiatan</x-table.th>
                        <x-table.th>Organisasi</x-table.th>
                        <x-table.th>Waktu Pelaksanaan</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </x-table.tr>
                </thead>
                <tbody>
                    <x-table.tr>
                        <x-table.td class="font-semibold text-white">Workshop Laravel & AI Integration</x-table.td>
                        <x-table.td>HMTI</x-table.td>
                        <x-table.td>25 Sep 2026, 09:00</x-table.td>
                        <x-table.td><x-badge status="active" /></x-table.td>
                        <x-table.td align="right">
                            <x-button variant="ghost" size="sm">Detail</x-button>
                        </x-table.td>
                    </x-table.tr>
                    <x-table.tr>
                        <x-table.td class="font-semibold text-white">National Tech Seminar 2026</x-table.td>
                        <x-table.td>BEM</x-table.td>
                        <x-table.td>10 Okt 2026, 08:00</x-table.td>
                        <x-table.td><x-badge status="pending" /></x-table.td>
                        <x-table.td align="right">
                            <x-button variant="ghost" size="sm">Detail</x-button>
                        </x-table.td>
                    </x-table.tr>
                </tbody>
            </x-table>
        </x-card>

        <!-- 7. Empty State & Loading State -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-empty-state
                title="Belum Ada Proposal"
                description="Pengurus organisasi belum mengajukan proposal kegiatan terbaru."
            >
                <x-button variant="primary" size="sm">Buat Proposal Baru</x-button>
            </x-empty-state>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl flex items-center justify-center">
                <x-loading-state message="Memuat analitik sentimen AI..." />
            </div>
        </div>

        <!-- 8. Modal Component -->
        <x-modal name="demo-modal" title="Konfirmasi Pengesahan Proposal" size="md">
            <p>Apakah Anda yakin ingin menyetujui proposal kegiatan <strong>"Workshop Laravel & AI Integration 2026"</strong>?</p>
            <p class="text-xs text-slate-400 mt-2">Setelah disetujui, status kegiatan akan berubah menjadi Aktif dan dapat dibuka untuk pendaftaran mahasiswa.</p>
            <x-slot:footer>
                <x-button variant="outline" size="sm" onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'demo-modal' }))">
                    Batal
                </x-button>
                <x-button variant="success" size="sm" onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'demo-modal' }))">
                    Setujui Proposal
                </x-button>
            </x-slot:footer>
        </x-modal>
    </div>
</x-app-layout>
