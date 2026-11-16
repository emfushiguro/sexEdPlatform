import { emptyActivityFeedback, feedbackForEvaluation, feedbackForLifecycle } from './activity-feedback.js';

async function readResponse(response) {
    const data = await response.json();
    if (!response.ok) throw new Error(data.errors?.preview_token?.[0] || data.message || 'Unable to save the activity.');
    return data;
}

export function createInteractiveActivity(config = {}, request = globalThis.fetch?.bind(globalThis)) {
    const feedback = config.feedback ?? globalThis.Alpine?.store?.('learningFeedback');
    const sourceId = `activity:${config.activityId}`;
    const activity = {
        activityId: config.activityId,
        status: config.initialStatus || 'in_progress',
        revision: config.revision ?? 1,
        payload: config.payload ?? null,
        explanation: config.initialExplanation ?? null,
        error: '',
        feedback: emptyActivityFeedback(),
        feedbackId: null,
        resultSequence: 0,
        warningSequence: 0,
        submitting: false,
        practiceMode: false,
        helpOpen: false,
        helpTrigger: null,
        previewToken: config.previewToken ?? null,
        previewEvaluateUrl: config.previewEvaluateUrl ?? null,

        showSkip() {
            return !['completed', 'skipped'].includes(this.status);
        },

        showResume() {
            return this.status === 'skipped';
        },

        showContinue() {
            return ['completed', 'practice_completed', 'skipped'].includes(this.status);
        },

        showPracticeAgain() {
            return ['completed', 'practice_completed'].includes(this.status);
        },

        clearFeedback() {
            this.dismissFeedback();
            this.feedback = emptyActivityFeedback();
            this.feedbackId = null;
            return this;
        },

        dismissFeedback(type = null) {
            const matches = item => item?.sourceId === sourceId && (type === null || item.type === type);
            if (matches(feedback?.pending)) feedback.pending = null;
            // The manager dismisses after action dispatch; do not dismiss a promoted card twice.
            if (!feedback?.acting && matches(feedback?.active)) feedback.dismiss?.();
            return this;
        },

        hasOpenFeedback() {
            return Boolean(feedback?.open && this.feedbackId && feedback.active?.id === this.feedbackId
                && feedback.active?.sourceId === sourceId);
        },

        showFeedback(payload) {
            this.dismissFeedback();
            this.feedback = payload;
            const warning = payload.type === 'warning';
            const sequence = warning ? ++this.warningSequence : ++this.resultSequence;
            const id = `${sourceId}:${warning ? 'warning' : 'result'}:${sequence}`;
            const actions = [];
            if (payload.type === 'activity_completed') {
                if (this.showContinue()) actions.push({ id: 'continue', intent: 'continue', label: 'Continue' });
                if (this.showPracticeAgain()) actions.push({ id: 'retry', intent: 'retry', label: 'Practice Again' });
            } else if (payload.type === 'incorrect') {
                actions.push({ id: 'retry', intent: 'retry', label: 'Retry' }, { id: 'dismiss', intent: 'dismiss', label: 'Dismiss' });
            } else if (warning) {
                actions.push({ id: 'dismiss', intent: 'dismiss', label: 'Dismiss' });
            }
            const explanation = typeof this.explanation === 'string'
                ? this.explanation.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
                : '';
            this.feedbackId = feedback?.show?.({ ...payload, id, sourceId, explanation, actions }) === true ? id : null;
            return this;
        },

        handleLearningFeedbackAction(event) {
            const detail = event?.detail ?? {};
            if (detail.sourceId !== sourceId) return this;
            if (detail.intent === 'continue' && this.showContinue()) {
                event.preventDefault?.();
                this.continueLearning();
            } else if (detail.intent === 'retry' && ['completed', 'practice_completed'].includes(this.status)) {
                event.preventDefault?.();
                void this.practice();
            }
            return this;
        },

        openHelp(trigger = null) {
            this.helpTrigger = trigger;
            this.helpOpen = true;
            const focusDialog = () => this.$refs?.helpDialog?.focus?.();
            if (typeof this.$nextTick === 'function') this.$nextTick(focusDialog);
            else if (typeof queueMicrotask === 'function') queueMicrotask(focusDialog);
            else setTimeout(focusDialog, 0);
            return this;
        },

        handleHelpKeydown(event) {
            if (event?.key !== 'Tab') return this;

            const dialog = this.$refs?.helpDialog;
            const focusable = Array.from(dialog?.querySelectorAll?.('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])') ?? []);
            if (focusable.length === 0) {
                event.preventDefault();
                dialog?.focus?.();
                return this;
            }

            const active = typeof document !== 'undefined' ? document.activeElement : null;
            const first = focusable[0];
            const last = focusable.at(-1);
            if (event.shiftKey && active === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            }

            return this;
        },

        closeHelp() {
            this.helpOpen = false;
            const trigger = this.helpTrigger;
            this.helpTrigger = null;
            trigger?.focus?.();
            return this;
        },

        handleActivityResult(detail = {}) {
            if (detail.activityId !== config.activityId) return this;
            this.error = '';
            this.status = detail.data?.status ?? this.status;
            this.explanation = detail.data?.explanation ?? null;
            if (detail.data?.preview_token !== undefined) this.previewToken = detail.data.preview_token;
            return this.showFeedback(feedbackForEvaluation(detail.type, detail.data, detail.meta));
        },

        handleActivityError(detail = {}) {
            if (detail.activityId !== config.activityId) return this;
            this.error = detail.message || 'Unable to save the activity.';
            return this.showFeedback({ type: 'warning', message: this.error });
        },

        handleActivityRecovered(detail = {}) {
            if (detail.activityId !== config.activityId) return this;
            this.error = '';
            this.dismissFeedback('warning');
            if (this.feedback.type === 'warning') {
                this.feedback = emptyActivityFeedback();
                this.feedbackId = null;
            }
            return this;
        },

        handleActivityRetry(detail = {}) {
            if (detail.activityId !== config.activityId) return this;
            this.error = '';
            return this.clearFeedback();
        },

        applyResponse(data) {
            this.status = data.status ?? this.status;
            this.payload = data.payload ?? this.payload;
            this.explanation = data.explanation ?? null;
            if (data.preview_token !== undefined) this.previewToken = data.preview_token;
            this.practiceMode = this.status.startsWith('practice');
            this.error = '';
            this.$dispatch?.('interactive-activity-state', { activityId: this.activityId, status: this.status, data });
            if (data.payload) {
                this.$dispatch?.('interactive-activity-payload', {
                    activityId: this.activityId,
                    status: this.status,
                    payload: data.payload,
                    ...(data.preview_token === undefined ? {} : { previewToken: data.preview_token }),
                });
            }
            return data;
        },

        async send(url, method, body = null) {
            if (this.submitting || !url || typeof request !== 'function') return null;
            this.submitting = true;
            this.error = '';
            try {
                const response = await request(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        Accept: 'application/json',
                    },
                    ...(body === null ? {} : { body: JSON.stringify(body) }),
                });
                return this.applyResponse(await readResponse(response));
            } catch (error) {
                this.handleActivityError({ activityId: config.activityId, message: error.message });
                return null;
            } finally {
                this.submitting = false;
            }
        },

        async skip() {
            if (config.preview) {
                this.status = 'skipped';
                this.showFeedback(feedbackForLifecycle(this.status));
                this.$dispatch?.('interactive-activity-state', { activityId: this.activityId, status: this.status, data: { status: this.status } });
                return { status: this.status };
            }
            const data = await this.send(config.skipUrl, 'POST', { revision: this.revision });
            if (data) this.showFeedback(feedbackForLifecycle(this.status));
            return data;
        },

        async resume() {
            this.clearFeedback();
            if (config.preview) {
                this.status = 'in_progress';
                this.$dispatch?.('interactive-activity-state', { activityId: this.activityId, status: this.status, data: { status: this.status } });
                return { status: this.status };
            }
            return this.send(config.resumeUrl, 'POST', { revision: this.revision });
        },

        async practice() {
            this.clearFeedback();
            if (config.preview && this.previewEvaluateUrl && this.previewToken) {
                const data = await this.send(this.previewEvaluateUrl, 'POST', {
                    preview_token: this.previewToken,
                    action: 'practice',
                });
                if (data) {
                    this.practiceMode = true;
                    this.$dispatch?.('interactive-activity-practice', {
                        activityId: this.activityId,
                        payload: data.payload,
                        previewToken: data.preview_token,
                    });
                }
                return data;
            }
            if (config.preview) {
                this.status = 'practice';
                this.$dispatch?.('interactive-activity-state', { activityId: this.activityId, status: this.status, data: { status: this.status } });
                this.$dispatch?.('interactive-activity-practice', { activityId: this.activityId });
                return { status: this.status };
            }
            const data = await this.send(config.practiceUrl, 'POST', { revision: this.revision });
            if (data) {
                this.practiceMode = true;
                this.$dispatch?.('interactive-activity-practice', { activityId: this.activityId, payload: data.payload });
            }
            return data;
        },

        continueLearning() {
            if (!config.preview && config.continueUrl) {
                window.location.assign(config.continueUrl);
                return;
            }
            this.$dispatch?.('interactive-activity-continued', { activityId: this.activityId, preview: config.preview === true });
        },
    };

    return activity;
}
