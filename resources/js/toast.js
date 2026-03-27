/**
 * Toast Notification System
 * Enhanced wrapper for Toastify.js with custom styling, icons, and animations
 */

import Toastify from 'toastify-js';

// Track active toasts for stacking
let activeToasts = 0;
const maxToasts = 5;
const toastOffset = 70; // pixels between stacked toasts

// Toast configuration defaults (NO duration here - set per toast type)
const defaultConfig = {
    gravity: "top",
    position: "right",
    stopOnFocus: true,
    close: true,
    escapeMarkup: false, // Allow HTML for icons
    className: 'custom-toast',
    offset: { y: 0 }, // Dynamic offset for stacking
    onClick: function() {}, // Keyboard support
    callback: function() {
        activeToasts--;
        if (activeToasts < 0) activeToasts = 0;
    },
};

// Icon templates with better styling
const icons = {
    success: '<svg class="toast-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    error: '<svg class="toast-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
    warning: '<svg class="toast-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    info: '<svg class="toast-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    primary: '<svg class="toast-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>',
};

// Calculate offset for stacked toasts
function getToastOffset() {
    return { y: activeToasts * toastOffset };
}

// Keyboard support - dismiss all toasts on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
        const toasts = document.querySelectorAll('.toastify');
        toasts.forEach(toast => {
            const closeBtn = toast.querySelector('.toast-close');
            if (closeBtn) closeBtn.click();
        });
    }
});

