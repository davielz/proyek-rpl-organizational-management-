<x-guest-layout title="Masuk">
    <form action="{{ route('login') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                class="w-full bg-slate-900 border border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 transition-all outline-none"
                placeholder="nama@campus.ac.id">
            @error('email')
                <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Password</label>
            <input type="password" name="password" id="password" required
                class="w-full bg-slate-900 border border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 transition-all outline-none"
                placeholder="••••••••">
            @error('password')
                <p class="text-xs text-rose-400 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center space-x-2 text-xs text-slate-400 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                <span>Ingat Saya</span>
            </label>
        </div>

        <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 px-4 rounded-xl text-sm transition-all duration-200 shadow-lg shadow-indigo-600/30 active:scale-[0.99]">
            Masuk
        </button>
    </form>

    <!-- Demo Credentials Hint Box -->
    <div class="mt-6 p-4 rounded-xl bg-slate-900/60 border border-slate-700/60 text-xs space-y-2">
        <p class="font-semibold text-slate-300">🔑 Akun Demo Development:</p>
        <div class="grid grid-cols-3 gap-2 text-slate-400">
            <div>
                <span class="text-rose-400 font-medium block">Admin:</span>
                <code>admin@campus.ac.id</code>
            </div>
            <div>
                <span class="text-amber-400 font-medium block">Pengurus:</span>
                <code>pengurus@campus.ac.id</code>
            </div>
            <div>
                <span class="text-indigo-400 font-medium block">Anggota:</span>
                <code>anggota1@campus.ac.id</code>
            </div>
        </div>
        <p class="text-[10px] text-slate-500 italic mt-1">* Password semua akun: <code>password</code></p>
    </div>

    <div class="text-center pt-2">
        <p class="text-xs text-slate-400">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-indigo-400 font-semibold hover:underline">Daftar Anggota</a>
        </p>
    </div>
</x-guest-layout>
