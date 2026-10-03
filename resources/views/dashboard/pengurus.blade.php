<x-app-layout title="Pengurus Dashboard">
    <x-slot:header>
        <x-page-header
            title="Dashboard Pengurus"
            description="Pengelolaan kegiatan, penyusunan proposal/LPJ, dan pantauan umpan balik organisasi."
        >
            <x-slot:actions>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('manage.events.create') }}">
                        <x-button variant="primary" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                            Buat Kegiatan
                        </x-button>
                    </a>
                    <a href="{{ route('documents.create') }}">
                        <x-button variant="secondary" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                            Buat Dokumen
                        </x-button>
                    </a>
                </div>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6">
        <!-- AI Chatbot Banner -->
        <x-ai-chat-banner />
        <!-- Organization Banner -->
        <div class="p-4 bg-gradient-to-r from-amber-950/40 via-slate-900 to-indigo-950/30 border border-amber-500/20 rounded-2xl flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-1">
                <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Organisasi Mahasiswa Anda:</span>
                <h3 class="text-lg font-extrabold text-white">
                    {{ $userOrgs->pluck('name')->implode(', ') ?: 'Organisasi Kampus' }}
                </h3>
            </div>
            <a href="{{ route('manage.events.index') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 underline">
                Kelola Seluruh Kegiatan &rarr;
            </a>
        </div>

        <!-- 1. Real Database Metric Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Kegiatan Dikelola -->
            <x-card class="p-4 bg-slate-900 border-slate-800">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kegiatan Dikelola</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-white">{{ $stats['total_events'] }}</span>
                    <span class="text-[11px] text-emerald-400 font-bold">{{ $stats['active_events'] }} Aktif</span>
                </div>
            </x-card>

            <!-- Total Pendaftaran -->
            <x-card class="p-4 bg-slate-900 border-slate-800">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Peserta Terdaftar</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-indigo-400">{{ $stats['total_registrations'] }}</span>
                    <span class="text-[11px] text-slate-500">Pendaftar</span>
                </div>
            </x-card>

            <!-- Dokumen Proposal & LPJ -->
            <x-card class="p-4 bg-slate-900 {{ $stats['pending_documents'] > 0 || $stats['revision_documents'] > 0 ? 'border-amber-500/40' : 'border-slate-800' }}">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Proposal & LPJ</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-white">{{ $stats['total_documents'] }}</span>
                    <div class="text-right text-[11px]">
                        @if($stats['pending_documents'] > 0)
                            <span class="text-amber-400 font-bold block">{{ $stats['pending_documents'] }} Review</span>
                        @endif
                        @if($stats['revision_documents'] > 0)
                            <span class="text-rose-400 font-bold block">{{ $stats['revision_documents'] }} Revisi</span>
                        @endif
                    </div>
                </div>
            </x-card>

            <!-- Rata-Rata Rating -->
            <x-card class="p-4 bg-slate-900 border-slate-800">
                <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider block">Rata-Rata Rating</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <div class="flex items-center space-x-1">
                        <span class="text-2xl font-bold text-amber-400">{{ $stats['avg_rating'] }}</span>
                        <span class="text-xs text-amber-400/80">/ 5.0</span>
                    </div>
                    <span class="text-xs text-slate-500">{{ $stats['total_feedbacks'] }} Ulasan</span>
                </div>
            </x-card>

            <!-- Sentimen Positif -->
            <x-card class="p-4 bg-slate-900 border-slate-800">
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider block">Sentimen Positif</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-emerald-400">{{ $stats['positive_feedbacks'] }}</span>
                    <span class="text-[11px] text-slate-400">{{ $stats['neutral_feedbacks'] }} Netral | {{ $stats['negative_feedbacks'] }} Neg</span>
                </div>
            </x-card>
        </div>

        <!-- 2. Two-Column Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Kegiatan yang Dikelola -->
            <x-card>
                <x-card.header title="Kegiatan Organisasi" :subtitle="'Menampilkan 5 kegiatan terbaru'">
                    <x-slot:action>
                        <a href="{{ route('manage.events.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                            Kelola Semua &rarr;
                        </a>
                    </x-slot:action>
                </x-card.header>
                @if ($managedEvents->isEmpty())
                    <x-card.body>
                        <x-empty-state
                            title="Belum Ada Kegiatan Dibuat"
                            description="Buat kegiatan baru untuk memublikasikan agenda organisasi kepada mahasiswa."
                        >
                            <x-slot:action>
                                <a href="{{ route('manage.events.create') }}">
                                    <x-button variant="primary" size="sm">Buat Kegiatan Sekarang</x-button>
                                </a>
                            </x-slot:action>
                        </x-empty-state>
                    </x-card.body>
                @else
                    <x-table>
                        <thead>
                            <x-table.tr>
                                <x-table.th>Nama Kegiatan</x-table.th>
                                <x-table.th>Jadwal</x-table.th>
                                <x-table.th align="right">Status</x-table.th>
                            </x-table.tr>
                        </thead>
                        <tbody>
                            @foreach ($managedEvents as $event)
                                <x-table.tr>
                                    <x-table.td>
                                        <a href="{{ route('events.show', $event->slug) }}" class="font-bold text-white hover:text-indigo-400">
                                            {{ $event->title }}
                                        </a>
                                    </x-table.td>
                                    <x-table.td class="text-xs text-slate-400">
                                        {{ $event->start_time->format('d M Y, H:i') }}
                                    </x-table.td>
                                    <x-table.td align="right">
                                        <x-badge :status="$event->status" size="sm" />
                                    </x-table.td>
                                </x-table.tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>

            <!-- Proposal & LPJ Dokumen Terbaru -->
            <x-card>
                <x-card.header title="Proposal & LPJ Organisasi">
                    <x-slot:action>
                        <a href="{{ route('documents.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                            Daftar Dokumen &rarr;
                        </a>
                    </x-slot:action>
                </x-card.header>
                @if ($recentDocuments->isEmpty())
                    <x-card.body>
                        <x-empty-state
                            title="Belum Ada Dokumen"
                            description="Susun draf proposal atau LPJ kegiatan untuk diajukan ke admin kampus."
                        >
                            <x-slot:action>
                                <a href="{{ route('documents.create') }}">
                                    <x-button variant="primary" size="sm">Buat Dokumen Sekarang</x-button>
                                </a>
                            </x-slot:action>
                        </x-empty-state>
                    </x-card.body>
                @else
                    <div class="divide-y divide-slate-800">
                        @foreach ($recentDocuments as $doc)
                            <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-850 transition-colors">
                                <div class="space-y-1 min-w-0">
                                    <a href="{{ route('documents.show', $doc) }}" class="font-bold text-sm text-white hover:text-indigo-400 truncate block">
                                        {{ $doc->title }}
                                    </a>
                                    <span class="text-xs text-slate-400 block truncate">Kegiatan: {{ $doc->event->title }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <x-badge :status="$doc->status" size="sm" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <!-- 3. Ulasan Kegiatan Organisasi Terbaru -->
        <x-card>
            <x-card.header title="Ulasan & Feedback Kegiatan Organisasi">
                <x-slot:action>
                    <a href="{{ route('feedback.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                        Analisis Sentimen Lengkap &rarr;
                    </a>
                </x-slot:action>
            </x-card.header>
            @if ($recentFeedbacks->isEmpty())
                <x-card.body>
                    <x-empty-state
                        title="Belum Ada Ulasan Masuk"
                        description="Ulasan dari peserta kegiatan organisasi Anda akan tampil di sini."
                    />
                </x-card.body>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                    @foreach ($recentFeedbacks as $fb)
                        <div class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-white">{{ $fb->user->name ?? 'Peserta' }}</span>
                                <div class="flex text-amber-400 text-xs">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span>{{ $i <= $fb->rating ? '★' : '☆' }}</span>
                                    @endfor
                                </div>
                            </div>
                            <p class="text-xs text-slate-300 italic line-clamp-2">"{{ $fb->comment }}"</p>
                            <div class="flex items-center justify-between text-[11px] pt-1">
                                <span class="text-slate-400 truncate max-w-[200px]">{{ $fb->event->title }}</span>
                                @if($fb->sentiment === 'positive')
                                    <span class="text-emerald-400 font-bold shrink-0">✨ Positif</span>
                                @elseif($fb->sentiment === 'negative')
                                    <span class="text-rose-400 font-bold shrink-0">⚠️ Negatif</span>
                                @else
                                    <span class="text-slate-400 font-semibold shrink-0">💬 Netral</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
