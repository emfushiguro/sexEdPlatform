import test from 'node:test';
import assert from 'node:assert/strict';
import { FEEDBACK_CONFIG, createLearningFeedbackManager, normalizeLearningFeedback } from '../../resources/js/learning-feedback.js';

// Lifecycle tests use injected timers so no real timeout keeps Node alive.
const createManager = (dependencies = {}) => createLearningFeedbackManager({
    setTimer: () => 0, clearTimer: () => {}, ...dependencies,
});

function clock() {
    let nextId = 0;
    const timers = new Map();
    return {
        timers,
        setTimer(callback, delay) { timers.set(++nextId, { callback, delay }); return nextId; },
        clearTimer(id) { timers.delete(id); },
        fire() {
            const [id, timer] = timers.entries().next().value;
            timers.delete(id);
            timer.callback();
        },
    };
}

const action = (intent, overrides = {}) => ({ id: intent, intent, label: intent, ...overrides });
const focusTarget = () => ({ isConnected: true, focused: 0, focus() { this.focused++; } });
const key = (value, shiftKey = false) => ({ key: value, shiftKey, prevented: 0, preventDefault() { this.prevented++; } });

const payload = (overrides = {}) => ({
    id: 'checkpoint:17:result:1',
    sourceId: 'checkpoint:17',
    type: 'correct',
    message: 'That answer is correct.',
    ...overrides,
});

test('configuration maps supported types to fixed local assets and existing sounds', () => {
    assert.deepEqual(Object.keys(FEEDBACK_CONFIG), [
        'correct', 'incorrect', 'try_again', 'activity_completed',
        'lesson_completed', 'module_completed', 'informational', 'warning',
    ]);
    assert.equal(FEEDBACK_CONFIG.correct.illustration, '/assets/illustrations/learning-feedback/correct-answer.svg');
    assert.equal(FEEDBACK_CONFIG.incorrect.sound, 'incorrect');
    assert.equal(FEEDBACK_CONFIG.lesson_completed.sound, 'complete');
    assert.equal(FEEDBACK_CONFIG.module_completed.sound, 'success');
    assert.equal(FEEDBACK_CONFIG.warning.sound, null);
});

test('normalization strips markup, clamps presentation/actions, and rejects cross-origin navigation', () => {
    const normalized = normalizeLearningFeedback(payload({
        title: '<b>Excellent</b>',
        explanation: '<script>alert(1)</script><p>Review the idea.</p>',
        presentation: 'completion_modal',
        illustration: 'https://evil.example/a.svg',
        sound: 'invented',
        actions: [
            { id: 'go', intent: 'navigate', label: '<b>Continue</b>', url: '/learn/next?from=feedback' },
            { id: 'bad', intent: 'navigate', label: 'Leave', url: 'https://evil.example/' },
            { id: 'callback', intent: 'run-code', label: 'Run', callback: 'alert(1)' },
        ],
    }), 'https://conscious.test');

    assert.equal(normalized.title, 'Excellent');
    assert.equal(normalized.explanation, 'alert(1) Review the idea.');
    assert.equal(normalized.presentation, 'card');
    assert.equal(normalized.illustration, FEEDBACK_CONFIG.correct.illustration);
    assert.equal(normalized.sound, 'correct');
    assert.deepEqual(normalized.actions, [
        { id: 'go', intent: 'navigate', label: 'Continue', url: '/learn/next?from=feedback' },
    ]);
});

test('unknown type and missing ids are rejected', () => {
    assert.equal(normalizeLearningFeedback(payload({ type: 'celebration' })), null);
    assert.equal(normalizeLearningFeedback(payload({ id: '' })), null);
    assert.equal(normalizeLearningFeedback(payload({ sourceId: '' })), null);
});

test('duplicates are rejected and completion supersedes routine feedback', () => {
    const sounds = [];
    const manager = createManager({ audio: { play: (key) => sounds.push(key) } });
    assert.equal(manager.show(payload()), true);
    assert.equal(manager.show(payload()), false);
    manager.show(payload({ id: 'warning:1', type: 'warning', message: 'Connection interrupted.' }));
    assert.equal(manager.pending.id, 'warning:1');
    manager.show(payload({ id: 'lesson:9:complete', sourceId: 'lesson:9', type: 'lesson_completed', message: 'Lesson complete.' }));
    assert.equal(manager.active.id, 'lesson:9:complete');
    assert.equal(manager.pending, null);
    assert.deepEqual(sounds, ['correct', 'complete']);
});

