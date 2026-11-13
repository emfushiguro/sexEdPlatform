export const FEEDBACK_CONFIG = Object.freeze({
    correct: Object.freeze({ title: 'Excellent work', message: 'That answer is correct.', illustration: '/assets/illustrations/learning-feedback/correct-answer.svg', presentations: Object.freeze(['card']), presentation: 'card', sound: 'correct', priority: 40, dismissAfter: 4000 }),
    incorrect: Object.freeze({ title: 'Not quite yet', message: 'Review your response and try again when you are ready.', illustration: '/assets/illustrations/learning-feedback/incorrect-answer.svg', presentations: Object.freeze(['card']), presentation: 'card', sound: 'incorrect', priority: 50, dismissAfter: null }),
    try_again: Object.freeze({ title: 'Keep going', message: 'You can adjust your response and try again.', illustration: '/assets/illustrations/learning-feedback/try-again.svg', presentations: Object.freeze(['card']), presentation: 'card', sound: null, priority: 30, dismissAfter: null }),
    activity_completed: Object.freeze({ title: 'Activity complete', message: 'You completed this learning activity.', illustration: '/assets/illustrations/learning-feedback/activity-completed.svg', presentations: Object.freeze(['card']), presentation: 'card', sound: 'success', priority: 80, dismissAfter: null }),
    lesson_completed: Object.freeze({ title: 'Lesson complete', message: 'You completed this lesson.', illustration: '/assets/illustrations/learning-feedback/lesson-completed.svg', presentations: Object.freeze(['completion_modal']), presentation: 'completion_modal', sound: 'complete', priority: 100, dismissAfter: null }),
    module_completed: Object.freeze({ title: 'Module complete', message: 'You completed this module.', illustration: '/assets/illustrations/learning-feedback/lesson-completed.svg', presentations: Object.freeze(['completion_modal']), presentation: 'completion_modal', sound: 'success', priority: 100, dismissAfter: null }),
    informational: Object.freeze({ title: 'Good to know', message: 'Here is some information for this activity.', illustration: '/assets/illustrations/learning-feedback/informational.svg', presentations: Object.freeze(['micro', 'card']), presentation: 'micro', sound: null, priority: 20, dismissAfter: 3000 }),
    warning: Object.freeze({ title: 'Let us pause here', message: 'Review this message before continuing.', illustration: '/assets/illustrations/learning-feedback/warning.svg', presentations: Object.freeze(['card']), presentation: 'card', sound: null, priority: 60, dismissAfter: null }),
});

const ACTION_INTENTS = new Set(['retry', 'continue', 'navigate', 'dismiss']);
const plainText = (value, limit) => typeof value === 'string'
    ? value.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, limit)
    : '';

function applicationPath(value, origin) {
    if (typeof value !== 'string' || !value.trim()) return null;
    try {
        const base = new URL(origin);
        const url = new URL(value, base.origin);
        if (!['http:', 'https:'].includes(base.protocol) || url.origin !== base.origin) return null;
        const path = url.pathname + url.search + url.hash;
        // A same-origin absolute URL can still have a protocol-relative pathname.
        return new URL(path, base.origin).origin === base.origin ? path : null;
    } catch {
        return null;
    }
}

export function normalizeLearningFeedback(payload, origin) {
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)
        || typeof payload.type !== 'string' || !Object.hasOwn(FEEDBACK_CONFIG, payload.type)) return null;

    const id = plainText(payload.id, 160);
    const sourceId = plainText(payload.sourceId, 160);
    if (!id || !sourceId) return null;

    const config = FEEDBACK_CONFIG[payload.type];
    const actions = [];
    const actionIds = new Set();
    for (const action of Array.isArray(payload.actions) ? payload.actions : []) {
        if (!action || typeof action !== 'object' || Array.isArray(action) || !ACTION_INTENTS.has(action.intent)) continue;
        const actionId = plainText(action.id, 160);
        const label = plainText(action.label, 80);
        if (!actionId || !label || actionIds.has(actionId)) continue;
        const normalized = { id: actionId, intent: action.intent, label };
        if (action.intent === 'navigate') {
            const url = applicationPath(action.url, origin);
            if (!url) continue;
            normalized.url = url;
        }
        actionIds.add(actionId);
        actions.push(normalized);
        if (actions.length === 2) break;
    }

    return {
        id, sourceId, type: payload.type,
        title: plainText(payload.title, 120) || config.title,
        message: plainText(payload.message, 500) || config.message,
        explanation: plainText(payload.explanation, 1500),
        illustration: config.illustration,
        presentation: config.presentations.includes(payload.presentation) ? payload.presentation : config.presentation,
        sound: config.sound,
        priority: config.priority,
        dismissAfter: config.dismissAfter,
        actions,
        replacesSuccessToast: payload.replacesSuccessToast === true,
    };
}

