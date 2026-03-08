import './bootstrap';
import Alpine from 'alpinejs';
import './toast'; // Toast notification system

window.Alpine = Alpine;

// Theme store — dark / light mode, persisted in localStorage
Alpine.store('theme', {
    init() {
        const saved = localStorage.getItem('theme');
        const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        this.mode = saved || system;
        this.applyTheme();
    },
    mode: 'light',
    toggle() {
        this.mode = this.mode === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.mode);
        this.applyTheme();
    },
    applyTheme() {
        if (this.mode === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
});

// Sidebar store — expand / collapse / mobile overlay
Alpine.store('sidebar', {
    isExpanded: true,
    isMobileOpen: false,
    isHovered: false,
    toggleExpanded() {
        this.isExpanded = !this.isExpanded;
        this.isMobileOpen = false;
    },
    toggleMobileOpen() {
        this.isMobileOpen = !this.isMobileOpen;
    },
    setMobileOpen(val) {
        this.isMobileOpen = val;
    },
    setHovered(val) {
        if (window.innerWidth >= 1280 && !this.isExpanded) {
            this.isHovered = val;
        }
    },
});

// Instructor sidebar store — separate from learner sidebar
Alpine.store('instructorSidebar', {
    isExpanded: true,
    isMobileOpen: false,
    isHovered: false,
    toggleExpanded() {
        this.isExpanded = !this.isExpanded;
        this.isMobileOpen = false;
    },
    toggleMobileOpen() {
        this.isMobileOpen = !this.isMobileOpen;
    },
    setHovered(val) {
        if (window.innerWidth >= 1280 && !this.isExpanded) {
            this.isHovered = val;
        }
    },
});

// Global modal state store
Alpine.store('modals', {
    quizModal: false,
    
    openQuizModal() {
        this.quizModal = true;
    },
    
    closeQuizModal() {
        this.quizModal = false;
    }
});

Alpine.start();
