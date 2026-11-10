import test from 'node:test';
import assert from 'node:assert/strict';

import { createOnboardingGuideModal } from '../../../resources/js/onboarding-guide-modal.js';

const config = {
    dismissUrl: '/onboarding-guides/versions/1/dismiss',
    completeUrl: '/onboarding-guides/versions/1/complete',
    csrfToken: 'token',
};

const response = (ok = true) => ({
    ok,
    async json() {
        return {};
    },
});

test('start watching requests fullscreen and plays without recording completion', async () => {
    let plays = 0;
    let fullscreen = 0;
    let requests = 0;
    const modal = createOnboardingGuideModal(config, async () => {
        requests += 1;
        return response();
    });
    modal.$refs = {
        video: { requestFullscreen: () => { fullscreen += 1; return Promise.resolve(); }, play: () => { plays += 1; } },
        dialog: { close() {} },
    };

    await modal.startWatching();

    assert.equal(plays, 1);
    assert.equal(fullscreen, 1);
    assert.equal(requests, 0);
});

test('playback still starts when fullscreen is denied', async () => {
    let plays = 0;
    const modal = createOnboardingGuideModal(config, async () => response());
    modal.$refs = { video: { requestFullscreen: () => Promise.reject(new Error('Denied')), play: () => { plays += 1; } } };

    await modal.startWatching();

    assert.equal(plays, 1);
    assert.equal(modal.error, '');
});

test('uses native video fullscreen where standard fullscreen is unavailable', async () => {
    let fullscreen = 0;
    const modal = createOnboardingGuideModal(config, async () => response());
    modal.$refs = { video: { webkitEnterFullscreen: () => { fullscreen += 1; }, play() {} } };

    await modal.startWatching();

    assert.equal(fullscreen, 1);
});

test('playback errors offer a retry instead of leaving an unhandled rejection', async () => {
    const modal = createOnboardingGuideModal(config, async () => response());
    modal.$refs = { video: { play: () => Promise.reject(new Error('Playback failed')) } };

    await modal.startWatching();

    assert.match(modal.error, /playback|play/i);
});

test('dismissing the guide stops its video', () => {
    let paused = false;
    const modal = createOnboardingGuideModal(config, async () => response());
    modal.$refs = { video: { pause: () => { paused = true; } }, dialog: { close() {} } };

    modal.dismiss();

    assert.equal(paused, true);
});

test('skip, close, and escape close before saving dismissal', async () => {
    let closed = false;
    let requestSawClosed = false;
    const modal = createOnboardingGuideModal(config, async () => {
        requestSawClosed = closed;
        return response();
    });
    modal.$refs = { dialog: { close: () => { closed = true; } } };

    modal.dismiss();
    await Promise.resolve();

    assert.equal(closed, true);
    assert.equal(requestSawClosed, true);
});

test('dismissal failure is non-blocking and warns that the guide may reappear', async () => {
    let message = '';
    const modal = createOnboardingGuideModal(
        config,
        async () => response(false),
        value => { message = value; },
    );
    modal.$refs = { dialog: { close() {} } };

    modal.dismiss();
    await new Promise(resolve => setImmediate(resolve));

    assert.match(message, /may appear again/i);
});

test('completion closes after success and sends the configured request', async () => {
    let requestUrl = '';
    let requestOptions = null;
    let closed = false;
    const modal = createOnboardingGuideModal(config, async (url, options) => {
        requestUrl = url;
        requestOptions = options;
        return response();
    });
    modal.$refs = { dialog: { close: () => { closed = true; } } };

    await modal.complete();

    assert.equal(closed, true);
    assert.equal(requestUrl, config.completeUrl);
    assert.equal(requestOptions.method, 'POST');
    assert.equal(requestOptions.headers['X-CSRF-TOKEN'], config.csrfToken);
});

test('completion failure keeps the dialog open and exposes a retry message', async () => {
    let closed = false;
    const modal = createOnboardingGuideModal(config, async () => response(false));
    modal.$refs = { dialog: { close: () => { closed = true; } } };

    await modal.complete();

    assert.equal(closed, false);
    assert.match(modal.error, /try again/i);
    assert.equal(modal.saving, false);
});

test('duplicate completion clicks share one in-flight request', async () => {
    let requests = 0;
    let resolveRequest;
    const modal = createOnboardingGuideModal(config, async () => {
        requests += 1;
        await new Promise(resolve => { resolveRequest = resolve; });
        return response();
    });
    modal.$refs = { dialog: { close() {} } };

    const first = modal.complete();
    const second = modal.complete();

    assert.equal(requests, 1);
    resolveRequest();
    await first;
    await second;
    assert.equal(modal.saving, false);
});