test('pending sound waits until the event is visible', () => {
    const sounds = [];
    const manager = createManager({ audio: { play: (key) => sounds.push(key) } });
    manager.show(payload({ id: 'incorrect:1', type: 'incorrect' }));
    manager.show(payload({ id: 'correct:2' }));
    assert.deepEqual(sounds, ['incorrect']);
    manager.dismiss('test');
    assert.deepEqual(sounds, ['incorrect', 'correct']);
});

test('fixed Retry closes only when the source handles the cancelable action', () => {
    const actions = [];
    const manager = createManager({
        dispatchAction: (detail) => { actions.push(detail); return detail.intent === 'retry'; },
    });
    manager.show(payload({ type: 'incorrect', actions: [{ id: 'retry', intent: 'retry', label: 'Try again' }] }));
    assert.equal(manager.act('retry'), true);
    assert.equal(manager.open, false);
    assert.deepEqual(actions[0], {
        feedbackId: 'checkpoint:17:result:1', sourceId: 'checkpoint:17', intent: 'retry',
    });
});

test('unhandled Continue remains open and directs the learner to existing controls', () => {
    const manager = createManager({ dispatchAction: () => false });
    manager.show(payload({ actions: [{ id: 'continue', intent: 'continue', label: 'Continue' }] }));
    assert.equal(manager.act('continue'), false);
    assert.equal(manager.open, true);
    assert.match(manager.announcement, /activity controls/i);
});

test('same-origin navigation uses only the normalized application path', () => {
    const navigated = [];
    const manager = createManager({
        origin: 'https://conscious.test',
        navigate: (url) => navigated.push(url),
    });
    manager.show(payload({ actions: [{ id: 'next', intent: 'navigate', label: 'Next lesson', url: 'https://conscious.test/learn/lessons/2' }] }));
    assert.equal(manager.act('next'), true);
    assert.deepEqual(navigated, ['/learn/lessons/2']);
});

test('all configuration values and nested allowlists are fixed', () => {
    const expected = [
        ['correct', 'Excellent work', 'That answer is correct.', 'correct-answer', ['card'], 'correct', 40, 4000],
        ['incorrect', 'Not quite yet', 'Review your response and try again when you are ready.', 'incorrect-answer', ['card'], 'incorrect', 50, null],
        ['try_again', 'Keep going', 'You can adjust your response and try again.', 'try-again', ['card'], null, 30, null],
        ['activity_completed', 'Activity complete', 'You completed this learning activity.', 'activity-completed', ['card'], 'success', 80, null],
        ['lesson_completed', 'Lesson complete', 'You completed this lesson.', 'lesson-completed', ['completion_modal'], 'complete', 100, null],
        ['module_completed', 'Module complete', 'You completed this module.', 'lesson-completed', ['completion_modal'], 'success', 100, null],
        ['informational', 'Good to know', 'Here is some information for this activity.', 'informational', ['micro', 'card'], null, 20, 3000],
        ['warning', 'Let us pause here', 'Review this message before continuing.', 'warning', ['card'], null, 60, null],
    ];
    assert.ok(Object.isFrozen(FEEDBACK_CONFIG));
    for (const [type, title, message, file, presentations, sound, priority, dismissAfter] of expected) {
        assert.deepEqual(FEEDBACK_CONFIG[type], {
            title, message, illustration: `/assets/illustrations/learning-feedback/${file}.svg`,
            presentations, presentation: presentations[0], sound, priority, dismissAfter,
        });
        assert.ok(Object.isFrozen(FEEDBACK_CONFIG[type]));
        assert.ok(Object.isFrozen(FEEDBACK_CONFIG[type].presentations));
    }
});

test('invalid payloads and inherited type names are rejected safely', () => {
    for (const value of [null, undefined, [], 'correct', 42, true]) {
        assert.equal(normalizeLearningFeedback(value), null);
    }
    for (const type of ['constructor', '__proto__', 'toString', {}, null]) {
        assert.equal(normalizeLearningFeedback(payload({ type })), null);
    }
    for (const value of ['', ' \n ', '<b></b>', {}, [], null, 17]) {
        assert.equal(normalizeLearningFeedback(payload({ id: value })), null);
        assert.equal(normalizeLearningFeedback(payload({ sourceId: value })), null);
    }
});

