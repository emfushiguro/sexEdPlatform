import test from 'node:test';
import assert from 'node:assert/strict';
import { calculateConnectorLines, createMatchingActivity } from '../../resources/js/matching-activity.js';

const response = (data, ok = true) => ({ ok, json: async () => data });

test('matching selections expose aria state and lock completed pairs', () => {
    const activity = createMatchingActivity({
        initialMatchedPairs: [{ left_id: 'left-1', right_id: 'right-1' }],
    });

    assert.equal(activity.isLeftMatched('left-1'), true);
    assert.equal(activity.isRightMatched('right-1'), true);
    assert.equal(activity.ariaPressed('left', 'left-1'), 'false');
    activity.selectLeft('left-2').selectRight('right-2');
    assert.equal(activity.ariaPressed('left', 'left-2'), 'true');
    assert.equal(activity.ariaPressed('right', 'right-2'), 'true');
});

test('incorrect proposals clear only the proposal and announce feedback', async () => {
    const activity = createMatchingActivity({ matchUrl: '/match' }, async () => response({
        status: 'in_progress',
        accepted: true,
        is_correct: false,
        is_complete: false,
    }));

    activity.selectLeft('left-1').selectRight('right-2');
    await activity.submitMatch();

    assert.deepEqual(activity.matchedPairs, []);
    assert.equal(activity.leftId, null);
    assert.equal(activity.rightId, null);
    assert.equal(activity.feedback, 'Not quite—try another match');
});

test('request errors preserve previously completed pairs', async () => {
    const activity = createMatchingActivity({
        matchUrl: '/match',
        initialMatchedPairs: [{ left_id: 'left-1', right_id: 'right-1' }],
    }, async () => response({ message: 'Offline' }, false));

    activity.selectLeft('left-2').selectRight('right-2');
    await activity.submitMatch();

    assert.deepEqual(activity.matchedPairs, [{ left_id: 'left-1', right_id: 'right-1' }]);
    assert.equal(activity.error, 'Offline');
});

test('successful matching responses dispatch state and a scoped result for the configured activity', async () => {
    const events = [];
    const activity = createMatchingActivity({
        activityId: 'matching-42',
        matchUrl: '/match',
        leftItems: [{ id: 'left-1', value: 'Left one' }],
    }, async () => response({
        status: 'completed',
        is_correct: true,
        is_complete: true,
    }));
    activity.$dispatch = (name, detail) => events.push({ name, detail });

    activity.selectLeft('left-1').selectRight('right-1');
    await activity.submitMatch();

    assert.deepEqual(events, [
        {
            name: 'interactive-activity-state',
            detail: {
                activityId: 'matching-42',
                status: 'completed',
                data: { status: 'completed', is_correct: true, is_complete: true },
            },
        },
        {
            name: 'interactive-activity-result',
            detail: {
                activityId: 'matching-42',
                type: 'matching',
                data: { status: 'completed', is_correct: true, is_complete: true },
                meta: { completed: 1, total: 1 },
            },
        },
    ]);
});

test('loadPayload replaces matching state from a practice payload', async () => {
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
    activity.leftId = 'stale-left';
    activity.rightId = 'stale-right';
    activity.feedback = 'Stale feedback';
    activity.error = 'Stale error';
    activity.refreshConnectors = () => {
        refreshes += 1;
        return activity;
    };

    activity.loadPayload(payload, 'practice');
    await new Promise((resolve) => queueMicrotask(resolve));

    assert.deepEqual(activity.leftItems, payload.left_items);
    assert.deepEqual(activity.rightItems, payload.right_items);
    assert.deepEqual(activity.matchedPairs, payload.completed_matches);
    assert.notEqual(activity.matchedPairs, payload.completed_matches);
    assert.equal(activity.status, 'practice');
    assert.equal(activity.leftId, null);
    assert.equal(activity.rightId, null);
    assert.equal(activity.feedback, '');
    assert.equal(activity.error, '');
    assert.equal(refreshes, 1);
});

test('connector geometry is derived from item centers relative to the container', () => {
    assert.deepEqual(calculateConnectorLines(
        [{ left: 20, top: 30, width: 100, height: 20 }],
        [{ left: 300, top: 50, width: 80, height: 40 }],
        { left: 10, top: 20 },
    ), [{ x1: 60, y1: 20, x2: 330, y2: 50 }]);
});
