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

test('start watching only plays the preview video', () => {
    let plays = 0;
    let requests = 0;
    const modal = createOnboardingGuideModal(config, async () => {
        requests += 1;
        return response();
    });
    modal.$refs = {
        video: { play: () => { plays += 1; } },
        dialog: { close() {} },
    };

    modal.startWatching();

    assert.equal(plays, 1);
    assert.equal(requests, 0);
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
