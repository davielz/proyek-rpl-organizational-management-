<x-app-layout title="Edit Diskusi">
    <x-slot:header>
        <x-page-header
            title="Edit Diskusi"
            description="Ubah judul atau isi postingan diskusi Anda."
        >
            <x-slot:actions>
                <a href="{{ route('discussions.show', $post) }}">
                    <x-button variant="ghost">Batal</x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-3xl mx-auto">
        <x-card>
            <x-card.body>
                <form action="{{ route('discussions.update', $post) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input
                            label="Judul Diskusi"
                            name="title"
                            value="{{ old('title', $post->title) }}"
                            placeholder="Judul postingan diskusi..."
                            required
                            :error="$errors->first('title')"
                        />
                    </div>

                    <div>
                        <x-textarea
                            label="Isi Diskusi"
                            name="content"
                            rows="6"
                            value="{{ old('content', $post->content) }}"
                            placeholder="Tuliskan isi diskusi..."
                            required
                            :error="$errors->first('content')"
                        />
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
                        <a href="{{ route('discussions.show', $post) }}">
                            <x-button type="button" variant="secondary">Batal</x-button>
                        </a>
                        <x-button type="submit" variant="primary">Simpan Perubahan</x-button>
                    </div>
                </form>
            </x-card.body>
        </x-card>
    </div>
</x-app-layout>
