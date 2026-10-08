import { emptyActivityFeedback, feedbackForEvaluation, feedbackForLifecycle } from './activity-feedback.js';

async function readResponse(response) {
    const data = await response.json();
    if (!response.ok) throw new Error(data.errors?.preview_token?.[0] || data.message || 'Unable to save the activity.');
    return data;
}

export function createInteractiveActivity(config = {}, request = globalThis.fetch?.bind(globalThis)) {
    const audio = config.audio ?? globalThis.learningAudio;
    const activity = {
        activityId: config.activityId,
        status: config.initialStatus || 'in_progress',
        revision: config.revision ?? 1,
        payload: config.payload ?? null,
        explanation: config.initialExplanation ?? null,
        error: '',
        feedback: emptyActivityFeedback(),
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
            this.feedback = emptyActivityFeedback();
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
            this.feedback = feedbackForEvaluation(detail.type, detail.data, detail.meta);
            const soundKey = detail.data?.is_complete === true
                ? 'success'
                : detail.data?.is_correct === true
                    ? 'correct'
                    : detail.data?.is_correct === false
                        ? 'incorrect'
                        : null;

            if (soundKey) audio?.play?.(soundKey);
            return this;
        },

        handleActivityError(detail = {}) {
            if (detail.activityId !== config.activityId) return this;
            this.error = detail.message || 'Unable to save the activity.';
            this.feedback = { kind: 'error', message: this.error, icon: 'warning' };
            return this;
        },

        handleActivityRecovered(detail = {}) {
            if (detail.activityId !== config.activityId) return this;
            this.error = '';
            if (this.feedback.kind === 'error') this.clearFeedback();
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
                this.error = error.message || 'Unable to save the activity.';
                this.feedback = { kind: 'error', message: this.error, icon: 'warning' };
                return null;
            } finally {
                this.submitting = false;
            }
        },

        async skip() {
            if (config.preview) {
                this.status = 'skipped';
                this.feedback = feedbackForLifecycle(this.status);
                this.$dispatch?.('interactive-activity-state', { activityId: this.activityId, status: this.status, data: { status: this.status } });
                return { status: this.status };
            }
            const data = await this.send(config.skipUrl, 'POST', { revision: this.revision });
            if (data) this.feedback = feedbackForLifecycle(this.status);
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
