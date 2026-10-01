<x-app-layout title="Manajemen User">
    <x-slot:header>
        <x-page-header
            title="Manajemen User & Role"
            description="Kelola pengguna terdaftar dan otorisasi hak akses peranan (Admin, Pengurus, Anggota)."
        >
            <x-slot:actions>
                <x-badge status="admin" size="md">Khusus Admin</x-badge>
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

        <x-card class="p-0 overflow-hidden">
            <x-table>
                <x-slot:header>
                    <tr>
                        <x-table.th>Pengguna</x-table.th>
                        <x-table.th>Kontak / No. Telp</x-table.th>
                        <x-table.th>Role Saat Ini</x-table.th>
                        <x-table.th class="text-right">Aksi Ubah Role</x-table.th>
                    </tr>
                </x-slot:header>

                @foreach($users as $u)
                    <x-table.tr>
                        <x-table.td class="font-medium text-white">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-xs text-indigo-400 shrink-0">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-white truncate">{{ $u->name }}</div>
                                    <div class="text-xs text-slate-400 truncate">{{ $u->email }}</div>
                                </div>
                            </div>
                        </x-table.td>
                        <x-table.td class="text-xs text-slate-400">
                            {{ $u->phone ?? '-' }}
                        </x-table.td>
                        <x-table.td>
                            <x-badge :status="$u->role" size="sm" />
                        </x-table.td>
                        <x-table.td class="text-right">
                            @if($u->id === auth()->id())
                                <span class="text-xs text-slate-500 italic">Akun Anda Sendiri</span>
                            @else
                                <form action="{{ route('admin.users.update-role', $u) }}" method="POST" class="inline-flex items-center space-x-2">
                                    @csrf
                                    @method('PUT')
                                    <x-select name="role" class="w-32 py-1 text-xs">
                                        <option value="anggota" @selected($u->role === 'anggota')>Anggota</option>
                                        <option value="pengurus" @selected($u->role === 'pengurus')>Pengurus</option>
                                        <option value="admin" @selected($u->role === 'admin')>Admin</option>
                                    </x-select>
                                    <x-button type="submit" variant="secondary" size="xs">
                                        Simpan
                                    </x-button>
                                </form>
                            @endif
                        </x-table.td>
                    </x-table.tr>
                @endforeach
            </x-table>

            @if($users->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $users->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
