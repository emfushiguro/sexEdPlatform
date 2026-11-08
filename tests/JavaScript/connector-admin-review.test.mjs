import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const view = readFileSync(new URL('../../resources/views/admin/connectors/show.blade.php', import.meta.url), 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1];

function workspace(windowRef = {}) {
    const context = vm.createContext({ window: windowRef });
    vm.runInContext(script, context);
    return context.connectorReviewWorkspace();
}

test('opens only the requested native dialog without reopening an active one', () => {
    const page = workspace();
    let opens = 0;
    const dialog = { id: 'accept-evidence-1', open: false, showModal() { opens++; this.open = true; } };
    page.$root = { querySelectorAll: () => [dialog] };
    page.openDialog('missing');
    page.openDialog('accept-evidence-1');
    page.openDialog('accept-evidence-1');
    assert.equal(opens, 1);
});

test('thumbnail clicks populate the image/PDF dialog and closing clears private sources', () => {
    const page = workspace();
    let opened;
    page.openDialog = id => { opened = id; };
    page.previewDocument({ dataset: {
        previewUrl: '/private/evidence/3', previewName: 'Evidence "photo".png',
        previewType: 'image', downloadUrl: '/private/evidence/3?disposition=attachment',
    } });
    assert.equal(opened, 'evidence-preview');
    assert.equal(page.preview.url, '/private/evidence/3');
    assert.equal(page.preview.name, 'Evidence "photo".png');
    page.clearPreview();
    assert.equal(page.preview, null);
});

test('disables repeated submissions only after valid form validation', () => {
    const page = workspace();
    const button = { disabled: false };
    const form = { checkValidity: () => false, querySelector: () => button };
    page.submitReview({ currentTarget: form });
    assert.equal(button.disabled, false);
    form.checkValidity = () => true;
    page.submitReview({ currentTarget: form });
    assert.equal(button.disabled, true);
});

test('lazy PDF thumbnails observe visible buttons rather than hidden canvases', () => {
    let observed, observerCallback, unobserved, rendered, disconnected = false;
    class Observer {
        constructor(callback) { observerCallback = callback; }
        observe(target) { observed = target; }
        unobserve(target) { unobserved = target; }
        disconnect() { disconnected = true; }
    }
    const page = workspace({ IntersectionObserver: Observer });
    // The script uses the browser global constructor as well as window.
    const context = vm.createContext({ window: { IntersectionObserver: Observer }, IntersectionObserver: Observer });
    vm.runInContext(script, context);
    const initialized = context.connectorReviewWorkspace();
    const button = { querySelector: () => canvas };
    const canvas = { parentElement: button };
    initialized.$nextTick = callback => callback();
    initialized.$root = { dataset: { reopenDialog: 'reject-connector' }, querySelectorAll: () => [canvas] };
    let reopened;
    initialized.openDialog = id => { reopened = id; };
    initialized.renderPdfThumbnail = target => { rendered = target; };
    initialized.init();
    assert.equal(reopened, 'reject-connector');
    assert.equal(observed, button);
    observerCallback([{ isIntersecting: true, target: button }]);
    assert.equal(unobserved, button);
    assert.equal(rendered, canvas);
    initialized.destroy();
    assert.equal(disconnected, true);
    assert.equal(page.preview, null);
});

test('renders a bounded first-page PDF thumbnail and releases the PDF resource', async () => {
    let destroyed = false, requested, shown = false, fallbackHidden = false, loads = 0;
    const document = {
        getPage: async () => ({
            getViewport: ({ scale }) => ({ width: 600 * scale, height: 800 * scale }),
            render: () => ({ promise: Promise.resolve() }),
        }),
        destroy: async () => { destroyed = true; },
    };
    const page = workspace({
        ensurePdfJsLib: async () => {
            loads++;
            return { getDocument: options => { requested = options; return { promise: Promise.resolve(document) }; } };
        },
    });
    const canvas = {
        dataset: { previewUrl: '/private/evidence/8' },
        getContext: () => ({}),
        classList: { remove: () => { shown = true; } },
        nextElementSibling: { classList: { add: () => { fallbackHidden = true; } } },
    };
    await page.renderPdfThumbnail(canvas);
    assert.equal(requested.url, '/private/evidence/8');
    assert.equal(requested.isEvalSupported, false);
    assert.equal(canvas.height, 192);
    assert.equal(canvas.width, 144);
    assert.equal(shown && fallbackHidden && destroyed, true);
    await page.renderPdfThumbnail(canvas);
    assert.equal(loads, 1);
});

test('a PDF rendering failure preserves the clickable fallback', async () => {
    const page = workspace({ ensurePdfJsLib: async () => { throw new Error('unsupported'); } });
    let changed = false;
    await page.renderPdfThumbnail({
        dataset: { previewUrl: '/private/evidence/9' },
        classList: { remove: () => { changed = true; } },
    });
    assert.equal(changed, false);
});