test('copy is plain bounded text with configured fallbacks and a strict toast flag', () => {
    const normalized = normalizeLearningFeedback(payload({
        id: 'x'.repeat(200), sourceId: 's'.repeat(200), title: 't'.repeat(200),
        message: 'm'.repeat(700), explanation: 'e'.repeat(1700), replacesSuccessToast: true,
        class: 'injected', callback: () => {}, event: 'unsafe', priority: 100,
    }));
    for (const [field, length] of [['id', 160], ['sourceId', 160], ['title', 120], ['message', 500], ['explanation', 1500]]) {
        assert.equal(normalized[field].length, length);
    }
    assert.equal(normalized.priority, 40);
    assert.equal(normalized.replacesSuccessToast, true);
    for (const field of ['class', 'callback', 'event']) assert.equal(Object.hasOwn(normalized, field), false);
    const fallback = normalizeLearningFeedback(payload({ title: '<i> </i>', message: {}, explanation: null, replacesSuccessToast: 'true' }));
    assert.equal(fallback.title, FEEDBACK_CONFIG.correct.title);
    assert.equal(fallback.message, FEEDBACK_CONFIG.correct.message);
    assert.equal(fallback.explanation, '');
    assert.equal(fallback.replacesSuccessToast, false);
    assert.equal(normalizeLearningFeedback(payload({ message: ' a\n<b>b</b>   c ' })).message, 'a b c');
});

test('presentations are restricted for every type', () => {
    for (const [type, config] of Object.entries(FEEDBACK_CONFIG)) {
        for (const presentation of ['micro', 'card', 'completion_modal', 'unknown']) {
            assert.equal(normalizeLearningFeedback(payload({ type, presentation })).presentation,
                config.presentations.includes(presentation) ? presentation : config.presentation);
        }
    }
});

test('actions use bounded unique IDs, labels, fixed intents, and explicit fields', () => {
    const normalized = normalizeLearningFeedback(payload({ actions: [
        null, {}, action('run-code'), action('retry', { id: '', label: 'Retry' }),
        action('retry', { label: '<b> </b>' }),
        action('retry', { id: 'r'.repeat(200), label: 'L'.repeat(100), callback: () => {}, event: 'unsafe', url: '/ignored' }),
        action('continue', { id: 'r'.repeat(160) }), action('dismiss'), action('continue'),
    ] }));
    assert.deepEqual(normalized.actions, [
        { id: 'r'.repeat(160), intent: 'retry', label: 'L'.repeat(80) }, action('dismiss'),
    ]);
    assert.deepEqual(normalizeLearningFeedback(payload({ actions: {} })).actions, []);
});

test('navigation rejects unsafe or missing URLs and preserves same-origin path/query/hash', () => {
    for (const url of [undefined, '', ' ', {}, 'javascript:alert(1)', 'data:text/html,hello', '//evil.example/a', 'https://conscious.test//evil.example/a', 'https://conscious.test.evil/a', 'https://conscious.test:444/a', 'http://conscious.test/a', 'https://[broken']) {
        assert.deepEqual(normalizeLearningFeedback(payload({ actions: [action('navigate', { url })] }), 'https://conscious.test').actions, []);
    }
    for (const url of ['/learn/../next?q=1#part', 'https://conscious.test/next?q=1#part']) {
        assert.equal(normalizeLearningFeedback(payload({ actions: [action('navigate', { url })] }), 'https://conscious.test').actions[0].url, '/next?q=1#part');
    }
    assert.deepEqual(normalizeLearningFeedback(payload({ actions: [action('navigate', { url: '/next' })] })).actions, []);
});

test('the latest 50 accepted IDs include pending, replaced and silently dropped events', () => {
    const manager = createManager();
    manager.show(payload({ id: 'completion', type: 'lesson_completed' }));
    for (let i = 0; i < 50; i++) assert.equal(manager.show(payload({ id: `event:${i}` })), true);
    assert.equal(manager.pending, null);
    assert.equal(manager.show(payload({ id: 'event:0' })), false);
    manager.dismiss();
    assert.equal(manager.show(payload({ id: 'completion', type: 'lesson_completed' })), true);
    assert.equal(manager.show(payload({ id: 'event:0' })), true);
    assert.equal(manager.show(payload({ id: 'event:49' })), false);
});

test('invalid events do not consume IDs and normalization precedes deduplication', () => {
    const manager = createManager();
    assert.equal(manager.show(payload({ type: 'unknown' })), false);
    assert.equal(manager.show(payload()), true);
    assert.equal(manager.show(payload({ id: ' <b>checkpoint:17:result:1</b> ' })), false);
});

