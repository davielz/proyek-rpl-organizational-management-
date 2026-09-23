<x-app-layout title="Proposal & LPJ">
    <x-slot:header>
        <x-page-header
            title="Dokumen Proposal & LPJ"
            description="Kelola penyusunan, pengajuan, review, serta persetujuan dokumen kegiatan organisasi."
        >
            <x-slot:actions>
                <a href="{{ route('documents.create') }}">
                    <x-button variant="primary" icon='<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'>
                        Buat Dokumen
                    </x-button>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot:header>

    <div class="space-y-6">
        <!-- Filter & Search Card -->
        <x-card class="p-4 sm:p-6">
            <form method="GET" action="{{ route('documents.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <x-input
                        name="search"
                        placeholder="Cari judul dokumen / kegiatan..."
                        value="{{ request('search') }}"
                    />
                </div>

                <div>
                    <x-select name="type">
                        <option value="">Semua Jenis Dokumen</option>
                        <option value="proposal" {{ request('type') === 'proposal' ? 'selected' : '' }}>Proposal Kegiatan</option>
                        <option value="lpj" {{ request('type') === 'lpj' ? 'selected' : '' }}>Laporan Pertanggungjawaban (LPJ)</option>
                    </x-select>
                </div>

                <div>
                    <x-select name="status">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Diajukan (Review)</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="revision_requested" {{ request('status') === 'revision_requested' ? 'selected' : '' }}>Perlu Revisi</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </x-select>
                </div>

                <div class="flex items-end space-x-2">
                    <x-button type="submit" variant="primary" class="w-full">
                        Filter
                    </x-button>
                    @if(request()->anyFilled(['search', 'type', 'status']))
                        <a href="{{ route('documents.index') }}" class="px-3 py-2 text-xs font-medium text-slate-400 hover:text-white border border-slate-700 rounded-xl hover:bg-slate-800 transition-colors flex items-center justify-center shrink-0">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <!-- Notification Alerts -->
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

        <!-- Document List Table -->
        @if ($documents->isEmpty())
            <x-empty-state
                title="Belum Ada Dokumen"
                description="Belum ada dokumen proposal atau LPJ yang dibuat sesuai dengan filter yang dipilih."
            >
                <x-slot:action>
                    <a href="{{ route('documents.create') }}">
                        <x-button variant="primary">Buat Dokumen Baru</x-button>
                    </a>
                </x-slot:action>
            </x-empty-state>
        @else
            <x-card class="overflow-hidden p-0">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <x-table.th>Jenis</x-table.th>
                            <x-table.th>Judul Dokumen & Kegiatan</x-table.th>
                            <x-table.th>Organisasi</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th>Pembuat</x-table.th>
                            <x-table.th>Tanggal Edit</x-table.th>
                            <x-table.th class="text-right">Aksi</x-table.th>
                        </tr>
                    </x-slot:header>

                    @foreach ($documents as $doc)
                        <x-table.tr>
                            <x-table.td class="font-bold">
                                <span class="px-2.5 py-1 text-xs rounded-lg font-semibold uppercase tracking-wider {{ $doc->type === 'proposal' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                    {{ strtoupper($doc->type) }}
                                </span>
                            </x-table.td>

                            <x-table.td>
                                <div class="flex flex-col">
                                    <a href="{{ route('documents.show', $doc) }}" class="font-bold text-white hover:text-indigo-400 transition-colors">
                                        {{ $doc->title }}
                                    </a>
                                    <span class="text-xs text-slate-400">
                                        Kegiatan: {{ $doc->event->title }}
                                    </span>
                                </div>
                            </x-table.td>

                            <x-table.td>
                                <span class="text-xs text-slate-300 font-medium">
                                    {{ $doc->event->organization->name }}
                                </span>
                            </x-table.td>

                            <x-table.td>
                                <x-badge :status="$doc->status" />
                            </x-table.td>

                            <x-table.td>
                                <span class="text-xs text-slate-300">
                                    {{ $doc->submitter->name }}
                                </span>
                            </x-table.td>

                            <x-table.td>
                                <span class="text-xs text-slate-400">
                                    {{ $doc->updated_at->format('d M Y, H:i') }}
                                </span>
                            </x-table.td>

                            <x-table.td class="text-right space-x-2">
                                <a href="{{ route('documents.show', $doc) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
                                    Detail
                                </a>

                                @can('update', $doc)
                                    <a href="{{ route('documents.edit', $doc) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-amber-400 hover:text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition-colors">
                                        Edit
                                    </a>
                                @endcan

                                <a href="{{ route('documents.pdf', $doc) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-medium text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 transition-colors">
                                    PDF
                                </a>
                            </x-table.td>
                        </x-table.tr>
                    @endforeach
                </x-table>

                @if ($documents->hasPages())
                    <div class="p-4 border-t border-slate-800">
                        {{ $documents->links() }}
                    </div>
                @endif
            </x-card>
        @endif
    </div>
</x-app-layout>
