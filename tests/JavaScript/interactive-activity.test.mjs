import test from 'node:test';
import assert from 'node:assert/strict';
import { createInteractiveActivity } from '../../resources/js/interactive-activity.js';

const response = (data, ok = true) => ({ ok, json: async () => data });

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
        kind: 'skipped', message: 'Activity skipped. You can resume when ready.', icon: 'skip',
    });
    assert.deepEqual(JSON.parse(calls[0].options.body), { revision: 4 });
    assert.equal(calls[0].options.headers['X-CSRF-TOKEN'], 'token');
});

test('common activity retains state and reports request errors', async () => {
    const activity = createInteractiveActivity({ skipUrl: '/skip' }, async () => response({ message: 'Offline' }, false));

    await activity.skip();

    assert.equal(activity.error, 'Offline');
    assert.deepEqual(activity.feedback, { kind: 'error', message: 'Offline', icon: 'warning' });
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
    assert.deepEqual(activity.feedback, { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' });
});

test('common activity reports only its child request errors through shared feedback', () => {
    const activity = createInteractiveActivity({ activityId: 41 });

    activity.handleActivityError({ activityId: 42, message: 'Ignore me' });
    assert.equal(activity.error, '');

    activity.handleActivityError({ activityId: 41, message: 'Offline' });
    assert.equal(activity.error, 'Offline');
    assert.deepEqual(activity.feedback, { kind: 'error', message: 'Offline', icon: 'warning' });
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
    assert.deepEqual(activity.feedback, { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' });
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
