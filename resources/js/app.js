import './bootstrap';
import Alpine from 'alpinejs';
import './toast'; // Toast notification system

// Heavy libraries are loaded on-demand to keep the main bundle small.
let cachedPdfJsLib = null;
window.ensurePdfJsLib = async function ensurePdfJsLib() {
    if (cachedPdfJsLib) {
        return cachedPdfJsLib;
    }

    const [pdfjsModule, workerModule] = await Promise.all([
        import('pdfjs-dist'),
        import('pdfjs-dist/build/pdf.worker?url'),
    ]);

    const pdfjsLib = pdfjsModule.default ?? pdfjsModule;
    const workerUrl = workerModule.default ?? workerModule;
    pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;
    cachedPdfJsLib = pdfjsLib;
    window.pdfjsLib = pdfjsLib;

    return pdfjsLib;
};

// Auto-initialize Plyr players only on pages that actually have video elements.
document.addEventListener('DOMContentLoaded', async () => {
    const players = document.querySelectorAll('.plyr-video');
    if (players.length === 0) {
        return;
    }

    const [{ default: Plyr }] = await Promise.all([
        import('plyr'),
        import('plyr/dist/plyr.css'),
    ]);

    window.Plyr = Plyr;

    players.forEach((el) => {
        new Plyr(el, {
            speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
            captions: { active: el.querySelector('track') !== null, language: 'en', update: true },
            controls: [
                'play-large', 'play', 'progress', 'current-time',
                'mute', 'volume', 'captions', 'settings', 'fullscreen',
            ],
            settings: ['captions', 'speed'],
        });
    });
});

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

// Global modal state store
Alpine.store('modals', {
    quizModal: false,
    moduleModal: false,
    lessonSlideout: false,
    lessonSlideoutModuleId: null,
    editProfile: false,

    openQuizModal() { this.quizModal = true; },
    closeQuizModal() { this.quizModal = false; },

    openModuleModal() { this.moduleModal = true; },
    closeModuleModal() { this.moduleModal = false; },

    openLessonSlideout(moduleId = null) {
        this.lessonSlideoutModuleId = moduleId;
        this.lessonSlideout = true;
    },
    closeLessonSlideout() {
        this.lessonSlideout = false;
        this.lessonSlideoutModuleId = null;
    },

    openEditProfile() { this.editProfile = true; },
    closeEditProfile() { this.editProfile = false; },
});

Alpine.start();
