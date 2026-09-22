<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} - Campus Org System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen antialiased flex flex-col">
    <!-- Navbar -->
    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                        <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-indigo-600/30">
                            CO
                        </div>
                        <span class="font-bold text-lg text-white tracking-tight hidden sm:inline-block">Campus Org System</span>
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">
                        Dashboard
                    </a>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">
                            Kelola User
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">
                        Profil
                    </a>

                    <!-- User Role Badge -->
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider
                        @if(auth()->user()->isAdmin()) bg-rose-500/10 text-rose-400 border border-rose-500/20
                        @elseif(auth()->user()->isPengurus()) bg-amber-500/10 text-amber-400 border border-amber-500/20
                        @else bg-indigo-500/10 text-indigo-400 border border-indigo-500/20
                        @endif">
                        {{ auth()->user()->role }}
                    </span>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs text-slate-400 hover:text-rose-400 font-medium transition-colors border border-slate-700 hover:border-rose-500/30 px-3 py-1.5 rounded-lg">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-xl flex items-center justify-between shadow-lg">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm rounded-xl flex items-center justify-between shadow-lg">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4">
            &copy; {{ date('Y') }} Campus Organization Management System. All rights reserved.
        </div>
    </footer>
</body>
</html>
