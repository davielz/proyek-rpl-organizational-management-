<x-app-layout title="Buat Polling Baru">
    <x-slot:header>
        <x-page-header
            title="Buat Polling Baru"
            description="Buat pertanyaan dan pilihan jawaban untuk pengumpulan suara internal."
        />
    </x-slot:header>

    <div class="max-w-3xl mx-auto space-y-6" x-data="createPollForm()">
        <x-card class="p-6">
            <form action="{{ route('polls.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Judul Polling -->
                <div>
                    <x-input
                        name="title"
                        label="Judul / Topik Polling *"
                        value="{{ old('title') }}"
                        placeholder="Contoh: Pilihan Tempat dan Tema Kegiatan Dies Natalis 2026"
                        required
                    />
                    @error('title')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Organisasi (Opsional) -->
                <div>
                    <x-select
                        name="organization_id"
                        label="Organisasi Mahasiswa (Opsional)"
                        placeholder="-- Pilih Organisasi --"
                        :selected="old('organization_id')"
                        :options="$organizations->pluck('name', 'id')->toArray()"
                    />
                    @error('organization_id')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi -->
                <div>
                    <x-textarea
                        name="description"
                        label="Deskripsi / Detail Pertanyaan"
                        value="{{ old('description') }}"
                        placeholder="Jelaskan secara singkat latar belakang polling ini..."
                        rows="3"
                    />
                    @error('description')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deadline -->
                <div>
                    <label for="deadline" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                        Batas Waktu (Deadline) *
                    </label>
                    <input
                        type="datetime-local"
                        name="deadline"
                        id="deadline"
                        value="{{ old('deadline', now()->addDays(3)->format('Y-m-d\TH:i')) }}"
                        required
                        class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none"
                    />
                    @error('deadline')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Daftar Pilihan Jawaban (Min 2) -->
                <div class="border-t border-slate-800 pt-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-white">Daftar Pilihan Jawaban *</h3>
                            <p class="text-xs text-slate-400">Minimal 2 opsi pilihan jawaban yang berbeda.</p>
                        </div>
                        <x-button type="button" variant="outline" size="sm" @click="addOption()">
                            + Tambah Opsi
                        </x-button>
                    </div>

                    @error('options')
                        <p class="text-xs text-rose-400">{{ $message }}</p>
                    @enderror

                    <div class="space-y-3">
                        <template x-for="(opt, index) in options" :key="index">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-bold text-slate-400 w-6 text-center" x-text="index + 1 + '.'"></span>
                                <input
                                    type="text"
                                    name="options[]"
                                    x-model="options[index]"
                                    :placeholder="'Pilihan ' + (index + 1)"
                                    required
                                    class="flex-1 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none"
                                />
                                <button
                                    type="button"
                                    @click="removeOption(index)"
                                    x-show="options.length > 2"
                                    class="p-2 text-rose-400 hover:text-rose-300 hover:bg-slate-800 rounded-xl transition-colors"
                                    title="Hapus Opsi"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 flex justify-end space-x-3">
                    <a href="{{ route('polls.index') }}">
                        <x-button type="button" variant="ghost">Batal</x-button>
                    </a>
                    <x-button type="submit" variant="primary">
                        Terbitkan Polling
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>

    @php
        $initialOptions = old('options', ['Hadir', 'Tidak Hadir', 'Izin']);
    @endphp

    <script>
    function createPollForm() {
        return {
            options: @json($initialOptions),
            addOption() {
                this.options.push('');
            },
            removeOption(index) {
                if (this.options.length > 2) {
                    this.options.splice(index, 1);
                }
            }
        }
    }
    </script>
</x-app-layout>
