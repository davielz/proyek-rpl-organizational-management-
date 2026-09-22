<x-app-layout title="Pengurus Dashboard">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Pengurus Dashboard</h1>
                <p class="text-xs text-slate-400">Manajemen kegiatan dan proposal organisasi Anda.</p>
            </div>
            <span class="bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs font-semibold px-3 py-1.5 rounded-xl">
                Area Pengurus Organisasi
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kegiatan Organisasi</span>
                <p class="text-3xl font-extrabold text-white">{{ $stats['total_org_events'] }}</p>
                <p class="text-[11px] text-slate-500">Total kegiatan yang dikelola</p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Dokumen Pending</span>
                <p class="text-3xl font-extrabold text-amber-400">{{ $stats['pending_documents'] }}</p>
                <p class="text-[11px] text-slate-500">Proposal / LPJ dalam proses</p>
            </div>
        </div>

        <!-- Recent Events Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
            <h2 class="text-base font-bold text-white">Kegiatan Terbaru Organisasi</h2>
            @if($recentEvents->isEmpty())
                <p class="text-xs text-slate-500">Belum ada kegiatan yang terdaftar.</p>
            @else
                <div class="divide-y divide-slate-800">
                    @foreach($recentEvents as $event)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-white">{{ $event->title }}</h3>
                                <p class="text-xs text-slate-400">{{ $event->location }} &bull; {{ $event->start_time->format('d M Y, H:i') }}</p>
                            </div>
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium uppercase
                                @if($event->status === 'active') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                @elseif($event->status === 'pending') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                @else bg-slate-800 text-slate-400 @endif">
                                {{ $event->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
