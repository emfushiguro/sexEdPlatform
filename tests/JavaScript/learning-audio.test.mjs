import test from 'node:test';
import assert from 'node:assert/strict';
import {
    LEARNING_AUDIO_SOURCES,
    LEARNING_AUDIO_STORAGE_KEYS,
    createLearningAudioService,
    initializeLearningAudioPage,
} from '../../resources/js/learning-audio.js';

const flush = () => new Promise((resolve) => setImmediate(resolve));

function createStorage(values = {}, { throws = false } = {}) {
    const entries = new Map(Object.entries(values));

    return {
        getItem(key) {
            if (throws) throw new Error('Storage is unavailable');
            return entries.has(key) ? entries.get(key) : null;
        },
        setItem(key, value) {
            if (throws) throw new Error('Storage is unavailable');
            entries.set(key, String(value));
        },
        entries,
    };
}

function createEventTarget() {
    const listeners = new Map();

    return {
        listeners,
        addEventListener(type, listener) {
            listeners.set(type, listener);
        },
        removeEventListener(type, listener) {
            if (listeners.get(type) === listener) listeners.delete(type);
        },
        fire(type) {
            listeners.get(type)?.({ type });
        },
    };
}

function createHowlerDouble({ constructError = false, playError = false } = {}) {
    const sounds = [];
    let nextId = 1;
    const Howler = { ctx: { state: 'running', resumeCalls: 0, resume() { this.resumeCalls += 1; } } };

    class Howl {
        constructor(options) {
            if (constructError) throw new Error('Cannot construct audio');
            this.options = options;
            this.src = options.src;
            this.preload = options.preload;
            this.initialVolume = options.volume;
            this.volumeCalls = [];
            this.playCalls = [];
            this.stopCalls = [];
            this.callbackCalls = { onloaderror: [], onend: [], onstop: [], onplayerror: [] };
            sounds.push(this);
        }

        play() {
            const id = nextId++;
            this.playCalls.push(id);
            const errorMode = Array.isArray(playError) ? playError.shift() : playError ? 'async' : null;
            if (errorMode) {
                const notify = () => this.trigger('onplayerror', id, new Error('Playback blocked'));
                if (errorMode === 'sync') notify();
                else queueMicrotask(notify);
            }
            return id;
        }

        stop(id) {
            this.stopCalls.push(id);
            this.trigger('onstop', id);
        }

        volume(value) {
            this.volumeCalls.push(value);
        }

        failLoad() {
            this.trigger('onloaderror', null, new Error('Asset unavailable'));
        }

        end(id) {
            this.trigger('onend', id);
        }

        trigger(callback, ...args) {
            this.callbackCalls[callback].push(args);
            this.options[callback]?.(...args);
        }
    }

    return { Howl, Howler, sounds };
}

function createService(options = {}) {
    const audio = createHowlerDouble(options.howlerOptions);
    let loads = 0;
    const service = createLearningAudioService({
        storage: options.storage ?? createStorage(),
        eventTarget: options.eventTarget ?? createEventTarget(),
        now: options.now ?? (() => 1_000),
        warn: options.warn ?? (() => {}),
        loadHowler: options.loadHowler ?? (() => {
            loads += 1;
            return Promise.resolve(audio);
        }),
    });

    return { service, audio, get loads() { return loads; } };
}

test('defaults to enabled at seventy percent when storage is missing or malformed', async () => {
    const missing = createService({ storage: createStorage() }).service;
    const malformed = createService({ storage: createStorage({
        [LEARNING_AUDIO_STORAGE_KEYS.enabled]: 'yes',
        [LEARNING_AUDIO_STORAGE_KEYS.volume]: '1.1',
    }) }).service;

    assert.equal(missing.enabled, true);
    assert.equal(missing.volume, 0.7);
    assert.equal(malformed.enabled, true);
    assert.equal(malformed.volume, 0.7);
});

