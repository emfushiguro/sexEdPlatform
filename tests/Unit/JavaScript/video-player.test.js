import test from 'node:test';
import assert from 'node:assert/strict';
import { plyrOptionsFor } from '../../../resources/js/video-player.js';

const video = (tracks) => ({
    querySelectorAll() {
        return tracks;
    },
});

test('omits dead caption controls when no tracks exist', () => {
    const options = plyrOptionsFor(video([]));
    assert.equal(options.controls.includes('captions'), false);
    assert.deepEqual(options.settings, ['speed']);
    assert.equal('captions' in options, false);
});

test('keeps captions off with automatic language when no default exists', () => {
    const options = plyrOptionsFor(video([
        { default: false, srclang: 'en' },
        { default: false, srclang: 'ko' },
    ]));
    assert.equal(options.captions.active, false);
    assert.equal(options.captions.language, 'auto');
    assert.equal(options.controls.includes('captions'), true);
    assert.deepEqual(options.settings, ['captions', 'speed']);
});

test('activates the configured default language', () => {
    const options = plyrOptionsFor(video([
        { default: false, srclang: 'en' },
        { default: true, srclang: 'fil' },
    ]));
    assert.equal(options.captions.active, true);
    assert.equal(options.captions.language, 'fil');
    assert.equal(options.captions.update, false);
});
