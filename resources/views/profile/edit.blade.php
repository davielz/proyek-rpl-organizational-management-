<x-app-layout title="Edit Profil">
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">Pengaturan Profil</h1>
                <p class="text-xs text-slate-400">Kelola informasi diri dan kata sandi akun Anda.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider
                @if($user->isAdmin()) bg-rose-500/10 text-rose-400 border border-rose-500/20
                @elseif($user->isPengurus()) bg-amber-500/10 text-amber-400 border border-amber-500/20
                @else bg-indigo-500/10 text-indigo-400 border border-indigo-500/20
                @endif">
                Role: {{ $user->role }}
            </span>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                    class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none">
                @error('name')
                    <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email</label>
                <input type="email" value="{{ $user->email }}" disabled
                    class="w-full bg-slate-950/50 border border-slate-800/80 rounded-xl px-4 py-2.5 text-sm text-slate-500 cursor-not-allowed">
                <p class="text-[11px] text-slate-500 mt-1">* Email digunakan sebagai username utama dan tidak dapat diubah secara langsung.</p>
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nomor HP / WhatsApp</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                    class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none">
                @error('phone')
                    <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-slate-800 pt-5 space-y-4">
                <h3 class="text-sm font-semibold text-white">Ubah Password <span class="text-xs font-normal text-slate-400">(Opsional)</span></h3>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Password Baru</label>
                    <input type="password" name="password" id="password"
                        class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none"
                        placeholder="Biarkan kosong jika tidak ingin mengubah password">
                    @error('password')
                        <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white transition-all outline-none"
                        placeholder="Ketik ulang password baru">
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-6 rounded-xl text-sm transition-all duration-200 shadow-lg shadow-indigo-600/30">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
