<x-guest-layout title="Masuk">
    @if (session('whatsapp_notify'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-500/30 text-emerald-950 dark:text-emerald-200 space-y-2 shadow-sm">
            <div class="flex items-center space-x-2 text-emerald-600 dark:text-emerald-400 font-semibold text-xs">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2m.01 1.67c4.54 0 8.24 3.7 8.24 8.24 0 2.2-.86 4.28-2.42 5.83a8.216 8.216 0 01-5.82 2.41c-1.48 0-2.93-.39-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 01-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24m4.52 11.64c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.14-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.77 2.7 4.29 3.78.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.11-.23-.17-.48-.29z"/>
                </svg>
                <span>Notifikasi WhatsApp Aktif</span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                Pendaftaran akun Anda sedang ditinjau. Pesan konfirmasi akan dikirimkan otomatis ke nomor WhatsApp <b>{{ session('registered_phone') }}</b> setelah disetujui (ACC) oleh Admin.
            </p>
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 transition-all outline-none"
                placeholder="nama@campus.ac.id">
            @error('email')
                <p class="text-xs text-rose-500 dark:text-rose-400 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Password</label>
            <input type="password" name="password" id="password" required
                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 transition-all outline-none"
                placeholder="••••••••">
            @error('password')
                <p class="text-xs text-rose-500 dark:text-rose-400 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center space-x-2 text-xs text-slate-600 dark:text-slate-400 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                <span>Ingat Saya</span>
            </label>
        </div>

        <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-4 rounded-xl text-sm transition-all duration-200 shadow-lg shadow-indigo-600/30 active:scale-[0.99] cursor-pointer">
            Masuk
        </button>
    </form>

    <div class="text-center pt-4">
        <p class="text-xs text-slate-600 dark:text-slate-400">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Daftar Anggota</a>
        </p>
    </div>
</x-guest-layout>
