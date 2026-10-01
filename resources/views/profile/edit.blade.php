<x-app-layout title="Edit Profil">
    <x-slot:header>
        <x-page-header
            title="Pengaturan Profil"
            description="Kelola informasi diri, nomor kontak, dan kata sandi akun Anda."
        >
            <x-slot:actions>
                <x-badge :status="$user->role" size="md" />
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="max-w-2xl mx-auto space-y-6">
        @if (session('success'))
            <x-alert type="success" title="Berhasil">
                {{ session('success') }}
            </x-alert>
        @endif

        <x-card class="p-6">
            <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <x-input
                        name="name"
                        label="Nama Lengkap *"
                        value="{{ old('name', $user->name) }}"
                        required
                    />
                    @error('name')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input
                        name="email"
                        label="Email Terdaftar"
                        value="{{ $user->email }}"
                        disabled
                    />
                    <p class="text-[11px] text-slate-500 mt-1">* Email digunakan sebagai username akun utama dan tidak dapat diubah.</p>
                </div>

                <div>
                    <x-input
                        name="phone"
                        label="Nomor HP / WhatsApp"
                        value="{{ old('phone', $user->phone) }}"
                        placeholder="Contoh: 081234567890"
                    />
                    @error('phone')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-800 pt-5 space-y-4">
                    <h3 class="text-sm font-semibold text-white">Ubah Kata Sandi <span class="text-xs font-normal text-slate-400">(Opsional)</span></h3>

                    <div>
                        <x-input
                            type="password"
                            name="password"
                            label="Password Baru"
                            placeholder="Biarkan kosong jika tidak ingin mengubah password"
                        />
                        @error('password')
                            <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-input
                            type="password"
                            name="password_confirmation"
                            label="Konfirmasi Password Baru"
                            placeholder="Ketik ulang password baru"
                        />
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <x-button type="submit" variant="primary">
                        Simpan Perubahan
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
