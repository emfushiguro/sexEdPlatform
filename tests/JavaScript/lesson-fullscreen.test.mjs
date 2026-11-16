import test from 'node:test';
import assert from 'node:assert/strict';
import { createLessonFullscreen } from '../../resources/js/lesson-fullscreen.js';

test('fullscreen enters and restores the previous sidebar state on exit', async () => {
    let active = false;
    const events = [];
    const document = { documentElement: { requestFullscreen: async () => { active = true; } },
        get fullscreenElement() { return active ? this.documentElement : null; },
        exitFullscreen: async () => { active = false; } };
    const storage = new Map();
    const state = createLessonFullscreen({ document, storage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key) }, dispatch: (name, detail) => events.push([name, detail]) });
    await state.toggle();
    assert.equal(state.focused, true);
    assert.equal(events.at(-1)[0], 'lesson-focus-enter');
    await state.toggle();
    assert.equal(state.focused, false);
    assert.equal(events.at(-1)[0], 'lesson-focus-exit');
});

test('fullscreen denial leaves the lesson usable with an explanation', async () => {
    const state = createLessonFullscreen({ document: { documentElement: { requestFullscreen: async () => { throw Error('denied'); } } }, storage: { getItem: () => null, setItem() {}, removeItem() {} }, dispatch() {} });
    await state.toggle();
    assert.equal(state.focused, false);
    assert.match(state.message, /unavailable/i);
});

test('browser exit restores the ordinary lesson view', async () => {
    let active = false;
    const events = [];
    const document = { documentElement: { requestFullscreen: async () => { active = true; } }, get fullscreenElement() { return active ? this.documentElement : null; } };
    const state = createLessonFullscreen({ document, storage: { getItem: () => null, setItem() {}, removeItem() {} }, dispatch: name => events.push(name) });
    await state.enter();
    active = false;
    state.sync();
    assert.equal(state.focused, false);
    assert.equal(events.at(-1), 'lesson-focus-exit');
});

test('focused mode survives lesson navigation and can re-enter browser fullscreen', async () => {
    const values = new Map([['lesson-focus-mode', '1']]);
    let active = false;
    const document = { documentElement: { requestFullscreen: async () => { active = true; } }, get fullscreenElement() { return active ? this.documentElement : null; } };
    const state = createLessonFullscreen({ document, storage: { getItem: key => values.get(key), setItem: (key, value) => values.set(key, value), removeItem: key => values.delete(key) }, dispatch() {} });
    assert.equal(state.focused, true);
    assert.equal(state.nativeActive, false);
    await state.enter();
    assert.equal(state.nativeActive, true);
    assert.equal(active, true);
});
