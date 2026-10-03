@props([
    'active' => 'dashboard',
])

@php
    $user = auth()->user();
@endphp

<div class="flex flex-col h-full bg-slate-900 border-r border-slate-800 w-64 text-slate-300 select-none">
    <!-- Brand Header -->
    <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-600/30 shrink-0">
                CO
            </div>
            <div class="flex flex-col">
                <span class="font-bold text-sm text-white tracking-tight leading-tight">Campus Org</span>
                <span class="text-[10px] text-slate-400">Management System</span>
            </div>
        </a>
    </div>

    <!-- Navigation List -->
    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6 custom-scrollbar">
        <!-- Main Menu -->
        <div class="space-y-1">
            <span class="px-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-2">Navigasi Utama</span>

            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 font-semibold' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('events.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('events.index', 'events.show') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 font-semibold' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Katalog Kegiatan</span>
            </a>

            @if ($user)
                <a href="{{ route('polls.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('polls.*') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 font-semibold' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Polling & Voting</span>
                </a>

                <a href="{{ route('profile.edit') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('profile.*') ? 'bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 font-semibold' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Pengaturan Profil</span>
                </a>
            @endif
        </div>

        @if ($user)
            <!-- Role Specific Menu -->
            @if ($user->isAdmin())
                <div class="space-y-1">
                    <span class="px-3 text-[10px] font-bold text-rose-400 uppercase tracking-wider block mb-2">Area Administrator</span>

                    <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Kelola User & Role</span>
                    </a>
                </div>
            @endif

            @if ($user->isPengurus() || $user->isAdmin())
                <div class="space-y-1">
                    <span class="px-3 text-[10px] font-bold text-amber-400 uppercase tracking-wider block mb-2">Manajemen Kegiatan</span>

                    <a href="{{ route('manage.events.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('manage.events.*') ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Kelola Kegiatan</span>
                    </a>

                    <a href="{{ route('documents.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('documents.*') ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Proposal & LPJ</span>
                    </a>

                    <a href="{{ route('feedback.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('feedback.*') ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold' : 'hover:bg-slate-800/60 text-slate-400 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        <span>Feedback & Sentimen</span>
                    </a>
                </div>
            @endif
        @endif
    </div>

    <!-- User Profile Footer -->
    <div class="p-4 border-t border-slate-800 shrink-0">
        @if ($user)
            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 font-bold text-xs flex items-center justify-center shrink-0">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-white truncate leading-tight">{{ $user->name }}</p>
                        <x-badge :status="$user->role" size="sm" class="mt-0.5" />
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-colors" title="Keluar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="flex items-center justify-center space-x-2 w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm rounded-xl transition-colors">
                <span>Masuk</span>
            </a>
        @endif
    </div>
</div>
