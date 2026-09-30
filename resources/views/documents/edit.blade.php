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

    <div class="max-w-4xl mx-auto space-y-6" x-data="documentEditForm()">
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
            <form action="{{ route('documents.update', $document) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Event Select -->
                    <div>
                        <x-select
                            name="event_id"
                            label="Pilih Kegiatan *"
                            x-model="eventId"
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
                            x-model="docType"
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
                        x-model="title"
                        required
                    />
                    @error('title')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- AI Draft Assistant Helper -->
                <div class="p-4 bg-gradient-to-r from-indigo-950/60 via-slate-900 to-purple-950/40 border border-indigo-500/30 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-inner">
                    <div class="space-y-0.5">
                        <div class="flex items-center space-x-2 text-indigo-300 font-bold text-xs">
                            <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Regenerate Draf dengan AI</span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            Perbarui atau buat ulang draf dokumen menggunakan AI Assistant.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="generateAiDraft()"
                        :disabled="isGeneratingAi || !eventId"
                        class="px-4 py-2 text-xs font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 disabled:opacity-50 text-white rounded-xl transition-all shadow-md shadow-indigo-600/30 flex items-center space-x-2 shrink-0"
                    >
                        <template x-if="isGeneratingAi">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="isGeneratingAi ? 'Menyusun Draf AI...' : '✨ Generate Ulang Draf AI'"></span>
                    </button>
                </div>

                <!-- Background -->
                <div>
                    <x-textarea
                        name="background"
                        label="1. Latar Belakang / Pendahuluan *"
                        rows="5"
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
                        x-model="objectives"
                        required
                    />
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
                        x-model="budgetPlan"
                    />
                </div>

                <!-- Summary Content -->
                <div>
                    <x-textarea
                        name="summary_content"
                        label="4. Ringkasan Pelaksanaan / Catatan Penutup"
                        rows="4"
                        x-model="summaryContent"
                    />
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

    <script>
        function documentEditForm() {
            return {
                eventId: '{{ old('event_id', $document->event_id) }}',
                docType: '{{ old('type', $document->type) }}',
                title: @json(old('title', $document->title)),
                background: @json(old('background', $document->background)),
                objectives: @json(old('objectives', $document->objectives)),
                budgetPlan: @json(old('budget_plan', $document->budget_plan)),
                summaryContent: @json(old('summary_content', $document->summary_content)),

                isGeneratingAi: false,
                aiNotice: {
                    message: '',
                    isFallback: false
                },

                async generateAiDraft() {
                    if (!confirm('Generasi AI akan memperbarui isi form saat ini. Lanjutkan?')) {
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

                            this.aiNotice.message = result.message || 'Draf AI berhasil dimuat ke editor.';
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
                }
            }
        }
    </script>
</x-app-layout>
