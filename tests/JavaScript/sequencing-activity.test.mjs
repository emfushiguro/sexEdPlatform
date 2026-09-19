import test from 'node:test';
import assert from 'node:assert/strict';
import { createSequencingActivity, moveItem } from '../../resources/js/sequencing-activity.js';
import { createInteractiveActivity } from '../../resources/js/interactive-activity.js';

const response = (data, ok = true) => ({ ok, json: async () => data });

test('moveItem obeys bounds and preserves one shared order array', () => {
    assert.deepEqual(moveItem(['one', 'two', 'three'], 1, -1), ['two', 'one', 'three']);
    assert.deepEqual(moveItem(['one', 'two', 'three'], 0, -1), ['one', 'two', 'three']);
    assert.deepEqual(moveItem(['one', 'two', 'three'], 2, 1), ['one', 'two', 'three']);
});

test('buttons and keyboard use the same order primitive', () => {
    const activity = createSequencingActivity({ initialOrder: ['one', 'two', 'three'] });
    activity.move(2, -1);
    activity.keyboardMove(1, -1, { key: 'ArrowUp', preventDefault() {} });
    assert.deepEqual(activity.order, ['three', 'one', 'two']);
    assert.equal(activity.positionLabel(1), '2');
});

test('pointer reorder uses the shared order and a debounced full state save', async () => {
    const calls = [];
    const activity = createSequencingActivity({
        initialOrder: ['one', 'two', 'three'],
        stateUrl: '/state',
        saveDebounceMs: 1,
    }, async (url, options) => {
        calls.push({ url, options });
        return response({ status: 'in_progress' });
    });

    activity.startItemDrag(0).dropItem(2);
    await new Promise((resolve) => setTimeout(resolve, 10));

    assert.deepEqual(activity.order, ['two', 'three', 'one']);
    assert.equal(calls.length, 1);
    assert.deepEqual(JSON.parse(calls[0].options.body), {
        revision: 1,
        state: { item_order: ['two', 'three', 'one'] },
    });
});

test('check flushes state, locks requests, and preserves order after a failed check', async () => {
    const calls = [];
    const activity = createSequencingActivity({
        initialOrder: ['one', 'two', 'three'],
        stateUrl: '/state',
        checkUrl: '/check',
    }, async (url, options) => {
        calls.push({ url, options });
        return response({ message: 'Offline' }, false);
    });

    activity.move(0, 1);
    await activity.checkAnswer();

    assert.deepEqual(activity.order, ['two', 'one', 'three']);
    assert.deepEqual(calls.map(({ url }) => url), ['/state', '/check']);
    assert.equal(activity.error, 'Offline');
    assert.equal(activity.submitting, false);
});

test('correct state locks controls and practice resets local status', async () => {
    const activity = createSequencingActivity({ initialOrder: ['one', 'two', 'three'], checkUrl: '/check' }, async () => response({
        status: 'completed',
        is_correct: true,
        is_complete: true,
    }));

    await activity.checkAnswer();
    assert.equal(activity.isLocked(), true);
    activity.resetPractice();
    assert.equal(activity.status, 'practice');
    assert.deepEqual(activity.order, ['one', 'two', 'three']);
});

test('practice completion locks sequencing controls until practice is reset', () => {
    const activity = createSequencingActivity({ initialStatus: 'practice_completed' });

    assert.equal(activity.isLocked(), true);
    activity.retryAnswer();
    assert.deepEqual(activity.positionResults, []);
});

test('successful sequencing responses dispatch state and a scoped result for the configured activity', async () => {
    const events = [];
    const activity = createSequencingActivity({
        activityId: 'sequencing-42',
        initialOrder: ['one', 'two', 'three'],
        checkUrl: '/check',
    }, async () => response({
        status: 'completed',
        is_correct: true,
        is_complete: true,
    }));
    activity.$dispatch = (name, detail) => events.push({ name, detail });

    await activity.checkAnswer();

    assert.deepEqual(events, [
        {
            name: 'interactive-activity-state',
            detail: {
                activityId: 'sequencing-42',
                status: 'completed',
                data: { status: 'completed', is_correct: true, is_complete: true },
            },
        },
        {
            name: 'interactive-activity-result',
            detail: {
                activityId: 'sequencing-42',
                type: 'sequencing',
                data: { status: 'completed', is_correct: true, is_complete: true },
                meta: {},
            },
        },
    ]);
});

