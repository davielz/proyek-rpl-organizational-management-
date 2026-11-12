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
                                @if ($u->hasPhone())
                                    <a href="{{ $u->whatsapp_url }}" target="_blank" class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 hover:bg-emerald-100 transition-colors" title="Chat via WhatsApp">
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2m.01 1.67c4.54 0 8.24 3.7 8.24 8.24 0 2.2-.86 4.28-2.42 5.83a8.216 8.216 0 01-5.82 2.41c-1.48 0-2.93-.39-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 01-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24m4.52 11.64c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.14-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.77 2.7 4.29 3.78.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.11-.23-.17-.48-.29z"/></svg>
                                        WhatsApp
                                    </a>
                                @else
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Tanpa No. HP</span>
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
