import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createLearningFeedbackManager } from '../../resources/js/learning-feedback.js';
import {
    createCheckpointCoordinator,
    createInteractiveCheckpoint,
} from '../../resources/js/interactive-checkpoint.js';

test('perspective feedback submits exactly one guided pathway', async () => {
    let submittedBody;
    const checkpoint = createInteractiveCheckpoint({
        type: 'perspective_feedback',
        questionId: 17,
        submitUrl: '/submit',
        skipUrl: '/skip',
        csrf: 'token',
        perspectiveCharacterLimit: 1000,
    }, async (_url, options) => {
        submittedBody = JSON.parse(options.body);
        return {
            ok: true,
            json: async () => ({
                status: 'completed',
                is_correct: null,
                pathway: 'guided',
                result: { pathway: 'guided', option_id: 4, option_text: 'Listen', feedback: 'Listening helps.' },
                feedback: 'Listening helps.',
                explanation: 'Respect autonomy.',
            }),
        };
    });

    checkpoint.choosePerspectivePathway('guided');
    checkpoint.answer.option_id = 4;
    await checkpoint.submit();

    assert.deepEqual(submittedBody, {
        answer: { pathway: 'guided', option_id: 4, perspective_text: '' },
    });
    assert.equal(checkpoint.state, 'completed');
    assert.equal(checkpoint.isCorrect, null);
    assert.equal(checkpoint.showContinue(), true);
    assert.equal(checkpoint.feedback, 'Listening helps.');
});

test('written perspective counts remaining characters and survives request errors', async () => {
    const checkpoint = createInteractiveCheckpoint({
        type: 'perspective_feedback',
        submitUrl: '/submit',
        skipUrl: '/skip',
        csrf: 'token',
        perspectiveCharacterLimit: 20,
    }, async () => ({
        ok: false,
        json: async () => ({ message: 'Unable to save the checkpoint.' }),
    }));

    checkpoint.choosePerspectivePathway('own');
    checkpoint.answer.perspective_text = 'My own view';
    assert.equal(checkpoint.remainingPerspectiveCharacters(), 9);

    await checkpoint.submit();

    assert.equal(checkpoint.answer.perspective_text, 'My own view');
    assert.equal(checkpoint.state, 'error');
});

test('stored completed perspective is restored without correctness', () => {
    const checkpoint = createInteractiveCheckpoint({
        type: 'perspective_feedback',
        initialStatus: 'completed',
        initialResult: { pathway: 'own', perspective_text: 'Stored reflection' },
        initialExplanation: 'Consider the impact.',
    });

    assert.equal(checkpoint.state, 'completed');
    assert.equal(checkpoint.answer.perspective_text, 'Stored reflection');
    assert.equal(checkpoint.isCorrect, null);
    assert.equal(checkpoint.showSkip(), false);
});

test('incorrect hides explanation and exposes retry or skip', async () => {
    const request = async () => ({
        ok: true,
        json: async () => ({ status: 'incorrect', is_correct: false, explanation: null }),
    });
    const checkpoint = createInteractiveCheckpoint({
        type: 'identification', submitUrl: '/submit', skipUrl: '/skip', csrf: 'token',
    }, request);
    checkpoint.answer = 'Pressure';

    await checkpoint.submit();

    assert.equal(checkpoint.state, 'incorrect');
    assert.equal(checkpoint.explanation, null);
    assert.equal(checkpoint.showSkip(), true);
    assert.equal(checkpoint.showContinue(), false);
});

test('correct removes skip and exposes continuation', async () => {
    const request = async () => ({
        ok: true,
        json: async () => ({ status: 'correct', is_correct: true, explanation: 'Freely given.' }),
    });
    const checkpoint = createInteractiveCheckpoint({ type: 'identification', submitUrl: '/submit', skipUrl: '/skip', csrf: 'token' }, request);
    checkpoint.answer = 'Consent';

    await checkpoint.submit();

    assert.equal(checkpoint.state, 'correct');
    assert.equal(checkpoint.showSkip(), false);
    assert.equal(checkpoint.showContinue(), true);
});