test('correct preview sequencing result completes shared feedback', async () => {
    const events = [];
    const child = createSequencingActivity({
        activityId: 'sequencing-preview',
        preview: true,
        previewToken: 'token-1',
        previewEvaluateUrl: '/preview/evaluate',
        initialOrder: ['one', 'two'],
    }, async (url, options) => {
        assert.equal(url, '/preview/evaluate');
        assert.deepEqual(JSON.parse(options.body), {
            preview_token: 'token-1', action: 'check_sequence', item_order: ['one', 'two'],
        });
        return response({ status: 'practice_completed', is_correct: true, is_complete: true, preview_token: 'token-2' });
    });
    const parent = createInteractiveActivity({ activityId: 'sequencing-preview' });
    child.$dispatch = (name, detail) => events.push({ name, detail });

    await child.checkAnswer();

    const result = events.find(({ name }) => name === 'interactive-activity-result').detail;
    assert.equal(result.data.is_complete, true);
    assert.equal(child.previewToken, 'token-2');
    parent.handleActivityResult(result);
    assert.deepEqual(parent.feedback, { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' });
});

test('failed sequencing request dispatches a scoped error', async () => {
    const events = [];
    const activity = createSequencingActivity({
        activityId: 'sequencing-42',
        initialOrder: ['one'],
        checkUrl: '/check',
    }, async () => response({ message: 'Offline' }, false));
    activity.$dispatch = (name, detail) => events.push({ name, detail });

    await activity.checkAnswer();

    assert.deepEqual(events, [{
        name: 'interactive-activity-error',
        detail: { activityId: 'sequencing-42', message: 'Offline' },
    }]);
});

test('failed sequencing state save dispatches a scoped error', async () => {
    const events = [];
    const activity = createSequencingActivity({
        activityId: 'sequencing-42',
        initialOrder: ['one'],
        stateUrl: '/state',
    }, async () => response({ message: 'Offline' }, false));
    activity.$dispatch = (name, detail) => events.push({ name, detail });

    await activity.persistState();

    assert.deepEqual(events, [{
        name: 'interactive-activity-error',
        detail: { activityId: 'sequencing-42', message: 'Offline' },
    }]);
});

test('sequencing exposes per-position feedback and retry preserves the current arrangement', async () => {
    const activity = createSequencingActivity({
        initialOrder: ['one', 'two', 'three'],
        checkUrl: '/check',
    }, async () => response({
        status: 'in_progress',
        is_correct: false,
        is_complete: false,
        position_results: [
            { item_id: 'two', position: 1, is_correct: false },
            { item_id: 'one', position: 2, is_correct: false },
            { item_id: 'three', position: 3, is_correct: true },
        ],
    }));

    await activity.checkAnswer();

    assert.equal(activity.itemState('two', 0), 'incorrect');
    assert.equal(activity.itemState('one', 1), 'incorrect');
    assert.equal(activity.itemState('three', 2), 'correct');
    assert.equal(activity.hasIncorrectResults(), true);

    const arrangement = [...activity.order];
    activity.retryAnswer();

    assert.deepEqual(activity.order, arrangement);
    assert.deepEqual(activity.positionResults, []);
    assert.equal(activity.hasIncorrectResults(), false);
});

test('completed response rehydrates the persisted correct sequence instead of validating stale local order', async () => {
    const activity = createSequencingActivity({
        initialOrder: ['two', 'one', 'three'],
        items: [
            { id: 'two', value: 'Second' },
            { id: 'one', value: 'First' },
            { id: 'three', value: 'Third' },
        ],
        checkUrl: '/check',
    }, async () => response({
        status: 'completed',
        accepted: false,
        is_correct: true,
        is_complete: true,
        payload: {
            items: [
                { id: 'one', value: 'First' },
                { id: 'two', value: 'Second' },
                { id: 'three', value: 'Third' },
            ],
        },
    }));

    await activity.checkAnswer();

    assert.deepEqual(activity.order, ['one', 'two', 'three']);
    assert.equal(activity.isLocked(), true);
});

test('a recovered sequencing state save clears its error and dispatches a scoped recovery', async () => {
    const events = [];
    const responses = [response({ message: 'Offline' }, false), response({ status: 'in_progress' })];
    const activity = createSequencingActivity({
        activityId: 'sequencing-42',
        initialOrder: ['one'],
        stateUrl: '/state',
    }, async () => responses.shift());
    activity.$dispatch = (name, detail) => events.push({ name, detail });

    await activity.persistState();
    await activity.persistState();

    assert.equal(activity.error, '');
    assert.deepEqual(events, [
        {
            name: 'interactive-activity-error',
            detail: { activityId: 'sequencing-42', message: 'Offline' },
        },
        {
            name: 'interactive-activity-recovered',
            detail: { activityId: 'sequencing-42' },
        },
    ]);
});

test('loadPayload replaces sequencing state from a practice payload', () => {
    const activity = createSequencingActivity({
        items: [{ id: 'stale', value: 'Stale item' }],
        initialOrder: ['stale'],
    });
    const payload = {
        items: [
            { id: 'item-2', value: 'Second item' },
            { id: 'item-1', value: 'First item' },
        ],
    };
    activity.feedback = 'Stale feedback';
    activity.error = 'Stale error';
    activity.dragIndex = 0;
    activity.dragOverIndex = 0;

    activity.loadPayload(payload, 'practice');

    assert.deepEqual(activity.items, payload.items);
    assert.deepEqual(activity.order, ['item-2', 'item-1']);
    assert.deepEqual(activity.initialOrder, ['item-2', 'item-1']);
    assert.equal(activity.status, 'practice');
    assert.equal(activity.feedback, '');
    assert.equal(activity.error, '');
    assert.equal(activity.dragIndex, null);
    assert.equal(activity.dragOverIndex, null);
});

test('pointer drag keeps the committed order stable until drop', () => {
    const activity = createSequencingActivity({ initialOrder: ['one', 'two', 'three'] });

    activity.beginPointerDrag(0, { clientX: 20, clientY: 40 });
    activity.setDragTarget(2);

    assert.deepEqual(activity.order, ['one', 'two', 'three']);
    assert.deepEqual(activity.candidateOrder, ['two', 'three', 'one']);
    activity.dropPointerDrag();
    assert.deepEqual(activity.order, ['two', 'three', 'one']);
});

test('keyboard drag supports pickup, movement, drop, and cancellation', () => {
    const activity = createSequencingActivity({ initialOrder: ['one', 'two', 'three'] });
    const event = (key) => ({ key, preventDefault() {} });

    activity.handleDragKey(1, event(' '));
    activity.handleDragKey(1, event('Home'));
    activity.handleDragKey(1, event('Enter'));
    assert.deepEqual(activity.order, ['two', 'one', 'three']);
    assert.match(activity.dragAnnouncement, /Dropped/);

    activity.handleDragKey(1, event('Enter'));
    activity.handleDragKey(1, event('Escape'));
    assert.deepEqual(activity.order, ['two', 'one', 'three']);
    assert.match(activity.dragAnnouncement, /Cancelled/);
});

test('pointer cancellation restores the committed order and does not save', async () => {
    let calls = 0;
    const activity = createSequencingActivity({
        initialOrder: ['one', 'two', 'three'],
        stateUrl: '/state',
        saveDebounceMs: 1,
    }, async () => {
        calls += 1;
        return response({ status: 'in_progress' });
    });

    activity.beginPointerDrag(0, { clientX: 20, clientY: 40 });
    activity.setDragTarget(2);
    activity.cancelDrag();
    await new Promise((resolve) => setTimeout(resolve, 10));

    assert.deepEqual(activity.order, ['one', 'two', 'three']);
    assert.deepEqual(activity.candidateOrder, ['one', 'two', 'three']);
    assert.equal(calls, 0);
    assert.match(activity.dragAnnouncement, /Cancelled/);
});

test('sequencing plays selection only when a pointer or keyboard reorder commits', () => {
    const played = [];
    const audio = { play: (key) => played.push(key) };
    const activity = createSequencingActivity({
        audio,
        initialOrder: ['one', 'two', 'three'],
    });

    activity.move(0, -1);
    assert.deepEqual(played, []);
    activity.move(1, -1);
    assert.deepEqual(played, ['selection']);

    activity.beginPointerDrag(0, { clientX: 10, clientY: 10 });
    activity.setDragTarget(0);
    assert.deepEqual(played, ['selection']);
    activity.dropPointerDrag();
    assert.deepEqual(played, ['selection']);

    activity.beginPointerDrag(0, { clientX: 10, clientY: 10 });
    activity.setDragTarget(2);
    assert.deepEqual(played, ['selection']);
    activity.dropPointerDrag();
    assert.deepEqual(played, ['selection', 'selection']);

    const event = (key) => ({ key, preventDefault() {} });
    activity.handleDragKey(2, event('Enter'));
    activity.handleDragKey(2, event('Home'));
    assert.deepEqual(played, ['selection', 'selection']);
    activity.handleDragKey(2, event('Enter'));
    assert.deepEqual(played, ['selection', 'selection', 'selection']);
});

test('sequencing stays silent for locked moves, cancellation, and checkAnswer', async () => {
    const played = [];
    const activity = createSequencingActivity({
        audio: { play: (key) => played.push(key) },
        initialOrder: ['one', 'two'],
        initialStatus: 'completed',
        checkUrl: '/check',
    }, async () => response({ status: 'completed', is_correct: true, is_complete: true }));

    activity.move(1, -1);
    activity.beginPointerDrag(0, { clientX: 10, clientY: 10 });
    activity.setDragTarget(1);
    activity.cancelDrag();
    await activity.checkAnswer();

    assert.deepEqual(played, []);
});
