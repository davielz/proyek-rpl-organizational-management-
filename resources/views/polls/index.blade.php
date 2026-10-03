<x-app-layout title="Polling & Voting">
    <x-slot:header>
        <x-page-header
            title="Polling & Voting Internal Organisasi"
            description="Fasilitas pengambilan keputusan dan pengumpulan suara anggota secara transparan."
        >
            <x-slot:actions>
                @can('create', App\Models\Poll::class)
                    <a href="{{ route('polls.create') }}">
                        <x-button variant="primary" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                            Buat Polling Baru
                        </x-button>
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6">
        <!-- Filter Bar -->
        <x-card class="p-4">
            <form action="{{ route('polls.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <x-input name="search" value="{{ request('search') }}" placeholder="Cari judul atau pertanyaan polling..." />
                </div>
                <div class="w-full sm:w-48">
                    <x-select name="status" placeholder="Semua Status" :selected="request('status', 'all')" :options="[
                        'all' => 'Semua Status',
                        'active' => 'Polling Aktif',
                        'closed' => 'Polling Selesai/Ditutup',
                    ]" />
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary" size="md">Filter</x-button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('polls.index') }}">
                            <x-button variant="ghost" size="md">Reset</x-button>
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- Polls Grid -->
        @if ($polls->isEmpty())
            <x-card>
                <x-card.body>
                    <x-empty-state
                        title="Belum Ada Polling"
                        description="Belum ada polling atau voting yang diterbitkan saat ini."
                    >
                        @can('create', App\Models\Poll::class)
                            <a href="{{ route('polls.create') }}">
                                <x-button variant="primary" size="sm">Buat Polling Sekarang</x-button>
                            </a>
                        @endcan
                    </x-empty-state>
                </x-card.body>
            </x-card>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($polls as $poll)
                    @php
                        $isClosed = $poll->isClosed();
                        $hasVoted = $poll->hasUserVoted(auth()->user());
                    @endphp
                    <x-card class="p-6 flex flex-col justify-between hover:border-slate-700 transition-colors">
                        <div class="space-y-4">
                            <!-- Top Status Header -->
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center space-x-2">
                                    @if ($poll->organization)
                                        <span class="text-xs font-semibold text-indigo-400 bg-indigo-500/10 px-2.5 py-1 rounded-full border border-indigo-500/20">
                                            {{ $poll->organization->name }}
                                        </span>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400 bg-slate-800 px-2.5 py-1 rounded-full border border-slate-700">
                                            Umum Kampus
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if ($isClosed)
                                        <span class="text-xs font-bold text-rose-400 bg-rose-500/10 px-2.5 py-0.5 rounded-full border border-rose-500/20">
                                            Ditutup
                                        </span>
                                    @else
                                        <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20 flex items-center space-x-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            <span>Aktif</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Title & Description -->
                            <div>
                                <a href="{{ route('polls.show', $poll) }}" class="text-base font-bold text-white hover:text-indigo-400 transition-colors line-clamp-2">
                                    {{ $poll->title }}
                                </a>
                                @if ($poll->description)
                                    <p class="text-xs text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                                        {{ $poll->description }}
                                    </p>
                                @endif
                            </div>

                            <!-- Meta Info -->
                            <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                                <div class="flex items-center space-x-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Deadline: {{ $poll->deadline->format('d M Y, H:i') }}</span>
                                </div>
                                <div class="flex items-center space-x-1 font-semibold text-slate-300">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    <span>{{ $poll->totalVotes() }} Suara</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer Action -->
                        <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                            <div>
                                @if ($hasVoted)
                                    <span class="inline-flex items-center space-x-1 text-xs font-semibold text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-lg border border-emerald-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Sudah Memilih</span>
                                    </span>
                                @else
                                    <span class="text-xs text-amber-400/90 font-medium">
                                        {{ $isClosed ? 'Voting Berakhir' : 'Belum Memilih' }}
                                    </span>
                                @endif
                            </div>

                            <a href="{{ route('polls.show', $poll) }}">
                                <x-button variant="{{ $hasVoted || $isClosed ? 'secondary' : 'primary' }}" size="sm">
                                    {{ $hasVoted || $isClosed ? 'Lihat Hasil' : 'Buka & Vote' }}
                                </x-button>
                            </a>
                        </div>
                    </x-card>
                @endforeach
            </div>

            <!-- Pagination -->
            <x-pagination :paginator="$polls" />
        @endif
    </div>
</x-app-layout>
