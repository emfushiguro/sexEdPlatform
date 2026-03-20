import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
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
Alpine.plugin(collapse);

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
    isLocked: false,
    toggleExpanded() {
        if (this.isLocked) {
            return;
        }
        this.isExpanded = !this.isExpanded;
        this.isMobileOpen = false;
    },
    toggleMobileOpen() {
        if (this.isLocked) {
            return;
        }
        this.isMobileOpen = !this.isMobileOpen;
    },
    setMobileOpen(val) {
        if (this.isLocked) {
            this.isMobileOpen = false;
            return;
        }
        this.isMobileOpen = val;
    },
    setHovered(val) {
        if (this.isLocked) {
            this.isHovered = false;
            return;
        }
        if (window.innerWidth >= 1280 && !this.isExpanded) {
            this.isHovered = val;
        }
    },
    lock() {
        this.isLocked = true;
        this.isMobileOpen = false;
        this.isHovered = false;
    },
    unlock() {
        this.isLocked = false;
    },
});

let adminSidebarLockCount = 0;
window.adminSidebarLock = {
    lock() {
        adminSidebarLockCount += 1;
        const sidebar = Alpine.store('sidebar');
        if (!sidebar) {
            return;
        }

        sidebar.lock();
        document.body.classList.add('admin-sidebar-locked');
    },
    unlock() {
        adminSidebarLockCount = Math.max(0, adminSidebarLockCount - 1);
        if (adminSidebarLockCount > 0) {
            return;
        }

        const sidebar = Alpine.store('sidebar');
        if (sidebar) {
            sidebar.unlock();
        }
        document.body.classList.remove('admin-sidebar-locked');
    },
};

// Global modal state store
Alpine.store('modals', {
    quizModal: false,
    quizModalDraft: null,
    moduleModal: false,
    moduleModalDraft: null,
    lessonSlideout: false,
    lessonSlideoutModuleId: null,
    lessonSlideoutDraft: null,
    editProfile: false,
    enrollmentReview: false,
    enrollmentReviewData: null,
    rejectModal: false,
    rejectEnrollmentId: null,
    rejectReason: '',
    rejectNote: '',
    rejectReasons: [
        { value: 'prerequisite_missing', label: 'Prerequisite module not completed' },
        { value: 'age_requirement_not_met', label: 'Age requirement not met' },
        { value: 'profile_incomplete', label: 'Learner profile is incomplete' },
        { value: 'capacity_limit', label: 'Module capacity reached' },
        { value: 'other', label: 'Other (specify in notes)' },
    ],

    openQuizModal(quiz = null) {
        this.quizModalDraft = quiz;
        this.quizModal = true;
    },
    closeQuizModal() {
        this.quizModal = false;
        this.quizModalDraft = null;
    },

    openModuleModal(module = null) {
        this.moduleModalDraft = module;
        this.moduleModal = true;
    },
    closeModuleModal() {
        this.moduleModal = false;
        this.moduleModalDraft = null;
    },

    openLessonSlideout(moduleId = null, lesson = null) {
        this.lessonSlideoutModuleId = moduleId;
        this.lessonSlideoutDraft = lesson;
        this.lessonSlideout = true;
    },
    closeLessonSlideout() {
        this.lessonSlideout = false;
        this.lessonSlideoutModuleId = null;
        this.lessonSlideoutDraft = null;
    },

    openEditProfile() { this.editProfile = true; },
    closeEditProfile() { this.editProfile = false; },

    openEnrollmentReview(enrollmentData) {
        this.enrollmentReviewData = enrollmentData;
        this.enrollmentReview = true;
    },
    closeEnrollmentReview() {
        this.enrollmentReview = false;
        setTimeout(() => {
            this.enrollmentReviewData = null;
        }, 300);
    },

    openRejectModal(enrollmentId) {
        this.rejectEnrollmentId = enrollmentId;
        this.rejectReason = '';
        this.rejectNote = '';
        this.rejectModal = true;
        this.closeEnrollmentReview();
    },
    closeRejectModal() {
        this.rejectModal = false;
        setTimeout(() => {
            this.rejectEnrollmentId = null;
            this.rejectReason = '';
            this.rejectNote = '';
        }, 300);
    },
});

Alpine.start();
