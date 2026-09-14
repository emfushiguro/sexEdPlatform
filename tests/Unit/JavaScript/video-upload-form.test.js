import test from 'node:test';
import assert from 'node:assert/strict';

import {
    VIDEO_UPLOAD_MAX_BYTES,
    allowedVideoFileError,
    formatMiB,
    uploadPercent,
} from '../../../resources/js/video-upload-form.js';

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