test('pending keeps only newest routine event and only visible events play audio once', () => {
    const sounds = [];
    const manager = createManager({ audio: { play: key => sounds.push(key) } });
    manager.show(payload({ id: 'first', type: 'incorrect' }));
    manager.show(payload({ id: 'discarded' }));
    manager.show(payload({ id: 'newest', type: 'activity_completed' }));
    assert.equal(manager.pending.id, 'newest');
    assert.deepEqual(sounds, ['incorrect']);
    manager.dismiss();
    assert.equal(manager.active.id, 'newest');
    manager.opened(); manager.opened(); manager.resumeTimer();
    assert.deepEqual(sounds, ['incorrect', 'success']);
    assert.equal(manager.show(payload({ id: 'discarded' })), false);
    manager.dismiss();
    assert.equal(manager.active, null);
    assert.equal(manager.pending, null);
    assert.equal(manager.open, false);
});

for (const type of ['lesson_completed', 'module_completed']) {
    test(`${type} supersedes routine feedback, cancels its timer, and drops routine events silently`, () => {
        const timers = clock();
        const sounds = [];
        const manager = createManager({ ...timers, audio: { play: value => sounds.push(value) } });
        manager.show(payload());
        const stale = [...timers.timers.values()][0].callback;
        manager.show(payload({ id: 'queued', type: 'incorrect' }));
        manager.show(payload({ id: 'done', type }));
        assert.equal(manager.active.id, 'done');
        assert.equal(manager.pending, null);
        assert.equal(timers.timers.size, 0);
        stale();
        assert.equal(manager.active.id, 'done');
        manager.show(payload({ id: 'dropped', type: 'incorrect' }));
        assert.equal(manager.pending, null);
        assert.deepEqual(sounds, ['correct', FEEDBACK_CONFIG[type].sound]);
        manager.dismiss();
        assert.equal(manager.show(payload({ id: 'dropped' })), false);
    });
}

for (const [type, duration] of [['informational', 3000], ['correct', 4000]]) {
    test(`${type} dismisses after its configured ${duration}ms`, () => {
        const timers = clock();
        const manager = createManager(timers);
        manager.show(payload({ type }));
        assert.equal([...timers.timers.values()][0].delay, duration);
        timers.fire();
        assert.equal(manager.open, false);
        assert.equal(timers.timers.size, 0);
    });
}

test('pausing preserves remaining time and resume never creates duplicate timers', t => {
    let now = 10000;
    t.mock.method(Date, 'now', () => now);
    const timers = clock();
    const manager = createManager(timers);
    manager.show(payload());
    now += 1250;
    manager.pauseTimer(); manager.pauseTimer();
    assert.equal(timers.timers.size, 0);
    now += 5000;
    manager.resumeTimer(); manager.resumeTimer();
    assert.equal(timers.timers.size, 1);
    assert.equal([...timers.timers.values()][0].delay, 2750);
    now += 750;
    manager.pauseTimer(); manager.resumeTimer();
    assert.equal([...timers.timers.values()][0].delay, 2000);
    manager.dismiss(); manager.resumeTimer();
    assert.equal(timers.timers.size, 0);
});

test('persistent types and all actionable feedback never auto-dismiss', () => {
    const timers = clock();
    const manager = createManager(timers);
    for (const type of Object.keys(FEEDBACK_CONFIG)) {
        const variants = FEEDBACK_CONFIG[type].dismissAfter ? [[action('dismiss')]] : [[], [action('retry')]];
        for (const actions of variants) {
            manager.show(payload({ id: `${type}:${actions.length}`, type, actions }));
            manager.pauseTimer(); manager.resumeTimer();
            assert.equal(timers.timers.size, 0);
            manager.dismiss();
        }
    }
});

test('promoted feedback starts its full timer and stale callbacks cannot dismiss it', () => {
    const timers = clock();
    const manager = createManager(timers);
    manager.show(payload());
    const stale = [...timers.timers.values()][0].callback;
    manager.show(payload({ id: 'next', type: 'informational' }));
    manager.dismiss();
    assert.equal([...timers.timers.values()][0].delay, 3000);
    stale();
    assert.equal(manager.active.id, 'next');
    timers.fire();
    assert.equal(manager.open, false);
});

