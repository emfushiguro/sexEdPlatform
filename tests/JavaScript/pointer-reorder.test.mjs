import test from 'node:test';
import assert from 'node:assert/strict';
import {
    createReorderSession,
    edgeScrollDelta,
    keyboardDestination,
    moveAt,
} from '../../resources/js/pointer-reorder.js';

test('moveAt returns a bounded copy without mutating the source array', () => {
    const order = ['one', 'two', 'three'];

    assert.deepEqual(moveAt(order, 0, 2), ['two', 'three', 'one']);
    assert.deepEqual(order, ['one', 'two', 'three']);
    assert.deepEqual(moveAt(order, -1, 2), order);
    assert.deepEqual(moveAt(order, 0, 3), order);
    assert.deepEqual(moveAt(order, 1, 1), order);
});

test('reorder session does not mutate order until commit', () => {
    const session = createReorderSession();
    const order = ['one', 'two', 'three'];

    session.begin(0).target(2);
    assert.equal(session.active(), true);
    assert.deepEqual(order, ['one', 'two', 'three']);
    assert.deepEqual(session.commit(order), ['two', 'three', 'one']);
    assert.equal(session.active(), false);
});

test('cancelled reorder sessions return a copy and clear their state', () => {
    const session = createReorderSession().begin(1).target(2);
    const order = ['one', 'two', 'three'];

    session.cancel();
    assert.equal(session.active(), false);
    assert.deepEqual(session.commit(order), order);
    assert.notEqual(session.commit(order), order);
});

test('keyboard and edge destinations are bounded', () => {
    assert.equal(keyboardDestination('Home', 2, 4), 0);
    assert.equal(keyboardDestination('End', 1, 4), 3);
    assert.equal(keyboardDestination('ArrowUp', 0, 4), 0);
    assert.equal(keyboardDestination('ArrowDown', 3, 4), 3);
    assert.equal(keyboardDestination('PageDown', 2, 4), 2);
    assert.equal(edgeScrollDelta(10, 800, 72, 16), -16);
    assert.equal(edgeScrollDelta(790, 800, 72, 16), 16);
    assert.equal(edgeScrollDelta(400, 800, 72, 16), 0);
});
