/**
 * Toast Notification System
 * Wrapper for Toastify.js with custom styling
 */

import Toastify from 'toastify-js';

// Toast configuration defaults
const defaultConfig = {
    duration: 3000,
    gravity: "top",
    position: "right",
    stopOnFocus: true,
    close: true,
    style: {
        borderRadius: "8px",
        fontFamily: "Figtree, sans-serif",
    }
};

// Success Toast
export function showSuccess(message, options = {}) {
    Toastify({
        ...defaultConfig,
        text: message,
        style: {
            ...defaultConfig.style,
            background: "linear-gradient(135deg, #10b981 0%, #059669 100%)",
        },
        ...options
    }).showToast();
}

// Error Toast
export function showError(message, options = {}) {
    Toastify({
        ...defaultConfig,
        text: message,
        duration: 4000, // Longer for errors
        style: {
            ...defaultConfig.style,
            background: "linear-gradient(135deg, #ef4444 0%, #dc2626 100%)",
        },
        ...options
    }).showToast();
}

// Warning Toast
export function showWarning(message, options = {}) {
    Toastify({
        ...defaultConfig,
        text: message,
        style: {
            ...defaultConfig.style,
            background: "linear-gradient(135deg, #f59e0b 0%, #d97706 100%)",
        },
        ...options
    }).showToast();
}

// Info Toast
export function showInfo(message, options = {}) {
    Toastify({
        ...defaultConfig,
        text: message,
        style: {
            ...defaultConfig.style,
            background: "linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)",
        },
        ...options
    }).showToast();
}

// Primary/Purple Toast (for gamification)
export function showPrimary(message, options = {}) {
    Toastify({
        ...defaultConfig,
        text: message,
        style: {
            ...defaultConfig.style,
            background: "linear-gradient(135deg, #a855f7 0%, #9333ea 100%)",
        },
        ...options
    }).showToast();
}

// Achievement Toast (special styling for gamification)
export function showAchievement(message, options = {}) {
    Toastify({
        ...defaultConfig,
        text: `🎉 ${message}`,
        duration: 5000,
        gravity: "top",
        position: "center",
        style: {
            ...defaultConfig.style,
            background: "linear-gradient(135deg, #a855f7 0%, #ec4899 100%)",
            fontSize: "16px",
            fontWeight: "600",
            padding: "16px 24px",
        },
        ...options
    }).showToast();
}

// Level Up Toast
export function showLevelUp(level, options = {}) {
    Toastify({
        ...defaultConfig,
        text: `🚀 Level Up! You're now Level ${level}!`,
        duration: 6000,
        gravity: "top",
        position: "center",
        style: {
            ...defaultConfig.style,
            background: "linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)",
            fontSize: "18px",
            fontWeight: "700",
            padding: "20px 30px",
            boxShadow: "0 10px 40px rgba(0, 0, 0, 0.3)",
        },
        ...options
    }).showToast();
}

// XP Gained Toast
export function showXPGained(xp, options = {}) {
    Toastify({
        ...defaultConfig,
        text: `+${xp} XP`,
        duration: 2000,
        gravity: "top",
        position: "right",
        style: {
            ...defaultConfig.style,
            background: "linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)",
            fontSize: "14px",
            fontWeight: "600",
        },
        ...options
    }).showToast();
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
};
