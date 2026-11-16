import test from 'node:test';
import assert from 'node:assert/strict';
import { createInteractiveActivity } from '../../resources/js/interactive-activity.js';
import { createLearningFeedbackManager } from '../../resources/js/learning-feedback.js';
import { readFileSync } from 'node:fs';

const response = (data, ok = true) => ({ ok, json: async () => data });
const createManager = (options = {}) => createLearningFeedbackManager({
    setTimer: () => 0, clearTimer: () => {}, ...options,
});
const result = (data = {}, activityId = 41) => ({ activityId, type: 'matching', data });
const action = (intent, sourceId = 'activity:41') => ({
    detail: { sourceId, intent }, prevented: 0, preventDefault() { this.prevented++; },
});

test('common activity state sends revisioned skip and exposes lifecycle controls', async () => {
    const calls = [];
    const activity = createInteractiveActivity({
        revision: 4,
        initialStatus: 'in_progress',
        skipUrl: '/skip',
        csrf: 'token',
    }, async (url, options) => {
        calls.push({ url, options });
        return response({ status: 'skipped', payload: { items: [] } });
    });

    assert.equal(activity.showSkip(), true);
    await activity.skip();
    assert.equal(activity.status, 'skipped');
    assert.equal(activity.showResume(), true);
    assert.equal(activity.showContinue(), true);
    assert.deepEqual(activity.feedback, {
        type: 'informational', presentation: 'micro', message: 'Activity skipped. You can resume when ready.',
    });
    assert.deepEqual(JSON.parse(calls[0].options.body), { revision: 4 });
    assert.equal(calls[0].options.headers['X-CSRF-TOKEN'], 'token');
});

test('common activity retains state and reports request errors', async () => {
    const activity = createInteractiveActivity({ skipUrl: '/skip' }, async () => response({ message: 'Offline' }, false));

    await activity.skip();

    assert.equal(activity.error, 'Offline');
    assert.deepEqual(activity.feedback, { type: 'warning', message: 'Offline' });
    assert.equal(activity.status, 'in_progress');
    assert.equal(activity.submitting, false);
});

