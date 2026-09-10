import test from 'node:test';
import assert from 'node:assert/strict';
import {
    calculateConnectorLines,
    connectorPoint,
    createMatchingActivity,
    normalizeProposal,
} from '../../resources/js/matching-activity.js';
import { createInteractiveActivity } from '../../resources/js/interactive-activity.js';

const response = (data, ok = true) => ({ ok, json: async () => data });

test('a connection can begin from either side and normalizes to the server shape', () => {
    assert.deepEqual(normalizeProposal(
        { side: 'right', id: 'right-1' },
        { side: 'left', id: 'left-1' },
    ), { left_id: 'left-1', right_id: 'right-1' });
    assert.equal(normalizeProposal({ side: 'left', id: 'left-1' }, { side: 'left', id: 'left-2' }), null);
});

test('incorrect connections remain removable and are replaced from either endpoint', async () => {
    const activity = createMatchingActivity({ matchUrl: '/match' }, async () => response({
        status: 'in_progress', accepted: true, is_correct: false, is_complete: false,
    }));
    activity.startConnection('left', 'left-1');
    await activity.finishConnection('right', 'right-2');
    assert.deepEqual(activity.rejectedConnection, { left_id: 'left-1', right_id: 'right-2' });
    activity.removeRejectedConnection();
    assert.equal(activity.rejectedConnection, null);

    activity.startConnection('right', 'right-2');
    await activity.finishConnection('left', 'left-1');
    assert.deepEqual(activity.rejectedConnection, { left_id: 'left-1', right_id: 'right-2' });
});

test('available endpoints accept only an unlocked endpoint on the opposite side', () => {
    const activity = createMatchingActivity({
        initialMatchedPairs: [{ left_id: 'left-1', right_id: 'right-1' }],
    });

    assert.equal(activity.isEndpointAvailable('left', 'left-1'), false);
    assert.equal(activity.isEndpointAvailable('right', 'right-1'), false);
    assert.equal(activity.isEndpointAvailable('left', 'left-2'), true);
    activity.startConnection('left', 'left-2');
    assert.equal(activity.isValidTarget('left', 'left-3'), false);
    assert.equal(activity.isValidTarget('right', 'right-1'), false);
    assert.equal(activity.isValidTarget('right', 'right-2'), true);
});

test('only the active or hovered endpoint receives selected state', () => {
    const activity = createMatchingActivity();

    activity.startConnection('left', 'left-1');
    assert.equal(activity.endpointState('left', 'left-1'), 'selected');
    assert.equal(activity.endpointState('right', 'right-1'), 'idle');

    activity.hoveredEndpoint = { side: 'right', id: 'right-2' };
    assert.equal(activity.endpointState('right', 'right-1'), 'idle');
    assert.equal(activity.endpointState('right', 'right-2'), 'selected');
});

test('invalid drops cancel the provisional connection without a request', async () => {
    let calls = 0;
    const activity = createMatchingActivity({ matchUrl: '/match' }, async () => {
        calls += 1;
        return response({});
    });

    activity.startConnection('left', 'left-1');
    await activity.finishConnection('left', 'left-2');

    assert.equal(calls, 0);
    assert.equal(activity.activeEndpoint, null);
    assert.equal(activity.pendingConnection, null);
    assert.equal(activity.hoveredEndpoint, null);
});

test('a pending request stays visible until the server evaluates it', async () => {
    let resolveRequest;
    const activity = createMatchingActivity({ matchUrl: '/match' }, () => new Promise((resolve) => {
        resolveRequest = resolve;
    }));

    activity.startConnection('left', 'left-1');
    const pending = activity.finishConnection('right', 'right-1');
    assert.deepEqual(activity.pendingConnection, { left_id: 'left-1', right_id: 'right-1' });
    assert.equal(activity.requestState, 'pending');

    resolveRequest(response({ status: 'in_progress', accepted: true, is_correct: true, is_complete: false }));
    await pending;
    assert.equal(activity.pendingConnection, null);
    assert.equal(activity.requestState, 'idle');
});

test('failed requests retain their pending connection in a neutral error state', async () => {
    const activity = createMatchingActivity({ matchUrl: '/match' }, async () => response({ message: 'Offline' }, false));

    activity.startConnection('left', 'left-1');
    await activity.finishConnection('right', 'right-1');

    assert.deepEqual(activity.pendingConnection, { left_id: 'left-1', right_id: 'right-1' });
    assert.equal(activity.requestState, 'error');
    assert.equal(activity.rejectedConnection, null);
    assert.equal(activity.error, 'Offline');
});

