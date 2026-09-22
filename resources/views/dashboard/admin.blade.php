<x-app-layout title="Admin Dashboard">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Admin Dashboard</h1>
                <p class="text-xs text-slate-400">Ringkasan sistem dan manajemen pengguna kampus.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all shadow-lg shadow-rose-600/30">
                Kelola User & Role &rarr;
            </a>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total User</span>
                <p class="text-3xl font-extrabold text-white">{{ $stats['total_users'] }}</p>
                <p class="text-[11px] text-slate-500">Pengguna terdaftar di sistem</p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total UKM / Org</span>
                <p class="text-3xl font-extrabold text-white">{{ $stats['total_organizations'] }}</p>
                <p class="text-[11px] text-slate-500">Organisasi aktif</p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Kegiatan</span>
                <p class="text-3xl font-extrabold text-white">{{ $stats['total_events'] }}</p>
                <p class="text-[11px] text-slate-500">Kegiatan kampus terdaftar</p>
            </div>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Proposal Masuk</span>
                <p class="text-3xl font-extrabold text-amber-400">{{ $stats['pending_proposals'] }}</p>
                <p class="text-[11px] text-slate-500">Menunggu review admin</p>
            </div>
        </div>

        <!-- Welcome Info -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-3">
            <h2 class="text-base font-bold text-white">Hak Akses Admin</h2>
            <p class="text-xs text-slate-300 leading-relaxed">
                Sebagai Administrator, Anda memiliki otoritas penuh untuk mengelola pengguna, mengubah peranan (*role*), meninjau proposal kegiatan dari pengurus organisasi, dan memantau umpan balik kegiatan di seluruh kampus.
            </p>
        </div>
    </div>
</x-app-layout>
