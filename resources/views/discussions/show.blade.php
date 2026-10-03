<x-app-layout title="{{ $post->title }}">
    <x-slot:header>
        <x-page-header
            title="Detail Diskusi"
            description="Informasi lengkap diskusi dan tanggapan dari anggota organisasi."
        >
            <x-slot:actions>
                <a href="{{ route('discussions.index') }}">
                    <x-button variant="ghost" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'>
                        Kembali ke Diskusi
                    </x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-4xl mx-auto space-y-6">
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

        <!-- Main Post Card -->
        <x-card>
            <x-card.body class="space-y-6">
                <!-- Post Header -->
                <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-800">
                    <div class="space-y-2 min-w-0">
                        <h1 class="text-2xl font-bold text-white tracking-tight">
                            {{ $post->title }}
                        </h1>
                        <div class="flex items-center space-x-2 text-xs text-slate-400">
                            <span class="font-medium text-indigo-400">oleh {{ $post->user->name ?? 'Pengguna' }}</span>
                            <span>•</span>
                            <span>{{ $post->created_at->diffForHumans() }}</span>
                            @if ($post->created_at != $post->updated_at)
                                <span>•</span>
                                <span class="italic text-slate-500">(Diedit {{ $post->updated_at->diffForHumans() }})</span>
                            @endif
                        </div>
                    </div>

                    <!-- Post Actions -->
                    <div class="flex items-center space-x-2 shrink-0">
                        @can('update', $post)
                            <a href="{{ route('discussions.edit', $post) }}">
                                <x-button variant="secondary" size="sm">Edit</x-button>
                            </a>
                        @endcan

                        @can('delete', $post)
                            <form action="{{ route('discussions.destroy', $post) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus postingan diskusi ini?');">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
                            </form>
                        @endcan
                    </div>
                </div>

                <!-- Post Content -->
                <div class="text-slate-200 leading-relaxed whitespace-pre-line text-sm sm:text-base">
                    {{ $post->content }}
                </div>
            </x-card.body>
        </x-card>

        <!-- Comments Section -->
        <x-card>
            <x-card.header>
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-white flex items-center space-x-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        <span>Komentar ({{ $post->comments_count ?? $comments->total() ?? count($comments) }})</span>
                    </h3>
                </div>
            </x-card.header>

            <x-card.body class="space-y-6">
                <!-- Comment List -->
                @if ($comments->isEmpty())
                    <div class="py-6">
                        <x-empty-state
                            title="Belum ada komentar."
                            description="Jadilah yang pertama memberikan pendapat pada diskusi ini."
                        />
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($comments as $comment)
                            <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2" id="comment-{{ $comment->id }}" x-data="{ editing: false }">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center space-x-2 text-xs">
                                        <div class="w-6 h-6 rounded-full bg-indigo-600/20 text-indigo-400 font-bold flex items-center justify-center text-[10px]">
                                            {{ strtoupper(substr($comment->user->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <span class="font-medium text-slate-200">{{ $comment->user->name ?? 'Pengguna' }}</span>
                                        <span class="text-slate-500">•</span>
                                        <span class="text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                                        @if ($comment->created_at != $comment->updated_at)
                                            <span class="italic text-slate-500 text-[11px]">(diedit)</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        @can('update', $comment)
                                            <button type="button" @click="editing = !editing" class="text-xs text-slate-400 hover:text-indigo-400 transition-colors">
                                                <span x-text="editing ? 'Batal' : 'Edit'">Edit</span>
                                            </button>
                                        @endcan

                                        @can('delete', $comment)
                                            <form action="{{ route('discussion-comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('Hapus komentar ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 transition-colors">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>

                                <!-- View Comment Content -->
                                <div x-show="!editing" class="text-sm text-slate-300 leading-relaxed whitespace-pre-line pl-8">
                                    {{ $comment->content }}
                                </div>

                                <!-- Edit Comment Form -->
                                @can('update', $comment)
                                    <div x-show="editing" x-cloak class="pt-2 pl-8">
                                        <form action="{{ route('discussion-comments.update', $comment) }}" method="POST" class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <x-textarea
                                                name="content"
                                                rows="2"
                                                value="{{ $comment->content }}"
                                                required
                                            />
                                            <div class="flex justify-end space-x-2">
                                                <x-button type="button" variant="ghost" size="sm" @click="editing = false">Batal</x-button>
                                                <x-button type="submit" variant="primary" size="sm">Simpan</x-button>
                                            </div>
                                        </form>
                                    </div>
                                @endcan
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $comments->links() }}
                    </div>
                @endif

                <!-- New Comment Form -->
                @auth
                    <div class="pt-6 border-t border-slate-800">
                        <form action="{{ route('discussions.comments.store', $post) }}" method="POST" class="space-y-4">
                            @csrf
                            <x-textarea
                                label="Tulis Komentar"
                                name="content"
                                rows="3"
                                placeholder="Tulis komentar atau tanggapan Anda di sini..."
                                required
                                :error="$errors->first('content')"
                            />

                            <div class="flex justify-end">
                                <x-button type="submit" variant="primary">
                                    Kirim Komentar
                                </x-button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="pt-6 border-t border-slate-800 text-center text-sm text-slate-400">
                        Silakan <a href="{{ route('login') }}" class="text-indigo-400 hover:underline font-medium">Masuk</a> untuk memberikan komentar.
                    </div>
                @endauth
            </x-card.body>
        </x-card>
    </div>
</x-app-layout>