test('confirmed connections lock both endpoints and publish the scoped result once', async () => {
    const events = [];
    const activity = createMatchingActivity({
        activityId: 'matching-42',
        matchUrl: '/match',
        leftItems: [{ id: 'left-1', value: 'Left one' }],
    }, async () => response({ status: 'completed', accepted: true, is_correct: true, is_complete: true }));
    activity.$dispatch = (name, detail) => events.push({ name, detail });

    activity.startConnection('right', 'right-1');
    await activity.finishConnection('left', 'left-1');

    assert.deepEqual(activity.matchedPairs, [{ left_id: 'left-1', right_id: 'right-1' }]);
    assert.equal(activity.isEndpointAvailable('left', 'left-1'), false);
    assert.equal(activity.isEndpointAvailable('right', 'right-1'), false);
    assert.deepEqual(events.at(-1), {
        name: 'interactive-activity-result',
        detail: {
            activityId: 'matching-42',
            type: 'matching',
            data: { status: 'completed', accepted: true, is_correct: true, is_complete: true },
            meta: { completed: 1, total: 1 },
        },
    });
});

test('keyboard Space, Enter, and Escape control the active connection', async () => {
    const activity = createMatchingActivity({ matchUrl: '/match' }, async () => response({
        status: 'in_progress', accepted: true, is_correct: false, is_complete: false,
    }));
    let prevented = 0;
    const event = (key) => ({ key, preventDefault: () => { prevented += 1; } });

    activity.activateEndpoint('left', 'left-1', event(' '));
    assert.deepEqual(activity.activeEndpoint, { side: 'left', id: 'left-1' });
    await activity.activateEndpoint('right', 'right-1', event('Enter'));
    assert.deepEqual(activity.rejectedConnection, { left_id: 'left-1', right_id: 'right-1' });
    activity.activateEndpoint('left', 'left-2', event('Enter'));
    activity.activateEndpoint('left', 'left-2', event('Escape'));
    assert.equal(activity.activeEndpoint, null);
    assert.equal(prevented, 4);
});

test('loadPayload rehydrates completed matches and clears transient connection state', async () => {
    const activity = createMatchingActivity({
        initialMatchedPairs: [{ left_id: 'stale-left', right_id: 'stale-right' }],
        leftItems: [{ id: 'stale-left', value: 'Stale left' }],
        rightItems: [{ id: 'stale-right', value: 'Stale right' }],
    });
    const payload = {
        left_items: [{ id: 'left-1', value: 'Left one' }],
        right_items: [{ id: 'right-1', value: 'Right one' }],
        completed_matches: [{ left_id: 'left-1', right_id: 'right-1' }],
    };
    let refreshes = 0;
    activity.activeEndpoint = { side: 'left', id: 'stale-left' };
    activity.pendingConnection = { left_id: 'stale-left', right_id: 'stale-right' };
    activity.rejectedConnection = { left_id: 'stale-left', right_id: 'stale-right' };
    activity.refreshConnectors = () => { refreshes += 1; return activity; };

    activity.loadPayload(payload, 'practice');
    await new Promise((resolve) => queueMicrotask(resolve));

    assert.deepEqual(activity.leftItems, payload.left_items);
    assert.deepEqual(activity.rightItems, payload.right_items);
    assert.deepEqual(activity.matchedPairs, payload.completed_matches);
    assert.notEqual(activity.matchedPairs, payload.completed_matches);
    assert.equal(activity.status, 'practice');
    assert.equal(activity.activeEndpoint, null);
    assert.equal(activity.pendingConnection, null);
    assert.equal(activity.rejectedConnection, null);
    assert.equal(refreshes, 1);
});

test('connector geometry uses dot-centered line coordinates', () => {
    assert.deepEqual(connectorPoint({ left: 20, top: 30, width: 100, height: 20 }, { left: 10, top: 20 }), { x: 60, y: 20 });
    assert.deepEqual(calculateConnectorLines(
        [{ left: 20, top: 30, width: 100, height: 20 }],
        [{ left: 300, top: 50, width: 80, height: 40 }],
        { left: 10, top: 20 },
    ), [{ x1: 60, y1: 20, x2: 330, y2: 50 }]);
});

test('Preview matching posts to the evaluator and rotates its token', async () => {
    const events = [];
    const child = createMatchingActivity({
        activityId: 'matching-preview', preview: true, previewToken: 'token-1', previewEvaluateUrl: '/preview/evaluate',
        leftItems: [{ id: 'left-1', value: 'Left one' }],
    }, async (url, options) => {
        assert.equal(url, '/preview/evaluate');
        assert.deepEqual(JSON.parse(options.body), {
            preview_token: 'token-1', action: 'match', left_id: 'left-1', right_id: 'right-1',
        });
        return response({ status: 'practice_completed', is_correct: true, is_complete: true, preview_token: 'token-2' });
    });
    const parent = createInteractiveActivity({ activityId: 'matching-preview' });
    child.$dispatch = (name, detail) => events.push({ name, detail });

    child.startConnection('left', 'left-1');
    await child.finishConnection('right', 'right-1');

    const result = events.find(({ name }) => name === 'interactive-activity-result').detail;
    assert.equal(result.data.is_complete, true);
    assert.equal(child.previewToken, 'token-2');
    parent.handleActivityResult(result);
    assert.deepEqual(parent.feedback, { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' });
});
