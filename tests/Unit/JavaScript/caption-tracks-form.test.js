import test from 'node:test';
import assert from 'node:assert/strict';
import {
    CAPTION_MAX_BYTES,
    captionFileError,
    captionRowHtml,
} from '../../../resources/js/caption-tracks-form.js';

test('accepts VTT through the exact 2 MiB boundary', () => {
    assert.equal(CAPTION_MAX_BYTES, 2097152);
    assert.equal(captionFileError({
        name: 'english.vtt',
        size: CAPTION_MAX_BYTES,
        type: 'text/vtt',
    }), null);
});

test('rejects oversized and non-VTT selections', () => {
    assert.match(captionFileError({
        name: 'english.vtt',
        size: CAPTION_MAX_BYTES + 1,
        type: 'text/vtt',
    }), /2 MB/);
    assert.match(captionFileError({
        name: 'english.srt',
        size: 100,
        type: 'text/plain',
    }), /WebVTT/);
});

test('substitutes every template index', () => {
    assert.equal(
        captionRowHtml('captions[__INDEX__][file]-__INDEX__', 4),
        'captions[4][file]-4',
    );
});
