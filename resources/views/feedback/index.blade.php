<x-app-layout title="Feedback & Analisis Sentimen AI">
    <x-slot:header>
        <x-page-header
            title="Feedback & Analisis Sentimen AI"
            description="Pantau ulasan peserta, distribusi rating, serta analisis sentimen otomatis dari kegiatan organisasi."
        />
    </x-slot:header>

    <div class="space-y-6">
        <!-- Analytics Stat Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Total Feedbacks -->
            <x-card class="p-4 bg-slate-900/90 border-slate-800">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Ulasan</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-white">{{ $stats['total'] }}</span>
                    <span class="text-xs text-slate-400">Feedback</span>
                </div>
            </x-card>

            <!-- Average Rating -->
            <x-card class="p-4 bg-slate-900/90 border-amber-500/20">
                <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider block">Rata-Rata Rating</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <div class="flex items-center space-x-1">
                        <span class="text-2xl font-bold text-amber-400">{{ $stats['avg_rating'] }}</span>
                        <span class="text-xs text-amber-400/80">/ 5.0</span>
                    </div>
                    <span class="text-amber-400 text-sm">★</span>
                </div>
            </x-card>

            <!-- Positive Sentiment -->
            <x-card class="p-4 bg-emerald-950/30 border-emerald-500/30">
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider block">Sentimen Positif</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-emerald-400">{{ $stats['positive_count'] }}</span>
                    <span class="text-xs font-bold text-emerald-400">{{ $stats['positive_pct'] }}%</span>
                </div>
            </x-card>

            <!-- Neutral Sentiment -->
            <x-card class="p-4 bg-slate-900/90 border-slate-700">
                <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider block">Sentimen Netral</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-slate-200">{{ $stats['neutral_count'] }}</span>
                    <span class="text-xs font-semibold text-slate-400">{{ $stats['neutral_pct'] }}%</span>
                </div>
            </x-card>

            <!-- Negative Sentiment -->
            <x-card class="p-4 bg-rose-950/30 border-rose-500/30">
                <span class="text-xs font-semibold text-rose-400 uppercase tracking-wider block">Sentimen Negatif</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-rose-400">{{ $stats['negative_count'] }}</span>
                    <span class="text-xs font-bold text-rose-400">{{ $stats['negative_pct'] }}%</span>
                </div>
            </x-card>
        </div>

        <!-- Filter & Search Card -->
        <x-card class="p-4 sm:p-6">
            <form method="GET" action="{{ route('feedback.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <x-input
                        name="search"
                        placeholder="Cari kata kunci komentar / nama peserta..."
                        value="{{ request('search') }}"
                    />
                </div>

                <div>
                    <x-select name="event_id">
                        <option value="">Semua Kegiatan</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                {{ $event->title }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <x-select name="sentiment">
                        <option value="">Semua Sentimen</option>
                        <option value="positive" {{ request('sentiment') === 'positive' ? 'selected' : '' }}>Positif</option>
                        <option value="neutral" {{ request('sentiment') === 'neutral' ? 'selected' : '' }}>Netral</option>
                        <option value="negative" {{ request('sentiment') === 'negative' ? 'selected' : '' }}>Negatif</option>
                    </x-select>
                </div>

                <div class="flex items-end space-x-2">
                    <x-button type="submit" variant="primary" class="w-full">
                        Filter
                    </x-button>
                    @if(request()->anyFilled(['search', 'event_id', 'sentiment', 'rating']))
                        <a href="{{ route('feedback.index') }}" class="px-3 py-2 text-xs font-medium text-slate-400 hover:text-white border border-slate-700 rounded-xl hover:bg-slate-800 transition-colors flex items-center justify-center shrink-0">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- Feedback Cards Grid -->
        @if ($feedbacks->isEmpty())
            <x-empty-state
                title="Belum Ada Feedback"
                description="Belum ada ulasan peserta yang masuk sesuai dengan filter yang Anda tentukan."
            />
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($feedbacks as $fb)
                    <x-card class="p-5 space-y-4 border-slate-800/80 hover:border-slate-700 transition-all">
                        <!-- Top Info Bar -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-9 h-9 rounded-full bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($fb->user->name ?? 'U', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-sm text-white truncate">{{ $fb->user->name ?? 'Peserta' }}</h4>
                                    <p class="text-xs text-slate-400 truncate">
                                        Kegiatan: <a href="{{ route('events.show', $fb->event->slug) }}" class="text-indigo-400 hover:underline">{{ $fb->event->title }}</a>
                                    </p>
                                </div>
                            </div>

                            <!-- Rating Stars & Timestamp -->
                            <div class="text-right shrink-0">
                                <div class="flex text-amber-400 text-sm justify-end">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span>{{ $i <= $fb->rating ? '★' : '☆' }}</span>
                                    @endfor
                                </div>
                                <span class="text-[11px] text-slate-400 mt-0.5 block">{{ $fb->created_at->format('d M Y, H:i') }}</span>
                            </div>
                        </div>

                        <!-- Comment Content -->
                        <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 text-xs text-slate-200 leading-relaxed whitespace-pre-line">
                            {{ $fb->comment }}
                        </div>

                        <!-- AI Sentiment Analysis Result Footer -->
                        <div class="pt-2 border-t border-slate-800/60 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                            <!-- Sentiment Badge -->
                            <div class="flex items-center space-x-2">
                                <span class="text-slate-400 font-semibold">Sentimen AI:</span>
                                @if($fb->sentiment === 'positive')
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        ✨ Positif ({{ round($fb->sentiment_score * 100) }}%)
                                    </span>
                                @elseif($fb->sentiment === 'negative')
                                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        ⚠️ Negatif ({{ round($fb->sentiment_score * 100) }}%)
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                        💬 Netral ({{ round($fb->sentiment_score * 100) }}%)
                                    </span>
                                @endif
                            </div>

                            <!-- Topics Tags -->
                            @if(!empty($fb->topics))
                                <div class="flex flex-wrap items-center gap-1">
                                    @foreach($fb->topics as $topic)
                                        <span class="px-2 py-0.5 rounded-md bg-indigo-950/50 border border-indigo-500/20 text-indigo-300 font-medium">#{{ $topic }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </x-card>
                @endforeach
            </div>

            @if ($feedbacks->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $feedbacks->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
