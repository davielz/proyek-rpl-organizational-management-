<x-app-layout title="Anggota Dashboard">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Selamat Datang, {{ auth()->user()->name }}!</h1>
            <p class="text-xs text-slate-400">Dashboard Anggota & Peserta Kegiatan Kampus.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Registered Events -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
                <h2 class="text-base font-bold text-white flex items-center justify-between">
                    <span>Kegiatan Yang Diikuti</span>
                    <span class="text-xs font-normal text-indigo-400">({{ $registeredEvents->count() }})</span>
                </h2>

                @if($registeredEvents->isEmpty())
                    <p class="text-xs text-slate-500">Anda belum mendaftar di kegiatan apa pun.</p>
                @else
                    <div class="divide-y divide-slate-800">
                        @foreach($registeredEvents as $event)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-white">{{ $event->title }}</h3>
                                    <p class="text-xs text-slate-400">{{ $event->start_time->format('d M Y') }}</p>
                                </div>
                                <span class="text-xs px-2.5 py-0.5 rounded-full font-medium capitalize
                                    @if($event->pivot->status === 'attended') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @else bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 @endif">
                                    {{ $event->pivot->status }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Upcoming Events -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
                <h2 class="text-base font-bold text-white">Kegiatan Mendatang</h2>

                @if($upcomingEvents->isEmpty())
                    <p class="text-xs text-slate-500">Belum ada kegiatan aktif baru.</p>
                @else
                    <div class="divide-y divide-slate-800">
                        @foreach($upcomingEvents as $event)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-white">{{ $event->title }}</h3>
                                    <p class="text-xs text-slate-400">{{ $event->location }} &bull; {{ $event->start_time->format('d M Y, H:i') }}</p>
                                </div>
                                <span class="text-xs text-slate-400 font-medium">Kuota: {{ $event->quota }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
