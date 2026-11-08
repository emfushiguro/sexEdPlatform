import assert from 'node:assert/strict';

// Use installed Playwright; CC_PLAYWRIGHT_MODULE can point to an existing cached installation.
const { chromium } = await import(process.env.CC_PLAYWRIGHT_MODULE ?? 'playwright');
assert.ok(process.env.CC_QA_PASSWORD, 'Set CC_QA_PASSWORD for the local Admin QA account.');
const origin = process.env.CC_QA_ORIGIN ?? 'http://127.0.0.1:8000';
const browser = await chromium.launch({
    headless: true,
    ...(process.env.CC_PLAYWRIGHT_EXECUTABLE ? { executablePath: process.env.CC_PLAYWRIGHT_EXECUTABLE } : {}),
});
const page = await browser.newPage();
page.setDefaultTimeout(15000);
const errors = [];
page.on('pageerror', error => errors.push(error.message));
try {
    await page.goto(`${origin}/secure-panel-access`);
    await page.locator('#email').fill(process.env.CC_ADMIN_QA_EMAIL ?? 'admin@test.local');
    await page.locator('#password').fill(process.env.CC_QA_PASSWORD);
    await Promise.all([
        page.waitForURL('**/admin/dashboard'),
        page.locator('button[type="submit"]').click(),
    ]);
    const previewClip = Buffer.from(await page.evaluate(async () => {
        const canvas = document.createElement('canvas');
        canvas.width = 320;
        canvas.height = 180;
        const context = canvas.getContext('2d');
        context.fillStyle = '#115e59';
        context.fillRect(0, 0, 320, 180);
        context.fillStyle = '#ffffff';
        context.font = '24px sans-serif';
        context.fillText('Video preview', 80, 95);
        const stream = canvas.captureStream(10);
        const recorder = new MediaRecorder(stream, { mimeType: 'video/webm' });
        const chunks = [];
        recorder.ondataavailable = event => chunks.push(event.data);
        const result = new Promise(resolve => {
            recorder.onstop = async () => resolve([...new Uint8Array(await new Blob(chunks).arrayBuffer())]);
        });
        recorder.start();
        await new Promise(resolve => setTimeout(resolve, 350));
        recorder.stop();
        stream.getTracks().forEach(track => track.stop());
        return result;
    }));
    const paths = [`/admin/topics/create?lesson=${process.env.CC_ADMIN_QA_LESSON ?? '3'}`];
    if (process.env.CC_ADMIN_QA_TOPIC) paths.push(`/admin/topics/${process.env.CC_ADMIN_QA_TOPIC}/edit`);
    for (const path of paths) {
        console.log(`Checking ${path}`);
        await page.goto(`${origin}${path}`);
        if (path.includes('/create')) {
            await page.locator('.topic-type-card').filter({ has: page.locator('input[value="video"]') }).click();
            await page.locator('#video_source').selectOption('upload');
        } else {
            await page.locator('input[name="video_source"][value="upload"]').check();
        }
        await page.locator('[data-video-file-trigger]').waitFor({ state: 'visible' });
        for (const width of [1366, 390]) {
            await page.setViewportSize({ width, height: 900 });
            if (path.includes('/create')) {
                await page.locator('#video_source').selectOption('url');
                assert.equal(await page.locator('[data-video-file-trigger]').isVisible(), false);
                await page.locator('#video_source').selectOption('upload');
            }
            const chooserPromise = page.waitForEvent('filechooser', { timeout: 5000 });
            await page.locator('[data-video-file-trigger]').click();
            const chooser = await chooserPromise;
            assert.equal(chooser.isMultiple(), false);
            await chooser.setFiles({ name: 'qa-video.webm', mimeType: 'video/webm', buffer: previewClip });
            assert.match(await page.locator('[data-video-file-name]').innerText(), /qa-video\.webm/);
            assert.equal(await page.locator('[data-video-file-input]').evaluate(el => el.files[0]?.name), 'qa-video.webm');
            await page.waitForFunction(() => {
                const video = document.querySelector('[data-video-preview-player]');
                return video?.readyState >= 2 && video.videoWidth === 320 && video.currentTime > 0;
            });
            assert.equal(await page.locator('[data-video-preview]').isVisible(), true);
            assert.equal(await page.locator('[data-video-preview-player]').evaluate(el => el.paused), true, 'Preview does not autoplay');
            await page.screenshot({ path: `storage/framework/admin-video-picker-${path.includes('/create') ? 'create' : 'edit'}-${width}.png`, fullPage: true });
            const keyboardChooserPromise = page.waitForEvent('filechooser', { timeout: 5000 });
            await page.locator('[data-video-file-input]').focus();
            await page.keyboard.press('Space');
            const keyboardChooser = await keyboardChooserPromise;
            await keyboardChooser.setFiles({ name: 'keyboard-video.mp4', mimeType: 'video/mp4', buffer: Buffer.from('Keyboard chooser test; never submitted.') });
            assert.match(await page.locator('[data-video-file-name]').innerText(), /keyboard-video\.mp4/);
            await page.getByRole('status').filter({ hasText: 'This browser cannot preview' }).waitFor();
            await page.locator('[data-video-file-input]').setInputFiles({ name: 'invalid.txt', mimeType: 'text/plain', buffer: Buffer.from('invalid') });
            assert.equal(await page.locator('[data-video-preview]').isVisible(), false);
            await page.locator('[data-video-file-input]').setInputFiles({ name: 'valid.webm', mimeType: 'video/webm', buffer: previewClip });
            await page.locator('[data-video-preview]').waitFor({ state: 'visible' });
            await page.locator('[data-video-upload-form]').evaluate(form => form.reset());
            assert.equal(await page.locator('[data-video-preview]').isVisible(), false);
        }
        console.log(`PASS ${path}: desktop/mobile native chooser, playable thumbnail, no autoplay, replacement fallback, invalid-file cleanup and reset.`);
    }
    assert.deepEqual(errors, [], 'No page JavaScript errors');
    console.log('No topics, uploads, or existing records were saved.');
} catch (error) {
    console.error(JSON.stringify({ url: page.url(), errors, input: await page.locator('[data-video-file-input]').evaluateAll(nodes => nodes.map(el => ({ disabled: el.disabled, hidden: el.hidden, html: el.outerHTML }))) }));
    throw error;
} finally {
    await browser.close();
}
