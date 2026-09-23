<x-app-layout :title="'Edit ' . $event->title">
    <div class="max-w-3xl mx-auto space-y-6">
        <x-page-header
            :title="'Edit Kegiatan: ' . $event->title"
            subtitle="Perbarui rincian, jadwal, kuota, atau status kegiatan."
            :breadcrumbs="[
                'Dashboard' => route('dashboard'),
                'Kelola Kegiatan' => route('manage.events.index'),
                'Edit Kegiatan' => null
            ]"
        />

        <x-card>
            <form action="{{ route('manage.events.update', $event->id) }}" method="POST">
                @csrf
                @method('PUT')

                <x-card.body class="space-y-6">
                    <div>
                        <x-select
                            label="Penyelenggara (Organisasi / UKM)"
                            name="organization_id"
                            required
                            :selected="old('organization_id', $event->organization_id)"
                            :error="$errors->first('organization_id')"
                        >
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" @selected(old('organization_id', $event->organization_id) == $org->id)>
                                    {{ $org->code }} - {{ $org->name }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-input
                            label="Judul Kegiatan"
                            name="title"
                            :value="$event->title"
                            required
                            :error="$errors->first('title')"
                        />
                    </div>

                    <div>
                        <x-textarea
                            label="Deskripsi & Rincian Kegiatan"
                            name="description"
                            rows="5"
                            :value="$event->description"
                            required
                            :error="$errors->first('description')"
                        />
                    </div>

                    <div>
                        <x-input
                            label="Lokasi / Tempat Pelaksanaan"
                            name="location"
                            :value="$event->location"
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
                                :value="$event->start_time ? $event->start_time->format('Y-m-d\TH:i') : ''"
                                required
                                :error="$errors->first('start_time')"
                            />
                        </div>
                        <div>
                            <x-input
                                type="datetime-local"
                                label="Waktu Selesai"
                                name="end_time"
                                :value="$event->end_time ? $event->end_time->format('Y-m-d\TH:i') : ''"
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
                                :value="$event->quota"
                                required
                                hint="Isi 0 jika tidak ada batasan kuota."
                                :error="$errors->first('quota')"
                            />
                        </div>

                        <div>
                            <x-select
                                label="Status Kegiatan"
                                name="status"
                                required
                                :selected="old('status', $event->status)"
                                :options="[
                                    'draft' => 'Draft',
                                    'pending' => 'Pending Approval',
                                    'active' => 'Aktif',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan',
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
                        Simpan Perubahan &rarr;
                    </x-button>
                </x-card.footer>
            </form>
        </x-card>
    </div>
</x-app-layout>
