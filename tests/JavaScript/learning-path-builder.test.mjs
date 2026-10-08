import test from 'node:test';
import assert from 'node:assert/strict';
import { createLearningPathBuilder } from '../../resources/js/learning-path-builder.js';

const fixtures = [
    { id: 11, title: 'Boundaries', categories: ['teens'], thumbnail: null },
    { id: 22, title: 'Communication', categories: ['teens', 'adults'], thumbnail: '/communication.png' },
    { id: 33, title: 'Family safety', categories: ['kids'], thumbnail: null },
    { id: 44, title: 'Retired module', categories: ['teens'], learnerVisible: false, thumbnail: null },
];

const keyEvent = (key) => ({ key, preventDefault() {} });

const pointerEvent = (overrides = {}) => ({
    clientX: 120,
    clientY: 180,
    currentTarget: {
        closest: () => ({ getBoundingClientRect: () => ({ width: 300, height: 64 }) }),
    },
    target: { closest: () => null },
    ...overrides,
});

test('adds modules matching any selected category and filters candidates without duplicates', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11],
        categories: ['teens', 'kids'],
    });

    assert.deepEqual(builder.eligibleModules.map((module) => module.id), [22, 33]);
    builder.add(22);
    builder.add(22);
    builder.add(33);

    assert.deepEqual(builder.moduleIds, [11, 22, 33]);
    assert.deepEqual(builder.eligibleModules, []);
    builder.remove(11);
    assert.deepEqual(builder.moduleIds, [22, 33]);
});

test('category changes expose selected modules that no longer match', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11, 22],
        categories: ['teens'],
    });

    builder.setCategories(['kids']);

    assert.deepEqual(builder.mismatchedModuleIds, [11, 22]);
    assert.equal(builder.moduleFor(22).thumbnail, '/communication.png');
});

test('unavailable selected modules are separated from category mismatches', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11, 44],
        categories: ['kids'],
    });

    assert.deepEqual(builder.mismatchedModuleIds, [11]);
    assert.deepEqual(builder.unavailableModuleIds, [44]);
});

test('move buttons and keyboard ordering share bounded destinations', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11, 22, 33],
        categories: ['teens'],
    });

    builder.moveDown(0);
    builder.moveUp(1);
    assert.deepEqual(builder.moduleIds, [11, 22, 33]);

    builder.handleDragKey(1, keyEvent(' '));
    assert.equal(builder.isDragging(), true);
    assert.match(builder.dragAnnouncement, /picked up/i);
    builder.handleDragKey(1, keyEvent('Home'));
    builder.handleDragKey(1, keyEvent('Enter'));

    assert.deepEqual(builder.moduleIds, [22, 11, 33]);
    assert.match(builder.dragAnnouncement, /position 1 of 3/i);
});

test('pointer cancellation leaves the original order intact', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11, 22, 33],
        categories: ['teens'],
    });

    builder.beginPointerDrag(0, pointerEvent());
    builder.setDragTarget(2);
    assert.deepEqual(builder.candidateOrder, [22, 33, 11]);
    builder.dropPointerDrag(pointerEvent({
        clientX: -10,
        clientY: -10,
        target: { closest: () => null },
    }));

    assert.deepEqual(builder.moduleIds, [11, 22, 33]);
    assert.deepEqual(builder.candidateOrder, [11, 22, 33]);
    assert.match(builder.dragAnnouncement, /cancelled/i);
});

test('pointer drops commit the preview and announce the final position', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11, 22, 33],
        categories: ['teens'],
    });

    builder.beginPointerDrag(0, pointerEvent());
    builder.setDragTarget(2);
    builder.dropPointerDrag();

    assert.deepEqual(builder.moduleIds, [22, 33, 11]);
    assert.match(builder.dragAnnouncement, /dropped/i);
    assert.match(builder.dragAnnouncement, /position 3 of 3/i);
});

test('list mutations cancel an active drag before changing the order', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11, 22, 33],
        categories: ['teens'],
    });

    builder.beginPointerDrag(0, pointerEvent());
    builder.setDragTarget(2);
    builder.moveDown(0);

    assert.equal(builder.isDragging(), false);
    assert.deepEqual(builder.moduleIds, [22, 11, 33]);
    builder.dropPointerDrag();
    assert.deepEqual(builder.moduleIds, [22, 11, 33]);
});
