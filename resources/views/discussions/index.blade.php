<x-app-layout title="Ruang Diskusi">
    <x-slot:header>
        <x-page-header
            title="Ruang Diskusi"
            description="Wadah pertukaran pikiran, ide, dan informasi antar anggota organisasi."
        >
            <x-slot:actions>
                <a href="{{ route('discussions.create') }}">
                    <x-button variant="primary" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                        Buat Diskusi
                    </x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6">
        @if (session('success'))
            <x-alert type="success">
                {{ session('success') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert type="error">
                {{ session('error') }}
            </x-alert>
        @endif

        <!-- Filter / Search Bar -->
        <x-card class="p-4">
            <form action="{{ route('discussions.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <x-input name="search" value="{{ request('search') }}" placeholder="Cari judul atau isi diskusi..." />
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary" size="md">Cari</x-button>
                    @if(request()->filled('search'))
                        <a href="{{ route('discussions.index') }}">
                            <x-button variant="ghost" size="md">Reset</x-button>
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- Discussions List -->
        @if ($posts->isEmpty())
            <x-card>
                <x-card.body>
                    <x-empty-state
                        title="Belum ada diskusi."
                        description="Belum ada topik diskusi yang dibuat. Mulai diskusi pertama Anda sekarang!"
                    >
                        <a href="{{ route('discussions.create') }}">
                            <x-button variant="primary" size="sm">Buat Diskusi Pertama</x-button>
                        </a>
                    </x-empty-state>
                </x-card.body>
            </x-card>
        @else
            <div class="space-y-4">
                @foreach ($posts as $post)
                    <x-card class="hover:border-slate-700 transition-all duration-200">
                        <x-card.body>
                            <div class="flex flex-col space-y-3">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-1 min-w-0">
                                        <a href="{{ route('discussions.show', $post) }}" class="text-lg font-bold text-white hover:text-indigo-400 transition-colors line-clamp-1">
                                            {{ $post->title }}
                                        </a>
                                        <div class="flex items-center space-x-2 text-xs text-slate-400">
                                            <span class="font-medium text-indigo-400">{{ $post->user->name ?? 'Pengguna' }}</span>
                                            <span>•</span>
                                            <span>{{ $post->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700 shrink-0">
                                        💬 {{ $post->comments_count }} komentar
                                    </span>
                                </div>

                                <p class="text-sm text-slate-300 line-clamp-2 leading-relaxed">
                                    {{ Str::limit($post->content, 180) }}
                                </p>

                                <div class="pt-2 flex items-center justify-between border-t border-slate-800/60 text-xs">
                                    <a href="{{ route('discussions.show', $post) }}" class="text-indigo-400 hover:text-indigo-300 font-medium inline-flex items-center space-x-1">
                                        <span>Baca & Komentar</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </x-card.body>
                    </x-card>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
