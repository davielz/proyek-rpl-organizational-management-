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
                                @if ($u->hasPhone())
                                    <a href="{{ $u->whatsapp_url }}" target="_blank" class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 hover:bg-emerald-100 transition-colors" title="Chat via WhatsApp">
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2m.01 1.67c4.54 0 8.24 3.7 8.24 8.24 0 2.2-.86 4.28-2.42 5.83a8.216 8.216 0 01-5.82 2.41c-1.48 0-2.93-.39-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.19 8.19 0 01-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24m4.52 11.64c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.14-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.77 2.7 4.29 3.78.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.11-.23-.17-.48-.29z"/></svg>
                                        WhatsApp
                                    </a>
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
