<x-app-layout title="Buat Kegiatan Baru">
    <div class="max-w-3xl mx-auto space-y-6">
        <x-page-header
            title="Buat Kegiatan Baru"
            subtitle="Isi formulir berikut untuk mempublikasikan atau membuat draf kegiatan organisasi baru."
            :breadcrumbs="[
                'Dashboard' => route('dashboard'),
                'Kelola Kegiatan' => route('manage.events.index'),
                'Buat Kegiatan' => null
            ]"
        />

        <x-card>
            <form action="{{ route('manage.events.store') }}" method="POST">
                @csrf
                <x-card.body class="space-y-6">
                    <div>
                        <x-select
                            label="Penyelenggara (Organisasi / UKM)"
                            name="organization_id"
                            placeholder="Pilih Organisasi Penyelenggara..."
                            required
                            :error="$errors->first('organization_id')"
                        >
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" @selected(old('organization_id') == $org->id)>
                                    {{ $org->code }} - {{ $org->name }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-input
                            label="Judul Kegiatan"
                            name="title"
                            placeholder="Contoh: Workshop Laravel & AI Integration 2026"
                            required
                            :error="$errors->first('title')"
                        />
                    </div>

                    <div>
                        <x-textarea
                            label="Deskripsi & Rincian Kegiatan"
                            name="description"
                            rows="5"
                            placeholder="Jelaskan mengenai tujuan, target sasaran, dan susunan acara..."
                            required
                            :error="$errors->first('description')"
                        />
                    </div>

                    <div>
                        <x-input
                            label="Lokasi / Tempat Pelaksanaan"
                            name="location"
                            placeholder="Contoh: Lab Komputer 3, Gedung Utama / Link Zoom"
                            required
                            :error="$errors->first('location')"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input
                                type="datetime-local"
                                label="Waktu Mulai"
                                name="start_time"
                                required
                                :error="$errors->first('start_time')"
                            />
                        </div>
                        <div>
                            <x-input
                                type="datetime-local"
                                label="Waktu Selesai"
                                name="end_time"
                                required
                                :error="$errors->first('end_time')"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input
                                type="number"
                                label="Kuota Peserta"
                                name="quota"
                                value="50"
                                placeholder="0 = Tanpa Batas Kuota"
                                required
                                hint="Isi 0 jika tidak ada batasan jumlah peserta."
                                :error="$errors->first('quota')"
                            />
                        </div>

                        <div>
                            <x-select
                                label="Status Awal Kegiatan"
                                name="status"
                                required
                                :selected="old('status', 'active')"
                                :options="[
                                    'draft' => 'Draft (Simpan Sementara)',
                                    'pending' => 'Pending Approval Proposal',
                                    'active' => 'Aktif (Buka Pendaftaran)',
                                ]"
                                :error="$errors->first('status')"
                            />
                        </div>
                    </div>
                </x-card.body>

                <x-card.footer>
                    <a href="{{ route('manage.events.index') }}">
                        <x-button variant="outline" size="md">Batal</x-button>
                    </a>
                    <x-button type="submit" variant="primary" size="md">
                        Simpan & Publikasikan Kegiatan &rarr;
                    </x-button>
                </x-card.footer>
            </form>
        </x-card>
    </div>
</x-app-layout>
