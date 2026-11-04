<x-app-layout title="Admin Dashboard">
    <x-slot:header>
        <x-page-header
            title="Dashboard Administrator"
            description="Overview statistik global sistem, kegiatan kampus, dokumen proposal, dan analisis ulasan."
        >
            <x-slot:actions>
                <a href="{{ route('admin.users.index') }}">
                    <x-button variant="primary" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'>
                        Kelola User & Role
                    </x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6">
        <!-- AI Chatbot Banner -->
        <x-ai-chat-banner />

        @if (($stats['pending_users_count'] ?? 0) > 0)
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Persetujuan Pendaftaran Akun Menunggu</h4>
                        <p class="text-xs text-slate-600 dark:text-slate-300">Ada <span class="font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending_users_count'] }} calon pengguna baru</span> yang menunggu persetujuan (ACC) sebelum dapat menggunakan sistem.</p>
                    </div>
                </div>
                <a href="{{ route('manage.approvals.index') }}" class="shrink-0">
                    <x-button variant="warning" size="sm">
                        Tinjau & Setujui
                    </x-button>
                </a>
            </div>
        @endif

        <!-- 1. Real Database Metric Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Total Anggota -->
            <x-card class="p-4">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Anggota</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_members'] }}</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">dari {{ $stats['total_users'] }} User</span>
                </div>
            </x-card>

            <!-- Total Kegiatan -->
            <x-card class="p-4">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Kegiatan</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_events'] }}</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Semua Organisasi</span>
                </div>
            </x-card>

            <!-- Proposal Menunggu Review -->
            <x-card class="p-4 {{ $stats['pending_reviews_count'] > 0 ? 'border-amber-300 dark:border-amber-500/40 bg-amber-50/60 dark:bg-amber-950/20' : '' }}">
                <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">Proposal Review</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending_reviews_count'] }}</span>
                    <span class="text-[11px] text-amber-600/80 dark:text-amber-400/80">Menunggu</span>
                </div>
            </x-card>

            <!-- Rating Ulasan -->
            <x-card class="p-4">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Rata-Rata Rating</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <div class="flex items-center space-x-1">
                        <span class="text-2xl font-bold text-amber-500 dark:text-amber-400">{{ $stats['avg_rating'] }}</span>
                        <span class="text-xs text-amber-600/80 dark:text-amber-400/80">/ 5.0</span>
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $stats['total_feedbacks'] }} Ulasan</span>
                </div>
            </x-card>

            <!-- Sentimen Positif Ratio -->
            <x-card class="p-4">
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Sentimen Positif</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['positive_feedbacks'] }}</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ $stats['neutral_feedbacks'] }} Netral | {{ $stats['negative_feedbacks'] }} Neg</span>
                </div>
            </x-card>
        </div>

        <!-- 2. Two-Column Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Proposal & LPJ Menunggu Review Admin -->
            <x-card>
                <x-card.header title="Proposal & LPJ Menunggu Review" :subtitle="$stats['pending_reviews_count'] . ' dokumen diajukan'">
                    <x-slot:action>
                        <a href="{{ route('documents.index', ['status' => 'submitted']) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                            Lihat Semua &rarr;
                        </a>
                    </x-slot:action>
                </x-card.header>
                @if ($pendingDocuments->isEmpty())
                    <x-card.body>
                        <x-empty-state
                            title="Tidak Ada Dokumen Menunggu Review"
                            description="Semua proposal dan LPJ yang diajukan telah ditinjau."
                        />
                    </x-card.body>
                @else
                    <x-table>
                        <thead>
                            <x-table.tr>
                                <x-table.th>Dokumen</x-table.th>
                                <x-table.th>Organisasi</x-table.th>
                                <x-table.th align="right">Aksi</x-table.th>
                            </x-table.tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingDocuments as $doc)
                                <x-table.tr>
                                    <x-table.td>
                                        <div class="flex flex-col">
                                            <a href="{{ route('documents.show', $doc) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ $doc->title }}
                                            </a>
                                            <span class="text-[11px] text-slate-500 dark:text-slate-400">Penyusun: {{ $doc->submitter->name }}</span>
                                        </div>
                                    </x-table.td>
                                    <x-table.td class="text-xs text-slate-600 dark:text-slate-300">
                                        {{ $doc->event->organization->name }}
                                    </x-table.td>
                                    <x-table.td align="right">
                                        <a href="{{ route('documents.show', $doc) }}">
                                            <x-button variant="warning" size="xs">
                                                Review
                                            </x-button>
                                        </a>
                                    </x-table.td>
                                </x-table.tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>

            <!-- Kegiatan Mendatang -->
            <x-card>
                <x-card.header title="Kegiatan Kampus Mendatang">
                    <x-slot:action>
                        <a href="{{ route('events.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                            Katalog Kegiatan &rarr;
                        </a>
                    </x-slot:action>
                </x-card.header>
                @if ($upcomingEvents->isEmpty())
                    <x-card.body>
                        <x-empty-state
                            title="Belum Ada Kegiatan Mendatang"
                            description="Belum ada agenda kegiatan organisasi yang dijadwalkan dalam waktu dekat."
                        />
                    </x-card.body>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($upcomingEvents as $event)
                            <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <div class="space-y-1 min-w-0">
                                    <a href="{{ route('events.show', $event->slug) }}" class="font-bold text-sm text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 truncate block">
                                        {{ $event->title }}
                                    </a>
                                    <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400">
                                        <span>{{ $event->organization->name }}</span>
                                        <span>•</span>
                                        <span>{{ $event->start_time->format('d M Y, H:i') }}</span>
                                    </div>
                                </div>
                                <x-badge :status="$event->status" size="sm" class="shrink-0" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <!-- 3. Ringkasan Feedback & Sentimen Terbaru -->
        <x-card>
            <x-card.header title="Ringkasan Feedback & Sentimen Ulasan Terbaru">
                <x-slot:action>
                    <a href="{{ route('feedback.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                        Lihat Dashboard Feedback &rarr;
                    </a>
                </x-slot:action>
            </x-card.header>
            @if ($recentFeedbacks->isEmpty())
                <x-card.body>
                    <x-empty-state
                        title="Belum Ada Ulasan Masuk"
                        description="Ulasan dari anggota yang mengikuti kegiatan akan muncul di sini."
                    />
                </x-card.body>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                    @foreach ($recentFeedbacks as $fb)
                        <div class="p-4 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-slate-900 dark:text-white">{{ $fb->user->name ?? 'Peserta' }}</span>
                                <div class="flex text-amber-400 text-xs">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span>{{ $i <= $fb->rating ? '★' : '☆' }}</span>
                                    @endfor
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300 italic line-clamp-2">"{{ $fb->comment }}"</p>
                            <div class="flex items-center justify-between text-[11px] pt-1">
                                <span class="text-slate-500 dark:text-slate-400">{{ $fb->event->title }}</span>
                                @if($fb->sentiment === 'positive')
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">✨ Positif</span>
                                @elseif($fb->sentiment === 'negative')
                                    <span class="text-rose-600 dark:text-rose-400 font-bold">⚠️ Negatif</span>
                                @else
                                    <span class="text-slate-500 dark:text-slate-400 font-semibold">💬 Netral</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