test('audio failures, including rejected promises, never interrupt feedback', async () => {
    for (const play of [() => { throw new Error('blocked'); }, () => Promise.reject(new Error('blocked'))]) {
        const manager = createManager({ audio: { play } });
        assert.equal(manager.show(payload()), true);
        manager.dismiss();
        assert.equal(manager.open, false);
    }
    await new Promise(resolve => setImmediate(resolve));
});

test('only configured non-null sounds are requested', () => {
    const sounds = [];
    const manager = createManager({ audio: { play: value => sounds.push(value) } });
    for (const type of Object.keys(FEEDBACK_CONFIG)) {
        manager.show(payload({ id: type, type, sound: 'invented' }));
        manager.dismiss();
    }
    assert.deepEqual(sounds, ['correct', 'incorrect', 'success', 'complete', 'success']);
});

test('unhandled Retry and Continue stay open with the exact activity-controls announcement', () => {
    for (const intent of ['retry', 'continue']) {
        const manager = createManager({ dispatchAction: () => false });
        manager.show(payload({ actions: [action(intent)] }));
        assert.equal(manager.act(intent), false);
        assert.equal(manager.announcement, 'Use the activity controls to continue.');
        assert.equal(manager.open, true);
        assert.equal(manager.acting, false);
    }
});

test('Dismiss only closes and invalid action IDs do nothing', () => {
    const manager = createManager({ dispatchAction: () => assert.fail(), navigate: () => assert.fail() });
    assert.equal(manager.act('dismiss'), false);
    manager.show(payload({ actions: [action('dismiss')] }));
    assert.equal(manager.act('unknown'), false);
    assert.equal(manager.open, true);
    assert.equal(manager.act('dismiss'), true);
    assert.equal(manager.act('dismiss'), false);
});

test('action locking prevents reentrant actions and feedback activation', () => {
    let calls = 0;
    const manager = createManager({ dispatchAction: () => {
        calls++;
        assert.equal(manager.acting, true);
        assert.equal(manager.act('continue'), false);
        assert.equal(manager.show(payload({ id: 'reentrant', type: 'lesson_completed' })), false);
        return true;
    } });
    manager.show(payload({ actions: [action('continue')] }));
    assert.equal(manager.act('continue'), true);
    assert.equal(calls, 1);
    assert.equal(manager.acting, false);
    assert.equal(manager.show(payload({ id: 'reentrant' })), true);
});

test('navigation runs once under reentry and actions always release their lock after exceptions', () => {
    let calls = 0;
    const manager = createManager({ origin: 'https://conscious.test', navigate: () => {
        calls++;
        assert.equal(manager.act('navigate'), false);
    } });
    manager.show(payload({ actions: [action('navigate', { url: '/next' })] }));
    assert.equal(manager.act('navigate'), true);
    assert.equal(manager.act('navigate'), false);
    assert.equal(calls, 1);
    for (const intent of ['retry', 'navigate']) {
        const failing = createManager({ origin: 'https://conscious.test',
            dispatchAction: () => { throw new Error('hook failed'); }, navigate: () => { throw new Error('hook failed'); } });
        failing.show(payload({ actions: [action(intent, { url: '/next' })] }));
        assert.throws(() => failing.act(intent), /hook failed/);
        assert.equal(failing.acting, false);
        assert.equal(failing.open, true);
    }
});

test('Escape requires an explicit Dismiss action and other keys do nothing', () => {
    const manager = createManager();
    manager.show(payload({ actions: [action('continue')] }));
    const escape = key('Escape');
    manager.handleKeydown(escape);
    assert.equal(manager.open, true);
    assert.equal(escape.prevented, 0);
    manager.dismiss();
    manager.show(payload({ id: 'dismissible', actions: [action('dismiss')] }));
    manager.handleKeydown(key('Enter'));
    assert.equal(manager.open, true);
    manager.handleKeydown(escape);
    assert.equal(manager.open, false);
    assert.equal(escape.prevented, 1);
    manager.handleKeydown(key('Escape'));
});

test('completion focuses its heading once and dismiss restores pre-open focus', () => {
    const before = focusTarget();
    const heading = focusTarget();
    const panel = { querySelectorAll: () => [] };
    let current = before;
    const manager = createManager({ getActiveElement: () => current });
    manager.show(payload({ type: 'lesson_completed' }));
    manager.opened(panel, heading);
    current = heading;
    manager.opened(panel, heading);
    assert.equal(heading.focused, 1);
    manager.dismiss();
    assert.equal(before.focused, 1);
    manager.show(payload({ id: 'card' }));
    manager.opened(panel, heading);
    assert.equal(heading.focused, 1);
});

