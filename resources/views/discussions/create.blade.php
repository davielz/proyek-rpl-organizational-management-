<x-app-layout title="Buat Diskusi Baru">
    <x-slot:header>
        <x-page-header
            title="Buat Diskusi Baru"
            description="Mulai topik pembicaraan baru untuk dibahas bersama anggota organisasi."
        >
            <x-slot:actions>
                <a href="{{ route('discussions.index') }}">
                    <x-button variant="ghost">Kembali</x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-3xl mx-auto">
        <x-card>
            <x-card.body>
                <form action="{{ route('discussions.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <x-input
                            label="Judul Diskusi"
                            name="title"
                            value="{{ old('title') }}"
                            placeholder="Contoh: Bagaimana pendapat kalian mengenai jadwal kegiatan minggu depan?"
                            required
                            :error="$errors->first('title')"
                        />
                    </div>

                    <div>
                        <x-textarea
                            label="Isi Diskusi"
                            name="content"
                            rows="6"
                            placeholder="Tuliskan detail topik pembicaraan atau pertanyaan yang ingin Anda sampaikan..."
                            required
                            :error="$errors->first('content')"
                        />
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
                        <a href="{{ route('discussions.index') }}">
                            <x-button type="button" variant="secondary">Batal</x-button>
                        </a>
                        <x-button type="submit" variant="primary">Kirim Diskusi</x-button>
                    </div>
                </form>
            </x-card.body>
        </x-card>
    </div>
</x-app-layout>