// Success Toast
export function showSuccess(message, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    
    const duration = 4000; // 4 seconds - enough time to read success message
    const { duration: _, ...userOptions } = options; // Ignore user duration
    
    const config = {
        ...defaultConfig,
        ...userOptions,  // User options WITHOUT duration
        duration: duration,  // Our controlled duration
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${icons.success}</span><span class="toast-message">${message}</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-success',
        ariaLive: 'polite',
    };
       
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Error Toast
export function showError(message, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    
    const duration = 4000; 
    const { duration: _, ...userOptions } = options; 
    
    const config = {
        ...defaultConfig,
        ...userOptions,  // User options WITHOUT duration
        duration: duration,  // Our controlled duration
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${icons.error}</span><span class="toast-message">${message}</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-error',
        ariaLive: 'assertive',
    };
    
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Warning Toast
export function showWarning(message, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    
    const duration = 4000; // 5 seconds - warnings need attention
    const { duration: _, ...userOptions } = options; // Ignore user duration
    
    const config = {
        ...defaultConfig,
        ...userOptions,  // User options WITHOUT duration
        duration: duration,  // Our controlled duration
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${icons.warning}</span><span class="toast-message">${message}</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-warning',
        ariaLive: 'polite',
    };
    
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Info Toast
export function showInfo(message, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    
    const duration = 4000; // 4 seconds - standard info message duration
    const { duration: _, ...userOptions } = options; // Ignore user duration
    
    const config = {
        ...defaultConfig,
        ...userOptions,  // User options WITHOUT duration
        duration: duration,  // Our controlled duration
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${icons.info}</span><span class="toast-message">${message}</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-info',
        ariaLive: 'polite',
    };
    
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Primary/Purple Toast (for gamification)
export function showPrimary(message, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    
    const duration = 4000; // 4 seconds - standard duration
    const { duration: _, ...userOptions } = options; // Ignore user duration
    
    const config = {
        ...defaultConfig,
        ...userOptions,  // User options WITHOUT duration
        duration: duration,  // Our controlled duration
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${icons.primary}</span><span class="toast-message">${message}</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-primary',
        ariaLive: 'polite',
    };
    
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Achievement Toast (special styling for gamification)
export function showAchievement(message, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    
    const achievementIcon = '<svg class="toast-icon toast-icon-large" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>';
    const duration = 7000; // 7 seconds - achievements deserve celebration time
    const { duration: _, ...userOptions } = options; // Ignore user duration
    
    const config = {
        ...defaultConfig,
        ...userOptions,  // User options WITHOUT duration
        duration: duration,  // Our controlled duration
        text: `<div class="toast-content toast-content-large"><span class="toast-icon-wrapper toast-icon-celebration">${achievementIcon}</span><span class="toast-message">${message}</span></div>`,
        gravity: "top",
        position: "center",
        offset: getToastOffset(),
        className: 'custom-toast toast-achievement',
        ariaLive: 'polite',
    };
    
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Level Up Toast
export function showLevelUp(level, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    
    const levelIcon = '<svg class="toast-icon toast-icon-large" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>';
    const duration = 8000; // 8 seconds - big milestone celebration
    const { duration: _, ...userOptions } = options; // Ignore user duration
    
    const config = {
        ...defaultConfig,
        ...userOptions,  // User options WITHOUT duration
        duration: duration,  // Our controlled duration
        text: `<div class="toast-content toast-content-large"><span class="toast-icon-wrapper toast-icon-celebration">${levelIcon}</span><span class="toast-message">Level Up! You're now Level ${level}!</span></div>`,
        gravity: "top",
        position: "center",
        offset: getToastOffset(),
        className: 'custom-toast toast-level-up',
        ariaLive: 'polite',
    };
    
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// XP Gained Toast
export function showXPGained(xp, options = {}) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    
    const xpIcon = '<svg class="toast-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7L12 12L22 7L12 2Z"></path><path d="M2 17L12 22L22 17"></path><path d="M2 12L12 17L22 12"></path></svg>';
    const duration = 3000; // 3 seconds - short but readable for frequent XP gains
    const { duration: _, ...userOptions } = options; // Ignore user duration
    
    const config = {
        ...defaultConfig,
        ...userOptions,  // User options WITHOUT duration
        duration: duration,  // Our controlled duration
        text: `<div class="toast-content toast-content-compact"><span class="toast-icon-wrapper">${xpIcon}</span><span class="toast-message">+${xp} XP</span></div>`,
        gravity: "top",
        position: "right",
        offset: getToastOffset(),
        className: 'custom-toast toast-xp',
        ariaLive: 'polite',
    };
    
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Custom Toast (full control)
export function showToast(message, type = 'info', options = {}) {
    const toastFunctions = {
        success: showSuccess,
        error: showError,
        warning: showWarning,
        info: showInfo,
        primary: showPrimary,
        achievement: showAchievement,
    };

    const toastFunction = toastFunctions[type] || showInfo;
    toastFunction(message, options);
}

// Add progress bar to toast for visual feedback
function addProgressBar(toastElement, duration) {
    if (!toastElement || !duration) {
        console.warn('addProgressBar missing element or duration:', { toastElement: !!toastElement, duration });
        return;
    }
    
    console.log('Adding progress bar with duration:', duration, 'ms');
    
    const progressBar = document.createElement('div');
    progressBar.className = 'toast-progress';
    toastElement.appendChild(progressBar);
    
    // Force reflow to ensure initial state is registered
    progressBar.offsetHeight;
    
    // Animate progress bar from 100% to 0%
    requestAnimationFrame(() => {
        progressBar.style.width = '0%';
        progressBar.style.transition = `width ${duration}ms linear`;
    });
}

// Shield Lost Toast
function showShieldLost(shieldsLeft) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const shieldIcon = '<svg class="toast-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>';
    const duration = 4000;
    const config = {
        ...defaultConfig, duration,
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${shieldIcon}</span><span class="toast-message">Shield lost! ${shieldsLeft}/3 remaining today.</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-shield-lost',
        ariaLive: 'assertive',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Shield Refilled Toast
function showShieldRefilled(message) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const shieldIcon = '<svg class="toast-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
    const duration = 4000;
    const config = {
        ...defaultConfig, duration,
        text: `<div class="toast-content"><span class="toast-icon-wrapper">${shieldIcon}</span><span class="toast-message">${message}</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-shield-refill',
        ariaLive: 'polite',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Points Earned Toast
function showPointsEarned(points) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const starIcon = '<svg class="toast-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    const duration = 3000;
    const config = {
        ...defaultConfig, duration,
        text: `<div class="toast-content toast-content-compact"><span class="toast-icon-wrapper">${starIcon}</span><span class="toast-message">+${points} points earned!</span></div>`,
        offset: getToastOffset(),
        className: 'custom-toast toast-points',
        ariaLive: 'polite',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Streak Milestone Toast
function showStreakMilestone(bonus) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const flameIcon = '<svg class="toast-icon toast-icon-large" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z" clip-rule="evenodd"/></svg>';
    const duration = 8000;
    const config = {
        ...defaultConfig, duration,
        text: `<div class="toast-content toast-content-large"><span class="toast-icon-wrapper toast-icon-celebration">${flameIcon}</span><span class="toast-message">🎉 Streak Milestone! +${bonus} bonus points awarded!</span></div>`,
        gravity: 'top', position: 'center',
        offset: getToastOffset(),
        className: 'custom-toast toast-streak-milestone',
        ariaLive: 'polite',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Streak Saved Toast
function showStreakSaved(saversLeft) {
    if (activeToasts >= maxToasts) return;
    activeToasts++;
    const shieldIcon = '<svg class="toast-icon toast-icon-large" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
    const duration = 6000;
    const config = {
        ...defaultConfig, duration,
        text: `<div class="toast-content toast-content-large"><span class="toast-icon-wrapper toast-icon-celebration">${shieldIcon}</span><span class="toast-message">🛡 Streak Saved! ${saversLeft} saver${saversLeft !== 1 ? 's' : ''} remaining.</span></div>`,
        gravity: 'top', position: 'center',
        offset: getToastOffset(),
        className: 'custom-toast toast-streak-saved',
        ariaLive: 'polite',
    };
    const toast = Toastify(config);
    toast.showToast();
    addProgressBar(toast.toastElement, duration);
}

// Make available globally
window.toast = {
    success: showSuccess,
    error: showError,
    warning: showWarning,
    info: showInfo,
    primary: showPrimary,
    achievement: showAchievement,
    levelUp: showLevelUp,
    xp: showXPGained,
    show: showToast,
    shieldLost: showShieldLost,
    shieldRefilled: showShieldRefilled,
    pointsEarned: showPointsEarned,
    streakMilestone: showStreakMilestone,
    streakSaved: showStreakSaved,
};