test('dismiss restores focus before capturing the promoted event focus target', () => {
    let current;
    const before = { isConnected: true, focus() { current = this; } };
    current = before;
    const manager = createManager({ getActiveElement: () => current });
    manager.show(payload());
    current = focusTarget();
    manager.show(payload({ id: 'next', type: 'incorrect' }));
    manager.dismiss();
    assert.equal(current, before);
    assert.equal(manager.active.id, 'next');
    current = focusTarget();
    manager.dismiss();
    assert.equal(current, before);
});

test('completion replacement retains the original external focus target', () => {
    const before = focusTarget();
    const heading = focusTarget();
    let current = before;
    const manager = createManager({ getActiveElement: () => current });
    manager.show(payload());
    manager.opened({}, heading);
    manager.show(payload({ id: 'done', type: 'module_completed' }));
    manager.opened({}, heading);
    current = heading;
    manager.opened({}, heading);
    manager.dismiss();
    assert.equal(before.focused, 1);
    assert.equal(heading.focused, 1);
});

test('Tab and Shift+Tab wrap completion controls and contain focus entering from outside', () => {
    const first = focusTarget(), last = focusTarget(), heading = focusTarget();
    const hidden = { ...focusTarget(), hidden: true };
    const disabled = { ...focusTarget(), disabled: true };
    let current = last;
    const panel = { querySelectorAll: () => [first, disabled, hidden, last], contains: target => [first, last, heading].includes(target) };
    const manager = createManager({ getActiveElement: () => current });
    manager.show(payload({ type: 'lesson_completed' }));
    manager.opened(panel, heading);
    let event = key('Tab');
    manager.handleKeydown(event);
    assert.equal(first.focused, 1);
    assert.equal(event.prevented, 1);
    current = first;
    event = key('Tab', true);
    manager.handleKeydown(event);
    assert.equal(last.focused, 1);
    assert.equal(event.prevented, 1);
    current = heading;
    manager.handleKeydown(key('Tab', true));
    assert.equal(last.focused, 2);
    current = focusTarget();
    manager.handleKeydown(key('Tab'));
    assert.equal(first.focused, 2);
    current = first;
    event = key('Tab');
    manager.handleKeydown(event);
    assert.equal(event.prevented, 0);
    manager.dismiss();
    manager.show(payload({ id: 'card' }));
    manager.opened(panel, heading);
    current = last;
    event = key('Tab');
    manager.handleKeydown(event);
    assert.equal(event.prevented, 0);
});

test('empty modal controls fall back to heading or panel and absent focus targets are safe', () => {
    for (const withHeading of [true, false]) {
        const panel = { ...focusTarget(), querySelectorAll: () => [] };
        const heading = withHeading ? focusTarget() : null;
        const manager = createManager();
        manager.show(payload({ type: 'lesson_completed' }));
        manager.opened(panel, heading);
        const event = key('Tab');
        manager.handleKeydown(event);
        assert.equal(event.prevented, 1);
        assert.equal((heading ?? panel).focused, 2);
        manager.dismiss();
    }
    for (const target of [null, {}, { ...focusTarget(), isConnected: false }, { focus() { throw new Error('gone'); } }]) {
        const manager = createManager({ getActiveElement: () => target });
        manager.show(payload({ type: 'lesson_completed' }));
        manager.opened(); manager.handleKeydown(key('Tab')); manager.dismiss();
        assert.equal(manager.open, false);
    }
});

test('modal containment skips CSS-hidden, disabled-fieldset, and negative-tabindex controls', () => {
    const first = focusTarget();
    const last = focusTarget();
    const hidden = { ...focusTarget(), getClientRects: () => [] };
    const disabled = { ...focusTarget(), matches: selector => selector === ':disabled' };
    const negative = { ...focusTarget(), tabIndex: -2 };
    const controls = [hidden, disabled, negative, first, last, hidden, disabled, negative];
    let current = last;
    const manager = createManager({ getActiveElement: () => current });
    manager.show(payload({ type: 'lesson_completed' }));
    manager.opened({ querySelectorAll: () => controls }, focusTarget());
    const tab = key('Tab');
    manager.handleKeydown(tab);
    assert.equal(tab.prevented, 1);
    assert.equal(first.focused, 1);
    current = first;
    const shiftTab = key('Tab', true);
    manager.handleKeydown(shiftTab);
    assert.equal(shiftTab.prevented, 1);
    assert.equal(last.focused, 1);
});