test('defaults blank and whitespace persisted volume to seventy percent', () => {
    const blank = createService({ storage: createStorage({ [LEARNING_AUDIO_STORAGE_KEYS.volume]: '' }) }).service;
    const whitespace = createService({ storage: createStorage({ [LEARNING_AUDIO_STORAGE_KEYS.volume]: '  ' }) }).service;

    assert.equal(blank.volume, 0.7);
    assert.equal(whitespace.volume, 0.7);
});

test('persists enabled and clamped volume with stable namespaced keys', async () => {
    const storage = createStorage();
    const { service } = createService({ storage });

    service.setEnabled(false);
    service.setVolume(4);
    assert.equal(storage.entries.get(LEARNING_AUDIO_STORAGE_KEYS.enabled), 'false');
    assert.equal(storage.entries.get(LEARNING_AUDIO_STORAGE_KEYS.volume), '1');
    assert.equal(service.volume, 1);

    service.setEnabled(true);
    service.setVolume(-2);
    assert.equal(storage.entries.get(LEARNING_AUDIO_STORAGE_KEYS.enabled), 'true');
    assert.equal(storage.entries.get(LEARNING_AUDIO_STORAGE_KEYS.volume), '0');
    assert.deepEqual(Object.keys(LEARNING_AUDIO_STORAGE_KEYS), ['enabled', 'volume']);
});

test('maps and preloads exactly five semantic files through one Howl bank', async () => {
    const { service, audio } = createService();

    await service.initialize();
    await service.initialize();

    assert.deepEqual(Object.keys(LEARNING_AUDIO_SOURCES), ['selection', 'correct', 'incorrect', 'success', 'complete']);
    assert.equal(audio.sounds.length, 5);
    assert.deepEqual(audio.sounds.map((sound) => sound.src[0]), Object.values(LEARNING_AUDIO_SOURCES));
    assert.deepEqual(audio.sounds.map((sound) => sound.preload), [true, true, true, true, true]);
    assert.deepEqual(audio.sounds.map((sound) => sound.initialVolume), [0.7, 0.7, 0.7, 0.7, 0.7]);
});

test('does not import howler until an enabled learner page initializes', async () => {
    const storage = createStorage({ [LEARNING_AUDIO_STORAGE_KEYS.enabled]: 'false' });
    const subject = createService({ storage });

    assert.equal(subject.loads, 0);
    assert.equal(await subject.service.initialize(), false);
    assert.equal(subject.loads, 0);

    subject.service.setEnabled(true);
    await flush();
    assert.equal(subject.loads, 1);
});

test('disabled playback is silent and reenabling initializes once', async () => {
    const subject = createService({ storage: createStorage({ [LEARNING_AUDIO_STORAGE_KEYS.enabled]: 'false' }) });

    subject.service.play('correct');
    await flush();
    assert.equal(subject.loads, 0);
    assert.equal(subject.audio.sounds.length, 0);

    subject.service.setEnabled(true);
    subject.service.setEnabled(true);
    await subject.service.initialize();
    assert.equal(subject.loads, 1);
    assert.equal(subject.audio.sounds.length, 5);
});

test('propagates application volume to every loaded sound', async () => {
    const { service, audio } = createService();
    await service.initialize();

    service.setVolume(0.25);

    assert.equal(service.volume, 0.25);
    assert.deepEqual(audio.sounds.map((sound) => sound.volumeCalls), [[0.25], [0.25], [0.25], [0.25], [0.25]]);
});

test('higher priority replaces lower priority and lower priority is ignored', async () => {
    const { service, audio } = createService();
    await service.initialize();
    const [selection, , , success] = audio.sounds;

    service.play('selection');
    await flush();
    service.play('success');
    await flush();
    selection.end(1);
    service.play('selection');
    await flush();

    assert.equal(selection.playCalls.length, 1);
    assert.deepEqual(selection.stopCalls, [1]);
    assert.equal(success.playCalls.length, 1);
});

