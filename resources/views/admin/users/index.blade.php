<x-app-layout title="Manajemen User">
    <div class="space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Manajemen User & Role</h1>
                <p class="text-xs text-slate-400">Kelola pengguna terdaftar dan otorisasi hak akses (*role*).</p>
            </div>
            <span class="bg-rose-500/10 text-rose-400 border border-rose-500/20 text-xs font-semibold px-3 py-1.5 rounded-xl">
                Khusus Admin
            </span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/80 text-xs uppercase text-slate-400 font-semibold border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-4">Pengguna</th>
                            <th class="px-6 py-4">Contact</th>
                            <th class="px-6 py-4">Role Saat Ini</th>
                            <th class="px-6 py-4 text-right">Aksi Ubah Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($users as $u)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 font-medium text-white">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-xs text-slate-300">
                                            {{ strtoupper(substr($u->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-white">{{ $u->name }}</div>
                                            <div class="text-xs text-slate-400">{{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400">
                                    {{ $u->phone ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider
                                        @if($u->isAdmin()) bg-rose-500/10 text-rose-400 border border-rose-500/20
                                        @elseif($u->isPengurus()) bg-amber-500/10 text-amber-400 border border-amber-500/20
                                        @else bg-indigo-500/10 text-indigo-400 border border-indigo-500/20
                                        @endif">
                                        {{ $u->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($u->id === auth()->id())
                                        <span class="text-xs text-slate-500 italic">Akun Anda</span>
                                    @else
                                        <form action="{{ route('admin.users.update-role', $u) }}" method="POST" class="inline-flex items-center space-x-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="role" class="bg-slate-950 border border-slate-800 rounded-lg text-xs text-white px-2.5 py-1.5 focus:border-indigo-500 outline-none">
                                                <option value="anggota" @selected($u->role === 'anggota')>Anggota</option>
                                                <option value="pengurus" @selected($u->role === 'pengurus')>Pengurus</option>
                                                <option value="admin" @selected($u->role === 'admin')>Admin</option>
                                            </select>
                                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors shadow-sm">
                                                Simpan
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-slate-800">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
