@props([
    'title' => null,
])

<header class="h-16 bg-white/80 dark:bg-[#0f172a]/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8 transition-colors duration-200">
    <div class="flex items-center space-x-3">
        <!-- Mobile Sidebar Toggle -->
        <button
            type="button"
            @click="sidebarOpen = true"
            class="md:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-colors focus:outline-none"
            aria-label="Buka Menu"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <span class="font-semibold text-sm text-slate-900 dark:text-white tracking-tight md:hidden">Campus Org System</span>
    </div>

    <!-- Top Right Items -->
    <div class="flex items-center space-x-2 sm:space-x-3">
        <!-- Theme Mode Toggle -->
        <x-theme-toggle />

        @auth
            <!-- Role Badge -->
            <x-badge :status="auth()->user()->role" size="md" />

            <!-- User Profile Quick Link -->
            <a href="{{ route('profile.edit') }}" class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                <div class="w-8 h-8 rounded-full bg-indigo-600/20 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 font-bold text-xs flex items-center justify-center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <span class="text-xs font-semibold hidden sm:inline-block">{{ auth()->user()->name }}</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 transition-colors">
                Masuk
            </a>
        @endauth
    </div>
</header>
