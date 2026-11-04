<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} - Campus Org System</title>
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
<body
    x-data="{ sidebarOpen: false }"
    class="bg-slate-50 dark:bg-[#080d19] text-slate-900 dark:text-slate-100 font-sans min-h-screen antialiased flex overflow-x-hidden transition-colors duration-200"
>
    <!-- Desktop Sidebar (Fixed Left) -->
    <aside class="hidden md:flex shrink-0 h-screen sticky top-0 z-40">
        <x-sidebar />
    </aside>

    <!-- Mobile Sidebar Drawer Overlay -->
    <div
        x-show="sidebarOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 md:hidden"
        @click="sidebarOpen = false"
        style="display: none;"
    ></div>

    <!-- Mobile Sidebar Content -->
    <aside
        x-show="sidebarOpen"
        x-transition:enter="transform transition ease-in-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-64 md:hidden shadow-2xl"
        style="display: none;"
    >
        <x-sidebar />
    </aside>

    <!-- Main Right Content Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
        <!-- Top Navigation -->
        <x-top-nav />

        <!-- Main Body Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
            @if (session('success'))
                <x-alert type="success" title="Berhasil">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if (session('error'))
                <x-alert type="error" title="Terjadi Kesalahan">
                    {{ session('error') }}
                </x-alert>
            @endif

            @if (session('warning'))
                <x-alert type="warning" title="Peringatan">
                    {{ session('warning') }}
                </x-alert>
            @endif

            @if (session('info'))
                <x-alert type="info" title="Informasi">
                    {{ session('info') }}
                </x-alert>
            @endif

            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 dark:border-slate-800 py-6 text-center text-xs text-slate-500 dark:text-slate-500 mt-auto">
            <div class="max-w-7xl mx-auto px-4">
                &copy; {{ date('Y') }} Campus Organization Management System. All rights reserved.
            </div>
        </footer>
    </div>

    <!-- AI Chatbot Widget Component -->
    <x-chatbot-widget />
</body>
</html>
