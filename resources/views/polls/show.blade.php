<x-app-layout title="{{ $poll->title }}">
    <x-slot:header>
        <x-page-header
            title="{{ $poll->title }}"
            description="Detail polling, opsi suara, serta hasil kalkulasi suara real-time."
            :breadcrumbs="['Dashboard' => route('dashboard'), 'Polling & Voting' => route('polls.index'), 'Detail' => null]"
        >
            <x-slot:actions>
                <div class="flex items-center space-x-2">
                    @can('update', $poll)
                        <a href="{{ route('polls.edit', $poll) }}">
                            <x-button variant="secondary" size="sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit Polling
                            </x-button>
                        </a>
                    @endcan

                    @can('close', $poll)
                        @if (!$poll->isClosed())
                            <form action="{{ route('polls.close', $poll) }}" method="POST" class="inline">
                                @csrf
                                <x-button type="submit" variant="warning" size="sm" onclick="return confirm('Apakah Anda yakin ingin menutup polling ini sekarang?')">
                                    Tutup Polling
                                </x-button>
                            </form>
                        @endif
                    @endcan

                    @can('delete', $poll)
                        <x-button
                            variant="danger"
                            size="sm"
                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-poll-modal' }))"
                        >
                            Hapus
                        </x-button>
                    @endcan
                </div>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6 max-w-4xl mx-auto">
        <!-- Main Poll Information Header Card -->
        <x-card class="p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center space-x-2">
                        @if ($poll->organization)
                            <span class="text-xs font-semibold text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">
                                {{ $poll->organization->name }}
                            </span>
                        @endif
                        <span class="text-xs text-slate-400">Dibuat oleh: <strong class="text-slate-200">{{ $poll->creator->name }}</strong></span>
                    </div>

                    @if ($poll->isClosed())
                        <span class="text-xs font-bold text-rose-400 bg-rose-500/10 px-3 py-1 rounded-full border border-rose-500/20">
                            Ditutup
                        </span>
                    @else
                        <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20 flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Polling Aktif</span>
                        </span>
                    @endif
                </div>

                @if ($poll->description)
                    <div class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-300 leading-relaxed">
                        {{ $poll->description }}
                    </div>
                @endif

                <div class="flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-slate-800">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Deadline: <strong class="text-slate-200">{{ $poll->deadline->format('d M Y, H:i T') }}</strong></span>
                    </div>
                    <div class="flex items-center space-x-1 font-semibold text-slate-300">
                        <span class="text-indigo-400 text-sm font-bold">{{ $totalVotes }}</span> Total Suara Masuk
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Voting Section OR Already Voted Status -->
        @if ($hasVoted)
            <x-alert type="success" title="Suara Anda Terdaftar">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <span>Anda telah memberikan suara untuk opsi: <strong>"{{ $userVote->option->option_text }}"</strong> pada {{ $userVote->voted_at->format('d M Y, H:i') }}.</span>
                </div>
            </x-alert>
        @elseif ($poll->isClosed())
            <x-alert type="warning" title="Polling Ditutup">
                Masa berlaku polling ini sudah berakhir atau telah ditutup oleh pengurus. Anda tidak dapat memberikan suara lagi.
            </x-alert>
        @else
            <!-- Voting Form for Active Poll -->
            <x-card>
                <x-card.header title="Berikan Suara Anda" subtitle="Pilih salah satu opsi jawaban di bawah ini." />
                <x-card.body>
                    <form action="{{ route('polls.vote', $poll) }}" method="POST" class="space-y-4">
                        @csrf
                        @error('option_id')
                            <p class="text-xs text-rose-400 font-semibold mb-2">{{ $message }}</p>
                        @enderror

                        <div class="space-y-3">
                            @foreach ($poll->options as $option)
                                <label class="flex items-center space-x-3 p-4 bg-slate-950 hover:bg-slate-850 border border-slate-800 focus-within:border-indigo-500 rounded-xl cursor-pointer transition-all">
                                    <input
                                        type="radio"
                                        name="option_id"
                                        value="{{ $option->id }}"
                                        required
                                        class="w-4 h-4 text-indigo-600 border-slate-700 focus:ring-indigo-500 bg-slate-900"
                                    />
                                    <span class="text-sm font-semibold text-white">{{ $option->option_text }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="pt-4 flex justify-end">
                            <x-button type="submit" variant="primary">
                                Kirim Suara (Vote)
                            </x-button>
                        </div>
                    </form>
                </x-card.body>
            </x-card>
        @endif

        <!-- Real-Time Poll Results Section -->
        <x-card>
            <x-card.header title="Hasil Perolehan Suara" subtitle="Kalkulasi persentase dan jumlah suara berdasarkan data real-time database." />
            <x-card.body>
                @if ($totalVotes === 0)
                    <x-empty-state
                        title="Belum Ada Suara"
                        description="Belum ada anggota yang memberikan suara pada polling ini."
                    />
                @else
                    <div class="space-y-5">
                        @foreach ($poll->options as $option)
                            @php
                                $count = $option->votesCount();
                                $percentage = $option->votePercentage($totalVotes);
                                $isUserChoice = $hasVoted && $userVote->option_id === $option->id;
                            @endphp
                            <div class="space-y-1.5 p-3 rounded-xl border {{ $isUserChoice ? 'bg-indigo-950/20 border-indigo-500/40' : 'bg-slate-950/40 border-slate-800/80' }}">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-white text-sm">{{ $option->option_text }}</span>
                                        @if ($isUserChoice)
                                            <span class="text-[10px] bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 px-2 py-0.5 rounded-full font-bold">
                                                Pilihan Anda
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-slate-400">{{ $count }} Suara</span>
                                        <span class="text-indigo-400 font-bold text-sm">{{ $percentage }}%</span>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full bg-slate-900 rounded-full h-3 overflow-hidden border border-slate-800">
                                    <div
                                        class="h-full rounded-full transition-all duration-500 {{ $isUserChoice ? 'bg-gradient-to-r from-indigo-500 to-purple-500' : 'bg-indigo-600' }}"
                                        style="width: {{ $percentage }}%"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card.body>
        </x-card>
    </div>

    <!-- Modal Delete -->
    @can('delete', $poll)
        <x-modal name="delete-poll-modal" title="Hapus Polling" size="md">
            <p class="text-sm text-slate-300">Apakah Anda yakin ingin menghapus polling <strong>"{{ $poll->title }}"</strong>?</p>
            <p class="text-xs text-rose-400 mt-2">Tindakan ini tidak dapat dibatalkan. Seluruh data perolehan suara terkait akan dihapus secara permanen.</p>
            <x-slot:footer>
                <x-button variant="outline" size="sm" onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'delete-poll-modal' }))">
                    Batal
                </x-button>
                <form action="{{ route('polls.destroy', $poll) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm">
                        Ya, Hapus Polling
                    </x-button>
                </form>
            </x-slot:footer>
        </x-modal>
    @endcan
</x-app-layout>