test('publishes correct feedback with explanation and Continue/Dismiss without direct audio', async () => {
    const shown = [];
    const sounds = [];
    const checkpoint = createInteractiveCheckpoint({
        type: 'identification', questionId: 17, submitUrl: '/submit', csrf: 'token',
        feedback: { show: (value) => { shown.push(value); return true; } },
        audio: { play: (key) => sounds.push(key) },
    }, async () => ({
        ok: true,
        json: async () => ({ status: 'correct', is_correct: true, explanation: 'Correct.' }),
    }));

    await checkpoint.submit();

    assert.deepEqual(shown, [{
        id: 'checkpoint:17:result:1', sourceId: 'checkpoint:17', type: 'correct',
        message: 'That answer is correct.', explanation: 'Correct.',
        actions: [
            { id: 'continue', intent: 'continue', label: 'Continue' },
            { id: 'dismiss', intent: 'dismiss', label: 'Review answer' },
        ],
    }]);
    assert.deepEqual(sounds, []);
});

test('publishes incorrect feedback with Retry/Dismiss without direct audio', async () => {
    const shown = [];
    const sounds = [];
    const checkpoint = createInteractiveCheckpoint({
        type: 'identification', questionId: 17, submitUrl: '/submit', csrf: 'token',
        feedback: { show: (value) => { shown.push(value); return true; } },
        audio: { play: (key) => sounds.push(key) },
    }, async () => ({
        ok: true,
        json: async () => ({ status: 'incorrect', is_correct: false }),
    }));

    await checkpoint.submit();

    assert.deepEqual(shown, [{
        id: 'checkpoint:17:result:1', sourceId: 'checkpoint:17', type: 'incorrect',
        message: 'Review your response and try again when you are ready.',
        actions: [
            { id: 'retry', intent: 'retry', label: 'Try again' },
            { id: 'dismiss', intent: 'dismiss', label: 'Review answer' },
        ],
    }]);
    assert.deepEqual(sounds, []);
});

test('publishes neutral Perspective completion with response feedback and explanation without direct audio', async () => {
    const shown = [];
    const sounds = [];
    const checkpoint = createInteractiveCheckpoint({
        type: 'perspective_feedback', questionId: 17, submitUrl: '/submit', csrf: 'token',
        feedback: { show: (value) => { shown.push(value); return true; } },
        audio: { play: (key) => sounds.push(key) },
    }, async () => ({
        ok: true,
        json: async () => ({
            status: 'completed', is_correct: null,
            result: { pathway: 'own', perspective_text: 'My perspective' },
            feedback: 'Thank you for reflecting.', explanation: 'Respect autonomy.',
        }),
    }));

    checkpoint.choosePerspectivePathway('own');
    checkpoint.answer.perspective_text = 'My perspective';
    await checkpoint.submit();

    assert.deepEqual(shown, [{
        id: 'checkpoint:17:result:1', sourceId: 'checkpoint:17', type: 'activity_completed',
        message: 'Thank you for reflecting.', explanation: 'Respect autonomy.',
        actions: [
            { id: 'continue', intent: 'continue', label: 'Continue' },
            { id: 'dismiss', intent: 'dismiss', label: 'Review response' },
        ],
    }]);
    assert.equal(checkpoint.isCorrect, null);
    assert.equal(checkpoint.result.perspective_text, 'My perspective');
    assert.deepEqual(sounds, []);
});

test('publishes a silent informational micro for skip and silent warnings for failed submit and skip', async () => {
    const shown = [];
    const sounds = [];
    const feedback = createLearningFeedbackManager({ audio: { play: (key) => sounds.push(key) } });
    const checkpoint = createInteractiveCheckpoint({
        type: 'identification', questionId: 17, submitUrl: '/submit', skipUrl: '/skip', csrf: 'token',
        feedback: { show: (value) => { shown.push(value); return feedback.show(value); } },
    }, async () => ({
        ok: shown.length === 0,
        json: async () => shown.length === 0
            ? { status: 'skipped', is_correct: null }
            : { message: 'Unable to save the checkpoint.' },
    }));
    await checkpoint.skip();
    assert.deepEqual(shown[0], {
        id: 'checkpoint:17:result:1', sourceId: 'checkpoint:17', type: 'informational',
        presentation: 'micro', message: 'Checkpoint skipped. You can continue and return when ready.',
    });
    assert.equal(feedback.active.sound, null);
    feedback.dismiss();

    checkpoint.answer = 'Consent';
    await checkpoint.submit();
    feedback.dismiss();
    await checkpoint.skip();

    assert.deepEqual(shown.slice(1), [1, 2].map(sequence => ({
        id: `checkpoint:17:warning:${sequence}`, sourceId: 'checkpoint:17', type: 'warning',
        message: 'Unable to save the checkpoint.',
    })));
    assert.equal(feedback.active.sound, null);
    feedback.dismiss();
    assert.equal(checkpoint.state, 'error');
    assert.equal(checkpoint.answer, 'Consent');
    assert.equal(checkpoint.resultSequence, 1);
    assert.equal(checkpoint.warningSequence, 2);
    assert.deepEqual(sounds, []);
});

