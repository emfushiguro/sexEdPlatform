export function emptyCheckpointAnswer(type, blankCount = 1) {
    if (type === 'multiple_select') return [];
    if (type === 'perspective_feedback') return { pathway: null, option_id: null, perspective_text: '' };
    if (['fill_blank_text', 'fill_blank_select'].includes(type)) {
        return Array(Math.max(1, blankCount)).fill('');
    }
    return '';
}

async function readResponse(response) {
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Unable to save the checkpoint.');
    if (!['correct', 'incorrect', 'completed', 'skipped'].includes(data?.status)) {
        throw new Error('Unsupported checkpoint response status.');
    }
    return data;
}

export function createInteractiveCheckpoint(config = {}, request = globalThis.fetch?.bind(globalThis)) {
    const feedback = config.feedback ?? globalThis.Alpine?.store?.('learningFeedback');
    const sourceId = `checkpoint:${config.questionId}`;
    const initialStatus = ['correct', 'incorrect', 'completed', 'skipped'].includes(config.initialStatus)
        ? config.initialStatus
        : 'ready';
    const initialAnswer = config.type === 'perspective_feedback' && config.initialResult
        ? { ...emptyCheckpointAnswer(config.type), ...config.initialResult }
        : emptyCheckpointAnswer(config.type, config.blankCount);

    const checkpoint = {
        answer: initialAnswer,
        state: initialStatus,
        isCorrect: initialStatus === 'correct' ? true : null,
        explanation: ['correct', 'completed'].includes(initialStatus) ? config.initialExplanation || null : null,
        feedback: initialStatus === 'completed' ? config.initialFeedback || null : null,
        result: initialStatus === 'completed' ? config.initialResult || null : null,
        perspectiveCharacterLimit: Number(config.perspectiveCharacterLimit || 1000),
        error: '',
        resultSequence: 0,
        warningSequence: 0,
        choosePerspectivePathway(pathway) {
            if (!['guided', 'own'].includes(pathway) || this.state === 'completed') return;
            this.answer.pathway = pathway;
            if (pathway === 'guided') this.answer.perspective_text = '';
            if (pathway === 'own') this.answer.option_id = null;
        },
        remainingPerspectiveCharacters() {
            return this.perspectiveCharacterLimit - Array.from(this.answer.perspective_text || '').length;
        },
        showSkip() { return ['ready', 'incorrect', 'error'].includes(this.state); },
        showContinue() { return ['correct', 'completed', 'skipped'].includes(this.state); },
        hasOpenFeedbackCard() {
            return Boolean(feedback?.open && feedback.active?.sourceId === sourceId && feedback.active?.presentation === 'card');
        },
        retry() {
            const pathway = config.type === 'perspective_feedback' ? this.answer.pathway : null;
            this.answer = emptyCheckpointAnswer(config.type, config.blankCount);
            if (['guided', 'own'].includes(pathway)) this.answer.pathway = pathway;
            this.state = 'ready';
            this.isCorrect = null;
            this.explanation = null;
            this.error = '';
        },
        handleLearningFeedbackAction(event) {
            const detail = event?.detail ?? {};
            if (detail.sourceId !== sourceId) return this;

            if (detail.intent === 'retry' && ['incorrect', 'error'].includes(this.state)) {
                event.preventDefault?.();
                this.retry();
            } else if (detail.intent === 'continue' && this.showContinue()) {
                event.preventDefault?.();
                this.continueLearning();
            }
            return this;
        },
        showResultFeedback(data) {
            this.resultSequence += 1;
            const common = { id: `${sourceId}:result:${this.resultSequence}`, sourceId };
            if (data.status === 'correct') {
                feedback?.show?.({
                    ...common, type: 'correct', message: 'That answer is correct.', explanation: data.explanation,
                    actions: [
                        { id: 'continue', intent: 'continue', label: 'Continue' },
                        { id: 'dismiss', intent: 'dismiss', label: 'Review answer' },
                    ],
                });
            } else if (data.status === 'incorrect') {
                feedback?.show?.({
                    ...common, type: 'incorrect', message: 'Review your response and try again when you are ready.',
                    actions: [
                        { id: 'retry', intent: 'retry', label: 'Try again' },
                        { id: 'dismiss', intent: 'dismiss', label: 'Review answer' },
                    ],
                });
            } else if (data.status === 'completed') {
                feedback?.show?.({
                    ...common, type: 'activity_completed', message: data.feedback || 'Your perspective has been saved.',
                    explanation: data.explanation,
                    actions: [
                        { id: 'continue', intent: 'continue', label: 'Continue' },
                        { id: 'dismiss', intent: 'dismiss', label: 'Review response' },
                    ],
                });
            } else if (data.status === 'skipped') {
                feedback?.show?.({
                    ...common, type: 'informational', presentation: 'micro',
                    message: 'Checkpoint skipped. You can continue and return when ready.',
                });
            }
        },
        async submit() {
            this.state = 'submitting';
            this.error = '';
            try {
                const response = await request(config.submitUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                    body: JSON.stringify({ answer: this.answer }),
                });
                const data = await readResponse(response);
                this.state = data.status;
                this.isCorrect = data.is_correct;
                this.result = data.result || null;
                this.feedback = data.feedback || null;
                this.explanation = ['correct', 'completed'].includes(data.status) ? data.explanation : null;
                if (data.result && config.type === 'perspective_feedback') {
                    this.answer = { ...emptyCheckpointAnswer(config.type), ...data.result };
                }
                if (['correct', 'completed', 'skipped'].includes(data.status)) this.claimForward();
                this.showResultFeedback(data);
            } catch (error) {
                this.state = 'error';
                this.error = error.message || 'Unable to save the checkpoint.';
                this.warningSequence += 1;
                feedback?.show?.({
                    id: `${sourceId}:warning:${this.warningSequence}`, sourceId, type: 'warning', message: this.error,
                    actions: [{ id: 'dismiss', intent: 'dismiss', label: 'Dismiss' }],
                });
            }
        },
        async skip() {
            this.state = 'submitting';
            this.error = '';
            try {
                const response = await request(config.skipUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                });
                const data = await readResponse(response);
                this.state = data.status;
                this.isCorrect = data.is_correct;
                this.result = data.result || null;
                this.feedback = data.feedback || null;
                this.explanation = ['correct', 'completed'].includes(data.status) ? data.explanation : null;
                if (['correct', 'completed', 'skipped'].includes(data.status)) this.claimForward();
                this.showResultFeedback(data);
            } catch (error) {
                this.state = 'error';
                this.error = error.message || 'Unable to skip the checkpoint.';
                this.warningSequence += 1;
                feedback?.show?.({
                    id: `${sourceId}:warning:${this.warningSequence}`, sourceId, type: 'warning', message: this.error,
                    actions: [{ id: 'dismiss', intent: 'dismiss', label: 'Dismiss' }],
                });
            }
        },
        continueLearning() {
            const detail = { questionId: config.questionId, token: `checkpoint:${config.questionId}` };
            this.$dispatch?.('checkpoint-continued', detail);

            if (config.continueUrl) {
                window.location.assign(config.continueUrl);
                return;
            }
        },
        claimForward() {
            this.$dispatch?.('checkpoint-active', { questionId: config.questionId, token: `checkpoint:${config.questionId}` });
        },
    };

    if (config.wordBank) {
        checkpoint.wordBank = createWordBank(config.wordBank, config.blankCount, (answers) => {
            checkpoint.answer = answers;
        });
    }

    return checkpoint;
}

export function createOptionalInteractionCoordinator() {
    return {
        activeToken: null,
        activate(token) {
            this.activeToken = String(token);
        },
        release(token) {
            if (this.activeToken === String(token)) this.activeToken = null;
        },
        footerForwardVisible() { return this.activeToken === null; },
    };
}

export function createCheckpointCoordinator() {
    return createOptionalInteractionCoordinator();
}
import { createWordBank } from './word-bank.js';
