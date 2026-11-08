import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import puppeteer from 'puppeteer';

// Render real Blade components using an unsaved model; no database writes or external requests.
const markup = execFileSync('php', ['-r', `
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$topic = new App\\Models\\LessonTopic([
    'type' => 'video', 'video_provider' => 'youtube', 'video_id' => 'dQw4w9WgXcQ',
    'video_attribution' => ['original_title' => 'Original video title', 'creator' => 'Original creator',
        'source_url' => 'https://youtu.be/dQw4w9WgXcQ', 'license' => 'cc_by',
        'attribution_text' => '<script>window.injected = true</script>', 'permission_status' => 'pending'],
]);
$topic->id = 4242;
echo Illuminate\\Support\\Facades\\Blade::render('<x-video-attribution :topic="$topic" />', ['topic' => $topic]);
echo '<form><label for="test-source">Video source type</label><select id="test-source" name="video_source"><option value="url">URL</option><option value="upload">Upload</option></select>';
echo view('instructor.topics.partials.video-attribution', ['topic' => $topic, 'errors' => new Illuminate\\Support\\ViewErrorBag])->render();
echo '</form>';
`], { encoding: 'utf8' });
const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
const css = readFileSync(`public/build/${manifest['resources/css/app.css'].file}`, 'utf8');
const browser = await puppeteer.launch({ headless: true });
try {
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setRequestInterception(true);
    page.on('request', request => request.abort());
    await page.setContent(`<style>${css}</style><main class="mx-auto max-w-3xl p-6">${markup}</main>`);
    await page.addScriptTag({ path: 'node_modules/alpinejs/dist/cdn.min.js' });
    await page.waitForFunction(() => window.Alpine && document.querySelector('[x-data]')._x_dataStack);

    for (const width of [1366, 390]) {
        await page.setViewport({ width, height: 844 });
        await page.focus('button[aria-haspopup="dialog"]');
        await page.keyboard.press('Enter');
        await page.waitForFunction(() => document.querySelector('dialog').open);
        assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Close');
        assert.equal(await page.$eval('dialog', el => document.getElementById(el.getAttribute('aria-labelledby')).textContent), 'Video source');
        await page.keyboard.down('Shift');
        await page.keyboard.press('Tab');
        await page.keyboard.up('Shift');
        assert.equal(await page.evaluate(() => document.activeElement.tagName), 'A', 'Backward tab stays in dialog');
        await page.keyboard.press('Tab');
        assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Close');
        assert.equal(await page.$eval('dialog', el => {
            const rect = el.getBoundingClientRect();
            return rect.left >= 0 && rect.right <= innerWidth && el.scrollWidth <= el.clientWidth;
        }), true, 'Modal fits the viewport');
        assert.equal(await page.evaluate(() => window.injected), undefined);
        assert.equal(await page.$eval('dialog', el => el.textContent.includes('Permission pending')), false);
        await page.screenshot({ path: `storage/framework/video-attribution-${width}.png`, fullPage: true });
        await page.keyboard.press('Escape');
        assert.equal(await page.$eval('dialog', el => el.open), false);
        assert.equal(await page.evaluate(() => document.activeElement.getAttribute('aria-haspopup')), 'dialog');
        await page.click('button[aria-haspopup="dialog"]');
        await page.click('dialog button');
        assert.equal(await page.$eval('dialog', el => el.open), false);
    }

    await page.select('#test-source', 'upload');
    await page.waitForFunction(() => !document.querySelector('#video_attribution_source_url').disabled);
    await page.select('#video_attribution_license', 'other');
    await page.waitForFunction(() => !document.querySelector('#video_attribution_license_name').disabled);
    await page.select('#test-source', 'url');
    await page.waitForFunction(() => document.querySelector('#video_attribution_source_url').disabled);
    assert.equal(await page.$eval('#video_attribution_original_title', el => el.value), 'Original video title');
    assert.equal(await page.$eval('#video_attribution_creator', el => el.value), 'Original creator');
    assert.deepEqual(errors, []);
    console.log('PASS: Real Blade modal at desktop/mobile widths; keyboard opening, focus containment, Escape/Close and focus return; escaped credits and conditional authoring fields. No database writes or provider requests.');
} finally {
    await browser.close();
}
