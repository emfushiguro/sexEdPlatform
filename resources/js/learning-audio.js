export const LEARNING_AUDIO_STORAGE_KEYS = Object.freeze({
    enabled: 'cc_learning_audio_enabled',
    volume: 'cc_learning_audio_volume',
});

export const LEARNING_AUDIO_SOURCES = Object.freeze({
    selection: '/audio/feedback/selection.mp3',
    correct: '/audio/feedback/correct.mp3',
    incorrect: '/audio/feedback/incorrect.mp3',
    success: '/audio/feedback/success.mp3',
    complete: '/audio/feedback/complete.mp3',
});

const PRIORITY = Object.freeze({ selection: 1, correct: 2, incorrect: 2, success: 3, complete: 4 });
const DEFAULT_ENABLED = true;
const DEFAULT_VOLUME = 0.7;
const SELECTION_COOLDOWN_MS = 150;

export function createLearningAudioService({
    loadHowler = () => import('howler'),
    storage = undefined,
    eventTarget = globalThis.document,
    now = () => Date.now(),
    warn = (...args) => {
        if (import.meta.env?.DEV) console.warn(...args);
    },
} = {}) {
    let resolvedStorage = storage;
    let initializationPromise = null;
    let sounds = new Map();
    let howler = null;
    const unavailable = new Set();
    let activePlayback = null;
    let pendingPlayback = null;
    let lastSelectionAt = Number.NEGATIVE_INFINITY;

    const safelyWarn = (...args) => {
        try { warn(...args); } catch { /* Audio diagnostics must stay optional. */ }
    };
    const getStorage = () => {
        if (resolvedStorage !== undefined) return resolvedStorage;
        try { resolvedStorage = globalThis.localStorage; } catch { resolvedStorage = null; }
        return resolvedStorage;
    };
    const read = (key) => {
        try { return getStorage()?.getItem(key); } catch { return null; }
    };
    const write = (key, value) => {
        try { getStorage()?.setItem(key, String(value)); } catch { /* Storage is optional. */ }
    };
    const storedEnabled = read(LEARNING_AUDIO_STORAGE_KEYS.enabled);
    let enabled = storedEnabled === 'true' ? true : storedEnabled === 'false' ? false : DEFAULT_ENABLED;
    const storedVolume = read(LEARNING_AUDIO_STORAGE_KEYS.volume);
    const parsedStoredVolume = typeof storedVolume === 'string' && storedVolume.trim() !== ''
        ? Number(storedVolume)
        : Number.NaN;
    let volume = storedVolume !== null && Number.isFinite(parsedStoredVolume) && parsedStoredVolume >= 0 && parsedStoredVolume <= 1
        ? parsedStoredVolume
        : DEFAULT_VOLUME;
    const stateListeners = new Set();

    const getState = () => ({ enabled, volume });
    const notifyStateChange = () => {
        for (const listener of [...stateListeners]) {
            try { listener(getState()); } catch (error) { safelyWarn('Learning audio state listener failed.', error); }
        }
    };
    const subscribe = (listener) => {
        if (typeof listener !== 'function') return () => {};
        stateListeners.add(listener);
        return () => stateListeners.delete(listener);
    };

    const clearActive = (key, id) => {
        if (activePlayback?.key !== key || (activePlayback.id !== id && activePlayback.id !== null)) return;
        activePlayback = null;
        if (pendingPlayback?.key === key) pendingPlayback = null;
    };
    const removeUnlockListeners = () => {
        try {
            eventTarget?.removeEventListener?.('pointerdown', handleUnlock);
            eventTarget?.removeEventListener?.('keydown', handleUnlock);
        } catch { /* A missing document cannot affect application flow. */ }
    };
    const retryPending = (pending) => {
        if (pendingPlayback && pendingPlayback.priority > pending.priority) return;
        pendingPlayback = pending;
        try {
            if (!playLoaded(pending.key, true)) pendingPlayback = null;
        } catch (error) {
            pendingPlayback = null;
            safelyWarn('Learning audio playback failed.', error);
        }
    };
    const handleUnlock = () => {
        const pending = pendingPlayback;
        pendingPlayback = null;
        removeUnlockListeners();
        if (!enabled) {
            return Promise.resolve(false);
        }

        try {
            const resume = howler?.ctx?.state === 'suspended' ? howler.ctx.resume?.() : null;
            if (resume?.then) {
                return Promise.resolve(resume)
                    .then(() => {
                        if (pending) retryPending(pending);
                        return true;
                    })
                    .catch((error) => {
                        safelyWarn('Learning audio context resume failed.', error);
                        if (pending) retryPending(pending);
                        return false;
                    });
            } else {
                if (pending) retryPending(pending);
            }
            return Promise.resolve(true);
        } catch (error) {
            safelyWarn('Learning audio context resume failed.', error);
            if (pending) retryPending(pending);
            return Promise.resolve(false);
        }
    };
    const retainPending = (key) => {
        const priority = PRIORITY[key];
        if (!pendingPlayback || priority > pendingPlayback.priority) pendingPlayback = { key, priority };
        try {
            eventTarget?.addEventListener?.('pointerdown', handleUnlock, { once: true });
            eventTarget?.addEventListener?.('keydown', handleUnlock, { once: true });
        } catch (error) {
            safelyWarn('Learning audio unlock listener failed.', error);
        }
    };
    const handlePlaybackError = (key, id) => {
        if (activePlayback?.key !== key || (id !== null && activePlayback.id !== null && activePlayback.id !== id)) return;
        const isRetry = pendingPlayback?.key === key
            && activePlayback?.key === key
            && (activePlayback.id === id || activePlayback.id === null);
        clearActive(key, id);
        if (isRetry) return;
        retainPending(key);
    };
    const stopActive = () => {
        const active = activePlayback;
        activePlayback = null;
        if (pendingPlayback?.key === active?.key) pendingPlayback = null;
        try { sounds.get(active?.key)?.stop(active.id); } catch (error) { safelyWarn('Learning audio stop failed.', error); }
    };
    const playLoaded = (key, retry = false) => {
        if (!enabled || unavailable.has(key)) return false;
        const sound = sounds.get(key);
        if (!sound) return false;

        const priority = PRIORITY[key];
        if (activePlayback) {
            if (priority < activePlayback.priority) return false;
        }
        if (key === 'selection') {
            const at = now();
            if (!retry && at - lastSelectionAt < SELECTION_COOLDOWN_MS) return false;
            lastSelectionAt = at;
        }
        if (activePlayback) {
            stopActive();
        }

        try {
            if (!retry && pendingPlayback?.key === key) {
                pendingPlayback = null;
                removeUnlockListeners();
            }
            activePlayback = { key, id: null, priority };
            const id = sound.play();
            if (activePlayback?.key === key && activePlayback.id === null) activePlayback.id = id;
            return true;
        } catch (error) {
            safelyWarn('Learning audio playback failed.', error);
            handlePlaybackError(key, undefined);
            return false;
        }
    };
    const initialize = () => {
        if (initializationPromise) return initializationPromise;
        if (!enabled) return Promise.resolve(false);

        initializationPromise = Promise.resolve()
            .then(loadHowler)
            .then(({ Howl, Howler }) => {
                howler = Howler;
                for (const [key, path] of Object.entries(LEARNING_AUDIO_SOURCES)) {
                    sounds.set(key, new Howl({
                        src: [path],
                        preload: true,
                        volume,
                        onloaderror: () => unavailable.add(key),
                        onend: (id) => clearActive(key, id),
                        onstop: (id) => clearActive(key, id),
                        onplayerror: (id) => handlePlaybackError(key, id),
                    }));
                }
                return true;
            })
            .catch((error) => {
                safelyWarn('Learning audio initialization failed.', error);
                return false;
            });
        return initializationPromise;
    };
    const setEnabled = (value) => {
        enabled = Boolean(value);
        write(LEARNING_AUDIO_STORAGE_KEYS.enabled, enabled);
        notifyStateChange();
        if (!enabled) {
            stopActive();
            pendingPlayback = null;
            removeUnlockListeners();
            return;
        }
        void initialize().catch((error) => safelyWarn('Learning audio initialization failed.', error));
    };
    const setVolume = (value) => {
        const parsed = Number(value);
        volume = Number.isFinite(parsed) ? Math.min(1, Math.max(0, parsed)) : DEFAULT_VOLUME;
        write(LEARNING_AUDIO_STORAGE_KEYS.volume, volume);
        notifyStateChange();
        for (const sound of sounds.values()) {
            try { sound.volume(volume); } catch (error) { safelyWarn('Learning audio volume failed.', error); }
        }
    };
    const unlock = () => {
        if (!enabled) return Promise.resolve(false);

        return initialize()
            .then((initialized) => initialized && enabled ? handleUnlock() : false)
            .catch((error) => {
                safelyWarn('Learning audio unlock failed.', error);
                return false;
            });
    };
    const play = (key) => {
        if (!Object.hasOwn(LEARNING_AUDIO_SOURCES, key) || !enabled) return;
        void initialize()
            .then((initialized) => { if (initialized && enabled) playLoaded(key); })
            .catch((error) => safelyWarn('Learning audio playback failed.', error));
    };

    return {
        get enabled() { return enabled; },
        get volume() { return volume; },
        subscribe,
        initialize,
        unlock,
        setEnabled,
        setVolume,
        play,
    };
}

export function initializeLearningAudioPage(service, root = globalThis.document) {
    const body = root?.body;
    if (!body?.hasAttribute('data-learning-audio')) return;

    service.initialize();
    const soundKey = body.dataset.learningAudioEvent;
    if (Object.hasOwn(LEARNING_AUDIO_SOURCES, soundKey)) service.play(soundKey);
}

export const learningAudio = createLearningAudioService();
