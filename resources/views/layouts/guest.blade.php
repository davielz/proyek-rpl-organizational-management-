<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Campus Organization Management System' }}</title>
    <script>
        (function() {
            try {
                const theme = localStorage.getItem('theme');
                if (theme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch(e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col justify-center items-center p-4 antialiased transition-colors duration-200 relative">
    <!-- Top Floating Theme Toggle -->
    <div class="absolute top-4 right-4 z-20">
        <x-theme-toggle :show-label="true" class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 px-3 py-1.5 shadow-sm" />
    </div>

    <div class="w-full max-w-md bg-white/90 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-6 md:p-8 space-y-6">
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 mb-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Campus Org System</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Sistem Manajemen Organisasi Kampus & UKM</p>
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-3 bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-sm rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </div>
</body>
</html>
