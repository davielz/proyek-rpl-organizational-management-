<x-app-layout title="Kelola Kegiatan">
    <div class="space-y-6">
        <x-page-header
            title="Kelola Kegiatan Organisasi"
            subtitle="Manajemen dan publikasi kegiatan organisasi/UKM kampus."
            :breadcrumbs="['Dashboard' => route('dashboard'), 'Kelola Kegiatan' => null]"
        >
            <a href="{{ route('manage.events.create') }}">
                <x-button variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Kegiatan Baru
                </x-button>
            </a>
        </x-page-header>

        <!-- Filter Bar -->
        <x-card class="p-4">
            <form action="{{ route('manage.events.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <x-input name="search" value="{{ request('search') }}" placeholder="Cari judul kegiatan..." />
                </div>
                <div class="w-full sm:w-48">
                    <x-select name="status" placeholder="Semua Status" :selected="request('status')" :options="[
                        'draft' => 'Draft',
                        'pending' => 'Pending Approval',
                        'active' => 'Aktif',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]" />
                </div>
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary" size="md">Filter</x-button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('manage.events.index') }}">
                            <x-button variant="ghost" size="md">Reset</x-button>
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- Management Table -->
        <x-card>
            <x-card.header title="Daftar Kegiatan" :subtitle="'Total ' . $events->total() . ' kegiatan ditemukan'" />
            @if($events->isEmpty())
                <x-card.body>
                    <x-empty-state
                        title="Belum Ada Kegiatan"
                        description="Belum ada kegiatan yang dibuat atau sesuai dengan filter pencarian."
                    >
                        <a href="{{ route('manage.events.create') }}">
                            <x-button variant="primary" size="sm">Buat Kegiatan Baru</x-button>
                        </a>
                    </x-empty-state>
                </x-card.body>
            @else
                <x-table>
                    <thead>
                        <x-table.tr>
                            <x-table.th>Judul Kegiatan</x-table.th>
                            <x-table.th>Penyelenggara</x-table.th>
                            <x-table.th>Waktu Pelaksanaan</x-table.th>
                            <x-table.th>Peserta / Kuota</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </x-table.tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            @php
                                $registeredCount = $event->registrations->where('status', '!=', 'cancelled')->count();
                            @endphp
                            <x-table.tr>
                                <x-table.td class="font-semibold text-white">
                                    <a href="{{ route('events.show', $event->slug) }}" class="hover:text-indigo-400 transition-colors">
                                        {{ $event->title }}
                                    </a>
                                    <div class="text-xs text-slate-400 font-normal truncate max-w-xs">{{ $event->location }}</div>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider bg-indigo-500/10 px-2 py-0.5 rounded-full border border-indigo-500/20">
                                        {{ $event->organization->code ?? 'Org' }}
                                    </span>
                                </x-table.td>
                                <x-table.td class="text-xs text-slate-300">
                                    {{ $event->start_time->format('d M Y, H:i') }}
                                </x-table.td>
                                <x-table.td class="text-xs">
                                    <span class="font-semibold text-white">{{ $registeredCount }}</span> / {{ $event->quota > 0 ? $event->quota : '∞' }}
                                </x-table.td>
                                <x-table.td>
                                    <form action="{{ route('manage.events.update-status', $event->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" onchange="this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-200 px-2 py-1 focus:border-indigo-500 outline-none">
                                            <option value="draft" @selected($event->status === 'draft')>Draft</option>
                                            <option value="pending" @selected($event->status === 'pending')>Pending</option>
                                            <option value="active" @selected($event->status === 'active')>Aktif</option>
                                            <option value="completed" @selected($event->status === 'completed')>Selesai</option>
                                            <option value="cancelled" @selected($event->status === 'cancelled')>Batal</option>
                                        </select>
                                    </form>
                                </x-table.td>
                                <x-table.td align="right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('manage.events.edit', $event->id) }}">
                                            <x-button variant="ghost" size="sm" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </x-button>
                                        </a>

                                        <x-button
                                            variant="ghost"
                                            size="sm"
                                            class="text-rose-400 hover:text-rose-300"
                                            onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-event-{{ $event->id }}' }))"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </x-button>
                                    </div>
                                </x-table.td>
                            </x-table.tr>

                            <!-- Modal Hapus -->
                            <x-modal name="delete-event-{{ $event->id }}" title="Hapus Kegiatan" size="md">
                                <p>Apakah Anda yakin ingin menghapus kegiatan <strong>"{{ $event->title }}"</strong>?</p>
                                <p class="text-xs text-rose-400 mt-2">Tindakan ini tidak dapat dibatalkan. Seluruh data pendaftaran dan proposal terkait akan ikut terhapus.</p>
                                <x-slot:footer>
                                    <x-button variant="outline" size="sm" onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'delete-event-{{ $event->id }}' }))">
                                        Batal
                                    </x-button>
                                    <form action="{{ route('manage.events.destroy', $event->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm">
                                            Ya, Hapus Kegiatan
                                        </x-button>
                                    </form>
                                </x-slot:footer>
                            </x-modal>
                        @endforeach
                    </tbody>
                </x-table>
                <x-pagination :paginator="$events" />
            @endif
        </x-card>
    </div>
</x-app-layout>
