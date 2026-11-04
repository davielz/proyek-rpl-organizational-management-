<x-guest-layout title="Masuk">
    @if (session('telegram_connect_user_id'))
        <div class="p-4 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-500/30 text-sky-950 dark:text-sky-200 space-y-3 shadow-sm">
            <div class="flex items-center space-x-2 text-sky-600 dark:text-sky-400 font-semibold text-xs">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
                <span>Terima Notifikasi ACC via Bot Telegram</span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                Agar bot dapat mengirimkan pesan pemberitahuan langsung ke nomor WhatsApp/HP Anda saat akun disetujui (ACC) oleh Admin atau Pengurus, silakan buka Bot Telegram kami dan tekan <b>Start</b>:
            </p>
            <a href="https://t.me/ManagamentOrg_bot?start=reg_{{ session('telegram_connect_user_id') }}" target="_blank"
                class="flex items-center justify-center space-x-2 w-full py-2.5 px-3 bg-sky-600 hover:bg-sky-500 text-white font-medium text-xs rounded-xl transition-all shadow-sm shadow-sky-600/30">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                <span>Buka Bot & Mulai (@ManagamentOrg_bot)</span>
            </a>
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
