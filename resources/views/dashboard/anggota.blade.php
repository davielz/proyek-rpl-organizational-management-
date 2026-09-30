<x-app-layout title="Anggota Dashboard">
    <x-slot:header>
        <x-page-header
            title="Dashboard Anggota"
            description="Pantau kegiatan yang Anda ikuti, status pendaftaran, umpan balik ulasan, serta agenda kegiatan kampus mendatang."
        >
            <x-slot:actions>
                <a href="{{ route('events.index') }}">
                    <x-button variant="primary" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'>
                        Jelajahi Katalog Kegiatan
                    </x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6">
        <!-- 1. Real Database Metric Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-card class="p-4 bg-slate-900 border-slate-800">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Kegiatan Diikuti</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-white">{{ $stats['total_followed'] }}</span>
                    <span class="text-[11px] text-slate-500">Pendaftaran</span>
                </div>
            </x-card>

            <x-card class="p-4 bg-slate-900 border-slate-800">
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider block">Kehadiran Dikonfirmasi</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-emerald-400">{{ $stats['attended'] }}</span>
                    <span class="text-[11px] text-slate-500">Hadir</span>
                </div>
            </x-card>

            <x-card class="p-4 bg-slate-900 border-slate-800">
                <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider block">Status Terdaftar</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-indigo-400">{{ $stats['registered'] }}</span>
                    <span class="text-[11px] text-slate-500">Mendatang</span>
                </div>
            </x-card>

            <x-card class="p-4 bg-slate-900 border-slate-800">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Dibatalkan</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-slate-300">{{ $stats['cancelled'] }}</span>
                    <span class="text-[11px] text-slate-500">Batal</span>
                </div>
            </x-card>
        </div>

        <!-- 2. Ulasan yang Belum Diberikan -->
        @if ($pendingFeedbackRegistrations->isNotEmpty())
            <x-card class="border-indigo-500/30 bg-indigo-950/20">
                <x-card.header title="Feedback & Ulasan Belum Diberikan" subtitle="Berikan masukan untuk kegiatan yang telah Anda ikuti">
                </x-card.header>
                <div class="divide-y divide-slate-800">
                    @foreach ($pendingFeedbackRegistrations as $reg)
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <a href="{{ route('events.show', $reg->event->slug) }}" class="font-bold text-sm text-white hover:text-indigo-400">
                                    {{ $reg->event->title }}
                                </a>
                                <p class="text-xs text-slate-400">
                                    {{ $reg->event->organization->name }} • {{ $reg->event->start_time->format('d M Y') }}
                                </p>
                            </div>
                            <a href="{{ route('events.show', $reg->event->slug) }}">
                                <x-button variant="primary" size="xs">
                                    Beri Ulasan Sekarang &rarr;
                                </x-button>
                            </a>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        <!-- 3. Two-Column Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Kegiatan yang Diikuti & Status Pendaftaran -->
            <x-card>
                <x-card.header title="Kegiatan Yang Anda Ikuti">
                    <x-slot:action>
                        <a href="{{ route('events.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                            Cari Kegiatan &rarr;
                        </a>
                    </x-slot:action>
                </x-card.header>
                @if ($myRegistrations->isEmpty())
                    <x-card.body>
                        <x-empty-state
                            title="Belum Mengikuti Kegiatan"
                            description="Anda belum mendaftar pada kegiatan organisasi manapun."
                        >
                            <x-slot:action>
                                <a href="{{ route('events.index') }}">
                                    <x-button variant="primary" size="sm">Cari & Daftar Kegiatan</x-button>
                                </a>
                            </x-slot:action>
                        </x-empty-state>
                    </x-card.body>
                @else
                    <x-table>
                        <thead>
                            <x-table.tr>
                                <x-table.th>Kegiatan</x-table.th>
                                <x-table.th>Waktu</x-table.th>
                                <x-table.th align="right">Status</x-table.th>
                            </x-table.tr>
                        </thead>
                        <tbody>
                            @foreach ($myRegistrations as $reg)
                                <x-table.tr>
                                    <x-table.td>
                                        <div class="flex flex-col">
                                            <a href="{{ route('events.show', $reg->event->slug) }}" class="font-bold text-white hover:text-indigo-400">
                                                {{ $reg->event->title }}
                                            </a>
                                            <span class="text-[11px] text-slate-400">{{ $reg->event->organization->name }}</span>
                                        </div>
                                    </x-table.td>
                                    <x-table.td class="text-xs text-slate-400">
                                        {{ $reg->event->start_time->format('d M Y, H:i') }}
                                    </x-table.td>
                                    <x-table.td align="right">
                                        <x-badge :status="$reg->status" size="sm" />
                                    </x-table.td>
                                </x-table.tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>

            <!-- Katalog Kegiatan Mendatang -->
            <x-card>
                <x-card.header title="Katalog Kegiatan Mendatang">
                    <x-slot:action>
                        <a href="{{ route('events.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                            Lihat Semua &rarr;
                        </a>
                    </x-slot:action>
                </x-card.header>
                @if ($upcomingEvents->isEmpty())
                    <x-card.body>
                        <x-empty-state
                            title="Belum Ada Kegiatan Baru"
                            description="Belum ada kegiatan mendatang yang dipublikasikan dalam waktu dekat."
                        />
                    </x-card.body>
                @else
                    <div class="divide-y divide-slate-800">
                        @foreach ($upcomingEvents as $event)
                            <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-850 transition-colors">
                                <div class="space-y-1 min-w-0">
                                    <a href="{{ route('events.show', $event->slug) }}" class="font-bold text-sm text-white hover:text-indigo-400 truncate block">
                                        {{ $event->title }}
                                    </a>
                                    <div class="flex items-center space-x-2 text-xs text-slate-400">
                                        <span>{{ $event->organization->name }}</span>
                                        <span>•</span>
                                        <span>{{ $event->start_time->format('d M Y, H:i') }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('events.show', $event->slug) }}">
                                    <x-button variant="secondary" size="xs">
                                        Detail &rarr;
                                    </x-button>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
