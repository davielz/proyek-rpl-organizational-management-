<x-app-layout title="Manajemen User">
    <x-slot:header>
        <x-page-header
            title="Manajemen User & Role"
            description="Kelola pengguna terdaftar, verifikasi approval akun, dan otorisasi peranan (Admin, Pengurus, Anggota)."
        >
            <x-slot:actions>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('manage.approvals.index') }}">
                        <x-button variant="warning" size="sm" icon='<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'>
                            <span>Persetujuan Akun</span>
                            @if (($stats['pending'] ?? 0) > 0)
                                <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-bold bg-amber-900/30 text-amber-100 rounded-full">
                                    {{ $stats['pending'] }}
                                </span>
                            @endif
                        </x-button>
                    </a>
                    <x-badge status="admin" size="md">Khusus Admin</x-badge>
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

        <!-- Filter & Search Card -->
        <x-card class="p-4">
            <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <!-- Status Tabs -->
                <div class="flex items-center space-x-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
                    <a href="{{ route('admin.users.index', ['search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ empty($status) ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Semua ({{ $stats['total'] ?? 0 }})
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'pending', 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ $status === 'pending' ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Menunggu ACC ({{ $stats['pending'] ?? 0 }})
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'active', 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ $status === 'active' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Aktif ({{ $stats['active'] ?? 0 }})
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'rejected', 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ $status === 'rejected' ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                        Ditolak ({{ $stats['rejected'] ?? 0 }})
                    </a>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    @if ($status)
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, email, telp..."
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 pl-9 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    @if ($search)
                        <a href="{{ route('admin.users.index', ['status' => $status]) }}" class="absolute right-3 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">×</a>
                    @endif
                </div>
            </form>
        </x-card>

        @if ($users->isEmpty())
            <x-empty-state
                title="Tidak Ada Pengguna"
                description="Tidak ada data pengguna yang sesuai dengan filter pencarian Anda."
            />
        @else
            <x-card class="p-0 overflow-hidden">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>Pengguna</x-table.th>
                            <x-table.th>Kontak / No. Telp</x-table.th>
                            <x-table.th>Role Saat Ini</x-table.th>
                            <x-table.th>Status Akun</x-table.th>
                            <x-table.th class="text-right">Aksi Kelola & Approval</x-table.th>
                        </tr>
                    </x-slot:header>

                    @foreach($users as $u)
                        <x-table.tr>
                            <x-table.td class="font-medium text-slate-900 dark:text-white">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-indigo-600 dark:text-indigo-400 shrink-0">
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
                                @endif
                            </x-table.td>
                            <x-table.td>
                                <x-badge :status="$u->role" size="sm" />
                            </x-table.td>
                            <x-table.td>
                                <x-badge :status="$u->status" size="sm" />
                            </x-table.td>
                            <x-table.td class="text-right">
                                @if($u->id === auth()->id())
                                    <span class="text-xs text-slate-400 italic">Akun Anda Sendiri</span>
                                @else
                                    <div class="inline-flex items-center space-x-3 justify-end">
                                        <!-- Quick ACC / Reject Buttons if Pending -->
                                        @if ($u->status === 'pending')
                                            <form action="{{ route('admin.users.approve', $u) }}" method="POST" class="inline">
                                                @csrf
                                                <x-button type="submit" variant="success" size="xs" icon='<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'>
                                                    Setujui (ACC)
                                                </x-button>
                                            </form>
                                            <form action="{{ route('admin.users.reject', $u) }}" method="POST" class="inline" onsubmit="return confirm('Tolak pendaftaran akun {{ $u->name }}?');">
                                                @csrf
                                                <x-button type="submit" variant="danger" size="xs">
                                                    Tolak
                                                </x-button>
                                            </form>
                                        @elseif ($u->status === 'rejected')
                                            <form action="{{ route('admin.users.approve', $u) }}" method="POST" class="inline">
                                                @csrf
                                                <x-button type="submit" variant="success" size="xs">
                                                    Setujui Kembali
                                                </x-button>
                                            </form>
                                        @endif

                                        <!-- Role Update Form -->
                                        <form action="{{ route('admin.users.update-role', $u) }}" method="POST" class="inline-flex items-center space-x-1.5 border-l border-slate-200 dark:border-slate-700 pl-3">
                                            @csrf
                                            @method('PUT')
                                            <x-select name="role" class="w-28 py-1 text-xs">
                                                <option value="anggota" @selected($u->role === 'anggota')>Anggota</option>
                                                <option value="pengurus" @selected($u->role === 'pengurus')>Pengurus</option>
                                                <option value="admin" @selected($u->role === 'admin')>Admin</option>
                                            </x-select>
                                            <x-button type="submit" variant="secondary" size="xs">
                                                Ubah
                                            </x-button>
                                        </form>
                                    </div>
                                @endif
                            </x-table.td>
                        </x-table.tr>
                    @endforeach
                </x-table>

                @if($users->hasPages())
                    <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                        {{ $users->links() }}
                    </div>
                @endif
            </x-card>
        @endif
    </div>
</x-app-layout>