test('equal result priority replaces the active result while selection is cooled down', async () => {
    let time = 0;
    const { service, audio } = createService({ now: () => time });
    await service.initialize();
    const [selection, correct, incorrect] = audio.sounds;

    service.play('selection');
    await flush();
    time += 1;
    service.play('incorrect');
    await flush();
    time += 1;
    service.play('correct');
    await flush();
    time += 1;
    service.play('selection');
    await flush();

    assert.equal(selection.playCalls.length, 1);
    assert.deepEqual(selection.stopCalls, [1]);
    assert.deepEqual(incorrect.stopCalls, [2]);
    assert.equal(correct.playCalls.length, 1);
});

test('blocked playback retains one highest-priority event and retries once on the next gesture', async () => {
    const eventTarget = createEventTarget();
    const { service, audio } = createService({ eventTarget, howlerOptions: { playError: true } });
    await service.initialize();
    audio.Howler.ctx.state = 'suspended';

    service.play('selection');
    await flush();
    service.play('complete');
    await flush();
    assert.deepEqual([...eventTarget.listeners.keys()].sort(), ['keydown', 'pointerdown']);

    eventTarget.fire('pointerdown');
    assert.equal(audio.Howler.ctx.resumeCalls, 1);
    assert.equal(audio.sounds[4].playCalls.length, 2);
    await flush();
    assert.equal(eventTarget.listeners.size, 0);
});

test('blocked selection retries once even while its cooldown is active', async () => {
    const eventTarget = createEventTarget();
    const { service, audio } = createService({ eventTarget, howlerOptions: { playError: ['async'] } });
    await service.initialize();

    service.play('selection');
    await flush();
    assert.equal(audio.sounds[0].callbackCalls.onplayerror.length, 1);

    eventTarget.fire('pointerdown');

    assert.equal(audio.sounds[0].playCalls.length, 2);
    assert.equal(audio.sounds[0].callbackCalls.onplayerror.length, 1);
});

test('synchronous retry failure is discarded without new listeners or another retry', async () => {
    const eventTarget = createEventTarget();
    const { service, audio } = createService({ eventTarget, howlerOptions: { playError: ['async', 'sync'] } });
    await service.initialize();

    service.play('complete');
    await flush();
    assert.deepEqual([...eventTarget.listeners.keys()].sort(), ['keydown', 'pointerdown']);

    eventTarget.fire('pointerdown');

    assert.equal(audio.sounds[4].playCalls.length, 2);
    assert.equal(audio.sounds[4].callbackCalls.onplayerror.length, 2);
    assert.equal(eventTarget.listeners.size, 0);
});

test('deferred unlock retries do not leave stale pending playback untracked', async () => {
    const eventTarget = createEventTarget();
    let time = 0;
    const { service, audio } = createService({ eventTarget, now: () => time, howlerOptions: { playError: ['async'] } });
    await service.initialize();
    const [selection, , , success] = audio.sounds;
    let resolveResume;
    audio.Howler.ctx.state = 'suspended';
    audio.Howler.ctx.resume = () => new Promise((resolve) => { resolveResume = resolve; });

    service.play('selection');
    await flush();
    eventTarget.fire('pointerdown');
    service.play('success');
    await flush();
    resolveResume();
    await flush();
    success.end(2);

    time += 151;
    service.play('selection');
    await flush();
    service.play('success');
    await flush();

    assert.deepEqual(selection.stopCalls, [3]);
});