test('restored correct, completed, skipped and retry do not publish feedback', () => {
    const shown = [];
    for (const initialStatus of ['correct', 'completed', 'skipped']) {
        const checkpoint = createInteractiveCheckpoint({
            type: 'perspective_feedback', questionId: 17, initialStatus,
            feedback: { show: (value) => { shown.push(value); return true; } },
        });
        assert.equal(checkpoint.resultSequence, 0);
        checkpoint.retry();
    }
    assert.deepEqual(shown, []);
});

test('accepted responses advance result IDs once across submit, retry and skip', async () => {
    const shown = [];
    const responses = [
        { status: 'incorrect', is_correct: false },
        { status: 'correct', is_correct: true },
        { status: 'skipped', is_correct: null },
    ];
    const checkpoint = createInteractiveCheckpoint({
        type: 'identification', questionId: 17, submitUrl: '/submit', skipUrl: '/skip',
        feedback: { show: (value) => { shown.push(value); return true; } },
    }, async () => ({ ok: true, json: async () => responses.shift() }));

    await checkpoint.submit();
    checkpoint.retry();
    await checkpoint.submit();
    await checkpoint.skip();

    assert.deepEqual(shown.map(value => value.id), [
        'checkpoint:17:result:1', 'checkpoint:17:result:2', 'checkpoint:17:result:3',
    ]);
    assert.equal(checkpoint.resultSequence, 3);
    assert.equal(checkpoint.warningSequence, 0);
});

test('uses the Alpine manager with a neutral default Perspective completion message', async () => {
    const previousAlpine = globalThis.Alpine;
    const shown = [];
    globalThis.Alpine = { store: (name) => {
        assert.equal(name, 'learningFeedback');
        return { show: (value) => { shown.push(value); return true; } };
    } };
    try {
        const checkpoint = createInteractiveCheckpoint({ type: 'perspective_feedback', questionId: 17 },
            async () => ({ ok: true, json: async () => ({ status: 'completed', is_correct: null }) }));
        await checkpoint.submit();
        assert.equal(shown[0].type, 'activity_completed');
        assert.equal(shown[0].message, 'Your perspective has been saved.');
    } finally {
        if (previousAlpine === undefined) delete globalThis.Alpine;
        else globalThis.Alpine = previousAlpine;
    }
});

test('manager Retry clears the matching checkpoint answer from incorrect or error', () => {
    for (const state of ['incorrect', 'error']) {
        const checkpoint = createInteractiveCheckpoint({ type: 'identification', questionId: 17 });
        checkpoint.state = state;
        checkpoint.answer = 'Pressure';
        let prevented = 0;
        assert.equal(checkpoint.handleLearningFeedbackAction({
            detail: { sourceId: 'checkpoint:17', intent: 'retry' },
            preventDefault: () => { prevented += 1; },
        }), checkpoint);
        assert.equal(checkpoint.state, 'ready');
        assert.equal(checkpoint.answer, '');
        assert.equal(prevented, 1);
    }
});

test('manager Continue uses existing continuation only for matching resolved checkpoints', () => {
    const checkpoint = createInteractiveCheckpoint({ type: 'identification', questionId: 17 });
    const events = [];
    checkpoint.$dispatch = (name, detail) => events.push([name, detail]);
    let prevented = 0;
    const action = (sourceId, intent) => ({ detail: { sourceId, intent }, preventDefault: () => { prevented += 1; } });
    for (const state of ['ready', 'submitting', 'incorrect', 'error']) {
        checkpoint.state = state;
        checkpoint.handleLearningFeedbackAction(action('checkpoint:17', 'continue'));
    }
    checkpoint.state = 'correct';
    checkpoint.handleLearningFeedbackAction(action('checkpoint:18', 'continue'));
    checkpoint.handleLearningFeedbackAction(action('checkpoint:17', 'retry'));
    checkpoint.handleLearningFeedbackAction(action('checkpoint:17', 'dismiss'));
    checkpoint.handleLearningFeedbackAction({});
    assert.equal(prevented, 0);
    assert.deepEqual(events, []);

    for (const state of ['correct', 'completed', 'skipped']) {
        checkpoint.state = state;
        checkpoint.handleLearningFeedbackAction(action('checkpoint:17', 'continue'));
    }
    assert.equal(prevented, 3);
    assert.deepEqual(events, Array(3).fill(['checkpoint-continued', { questionId: 17, token: 'checkpoint:17' }]));
});

