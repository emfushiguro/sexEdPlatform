import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const view = readFileSync(new URL('../../../resources/views/instructor/lessons/show.blade.php', import.meta.url), 'utf8');
const start = view.indexOf('    function renderTopicPreview(');
const end = view.indexOf("    document.getElementById('topicPreviewModal')?.addEventListener");
assert.ok(start >= 0 && end > start);
const context = vm.createContext({});
vm.runInContext(view.slice(start, end), context);

test('local video preview renders caption languages, escaped labels, and configured default', () => {
    const html = context.renderTopicPreview({
        type: 'video',
        video_file_url: '/storage/videos/lesson.mp4',
        captions: [
            { file_url: '/storage/captions/english.vtt', language_code: 'en', label: 'English "CC" & more', is_default: false },
            { file_url: '/storage/captions/filipino.vtt', language_code: 'fil', label: 'Filipino', is_default: true },
        ],
    });

    assert.equal((html.match(/<track\b/g) || []).length, 2);
    assert.match(html, /kind="subtitles" src="\/storage\/captions\/english.vtt" srclang="en" label="English &quot;CC&quot; &amp; more"/);
    assert.match(html, /srclang="fil" label="Filipino" default/);
    assert.equal((html.match(/ default(?=[\s>])/g) || []).length, 1);
});

test('video preview works with no captions and leaves tracks off without a default', () => {
    const topic = { type: 'video', video_file_url: '/storage/videos/lesson.mp4' };
    assert.doesNotMatch(context.renderTopicPreview(topic), /<track\b/);
    const html = context.renderTopicPreview({ ...topic, captions: [
        { file_url: '/storage/captions/english.vtt', language_code: 'en', label: 'English', is_default: false },
    ] });
    assert.match(html, /<track\b/);
    assert.doesNotMatch(html, / default(?=[\s>])/);
});

test('external video preview keeps its iframe without local caption tracks', () => {
    const html = context.renderTopicPreview({
        type: 'video',
        video_url: 'https://www.youtube.com/embed/example',
        captions: [{ file_url: '/storage/captions/english.vtt', language_code: 'en', label: 'English' }],
    });
    assert.match(html, /<iframe\b/);
    assert.doesNotMatch(html, /<track\b/);
});