test('a deferred selection retry preserves a newer blocked complete event', async () => {
    const eventTarget = createEventTarget();
    const { service, audio } = createService({ eventTarget, howlerOptions: { playError: ['async', 'async', 'async'] } });
    await service.initialize();
    const [selection, , , , complete] = audio.sounds;
    let resolveResume;
    audio.Howler.ctx.state = 'suspended';
    audio.Howler.ctx.resume = () => new Promise((resolve) => { resolveResume = resolve; });

    service.play('selection');
    await flush();
    eventTarget.fire('pointerdown');
    service.play('complete');
    await flush();

    audio.Howler.ctx.state = 'running';
    resolveResume();
    await flush();

    assert.deepEqual(selection.playCalls, [1]);
    assert.deepEqual(complete.playCalls, [2]);
    assert.deepEqual([...eventTarget.listeners.keys()].sort(), ['keydown', 'pointerdown']);

    eventTarget.fire('keydown');
    await flush();

    assert.deepEqual(complete.playCalls, [2, 3]);
    assert.equal(eventTarget.listeners.size, 0);
});

test('a later successful same-key play replaces active audio and clears stale retry state', async () => {
    const eventTarget = createEventTarget();
    const { service, audio } = createService({ eventTarget, howlerOptions: { playError: ['async'] } });
    await service.initialize();
    const success = audio.sounds[3];

    service.play('success');
    await flush();
    assert.deepEqual([...eventTarget.listeners.keys()].sort(), ['keydown', 'pointerdown']);

    service.play('success');
    await flush();
    assert.deepEqual(success.playCalls, [1, 2]);
    assert.equal(eventTarget.listeners.size, 0);

    eventTarget.fire('pointerdown');
    assert.deepEqual(success.playCalls, [1, 2]);

    service.play('success');
    await flush();
    assert.deepEqual(success.stopCalls, [2]);
    assert.deepEqual(success.playCalls, [1, 2, 3]);
});

test('obsolete same-key playback errors do not retain a retry listener', async () => {
    const eventTarget = createEventTarget();
    const { service, audio } = createService({ eventTarget });
    await service.initialize();
    const success = audio.sounds[3];

    service.play('success');
    await flush();
    service.play('success');
    await flush();

    success.trigger('onplayerror', 1, new Error('Playback blocked'));
    assert.equal(eventTarget.listeners.size, 0);

    success.end(2);
    eventTarget.fire('pointerdown');
    await flush();
    assert.deepEqual(success.playCalls, [1, 2]);
});

test('load, construction, asset, storage, and playback failures stay contained', async () => {
    const warnings = [];
    const failingLoad = createService({
        loadHowler: () => Promise.reject(new Error('Import failed')),
        warn: (...args) => warnings.push(args),
    }).service;
    assert.equal(await failingLoad.initialize(), false);

    const failingConstruction = createService({ howlerOptions: { constructError: true }, warn: (...args) => warnings.push(args) }).service;
    assert.equal(await failingConstruction.initialize(), false);

    const unavailable = createService({ howlerOptions: { playError: true }, storage: createStorage({}, { throws: true }), warn: (...args) => warnings.push(args) });
    await unavailable.service.initialize();
    unavailable.audio.sounds[0].failLoad();
    assert.doesNotThrow(() => unavailable.service.play('selection'));
    assert.equal(unavailable.audio.sounds[0].playCalls.length, 0);
    assert.doesNotThrow(() => unavailable.service.setEnabled(false));

    const failingPlayback = createService({ howlerOptions: { playError: true } });
    await failingPlayback.service.initialize();
    assert.doesNotThrow(() => failingPlayback.service.play('correct'));
    await flush();
    assert.ok(warnings.length >= 2);
});

test('page initialization ignores non-learner pages and consumes a valid flashed event', async () => {
    const calls = [];
    const service = { initialize: () => calls.push('initialize'), play: (key) => calls.push(key) };
    const noAudio = { body: { hasAttribute: () => false, dataset: { learningAudioEvent: 'complete' } } };
    const learner = { body: { hasAttribute: (name) => name === 'data-learning-audio', dataset: { learningAudioEvent: 'complete' } } };

    initializeLearningAudioPage(service, noAudio);
    initializeLearningAudioPage(service, learner);

    assert.deepEqual(calls, ['initialize', 'complete']);
});