test('checkpoint view wires manager actions and retains compact fallback, stored response and selection audio', () => {
    const view = readFileSync(new URL('../../resources/views/learner/lessons/partials/interactive-checkpoint.blade.php', import.meta.url), 'utf8');
    assert.ok(view.includes('@learning-feedback:action.window="handleLearningFeedbackAction($event)"'));
    assert.ok(view.includes("state === 'incorrect' ? 'Not quite; try again.'"));
    assert.ok(view.includes("state === 'skipped' ? 'Skipped for now.'"));
    assert.ok(view.includes(': error'));
    assert.ok(view.includes("result?.pathway === 'guided' ? result?.option_text : result?.perspective_text"));
    assert.equal(view.match(/\$store\.learningAudio\.play\('selection'\)/g)?.length, 7);
    assert.ok(!view.includes('x-text="explanation"'));
    assert.ok(!view.includes('x-text="feedback"'));
});

test('request failure retains the answer and exposes an error', async () => {
    const request = async () => ({
        ok: false,
        json: async () => ({ message: 'Unable to save the checkpoint.' }),
    });
    const checkpoint = createInteractiveCheckpoint({ type: 'identification', submitUrl: '/submit', skipUrl: '/skip', csrf: 'token' }, request);
    checkpoint.answer = 'Consent';

    await checkpoint.submit();

    assert.equal(checkpoint.state, 'error');
    assert.equal(checkpoint.answer, 'Consent');
    assert.equal(checkpoint.error, 'Unable to save the checkpoint.');
});

test('retry clears the answer and coordinator releases footer ownership', () => {
    const checkpoint = createInteractiveCheckpoint({ type: 'identification', submitUrl: '/submit', skipUrl: '/skip', csrf: 'token' });
    checkpoint.answer = 'Pressure';
    checkpoint.state = 'incorrect';
    checkpoint.retry();
    assert.equal(checkpoint.state, 'ready');
    assert.equal(checkpoint.answer, '');

    const coordinator = createCheckpointCoordinator();
    coordinator.activate(17);
    assert.equal(coordinator.footerForwardVisible(), false);
    coordinator.release(17);
    assert.equal(coordinator.footerForwardVisible(), true);
});

test('resolved inside checkpoint claims then releases footer ownership', async () => {
    const checkpoint = createInteractiveCheckpoint({
        type: 'identification', questionId: 17, submitUrl: '/submit', skipUrl: '/skip', csrf: 'token',
    }, async () => ({
        ok: true,
        json: async () => ({ status: 'skipped', is_correct: null, explanation: null }),
    }));
    const events = [];
    checkpoint.$dispatch = (name, detail) => events.push([name, detail]);

    await checkpoint.skip();
    checkpoint.continueLearning();

    assert.deepEqual(events, [
        ['checkpoint-active', { questionId: 17, token: 'checkpoint:17' }],
        ['checkpoint-continued', { questionId: 17, token: 'checkpoint:17' }],
    ]);
});

test('continuation releases the current interaction before navigating to the next item', () => {
    const previousWindow = globalThis.window;
    const navigatedTo = [];
    globalThis.window = { location: { assign: (url) => navigatedTo.push(url) } };

    try {
        const checkpoint = createInteractiveCheckpoint({
            type: 'identification',
            questionId: 17,
            continueUrl: '/learn/lessons/4?topic=2',
        });
        const events = [];
        checkpoint.$dispatch = (name, detail) => events.push([name, detail]);

        checkpoint.continueLearning();

        assert.deepEqual(events, [
            ['checkpoint-continued', { questionId: 17, token: 'checkpoint:17' }],
        ]);
        assert.deepEqual(navigatedTo, ['/learn/lessons/4?topic=2']);
    } finally {
        globalThis.window = previousWindow;
    }
});

test('optional interaction coordinator accepts string tokens and checkpoint alias remains compatible', () => {
    const coordinator = createCheckpointCoordinator();

    coordinator.activate('activity:17');
    assert.equal(coordinator.footerForwardVisible(), false);
    coordinator.release('activity:17');
    assert.equal(coordinator.footerForwardVisible(), true);
});
