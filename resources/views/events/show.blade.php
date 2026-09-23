<x-app-layout :title="$event->title">
    <div class="space-y-6">
        <x-page-header
            :title="$event->title"
            :subtitle="'Diselenggarakan oleh ' . ($event->organization->name ?? 'Organisasi Mahasiswa')"
            :breadcrumbs="[
                'Dashboard' => route('dashboard'),
                'Katalog Kegiatan' => route('events.index'),
                $event->title => null
            ]"
        >
            @can('update', $event)
                <a href="{{ route('manage.events.edit', $event->id) }}">
                    <x-button variant="outline" size="sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Kegiatan
                    </x-button>
                </a>
            @endcan
        </x-page-header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Event Details & Description -->
            <div class="lg:col-span-2 space-y-6">
                <x-card>
                    <x-card.header title="Rincian & Informasi Kegiatan">
                        <x-slot:action>
                            <x-badge :status="$event->status" size="md" />
                        </x-slot:action>
                    </x-card.header>
                    <x-card.body class="space-y-6">
                        <!-- Metadata Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Waktu Pelaksanaan</span>
                                <p class="text-sm font-semibold text-white">{{ $event->start_time->format('d F Y, H:i') }} WIB</p>
                                <p class="text-xs text-slate-400">s/d {{ $event->end_time->format('d F Y, H:i') }} WIB</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Lokasi / Tempat</span>
                                <p class="text-sm font-semibold text-white">{{ $event->location }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Penyelenggara</span>
                                <p class="text-sm font-semibold text-white">{{ $event->organization->name ?? '-' }} ({{ $event->organization->code ?? '' }})</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Penanggung Jawab</span>
                                <p class="text-sm font-semibold text-white">{{ $event->creator->name ?? 'Pengurus Organisasi' }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Deskripsi Kegiatan</h3>
                            <div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                                {{ $event->description }}
                            </div>
                        </div>
                    </x-card.body>
                </x-card>

                <!-- Participants List Section -->
                <x-card>
                    <x-card.header title="Daftar Peserta Terdaftar" :subtitle="'Total ' . $registeredCount . ' peserta terdaftar'">
                        <x-slot:action>
                            <span class="text-xs font-semibold text-slate-400">Kuota: {{ $event->quota > 0 ? $event->quota : 'Unbounded' }}</span>
                        </x-slot:action>
                    </x-card.header>
                    @if($event->registrations->where('status', '!=', 'cancelled')->isEmpty())
                        <x-card.body>
                            <p class="text-xs text-slate-500 italic">Belum ada peserta yang mendaftar pada kegiatan ini.</p>
                        </x-card.body>
                    @else
                        <x-table>
                            <thead>
                                <x-table.tr>
                                    <x-table.th>No</x-table.th>
                                    <x-table.th>Nama Peserta</x-table.th>
                                    <x-table.th>Waktu Pendaftaran</x-table.th>
                                    <x-table.th align="right">Status Kehadiran</x-table.th>
                                </x-table.tr>
                            </thead>
                            <tbody>
                                @foreach($event->registrations->where('status', '!=', 'cancelled') as $index => $reg)
                                    <x-table.tr>
                                        <x-table.td>{{ $loop->iteration }}</x-table.td>
                                        <x-table.td class="font-semibold text-white">{{ $reg->user->name ?? 'Peserta' }}</x-table.td>
                                        <x-table.td class="text-xs text-slate-400">{{ $reg->registered_at ? $reg->registered_at->format('d M Y, H:i') : '-' }}</x-table.td>
                                        <x-table.td align="right">
                                            <x-badge :status="$reg->status" size="sm" />
                                        </x-table.td>
                                    </x-table.tr>
                                @endforeach
                            </tbody>
                        </x-table>
                    @endif
                </x-card>
            </div>

            <!-- Right Column: Registration Card & Action -->
            <div class="space-y-6">
                <x-card>
                    <x-card.header title="Pendaftaran Kegiatan" />
                    <x-card.body class="space-y-4">
                        <!-- Quota Progress Bar -->
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400">Status Ketersediaan:</span>
                                <span class="font-bold {{ $isFull ? 'text-rose-400' : 'text-emerald-400' }}">
                                    {{ $isFull ? 'Kuota Penuh' : 'Tersedia' }}
                                </span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                                @php
                                    $percentage = $event->quota > 0 ? min(100, round(($registeredCount / $event->quota) * 100)) : 0;
                                @endphp
                                <div class="h-full bg-indigo-600 transition-all duration-300" style="width: {{ $percentage }}%"></div>
                            </div>
                            <p class="text-[11px] text-slate-400 text-right">{{ $registeredCount }} dari {{ $event->quota > 0 ? $event->quota : '∞' }} kursi terisi</p>
                        </div>

                        <!-- User Status & Button -->
                        @auth
                            @if($isRegistered)
                                <x-alert type="success" title="Status Pendaftaran Anda" :dismissible="false">
                                    Anda telah terdaftar sebagai peserta dalam kegiatan ini.
                                </x-alert>

                                @can('cancelRegistration', $event)
                                    <x-button
                                        variant="danger"
                                        size="md"
                                        class="w-full"
                                        onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-cancel' }))"
                                    >
                                        Batalkan Pendaftaran
                                    </x-button>
                                @endcan
                            @else
                                @can('register', $event)
                                    <form action="{{ route('events.register', $event->slug) }}" method="POST">
                                        @csrf
                                        <x-button type="submit" variant="primary" size="lg" class="w-full">
                                            Daftar Kegiatan Sekarang &rarr;
                                        </x-button>
                                    </form>
                                @else
                                    @if($isFull)
                                        <x-alert type="warning" title="Pendaftaran Ditutup" :dismissible="false">
                                            Kuota pendaftaran untuk kegiatan ini telah habis.
                                        </x-alert>
                                    @elseif($event->status !== 'active')
                                        <x-alert type="info" title="Pendaftaran Tidak Aktif" :dismissible="false">
                                            Kegiatan ini saat ini bernilai status <strong>{{ $event->status }}</strong>.
                                        </x-alert>
                                    @endif
                                @endcan
                            @endif
                        @else
                            <x-alert type="info" title="Ingin Mendaftar?" :dismissible="false">
                                Silakan masuk ke akun Anda terlebih dahulu untuk melakukan pendaftaran kegiatan.
                            </x-alert>
                            <a href="{{ route('login') }}" class="block">
                                <x-button variant="primary" size="md" class="w-full">
                                    Masuk Ke Akun &rarr;
                                </x-button>
                            </a>
                        @endauth
                    </x-card.body>
                </x-card>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Batal Pendaftaran -->
    @auth
        <x-modal name="confirm-cancel" title="Konfirmasi Pembatalan Pendaftaran" size="md">
            <p>Apakah Anda yakin ingin membatalkan pendaftaran pada kegiatan <strong>"{{ $event->title }}"</strong>?</p>
            <p class="text-xs text-slate-400 mt-2">Setelah dibatalkan, kuota Anda akan dilepaskan untuk peserta lainnya.</p>
            <x-slot:footer>
                <x-button variant="outline" size="sm" onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-cancel' }))">
                    Batal
                </x-button>
                <form action="{{ route('events.cancel-registration', $event->slug) }}" method="POST">
                    @csrf
                    <x-button type="submit" variant="danger" size="sm">
                        Ya, Batalkan Pendaftaran
                    </x-button>
                </form>
            </x-slot:footer>
        </x-modal>
    @endauth
</x-app-layout>