test('resume and practice clear activity feedback', async () => {
    const activity = createInteractiveActivity({
        initialStatus: 'skipped',
        resumeUrl: '/resume',
        practiceUrl: '/practice',
    }, async (url) => response(url === '/resume'
        ? { status: 'in_progress' }
        : { status: 'practice', payload: { items: [] } }));

    activity.feedback = { kind: 'skipped', message: 'Activity skipped. You can resume when ready.', icon: 'skip' };
    await activity.resume();
    assert.deepEqual(activity.feedback, { kind: 'idle', message: '', icon: null });

    activity.feedback = { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' };
    await activity.practice();
    assert.deepEqual(activity.feedback, { kind: 'idle', message: '', icon: null });
});

test('common activity applies only its child result details', () => {
    const activity = createInteractiveActivity({ activityId: 41 });

    activity.handleActivityResult({
        activityId: 42,
        type: 'matching',
        data: { status: 'completed', explanation: '<p>Ignore me</p>', is_correct: true, is_complete: true },
    });
    assert.equal(activity.status, 'in_progress');

    activity.handleActivityResult({
        activityId: 41,
        type: 'matching',
        data: { status: 'completed', explanation: '<p>Well done</p>', is_correct: true, is_complete: true },
        meta: { completed: 4, total: 4 },
    });
    assert.equal(activity.status, 'completed');
    assert.equal(activity.explanation, '<p>Well done</p>');
    assert.deepEqual(activity.feedback, { type: 'activity_completed', message: 'You completed this learning activity.' });
});

test('common activity reports only its child request errors through shared feedback', () => {
    const activity = createInteractiveActivity({ activityId: 41 });

    activity.handleActivityError({ activityId: 42, message: 'Ignore me' });
    assert.equal(activity.error, '');

    activity.handleActivityError({ activityId: 41, message: 'Offline' });
    assert.equal(activity.error, 'Offline');
    assert.deepEqual(activity.feedback, { type: 'warning', message: 'Offline' });
});

test('common activity clears a shared error after its child result succeeds', () => {
    const activity = createInteractiveActivity({ activityId: 41 });

    activity.handleActivityError({ activityId: 41, message: 'Offline' });
    activity.handleActivityResult({
        activityId: 41,
        type: 'matching',
        data: { status: 'completed', is_correct: true, is_complete: true },
    });

    assert.equal(activity.error, '');
    assert.deepEqual(activity.feedback, { type: 'activity_completed', message: 'You completed this learning activity.' });
});

test('activity coordinator publishes normalized scoped results and delegates all outcome audio', () => {
    const played = [];
    const payloads = [];
    const activity = createInteractiveActivity({
        activityId: 41,
        audio: { play: (soundKey) => played.push(soundKey) },
        feedback: { show: payload => { payloads.push(payload); return true; } },
    });

    activity.handleActivityResult({
        activityId: 41,
        type: 'matching',
        data: { is_complete: true, is_correct: true },
    });
    activity.handleActivityResult({
        activityId: 41,
        type: 'matching',
        data: { is_complete: false, is_correct: true },
    });
    activity.handleActivityResult({
        activityId: 41,
        type: 'matching',
        data: { is_complete: false, is_correct: false },
    });
    activity.handleActivityResult({
        activityId: 41,
        type: 'matching',
        data: {},
    });

    assert.deepEqual(payloads.map(({ id, sourceId, type }) => ({ id, sourceId, type })), [
        { id: 'activity:41:result:1', sourceId: 'activity:41', type: 'activity_completed' },
        { id: 'activity:41:result:2', sourceId: 'activity:41', type: 'correct' },
        { id: 'activity:41:result:3', sourceId: 'activity:41', type: 'incorrect' },
        { id: 'activity:41:result:4', sourceId: 'activity:41', type: 'incorrect' },
    ]);
    assert.deepEqual(payloads[2].actions, [
        { id: 'retry', intent: 'retry', label: 'Retry' },
        { id: 'dismiss', intent: 'dismiss', label: 'Dismiss' },
    ]);
    assert.deepEqual(played, []);
});

test('activity coordinator keeps unrelated results and lifecycle paths silent', async () => {
    const played = [];
    const activity = createInteractiveActivity({
        activityId: 41,
        preview: true,
        audio: { play: (soundKey) => played.push(soundKey) },
    });

    activity.handleActivityResult({
        activityId: 42,
        type: 'matching',
        data: { is_complete: true, is_correct: true },
    });
    activity.handleActivityError({ activityId: 41, message: 'Offline' });
    activity.handleActivityRecovered({ activityId: 41 });
    activity.handleActivityRetry({ activityId: 41 });
    await activity.skip();
    await activity.resume();
    await activity.practice();

    assert.deepEqual(played, []);
});

test('preview practice posts the current token and publishes the rotated payload token', async () => {
    const events = [];
    const activity = createInteractiveActivity({
        activityId: 41,
        preview: true,
        previewToken: 'token-1',
        previewEvaluateUrl: '/preview/evaluate',
    }, async (url, options) => {
        assert.equal(url, '/preview/evaluate');
        assert.deepEqual(JSON.parse(options.body), { preview_token: 'token-1', action: 'practice' });
        return response({ status: 'practice', payload: { items: [{ id: 'fresh' }] }, preview_token: 'token-2' });
    });
    activity.$dispatch = (name, detail) => events.push([name, detail]);

    await activity.practice();

    assert.equal(activity.previewToken, 'token-2');
    assert.deepEqual(events.find(([name]) => name === 'interactive-activity-practice')[1], {
        activityId: 41,
        payload: { items: [{ id: 'fresh' }] },
        previewToken: 'token-2',
    });
});

test('common activity clears only its recovered child save error', () => {
    const activity = createInteractiveActivity({ activityId: 41 });

    activity.handleActivityError({ activityId: 41, message: 'Offline' });
    activity.handleActivityRecovered({ activityId: 42 });
    assert.equal(activity.error, 'Offline');

    activity.handleActivityRecovered({ activityId: 41 });
    assert.equal(activity.error, '');
    assert.deepEqual(activity.feedback, { kind: 'idle', message: '', icon: null });
});

test('practice publishes the returned payload only to its activity instance', async () => {
    const events = [];
    const activity = createInteractiveActivity({
        activityId: 41,
        practiceUrl: '/practice',
    }, async () => response({
        status: 'practice',
        payload: { items: [{ id: 'fresh' }] },
    }));
    activity.$dispatch = (name, detail) => events.push([name, detail]);

    await activity.practice();

    assert.deepEqual(events.find(([name]) => name === 'interactive-activity-payload')[1], {
        activityId: 41,
        status: 'practice',
        payload: { items: [{ id: 'fresh' }] },
    });
});

test('common activity opens and closes the accessible help dialog and clears retry feedback', () => {
    const activity = createInteractiveActivity({ activityId: 41 });
    let focused = 0;
    const trigger = { focus: () => { focused += 1; } };

    activity.openHelp(trigger);
    assert.equal(activity.helpOpen, true);
    activity.closeHelp();
    assert.equal(activity.helpOpen, false);
    assert.equal(focused, 1);

    activity.feedback = { kind: 'incorrect', message: 'Try again', icon: 'x' };
    activity.handleActivityRetry({ activityId: 41 });
    assert.deepEqual(activity.feedback, { kind: 'idle', message: '', icon: null });
});

test('help dialog traps Tab focus at both ends', () => {
    const originalDocument = globalThis.document;
    const activity = createInteractiveActivity({ activityId: 41 });
    const first = { focus() { focused = 'first'; } };
    const last = { focus() { focused = 'last'; } };
    let focused = null;
    let prevented = 0;
    activity.$refs = { helpDialog: { querySelectorAll: () => [first, last], focus() {} } };

    try {
        globalThis.document = { activeElement: last };
        activity.handleHelpKeydown({ key: 'Tab', shiftKey: false, preventDefault: () => { prevented += 1; } });
        assert.equal(focused, 'first');

        globalThis.document.activeElement = first;
        activity.handleHelpKeydown({ key: 'Tab', shiftKey: true, preventDefault: () => { prevented += 1; } });
        assert.equal(focused, 'last');
        assert.equal(prevented, 2);
    } finally {
        globalThis.document = originalDocument;
    }
});

test('final completion replaces intermediate Correct and retains rich local explanation', () => {
    const sounds = [];
    const manager = createManager({ audio: { play: key => sounds.push(key) } });
    const activity = createInteractiveActivity({ activityId: 41, feedback: manager });
    activity.handleActivityResult({ ...result({ is_correct: true }), meta: { completed: 2, total: 4 } });
    assert.equal(manager.active.type, 'correct');
    assert.equal(manager.active.message, 'Correct match. 2 of 4 pairs complete.');
    assert.equal(activity.hasOpenFeedback(), true);

    activity.handleActivityResult(result({
        status: 'completed', is_correct: true, is_complete: true,
        explanation: '<p>Well <strong>done</strong>.</p>',
    }));
    assert.equal(manager.active.id, 'activity:41:result:2');
    assert.equal(manager.active.type, 'activity_completed');
    assert.equal(manager.pending, null);
    assert.equal(manager.active.explanation, 'Well done .');
    assert.equal(activity.explanation, '<p>Well <strong>done</strong>.</p>');
    assert.deepEqual(manager.active.actions, [
        { id: 'continue', intent: 'continue', label: 'Continue' },
        { id: 'retry', intent: 'retry', label: 'Practice Again' },
    ]);
    assert.deepEqual(sounds, ['correct', 'success']);
    manager.dismiss();
    assert.equal(activity.hasOpenFeedback(), false);
    assert.equal(activity.feedback.type, 'activity_completed');
});

test('manager receives plain explanations even before its own normalization', () => {
    let published;
    const activity = createInteractiveActivity({ activityId: 41, feedback: { show: payload => { published = payload; return true; } } });
    activity.handleActivityResult(result({ is_complete: true, explanation: '<p>A <b>clear</b> explanation.</p>' }));
    assert.equal(published.explanation, 'A clear explanation.');
    assert.equal(activity.explanation, '<p>A <b>clear</b> explanation.</p>');
});

test('warnings retain learner work and recovery dismisses only the matching warning', () => {
    const manager = createManager();
    const work = { items: [{ id: 'two' }, { id: 'one' }] };
    const activity = createInteractiveActivity({ activityId: 41, payload: work, feedback: manager });
    activity.handleActivityError({ activityId: 41, message: 'Offline' });
    assert.equal(manager.active.id, 'activity:41:warning:1');
    assert.equal(manager.active.type, 'warning');
    assert.deepEqual(manager.active.actions, [{ id: 'dismiss', intent: 'dismiss', label: 'Dismiss' }]);
    assert.equal(activity.payload, work);
    assert.equal(activity.status, 'in_progress');
    activity.handleActivityRecovered({ activityId: 42 });
    assert.equal(manager.open, true);
    activity.handleActivityRecovered({ activityId: 41 });
    assert.equal(manager.open, false);
    assert.equal(activity.error, '');

    activity.handleActivityResult(result({ is_correct: false }));
    activity.handleActivityRecovered({ activityId: 41 });
    assert.equal(manager.active.type, 'incorrect');
    activity.handleActivityError({ activityId: 41, message: 'Still offline' });
    assert.equal(manager.active.id, 'activity:41:warning:2');
    manager.show({ id: 'other-warning', sourceId: 'activity:42', type: 'warning' });
    manager.dismiss();
    activity.handleActivityRecovered({ activityId: 41 });
    assert.equal(manager.active.id, 'other-warning');
    assert.equal(activity.payload, work);
});

test('recovery removes a queued own warning without dismissing unrelated active feedback', () => {
    const manager = createManager();
    manager.show({ id: 'other', sourceId: 'activity:42', type: 'incorrect' });
    const activity = createInteractiveActivity({ activityId: 41, feedback: manager });
    activity.handleActivityError({ activityId: 41, message: 'Offline' });
    assert.equal(manager.pending.sourceId, 'activity:41');
    activity.handleActivityRecovered({ activityId: 41 });
    assert.equal(manager.active.id, 'other');
    assert.equal(manager.pending, null);
});

test('unrelated child events and restored state never publish or consume sequence IDs', () => {
    const payloads = [];
    const activity = createInteractiveActivity({
        activityId: 41, initialStatus: 'completed', initialExplanation: '<p>Restored</p>',
        feedback: { show: payload => payloads.push(payload) },
    });
    assert.equal(activity.explanation, '<p>Restored</p>');
    activity.applyResponse({ status: 'completed', payload: { items: [] } });
    activity.handleActivityResult(result({ is_complete: true }, 42));
    activity.handleActivityError({ activityId: 42, message: 'Offline' });
    activity.handleActivityRecovered({ activityId: 42 });
    activity.handleActivityRetry({ activityId: 42 });
    assert.deepEqual(payloads, []);
    assert.equal(activity.resultSequence, 0);
    assert.equal(activity.warningSequence, 0);
});

test('skip publishes micro feedback and resume or practice clears only this source', async () => {
    const manager = createManager();
    const activity = createInteractiveActivity({ activityId: 41, preview: true, feedback: manager });
    await activity.skip();
    assert.equal(manager.active.type, 'informational');
    assert.equal(manager.active.presentation, 'micro');
    assert.equal(manager.active.id, 'activity:41:result:1');
    assert.equal(activity.hasOpenFeedback(), true);
    await activity.resume();
    assert.equal(manager.open, false);
    activity.handleActivityResult(result({ is_complete: true, status: 'completed' }));
    await activity.practice();
    assert.equal(manager.open, false);
    manager.show({ id: 'other', sourceId: 'activity:42', type: 'correct' });
    activity.handleActivityResult(result({ is_correct: false }));
    assert.equal(manager.active.id, 'other');
    await activity.practice();
    assert.equal(manager.active.id, 'other');
    assert.equal(manager.pending, null);
});

test('parent actions scope Continue and Practice Again to supported states', async () => {
    const activity = createInteractiveActivity({ activityId: 41, preview: true });
    const events = [];
    activity.$dispatch = (name, detail) => events.push([name, detail]);
    for (const event of [action('continue'), action('retry'), action('navigate'), action('retry', 'activity:42')]) {
        activity.handleLearningFeedbackAction(event);
        assert.equal(event.prevented, 0);
    }
    for (const status of ['completed', 'practice_completed', 'skipped']) {
        activity.status = status;
        const next = action('continue');
        activity.handleLearningFeedbackAction(next);
        assert.equal(next.prevented, 1);
    }
    for (const status of ['completed', 'practice_completed']) {
        activity.status = status;
        const retry = action('retry');
        activity.handleLearningFeedbackAction(retry);
        await Promise.resolve();
        assert.equal(retry.prevented, 1);
        assert.equal(activity.status, 'practice');
    }
    assert.equal(events.filter(([name]) => name === 'interactive-activity-continued').length, 3);
    assert.equal(events.filter(([name]) => name === 'interactive-activity-practice').length, 2);
});

test('real manager Practice Again does not dismiss unrelated feedback promoted by its action', async () => {
    const manager = createManager({ dispatchAction: detail => {
        const event = { detail, preventDefault() { this.defaultPrevented = true; } };
        activity.handleLearningFeedbackAction(event);
        return event.defaultPrevented === true;
    } });
    const activity = createInteractiveActivity({ activityId: 41, preview: true, feedback: manager });
    activity.handleActivityResult(result({ status: 'completed', is_complete: true }));
    manager.show({ id: 'other', sourceId: 'activity:42', type: 'incorrect' });
    assert.equal(manager.act('retry'), true);
    await Promise.resolve();
    assert.equal(activity.status, 'practice');
    assert.equal(manager.active.id, 'other');
});

test('compact feedback survives an unavailable or rejecting manager and warning request failures', async () => {
    for (const feedback of [undefined, { show: () => false }]) {
        const activity = createInteractiveActivity({ activityId: 41, feedback, skipUrl: '/skip' }, async () => response({ message: 'Offline' }, false));
        activity.handleActivityResult(result({ is_correct: false }));
        assert.equal(activity.feedback.type, 'incorrect');
        assert.equal(activity.hasOpenFeedback(), false);
        await activity.skip();
        assert.equal(activity.feedback.type, 'warning');
        assert.equal(activity.error, 'Offline');
        assert.equal(activity.warningSequence, 1);
    }
});

test('coordinator resolves the Alpine manager and reports lifecycle request warnings', async () => {
    const original = globalThis.Alpine;
    const manager = createManager();
    try {
        globalThis.Alpine = { store: name => { assert.equal(name, 'learningFeedback'); return manager; } };
        const activity = createInteractiveActivity({ activityId: 41, skipUrl: '/skip' }, async () => response({ message: 'Offline' }, false));
        await activity.skip();
        assert.equal(manager.active.id, 'activity:41:warning:1');
        assert.equal(manager.active.message, 'Offline');
    } finally {
        globalThis.Alpine = original;
    }
});

test('Blade roots wire scoped actions and retain compact fallback and rich explanations', () => {
    const base = '../../resources/views/learner/lessons/partials/interactive-activities/';
    for (const name of ['shell', 'matching', 'sequencing']) {
        const blade = readFileSync(new URL(`${base}${name}.blade.php`, import.meta.url), 'utf8');
        assert.match(blade, /@learning-feedback:action\.window="handleLearningFeedbackAction\(\$event\)"/);
        if (name === 'shell') {
            assert.match(blade, /x-show="feedback\.message && !hasOpenFeedback\(\)"/);
            assert.match(blade, /x-show="error && !hasOpenFeedback\(\)"/);
            assert.match(blade, /x-html="explanation"/);
            assert.match(blade, /@click="continueLearning\(\)"/);
            assert.match(blade, /@click="practice\(\)"/);
        }
    }
});
