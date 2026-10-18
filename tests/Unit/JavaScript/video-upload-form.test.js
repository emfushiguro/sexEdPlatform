import test from 'node:test';
import assert from 'node:assert/strict';

import {
    VIDEO_UPLOAD_MAX_BYTES,
    allowedVideoFileError,
    bindVideoFileTrigger,
    formatMiB,
    initializeVideoUploadForm,
    initializeVideoFilePreview,
    syncVideoSourceFields,
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

test('previews valid local videos and releases object URLs on replacement, invalid input and reset', () => {
    const input = new FakeElement();
    const panel = new FakeElement();
    const video = new FakeElement();
    const status = new FakeElement();
    const revoked = [];
    const previousCreate = URL.createObjectURL;
    const previousRevoke = URL.revokeObjectURL;
    const previousWindow = globalThis.window;
    globalThis.window = { addEventListener() {} };
    let nextId = 0;
    video.pause = () => {};
    video.load = () => {};
    video.removeAttribute = name => { delete video[name]; };
    const form = new FakeElement();
    form.dataset = {};
    form.querySelector = selector => ({
        '[data-video-file-input]': input,
        '[data-video-preview]': panel,
        '[data-video-preview-player]': video,
        '[data-video-preview-status]': status,
    }[selector] ?? null);
    URL.createObjectURL = () => `blob:preview-${++nextId}`;
    URL.revokeObjectURL = url => revoked.push(url);
    try {
        initializeVideoFilePreview(form);
        input.files = [{ name: 'first.mp4', size: 100, type: 'video/mp4' }];
        input.dispatch('change');
        assert.equal(video.src, 'blob:preview-1');
        assert.equal(panel.hidden, false);
        video.dispatch('error');
        assert.match(status.textContent, /cannot preview/i);
        input.files = [{ name: 'replacement.webm', size: 100, type: 'video/webm' }];
        input.dispatch('change');
        assert.deepEqual(revoked, ['blob:preview-1']);
        assert.equal(video.src, 'blob:preview-2');
        input.files = [{ name: 'bad.txt', size: 100, type: 'text/plain' }];
        input.dispatch('change');
        assert.equal(panel.hidden, true);
        assert.equal(video.src, undefined);
        input.files = [{ name: 'third.mp4', size: 100, type: 'video/mp4' }];
        input.dispatch('change');
        form.dispatch('reset');
        assert.equal(panel.hidden, true);
        assert.deepEqual(revoked, ['blob:preview-1', 'blob:preview-2', 'blob:preview-3']);
    } finally {
        URL.createObjectURL = previousCreate;
        URL.revokeObjectURL = previousRevoke;
        globalThis.window = previousWindow;
    }
});

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

test('reveals the video file field when upload is selected', () => {
    const visibility = {
        url: null,
        file: null,
    };
    const form = {
        querySelector(selector) {
            return {
                '[data-video-source]:checked': { value: 'upload' },
                '[data-video-url-field]': {
                    classList: {
                        toggle(className, hidden) {
                            visibility.url = { className, hidden };
                        },
                    },
                },
                '[data-video-file-field]': {
                    classList: {
                        toggle(className, hidden) {
                            visibility.file = { className, hidden };
                        },
                    },
                },
            }[selector] ?? null;
        },
    };

    syncVideoSourceFields(form);

    assert.deepEqual(visibility.url, { className: 'hidden', hidden: true });
    assert.deepEqual(visibility.file, { className: 'hidden', hidden: false });
});

test('opens the native picker from the visible video file trigger', () => {
    let clicks = 0;
    const trigger = new FakeElement();
    const form = {
        querySelector(selector) {
            return {
                '[data-video-file-input]': {
                    click() {
                        clicks += 1;
                    },
                },
                'button[data-video-file-trigger]': trigger,
            }[selector] ?? null;
        },
    };

    bindVideoFileTrigger(form);
    trigger.dispatch('click');

    assert.equal(clicks, 1);
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

test('shows the first nested validation error returned by the server', () => {
    const previousDocument = globalThis.document;
    const previousFormData = globalThis.FormData;
    const document = {
        activeElement: null,
        addEventListener() {},
        querySelector(selector) {
            return selector === '[data-video-upload-form-error]' ? formError : null;
        },
    };
    const form = new FakeElement();
    const fileInput = new FakeElement();
    const submitButton = new FakeElement();
    const formError = new FakeElement();
    form.dataset = { videoMaxBytes: String(VIDEO_UPLOAD_MAX_BYTES) };
    form.method = 'POST';
    form.action = '/topics';
    form.querySelector = (selector) => ({
        '[data-video-file-input]': fileInput,
        '[type="submit"]': submitButton,
        'input[name="_token"]': null,
    }[selector] ?? null);
    fileInput.files = [{ name: 'video.mp4', size: 100, type: 'video/mp4' }];

    class FakeXhr {
        constructor() {
            this.upload = new FakeElement();
            this.listeners = {};
            this.status = 422;
            this.response = {
                errors: { 'captions.0.file': ['Caption file is invalid.'] },
            };
        }

        open() {}

        setRequestHeader() {}

        addEventListener(type, listener) {
            this.listeners[type] = listener;
        }

        send() {
            this.listeners.load();
        }
    }

    globalThis.document = document;
    globalThis.FormData = class {};

    try {
        initializeVideoUploadForm(form, () => new FakeXhr());
        form.dispatch('submit', { preventDefault() {} });
        assert.equal(formError.textContent, 'Caption file is invalid.');
    } finally {
        globalThis.document = previousDocument;
        globalThis.FormData = previousFormData;
    }
});