const focus = target => {
    if (target?.isConnected === false) return;
    try { target?.focus?.(); } catch { /* A removed focus target cannot block dismissal. */ }
};

export function createLearningFeedbackManager({
    audio,
    dispatchAction = () => false,
    navigate = () => {},
    origin,
    getActiveElement = () => null,
    setTimer = (callback, delay) => setTimeout(callback, delay),
    clearTimer = timer => clearTimeout(timer),
} = {}) {
    const recentIds = new Set();
    let timer = null;
    let timerVersion = 0;
    let startedAt = 0;
    let remaining = null;
    let previousFocus = null;
    let panel = null;
    let heading = null;
    let opened = false;

    const cancelTimer = () => {
        if (timer !== null) clearTimer(timer);
        timer = null;
        timerVersion++;
    };

    function activate(feedback) {
        cancelTimer();
        if (!this.open) previousFocus = getActiveElement();
        panel = null;
        heading = null;
        opened = false;
        this.active = feedback;
        this.announcement = feedback.message;
        remaining = feedback.actions.length === 0 ? feedback.dismissAfter : null;
        if (feedback.sound) {
            try {
                const playback = audio?.play(feedback.sound);
                Promise.resolve(playback).catch(() => {});
            } catch { /* Optional audio must never interrupt the learning flow. */ }
        }
        this.resumeTimer();
    }

    return {
        active: null,
        pending: null,
        acting: false,
        announcement: '',
        get open() { return this.active !== null; },

        show(payload) {
            if (this.acting) return false;
            const feedback = normalizeLearningFeedback(payload, origin);
            if (!feedback || recentIds.has(feedback.id)) return false;
            recentIds.add(feedback.id);
            if (recentIds.size > 50) recentIds.delete(recentIds.values().next().value);

            if (!this.open || feedback.priority === 100) {
                this.pending = null;
                activate.call(this, feedback);
            } else if (this.active.priority !== 100) {
                this.pending = feedback;
            }
            return true;
        },

        dismiss() {
            if (!this.open) return false;
            cancelTimer();
            remaining = null;
            const pending = this.pending;
            this.active = null;
            this.pending = null;
            this.announcement = '';
            panel = null;
            heading = null;
            opened = false;
            const target = previousFocus;
            previousFocus = null;
            focus(target);
            if (pending) activate.call(this, pending);
            return true;
        },

        act(id) {
            if (!this.open || this.acting) return false;
            const action = this.active.actions.find(action => action.id === id);
            if (!action) return false;
            this.acting = true;
            try {
                if (action.intent === 'navigate') {
                    navigate(action.url);
                } else if (action.intent !== 'dismiss') {
                    const handled = dispatchAction({
                        feedbackId: this.active.id, sourceId: this.active.sourceId, intent: action.intent,
                    });
                    if (handled !== true) {
                        this.announcement = 'Use the activity controls to continue.';
                        return false;
                    }
                }
                this.dismiss();
                return true;
            } finally {
                this.acting = false;
            }
        },

        opened(nextPanel, nextHeading) {
            if (!this.open) return;
            panel = nextPanel ?? panel;
            heading = nextHeading ?? heading;
            if (this.active.presentation !== 'completion_modal' || opened || (!heading && !panel)) return;
            opened = true;
            focus(heading ?? panel);
        },

        handleKeydown(event) {
            if (!this.open || this.acting) return;
            if (event?.key === 'Escape') {
                if (this.active.actions.some(action => action.intent === 'dismiss')) {
                    event.preventDefault?.();
                    this.dismiss();
                }
                return;
            }
            if (event?.key !== 'Tab' || this.active.presentation !== 'completion_modal' || !panel) return;
            const controls = [...(panel.querySelectorAll?.('a[href], button, input, select, textarea, [tabindex], [contenteditable="true"]') ?? [])]
                .filter(target => !target.disabled && !target.hidden && !(target.tabIndex < 0)
                    && !target.matches?.(':disabled') && target.getClientRects?.().length !== 0
                    && !target.closest?.('[hidden], [inert], [aria-hidden="true"]'));
            const current = getActiveElement();
            const index = controls.indexOf(current);
            if (controls.length === 0 || index === -1
                || (event.shiftKey ? index === 0 : index === controls.length - 1)) {
                event.preventDefault?.();
                focus(controls.length ? controls[event.shiftKey ? controls.length - 1 : 0] : heading ?? panel);
            }
        },

        pauseTimer() {
            if (timer === null) return;
            remaining = Math.max(0, remaining - (Date.now() - startedAt));
            cancelTimer();
        },

        resumeTimer() {
            if (!this.open || remaining === null || timer !== null) return;
            startedAt = Date.now();
            const version = timerVersion;
            timer = setTimer(() => {
                if (version !== timerVersion) return;
                timer = null;
                this.dismiss();
            }, remaining);
        },
    };
}
