import test from 'node:test';
import assert from 'node:assert/strict';

import {
    VIDEO_UPLOAD_MAX_BYTES,
    allowedVideoFileError,
    formatMiB,
    initializeVideoUploadForm,
    uploadPercent,
} from '../../../resources/js/video-upload-form.js';

class FakeElement {
    constructor() {
        this.listeners = {};
        this.classList = { toggle() {} };
        this.style = {};
        this.attributes = {};
        this.files = [];
        this.innerHTML = '';
    }

    addEventListener(type, listener) {
        this.listeners[type] ??= [];
        this.listeners[type].push(listener);
    }

    dispatch(type, event = {}) {
        for (const listener of this.listeners[type] ?? []) {
            listener(event);
        }
    }

    setAttribute(name, value) {
        this.attributes[name] = value;
    }
}

test('uses an exact 100 MiB upload boundary', () => {
    assert.equal(VIDEO_UPLOAD_MAX_BYTES, 104857600);
    assert.equal(allowedVideoFileError({
        name: 'boundary.mp4',
        size: VIDEO_UPLOAD_MAX_BYTES,
        type: 'video/mp4',
    }), null);
});

test('rejects oversized and unsupported videos before upload', () => {
    assert.match(allowedVideoFileError({
        name: 'oversized.mp4',
        size: VIDEO_UPLOAD_MAX_BYTES + 1,
        type: 'video/mp4',
    }), /100 MB or smaller/);

    assert.match(allowedVideoFileError({
        name: 'clip.ogg',
        size: 1024,
        type: 'video/ogg',
    }), /MP4, MPEG, MOV, AVI, and WebM/);
});

test('formats byte counts and clamps upload percentages', () => {
    assert.equal(formatMiB(52428800), '50.0 MB');
    assert.equal(uploadPercent(25, 100), 25);
    assert.equal(uploadPercent(120, 100), 100);
    assert.equal(uploadPercent(20, 0), 0);
});

test('keeps a cleared invalid selection blocked until reset', () => {
    const previousDocument = globalThis.document;
    const document = {
        activeElement: null,
        addEventListener() {},
        querySelector() {
            return null;
        },
    };
    const form = new FakeElement();
    const fileInput = new FakeElement();
    const submitButton = new FakeElement();
    form.dataset = { videoMaxBytes: String(VIDEO_UPLOAD_MAX_BYTES) };
    form.method = 'POST';
    form.action = '/topics';
    form.querySelector = (selector) => ({
        '[data-video-file-input]': fileInput,
        '[type="submit"]': submitButton,
    }[selector] ?? null);
    globalThis.document = document;

    try {
        initializeVideoUploadForm(form);

        const initialSubmit = { preventDefault() { this.defaultPrevented = true; } };
        form.dispatch('submit', initialSubmit);
        assert.notEqual(initialSubmit.defaultPrevented, true);

        fileInput.files = [{ name: 'clip.ogg', size: 1024, type: 'video/ogg' }];
        fileInput.dispatch('change');
        fileInput.files = [];

        const blockedSubmit = { preventDefault() { this.defaultPrevented = true; } };
        form.dispatch('submit', blockedSubmit);
        assert.equal(blockedSubmit.defaultPrevented, true);

        form.dispatch('reset');
        const resetSubmit = { preventDefault() { this.defaultPrevented = true; } };
        form.dispatch('submit', resetSubmit);
        assert.notEqual(resetSubmit.defaultPrevented, true);
    } finally {
        globalThis.document = previousDocument;
    }
});
