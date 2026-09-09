import { emptyActivityFeedback, feedbackForEvaluation, feedbackForLifecycle } from './activity-feedback.js';

async function readResponse(response) {
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Unable to save the activity.');
    return data;
}

export function createInteractiveActivity(config = {}, request = globalThis.fetch?.bind(globalThis)) {
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

        handleActivityResult(detail = {}) {
            if (detail.activityId !== config.activityId) return this;
            this.status = detail.data?.status ?? this.status;
            this.explanation = detail.data?.explanation ?? null;
            this.feedback = feedbackForEvaluation(detail.type, detail.data, detail.meta);
            return this;
        },

        applyResponse(data) {
            this.status = data.status ?? this.status;
            this.payload = data.payload ?? this.payload;
            this.explanation = data.explanation ?? null;
            this.practiceMode = this.status.startsWith('practice');
            this.error = '';
            this.$dispatch?.('interactive-activity-state', { activityId: this.activityId, status: this.status, data });
            if (data.payload) {
                this.$dispatch?.('interactive-activity-payload', {
                    activityId: this.activityId,
                    status: this.status,
                    payload: data.payload,
                    ...(data.previewToken === undefined ? {} : { previewToken: data.previewToken }),
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
