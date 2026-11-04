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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Waktu Pelaksanaan</span>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $event->start_time->format('d F Y, H:i') }} WIB</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">s/d {{ $event->end_time->format('d F Y, H:i') }} WIB</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Lokasi / Tempat</span>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $event->location }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Penyelenggara</span>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $event->organization->name ?? '-' }} ({{ $event->organization->code ?? '' }})</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Penanggung Jawab</span>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $event->creator->name ?? 'Pengurus Organisasi' }}</p>
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
                <!-- Feedback & Ulasan Section -->
                <x-card>
                    <x-card.header title="Ulasan & Feedback Kegiatan" :subtitle="$event->feedbacks->count() > 0 ? 'Rata-rata Rating: ' . $avgRating . ' / 5.0 (' . $event->feedbacks->count() . ' ulasan)' : 'Belum ada ulasan'">
                        @can('viewAny', \App\Models\EventFeedback::class)
                            <x-slot:action>
                                <a href="{{ route('feedback.index', ['event_id' => $event->id]) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                                    Lihat Analisis Sentimen &rarr;
                                </a>
                            </x-slot:action>
                        @endcan
                    </x-card.header>
                    <x-card.body class="space-y-6">
                        @auth
                            @if ($userFeedback)
                                <div class="p-4 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Ulasan Anda:</span>
                                            <div class="flex text-amber-400 text-sm">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <span>{{ $i <= $userFeedback->rating ? '★' : '☆' }}</span>
                                                @endfor
                                            </div>
                                        </div>

                                        <!-- AI Sentiment Badge -->
                                        @if($userFeedback->sentiment === 'positive')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                ✨ Sentimen Positif
                                            </span>
                                        @elseif($userFeedback->sentiment === 'negative')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                                ⚠️ Sentimen Negatif
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                💬 Sentimen Netral
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ $userFeedback->comment }}</p>

                                    @if(!empty($userFeedback->topics))
                                        <div class="flex items-center space-x-1 text-[11px] text-slate-500 dark:text-slate-400">
                                            <span>Topik:</span>
                                            @foreach($userFeedback->topics as $topic)
                                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">#{{ $topic }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                <form action="{{ route('feedback.store') }}" method="POST" class="space-y-4 p-4 bg-slate-50/80 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl" x-data="{ rating: 5 }">
                                    @csrf
                                    <input type="hidden" name="event_id" value="{{ $event->id }}">

                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Berikan Rating Bintang *</label>
                                        <div class="flex items-center space-x-2">
                                            <template x-for="star in 5">
                                                <button
                                                    type="button"
                                                    @click="rating = star"
                                                    class="text-2xl transition-colors focus:outline-none"
                                                    :class="star <= rating ? 'text-amber-400' : 'text-slate-700 hover:text-amber-300'"
                                                >★</button>
                                            </template>
                                            <input type="hidden" name="rating" :value="rating">
                                            <span class="text-xs text-amber-400 font-bold ml-2" x-text="rating + ' / 5 Bintang'"></span>
                                        </div>
                                    </div>

                                    <div>
                                        <x-textarea
                                            name="comment"
                                            label="Komentar & Masukan Ulasan *"
                                            rows="3"
                                            placeholder="Bagikan pengalaman, masukan, dan saran Anda mengenai kegiatan ini..."
                                            required
                                        />
                                    </div>

                                    <div class="flex justify-end">
                                        <x-button type="submit" variant="primary" size="sm">
                                            Kirim Ulasan & Feedback
                                        </x-button>
                                    </div>
                                </form>
                            @endif
                        @else
                            <p class="text-xs text-slate-400">Silakan <a href="{{ route('login') }}" class="text-indigo-400 underline">masuk</a> untuk memberikan ulasan kegiatan.</p>
                        @endauth
                    </x-card.body>
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
                            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
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
