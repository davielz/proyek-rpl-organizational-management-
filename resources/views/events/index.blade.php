<x-app-layout title="Katalog Kegiatan Kampus">
    <div class="space-y-6">
        <x-page-header
            title="Katalog Kegiatan Kampus"
            subtitle="Jelajahi dan ikuti berbagai kegiatan menarik yang diselenggarakan oleh organisasi dan UKM kampus."
            :breadcrumbs="['Dashboard' => route('dashboard'), 'Katalog Kegiatan' => null]"
        >
            @can('create', App\Models\Event::class)
                <a href="{{ route('manage.events.create') }}">
                    <x-button variant="primary" size="sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Buat Kegiatan Baru
                    </x-button>
                </a>
            @endcan
        </x-page-header>

        <!-- Filter & Search Bar -->
        <x-card class="p-4">
            <form action="{{ route('events.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <x-input name="search" value="{{ request('search') }}" placeholder="Cari judul kegiatan..." />
                </div>
                <div class="w-full sm:w-64">
                    <x-select name="organization_id" placeholder="Semua Organisasi" :selected="request('organization_id')">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" @selected(request('organization_id') == $org->id)>
                                {{ $org->code }} - {{ $org->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary" size="md">Cari</x-button>
                    @if(request()->hasAny(['search', 'organization_id']))
                        <a href="{{ route('events.index') }}">
                            <x-button variant="ghost" size="md">Reset</x-button>
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- Event Grid -->
        @if($events->isEmpty())
            <x-empty-state
                title="Tidak Ada Kegiatan Ditemukan"
                description="Belum ada kegiatan aktif yang sesuai dengan pencarian Anda."
            >
                @can('create', App\Models\Event::class)
                    <a href="{{ route('manage.events.create') }}">
                        <x-button variant="primary" size="sm">Buat Kegiatan Pertama</x-button>
                    </a>
                @endcan
            </x-empty-state>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($events as $event)
                    @php
                        $registeredCount = $event->registrations->where('status', '!=', 'cancelled')->count();
                        $isFull = $event->quota > 0 && $registeredCount >= $event->quota;
                    @endphp
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between hover:border-slate-700 transition-all duration-200 shadow-xl group">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider bg-indigo-500/10 px-2.5 py-1 rounded-full border border-indigo-500/20">
                                    {{ $event->organization->code ?? 'UKM' }}
                                </span>
                                <x-badge :status="$event->status" size="sm" />
                            </div>

                            <div class="space-y-1">
                                <h2 class="text-base font-bold text-white group-hover:text-indigo-400 transition-colors line-clamp-2">
                                    <a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a>
                                </h2>
                                <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed">{{ $event->description }}</p>
                            </div>

                            <div class="space-y-2 text-xs text-slate-300 pt-2 border-t border-slate-800/80">
                                <div class="flex items-center space-x-2 text-slate-400">
                                    <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>{{ $event->start_time->format('d M Y, H:i') }} WIB</span>
                                </div>
                                <div class="flex items-center space-x-2 text-slate-400">
                                    <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="truncate">{{ $event->location }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5 mt-4 border-t border-slate-800/80 space-y-3">
                            <!-- Quota Indicator -->
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Kuota Peserta:</span>
                                <span class="font-semibold {{ $isFull ? 'text-rose-400' : 'text-slate-200' }}">
                                    {{ $registeredCount }} / {{ $event->quota > 0 ? $event->quota : '∞' }}
                                </span>
                            </div>

                            <a href="{{ route('events.show', $event->slug) }}" class="block">
                                <x-button variant="outline" size="sm" class="w-full">
                                    Lihat Detail & Pendaftaran &rarr;
                                </x-button>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <x-pagination :paginator="$events" />
        @endif
    </div>
</x-app-layout>
