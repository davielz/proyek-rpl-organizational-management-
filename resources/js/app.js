import Alpine from 'alpinejs';

// Global Theme Manager
window.themeManager = {
    get current() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    },
    set(theme) {
        document.documentElement.classList.add('theme-transition');
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
        setTimeout(() => {
            document.documentElement.classList.remove('theme-transition');
        }, 300);
    },
    toggle() {
        const isDark = document.documentElement.classList.contains('dark');
        this.set(isDark ? 'light' : 'dark');
    }
};

window.Alpine = Alpine;

Alpine.start();
