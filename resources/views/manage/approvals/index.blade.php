<x-app-layout title="Persetujuan Akun">
    <x-slot:header>
        <x-page-header
            title="Persetujuan Pendaftaran Akun"
            description="Tinjau, setujui (ACC), atau tolak permohonan akun baru sebelum pengguna dapat masuk dan beraktivitas di sistem."
        >
            <x-slot:actions>
                <div class="flex items-center space-x-2">
                    <x-badge status="pending" size="md">
                        {{ $stats['pending'] }} Menunggu Approval
                    </x-badge>
                </div>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6">
        @if (session('success'))
            <x-alert type="success" title="Berhasil">
                {{ session('success') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert type="error" title="Gagal">
                {{ session('error') }}
            </x-alert>
        @endif

        @if (session('info'))
            <x-alert type="info" title="Informasi">
                {{ session('info') }}
            </x-alert>
        @endif

        <!-- Quick Summary Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('manage.approvals.index', ['status' => 'pending']) }}" class="block">
                <x-card class="p-4 transition hover:border-amber-400 dark:hover:border-amber-500/60 {{ $status === 'pending' ? 'ring-2 ring-amber-500/50 bg-amber-500/5 border-amber-500/30' : '' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Menunggu ACC</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['pending'] }}</span>
                        <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">Perlu Ditinjau</span>
                    </div>
                </x-card>
            </a>

            <a href="{{ route('manage.approvals.index', ['status' => 'active']) }}" class="block">
                <x-card class="p-4 transition hover:border-emerald-400 dark:hover:border-emerald-500/60 {{ $status === 'active' ? 'ring-2 ring-emerald-500/50 bg-emerald-500/5 border-emerald-500/30' : '' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Aktif / Disetujui</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['active'] }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Dapat Login</span>
                    </div>
                </x-card>
            </a>

            <a href="{{ route('manage.approvals.index', ['status' => 'rejected']) }}" class="block">
                <x-card class="p-4 transition hover:border-rose-400 dark:hover:border-rose-500/60 {{ $status === 'rejected' ? 'ring-2 ring-rose-500/50 bg-rose-500/5 border-rose-500/30' : '' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Ditolak</span>
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['rejected'] }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Akses Diblokir</span>
                    </div>
                </x-card>
            </a>

            <a href="{{ route('manage.approvals.index', ['status' => 'all']) }}" class="block">
                <x-card class="p-4 transition hover:border-indigo-400 dark:hover:border-indigo-500/60 {{ $status === 'all' ? 'ring-2 ring-indigo-500/50 bg-indigo-500/5 border-indigo-500/30' : '' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pengguna</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Seluruh Akun</span>
                    </div>
                </x-card>
            </a>
        </div>

        <!-- Filter Bar and Search -->
        <x-card class="p-4">
            <form action="{{ route('manage.approvals.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <!-- Status Tabs -->
                <div class="flex items-center space-x-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
                    <a href="{{ route('manage.approvals.index', ['status' => 'pending', 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ $status === 'pending' ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Menunggu ACC ({{ $stats['pending'] }})
                    </a>
                    <a href="{{ route('manage.approvals.index', ['status' => 'active', 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ $status === 'active' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Aktif ({{ $stats['active'] }})
                    </a>
                    <a href="{{ route('manage.approvals.index', ['status' => 'rejected', 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ $status === 'rejected' ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Ditolak ({{ $stats['rejected'] }})
                    </a>
                    <a href="{{ route('manage.approvals.index', ['status' => 'all', 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ $status === 'all' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Semua ({{ $stats['total'] }})
                    </a>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, email, telp..."
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 pl-9 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    @if ($search)
                        <a href="{{ route('manage.approvals.index', ['status' => $status]) }}" class="absolute right-3 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">×</a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- User Table -->
        @if ($users->isEmpty())
            <x-empty-state
                title="Tidak Ada Akun"
                description="{{ $status === 'pending' ? 'Hebat! Semua permohonan pendaftaran akun telah ditinjau dan disetujui.' : 'Tidak ditemukan pengguna yang sesuai dengan kriteria filter pencarian.' }}"
            />
        @else
            <x-card class="p-0 overflow-hidden">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>Calon Pengguna</x-table.th>
                            <x-table.th>Kontak / No. HP</x-table.th>
                            <x-table.th>Waktu Daftar</x-table.th>
                            <x-table.th>Role</x-table.th>
                            <x-table.th>Status Akun</x-table.th>
                            <x-table.th class="text-right">Keputusan / Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @foreach($users as $u)
                        <x-table.tr>
                            <x-table.td class="font-medium text-slate-900 dark:text-white">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-indigo-600 dark:text-indigo-400 shrink-0">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900 dark:text-white truncate">{{ $u->name }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </x-table.td>

                            <x-table.td class="text-xs text-slate-600 dark:text-slate-300">
                                <div>{{ $u->phone ?? '-' }}</div>
                                @if ($u->hasTelegram())
                                    <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-500/30">
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                                        Telegram Terhubung
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Telegram belum aktif</span>
                                @endif
                            </x-table.td>

                            <x-table.td class="text-xs text-slate-500 dark:text-slate-400">
                                <div title="{{ $u->created_at->format('d M Y H:i') }}">
                                    {{ $u->created_at->diffForHumans() }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    {{ $u->created_at->format('d/m/Y') }}
                                </div>
                            </x-table.td>

                            <x-table.td>
                                <x-badge :status="$u->role" size="sm" />
                            </x-table.td>

                            <x-table.td>
                                <x-badge :status="$u->status" size="sm" />
                            </x-table.td>

                            <x-table.td class="text-right">
                                <div class="inline-flex items-center space-x-2">
                                    @if ($u->id === auth()->id())
                                        <span class="text-xs text-slate-400 italic">Akun Anda Sendiri</span>
                                    @else
                                        @if ($u->status === 'pending')
                                            <!-- Approve Button -->
                                            <form action="{{ route('manage.approvals.approve', $u) }}" method="POST" class="inline">
                                                @csrf
                                                <x-button type="submit" variant="success" size="sm" icon='<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'>
                                                    Setujui (ACC)
                                                </x-button>
                                            </form>

                                            <!-- Reject Button -->
                                            <form action="{{ route('manage.approvals.reject', $u) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menolak pendaftaran akun {{ $u->name }}?');">
                                                @csrf
                                                <x-button type="submit" variant="danger" size="sm" icon='<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'>
                                                    Tolak
                                                </x-button>
                                            </form>
                                        @elseif ($u->status === 'rejected')
                                            <!-- Reactivate / Approve Button -->
                                            <form action="{{ route('manage.approvals.approve', $u) }}" method="POST" class="inline">
                                                @csrf
                                                <x-button type="submit" variant="success" size="sm" icon='<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'>
                                                    Setujui Kembali
                                                </x-button>
                                            </form>
                                        @elseif ($u->status === 'active')
                                            <!-- Reject / Disable Button -->
                                            @if (!$u->isAdmin() || auth()->user()->isAdmin())
                                                <form action="{{ route('manage.approvals.reject', $u) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menonaktifkan/menolak akses akun {{ $u->name }}?');">
                                                    @csrf
                                                    <x-button type="submit" variant="outline" size="sm" class="text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-900/50 hover:bg-rose-50 dark:hover:bg-rose-950/30">
                                                        Nonaktifkan
                                                    </x-button>
                                                </form>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @endforeach
                </x-table>

                @if ($users->hasPages())
                    <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                        {{ $users->links() }}
                    </div>
                @endif
            </x-card>
        @endif
    </div>
</x-app-layout>
