<x-app-layout title="Edit Dokumen {{ strtoupper($document->type) }}">
    <x-slot:header>
        <x-page-header
            title="Edit Dokumen {{ strtoupper($document->type) }}"
            description="Perbarui isi dan informasi draf atau revisi dokumen."
        >
            <x-slot:actions>
                <a href="{{ route('documents.show', $document) }}">
                    <x-button variant="secondary">
                        Batal / Detail
                    </x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-4xl mx-auto space-y-6">
        @if ($document->status === 'revision_requested' && $document->notes)
            <x-alert type="warning" title="Catatan Revisi dari Admin">
                <p class="text-sm font-medium mt-1">{{ $document->notes }}</p>
                <p class="text-xs text-amber-300/80 mt-2">Silakan perbaiki bagian dokumen di bawah ini sesuai arahan di atas sebelum mengajukan kembali.</p>
            </x-alert>
        @endif

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
            <form action="{{ route('documents.update', $document) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Event Select -->
                    <div>
                        <x-select
                            name="event_id"
                            label="Pilih Kegiatan *"
                            required
                        >
                            @foreach ($events as $event)
                                <option value="{{ $event->id }}" {{ old('event_id', $document->event_id) == $event->id ? 'selected' : '' }}>
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
                            required
                        >
                            <option value="proposal" {{ old('type', $document->type) === 'proposal' ? 'selected' : '' }}>Proposal Kegiatan</option>
                            <option value="lpj" {{ old('type', $document->type) === 'lpj' ? 'selected' : '' }}>Laporan Pertanggungjawaban (LPJ)</option>
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
                        value="{{ old('title', $document->title) }}"
                        required
                    />
                    @error('title')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Background -->
                <div>
                    <x-textarea
                        name="background"
                        label="1. Latar Belakang / Pendahuluan *"
                        rows="5"
                        required
                    >{{ old('background', $document->background) }}</x-textarea>
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
                        required
                    >{{ old('objectives', $document->objectives) }}</x-textarea>
                    @error('objectives')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Budget Plan -->
                <div>
                    <x-textarea
                        name="budget_plan"
                        label="3. Rencana / Realisasi Anggaran Biaya"
                        rows="4"
                    >{{ old('budget_plan', $document->budget_plan) }}</x-textarea>
                </div>

                <!-- Summary Content -->
                <div>
                    <x-textarea
                        name="summary_content"
                        label="4. Ringkasan Pelaksanaan / Catatan Penutup"
                        rows="4"
                    >{{ old('summary_content', $document->summary_content) }}</x-textarea>
                </div>

                <!-- Form Submit Actions -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                    <a href="{{ route('documents.show', $document) }}">
                        <x-button type="button" variant="secondary">Batal</x-button>
                    </a>
                    <x-button type="submit" variant="primary">Simpan Perubahan</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
