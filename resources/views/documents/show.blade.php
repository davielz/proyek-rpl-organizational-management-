<x-app-layout title="{{ $document->title }}">
    <x-slot:header>
        <x-page-header
            title="{{ $document->title }}"
            description="Detail Dokumen {{ strtoupper($document->type) }} — Organisasi: {{ $document->event->organization->name }}"
        >
            <x-slot:actions>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('documents.index') }}">
                        <x-button variant="secondary">Kembali</x-button>
                    </a>

                    <a href="{{ route('documents.pdf', $document) }}" target="_blank">
                        <x-button variant="secondary" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                            Export PDF
                        </x-button>
                    </a>

                    @can('update', $document)
                        <a href="{{ route('documents.edit', $document) }}">
                            <x-button variant="warning" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'>
                                Edit Dokumen
                            </x-button>
                        </a>
                    @endcan

                    @can('submit', $document)
                        <button type="button" @click="$dispatch('open-modal', 'submit-modal')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-xl transition-colors inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span>Ajukan ke Admin</span>
                        </button>
                    @endcan

                    @can('review', $document)
                        <button type="button" @click="$dispatch('open-modal', 'review-modal')" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-medium text-sm rounded-xl transition-colors inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Review Dokumen</span>
                        </button>
                    @endcan
                </div>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6 max-w-5xl mx-auto">
        <!-- Success Alert -->
        @if (session('success'))
            <x-alert type="success" title="Berhasil">
                {{ session('success') }}
            </x-alert>
        @endif

        <!-- Status & Workflow Stepper Card -->
        <x-card class="p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-6 mb-6">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Status Workflow</span>
                    <div class="mt-1 flex items-center space-x-3">
                        <x-badge :status="$document->status" size="lg" />
                        <span class="text-xs text-slate-400">Terakhir diperbarui: {{ $document->updated_at->format('d M Y, H:i') }}</span>
                    </div>
                </div>

                <div class="flex items-center space-x-4 text-xs text-slate-400">
                    <div>
                        <span class="block font-semibold text-slate-300">Dibuat Oleh:</span>
                        <span>{{ $document->submitter->name }}</span>
                    </div>

                    @if ($document->approved_by)
                        <div class="border-l border-slate-800 pl-4">
                            <span class="block font-semibold text-emerald-400">Disetujui Oleh:</span>
                            <span>{{ $document->approver?->name }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Workflow Visual Steps -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <!-- Step 1: Draft -->
                <div class="p-3 rounded-xl border {{ in_array($document->status, ['draft', 'submitted', 'approved', 'revision_requested', 'rejected']) ? 'bg-slate-100 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white' : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-500' }}">
                    <div class="flex items-center space-x-2">
                        <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center text-[10px] font-bold">1</span>
                        <span class="font-semibold text-xs">1. Draf</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Disusun oleh Pengurus</p>
                </div>

                <!-- Step 2: Submitted -->
                <div class="p-3 rounded-xl border {{ in_array($document->status, ['submitted', 'approved', 'revision_requested', 'rejected']) ? 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-200 dark:border-indigo-500/30 text-indigo-700 dark:text-indigo-300' : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-500' }}">
                    <div class="flex items-center space-x-2">
                        <span class="w-5 h-5 rounded-full {{ in_array($document->status, ['submitted', 'approved', 'revision_requested', 'rejected']) ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500' }} flex items-center justify-center text-[10px] font-bold">2</span>
                        <span class="font-semibold text-xs">2. Diajukan</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Menunggu Peninjauan Admin</p>
                </div>

                <!-- Step 3: Review / Revision -->
                <div class="p-3 rounded-xl border {{ $document->status === 'revision_requested' ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-500/40 text-amber-700 dark:text-amber-300' : (in_array($document->status, ['approved']) ? 'bg-slate-100 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white' : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-500') }}">
                    <div class="flex items-center space-x-2">
                        <span class="w-5 h-5 rounded-full {{ $document->status === 'revision_requested' ? 'bg-amber-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500' }} flex items-center justify-center text-[10px] font-bold">3</span>
                        <span class="font-semibold text-xs">3. Peninjauan</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">{{ $document->status === 'revision_requested' ? 'Perlu Perbaikan Pengurus' : 'Evaluasi Dokumen' }}</p>
                </div>

                <!-- Step 4: Approved / Final -->
                <div class="p-3 rounded-xl border {{ $document->status === 'approved' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-500/40 text-emerald-700 dark:text-emerald-300' : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-500' }}">
                    <div class="flex items-center space-x-2">
                        <span class="w-5 h-5 rounded-full {{ $document->status === 'approved' ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500' }} flex items-center justify-center text-[10px] font-bold">4</span>
                        <span class="font-semibold text-xs">4. Disetujui</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Dokumen Resmi & Final</p>
                </div>
            </div>
        </x-card>

        <!-- Admin Review Notes (If Any) -->
        @if ($document->notes)
            <x-card class="p-6 border-amber-500/30 bg-amber-950/20">
                <div class="flex items-start space-x-3">
                    <div class="p-2 bg-amber-500/10 text-amber-400 rounded-lg shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-amber-400">Catatan Review Administrator</h4>
                        <p class="text-xs text-slate-300 mt-1 whitespace-pre-line">{{ $document->notes }}</p>
                    </div>
                </div>
            </x-card>
        @endif

        <!-- Document Content Body -->
        <x-card class="p-8 space-y-8">
            <!-- Header Info -->
            <div class="border-b border-slate-800 pb-6 text-center">
                <span class="text-xs font-bold tracking-widest text-indigo-400 uppercase">
                    DOKUMEN {{ strtoupper($document->type) }}
                </span>
                <h1 class="text-2xl font-bold text-white mt-1">{{ $document->title }}</h1>
                <p class="text-sm text-slate-400 mt-1">
                    Kegiatan: <a href="{{ route('events.show', $document->event->slug) }}" class="text-indigo-400 hover:underline">{{ $document->event->title }}</a>
                    | {{ $document->event->organization->name }}
                </p>
            </div>

            <!-- Section 1: Background -->
            <div class="space-y-2">
                <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider border-b border-slate-800 pb-2">
                    1. Latar Belakang / Pendahuluan
                </h3>
                <div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                    {{ $document->background }}
                </div>
            </div>

            <!-- Section 2: Objectives -->
            <div class="space-y-2">
                <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider border-b border-slate-800 pb-2">
                    2. Maksud & Tujuan Kegiatan
                </h3>
                <div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                    {{ $document->objectives }}
                </div>
            </div>

            <!-- Section 3: Budget Plan -->
            @if ($document->budget_plan)
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider border-b border-slate-800 pb-2">
                        3. Rencana / Realisasi Anggaran Biaya
                    </h3>
                    <div class="p-4 bg-slate-900/60 rounded-xl border border-slate-800 text-sm text-slate-300 font-mono whitespace-pre-line">
                        {{ $document->budget_plan }}
                    </div>
                </div>
            @endif

            <!-- Section 4: Summary Content -->
            @if ($document->summary_content)
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wider border-b border-slate-800 pb-2">
                        4. Ringkasan Pelaksanaan / Catatan Penutup
                    </h3>
                    <div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                        {{ $document->summary_content }}
                    </div>
                </div>
            @endif
        </x-card>
    </div>

    <!-- Modal Submit for Review (Pengurus) -->
    @can('submit', $document)
        <x-modal name="submit-modal" title="Ajukan Dokumen untuk Review">
            <div class="space-y-4">
                <p class="text-sm text-slate-300">
                    Apakah Anda yakin ingin mengajukan dokumen <strong class="text-white">{{ $document->title }}</strong> ke Admin?
                </p>
                <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-xs text-amber-300">
                    ⚠️ Setelah diajukan, dokumen tidak dapat diubah hingga Admin memberikan ulasan atau meminta revisi.
                </div>

                <form action="{{ route('documents.submit', $document) }}" method="POST" class="pt-4 flex justify-end space-x-3 border-t border-slate-800">
                    @csrf
                    <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'submit-modal')">Batal</x-button>
                    <x-button type="submit" variant="primary">Ya, Ajukan Dokumen</x-button>
                </form>
            </div>
        </x-modal>
    @endcan

    <!-- Modal Review (Admin) -->
    @can('review', $document)
        <x-modal name="review-modal" title="Review & Evaluasi Dokumen">
            <form action="{{ route('documents.review', $document) }}" method="POST" class="space-y-4" x-data="{ action: 'approve' }">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-2">Keputusan Review *</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all text-center" :class="action === 'approve' ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-400 font-bold' : 'bg-slate-900 border-slate-800 text-slate-400'">
                            <input type="radio" name="action" value="approve" x-model="action" class="sr-only">
                            <span class="text-xs">Setujui</span>
                        </label>

                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all text-center" :class="action === 'request_revision' ? 'bg-amber-500/10 border-amber-500/40 text-amber-400 font-bold' : 'bg-slate-900 border-slate-800 text-slate-400'">
                            <input type="radio" name="action" value="request_revision" x-model="action" class="sr-only">
                            <span class="text-xs">Minta Revisi</span>
                        </label>

                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border cursor-pointer transition-all text-center" :class="action === 'reject' ? 'bg-rose-500/10 border-rose-500/40 text-rose-400 font-bold' : 'bg-slate-900 border-slate-800 text-slate-400'">
                            <input type="radio" name="action" value="reject" x-model="action" class="sr-only">
                            <span class="text-xs">Tolak</span>
                        </label>
                    </div>
                </div>

                <div>
                    <x-textarea
                        name="notes"
                        label="Catatan Review (Wajib jika revisi/tolak)"
                        rows="4"
                        placeholder="Berikan masukan atau poin-poin yang wajib diperbaiki oleh pengurus..."
                    />
                </div>

                <div class="pt-4 flex justify-end space-x-3 border-t border-slate-800">
                    <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'review-modal')">Batal</x-button>
                    <x-button type="submit" variant="primary">Simpan Keputusan Review</x-button>
                </div>
            </form>
        </x-modal>
    @endcan
</x-app-layout>
