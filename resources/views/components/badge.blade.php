@props([
    'status' => 'draft',
    'size' => 'md',
    'dot' => true,
    'label' => null,
])

@php
    $normalizedStatus = strtolower((string) $status);

    $configs = [
        // Draft
        'draft' => ['bg' => 'bg-slate-500/10 text-slate-300 border-slate-500/20', 'dot' => 'bg-slate-400', 'text' => 'Draft'],
        'draf' => ['bg' => 'bg-slate-500/10 text-slate-300 border-slate-500/20', 'dot' => 'bg-slate-400', 'text' => 'Draft'],

        // Pending / Diajukan
        'pending' => ['bg' => 'bg-amber-500/10 text-amber-400 border-amber-500/20', 'dot' => 'bg-amber-400', 'text' => 'Menunggu Approval'],
        'submitted' => ['bg' => 'bg-amber-500/10 text-amber-400 border-amber-500/20', 'dot' => 'bg-amber-400', 'text' => 'Diajukan'],
        'diajukan' => ['bg' => 'bg-amber-500/10 text-amber-400 border-amber-500/20', 'dot' => 'bg-amber-400', 'text' => 'Diajukan'],

        // Approved / Aktif / Disetujui
        'approved' => ['bg' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'dot' => 'bg-emerald-400', 'text' => 'Disetujui'],
        'disetujui' => ['bg' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'dot' => 'bg-emerald-400', 'text' => 'Disetujui'],
        'active' => ['bg' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'dot' => 'bg-emerald-400', 'text' => 'Aktif'],
        'aktif' => ['bg' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'dot' => 'bg-emerald-400', 'text' => 'Aktif'],

        // Revision / Revisi
        'revision_requested' => ['bg' => 'bg-orange-500/10 text-orange-400 border-orange-500/20', 'dot' => 'bg-orange-400', 'text' => 'Perlu Revisi'],
        'revisi' => ['bg' => 'bg-orange-500/10 text-orange-400 border-orange-500/20', 'dot' => 'bg-orange-400', 'text' => 'Perlu Revisi'],

        // Rejected / Ditolak / Dibatalkan
        'rejected' => ['bg' => 'bg-rose-500/10 text-rose-400 border-rose-500/20', 'dot' => 'bg-rose-400', 'text' => 'Ditolak'],
        'ditolak' => ['bg' => 'bg-rose-500/10 text-rose-400 border-rose-500/20', 'dot' => 'bg-rose-400', 'text' => 'Ditolak'],
        'cancelled' => ['bg' => 'bg-rose-500/10 text-rose-400 border-rose-500/20', 'dot' => 'bg-rose-400', 'text' => 'Dibatalkan'],
        'dibatalkan' => ['bg' => 'bg-rose-500/10 text-rose-400 border-rose-500/20', 'dot' => 'bg-rose-400', 'text' => 'Dibatalkan'],

        // Completed / Selesai / Hadir
        'completed' => ['bg' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20', 'dot' => 'bg-indigo-400', 'text' => 'Selesai'],
        'selesai' => ['bg' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20', 'dot' => 'bg-indigo-400', 'text' => 'Selesai'],
        'attended' => ['bg' => 'bg-teal-500/10 text-teal-400 border-teal-500/20', 'dot' => 'bg-teal-400', 'text' => 'Hadir'],
        'hadir' => ['bg' => 'bg-teal-500/10 text-teal-400 border-teal-500/20', 'dot' => 'bg-teal-400', 'text' => 'Hadir'],

        // Registered / Terdaftar
        'registered' => ['bg' => 'bg-sky-500/10 text-sky-400 border-sky-500/20', 'dot' => 'bg-sky-400', 'text' => 'Terdaftar'],
        'terdaftar' => ['bg' => 'bg-sky-500/10 text-sky-400 border-sky-500/20', 'dot' => 'bg-sky-400', 'text' => 'Terdaftar'],
        
        // Roles
        'admin' => ['bg' => 'bg-rose-500/10 text-rose-400 border-rose-500/20', 'dot' => 'bg-rose-400', 'text' => 'Admin'],
        'pengurus' => ['bg' => 'bg-amber-500/10 text-amber-400 border-amber-500/20', 'dot' => 'bg-amber-400', 'text' => 'Pengurus'],
        'anggota' => ['bg' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20', 'dot' => 'bg-indigo-400', 'text' => 'Anggota'],
    ];

    $config = $configs[$normalizedStatus] ?? [
        'bg' => 'bg-slate-500/10 text-slate-300 border-slate-500/20',
        'dot' => 'bg-slate-400',
        'text' => ucfirst($status),
    ];

    $displayText = $label ?? ($slot->isNotEmpty() ? $slot : $config['text']);

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-1 text-xs',
    ][$size] ?? 'px-2.5 py-1 text-xs';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-medium border rounded-full uppercase tracking-wider select-none {$config['bg']} {$sizeClasses}"]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }}"></span>
    @endif
    <span>{{ $displayText }}</span>
</span>
