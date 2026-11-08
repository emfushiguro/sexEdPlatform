import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const thumbnail = readFileSync(new URL('../../resources/views/seminars/_thumbnail.blade.php', import.meta.url), 'utf8');
const initialize = thumbnail.match(/x-init="([^"]+)"/)[1];
const form = readFileSync(new URL('../../resources/views/connectors/seminars/_form.blade.php', import.meta.url), 'utf8');
const previewSource = form.match(/<fieldset[^>]+x-data="([\s\S]+?)" @reset/)[1]
    .replace('@js($seminar->thumbnailUrl())', '"/saved.png"')
    .replace('@js((bool) old(\'remove_thumbnail\', false))', 'false');

test('fallback detects errors before Alpine initialization, without hiding loaded or pending images', () => {
    for (const [image, expected] of [[undefined, false], [{ complete: false, naturalWidth: 0 }, false], [{ complete: true, naturalWidth: 0 }, true], [{ complete: true, naturalWidth: 640 }, false]]) {
        const state = { failed: false, $refs: { image }, $nextTick: callback => callback() };
        Function(`with (this) { ${initialize} }`).call(state);
        assert.equal(state.failed, expected);
    }
});

test('preview replacement, reset and removal revoke URLs and retain the saved image', () => {
    const revoked = [];
    let next = 0;
    const preview = Function('URL', `return (${previewSource})`)({ createObjectURL: () => `blob:${++next}`, revokeObjectURL: url => revoked.push(url) });
    preview.$refs = { thumbnail: { value: 'selected.png' } };
    preview.remove = true;
    preview.select({ target: { files: [{}] } });
    assert.equal(preview.remove, false);
    assert.equal(preview.previewUrl, 'blob:1');
    preview.select({ target: { files: [{}] } });
    assert.deepEqual(revoked, ['blob:1']);
    preview.remove = true;
    preview.clear();
    assert.equal(preview.remove, true);
    assert.equal(preview.$refs.thumbnail.value, '');
    assert.equal(preview.previewUrl, null);
    assert.equal(preview.savedUrl, '/saved.png');
    preview.destroy();
    assert.deepEqual(revoked, ['blob:1', 'blob:2']);
});
