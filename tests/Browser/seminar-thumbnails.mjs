import assert from 'node:assert/strict';
import { writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import puppeteer from 'puppeteer';

// Checks unsaved authoring controls and existing pages; never submits event changes.
const browser = await puppeteer.launch({ headless: true });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
const base = process.env.CC_QA_ORIGIN ?? 'http://127.0.0.1:8000';
const connector = process.env.CC_CONNECTOR_QA_ID ?? '2';
const event = process.env.CC_EVENT_QA_ID ?? '1';
const fixture = resolve('storage/framework/seminar-thumbnail-qa.png');
await writeFile(fixture, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64'));
try {
    await page.setViewport({ width: 1440, height: 1000 });
    await page.goto(`${base}/login`, { waitUntil: 'networkidle2' });
    await page.type('#email', process.env.CC_CONNECTOR_QA_EMAIL ?? 'teen@test.local');
    await page.type('#password', process.env.CC_QA_PASSWORD ?? 'password123');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('button[type="submit"]')]);
    await page.goto(`${base}/connector/${connector}/seminars/create`, { waitUntil: 'networkidle2' });
    assert.equal(await page.$eval('[name="thumbnail"]', el => el.form.enctype), 'multipart/form-data');
    assert.equal(await page.$eval('[name="purpose"]', el => el.required), false);
    assert.equal(await page.$eval('[name="capacity"]', el => el.max), '100000');
    assert.match(await page.$eval('#seminar-capacity-help', el => el.textContent), /Your organization decides.*blank for no registration limit/);
    const upload = await page.$('[name="thumbnail"]');
    await upload.uploadFile(fixture);
    await page.waitForFunction(() => { const image = document.querySelector('img[alt="Event thumbnail preview"]'); return image?.getClientRects().length && image.complete && image.naturalWidth > 0; });
    await page.screenshot({ path: 'storage/framework/seminar-thumbnail-desktop.png', fullPage: true });
    await page.setViewport({ width: 390, height: 844 });
    await page.waitForFunction(() => document.documentElement.scrollWidth <= innerWidth + 1);
    assert.equal(await page.$eval('img[alt="Event thumbnail preview"]', el => el.getBoundingClientRect().right <= innerWidth), true);
    await page.screenshot({ path: 'storage/framework/seminar-thumbnail-mobile.png', fullPage: true });
    await page.evaluate(() => [...document.querySelectorAll('button')].find(el => el.textContent.trim() === 'Reset selection').click());
    await page.waitForFunction(() => document.querySelector('[name="thumbnail"]').files.length === 0 && !document.querySelector('img[alt="Event thumbnail preview"]').getClientRects().length);
    await page.goto(`${base}/connector/${connector}/seminars/${event}/edit`, { waitUntil: 'networkidle2' });
    assert.equal(await page.$eval('[name="purpose"]', el => el.required), false);
    assert.ok(await page.$('[name="thumbnail"]'));
    await page.goto(`${base}/connector/${connector}/seminars/${event}`, { waitUntil: 'networkidle2' });
    assert.ok(await page.$('[data-seminar-thumbnail]'));
    await page.setViewport({ width: 1440, height: 1000 });
    await page.screenshot({ path: 'storage/framework/seminar-thumbnail-event.png', fullPage: true });
    assert.deepEqual(errors, []);
    console.log('PASS: multipart optional upload, live preview/reset, optional Objectives, capacity help, desktop/mobile layout and existing-event thumbnail identity. No event changes submitted.');
} catch (error) {
    console.error('URL:', page.url(), 'JavaScript errors:', errors);
    await page.screenshot({ path: 'storage/framework/seminar-thumbnail-browser-failure.png', fullPage: true }).catch(() => {});
    throw error;
} finally {
    await browser.close();
}
