import assert from 'node:assert/strict';
import puppeteer from 'puppeteer';

// Uses existing QA accounts and GETs event pages; never saves events or attendance decisions.
const browser = await puppeteer.launch({ headless: true });
const errors = [];
const base = process.env.CC_QA_ORIGIN ?? 'http://127.0.0.1:8000';
const connector = process.env.CC_CONNECTOR_QA_ID ?? '2';
const event = process.env.CC_EVENT_QA_ID ?? '1';
let stage = 'login';
let page = await browser.newPage();
page.on('pageerror', error => errors.push(error.message));
async function login(email, portal = '/login') {
    await page.goto(`${base}${portal}`, { waitUntil: 'networkidle2' });
    await page.type('#email', email);
    await page.type('#password', process.env.CC_QA_PASSWORD ?? 'password123');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('button[type="submit"]')]);
}
async function drawer() {
    const trigger = await page.$('button[aria-haspopup="dialog"]');
    assert.ok(trigger, 'Access drawer trigger exists');
    await trigger.click();
    await page.waitForFunction(() => document.querySelector('[aria-labelledby="event-access-title"]')?.getClientRects().length && document.activeElement?.textContent.trim() === 'Close');
    assert.equal(await page.evaluate(() => document.body.style.overflow), 'hidden');
    assert.equal(await page.$eval('[aria-labelledby="event-access-title"]', el => document.elementFromPoint(el.getBoundingClientRect().right - 25, 40)?.closest('[aria-labelledby="event-access-title"]') === el), true, 'Drawer is above the navigation');
    await page.keyboard.down('Shift');
    await page.keyboard.press('Tab');
    await page.keyboard.up('Shift');
    assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Save access details');
    await page.keyboard.press('Tab');
    assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Close');
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => document.body.style.overflow !== 'hidden');
    assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Manage event access');
}
try {
    await page.setViewport({ width: 1440, height: 1000 });
    await login(process.env.CC_CONNECTOR_QA_EMAIL ?? 'teen@test.local');
    stage = 'schedule form';
    await page.goto(`${base}/connector/${connector}/seminars/create`, { waitUntil: 'networkidle2' });
    await page.waitForFunction(() => typeof window.seminarSchedule === 'function');
    const input = async (selector, value) => page.$eval(selector, (el, value) => { el.value = value; el.dispatchEvent(new Event('input', { bubbles: true })); }, value);
    await input('input[type="date"]', '2026-12-31');
    await input('input[type="time"]', '23:30');
    await page.waitForFunction(() => document.querySelector('[name="ends_at"]').value === '2027-01-01T00:30');
    assert.equal(await page.$eval('[name="registration_deadline_at"]', el => el.value), '2026-12-31T23:00');
    assert.equal(await page.$eval('[name="external_link_visible_at"]', el => el.value), '2026-12-31T23:15');
    await input('[name="registration_deadline_at"]', '');
    await input('input[type="time"]', '22:30');
    assert.equal(await page.$eval('[name="registration_deadline_at"]', el => el.value), '');
    assert.equal(await page.$eval('[name="external_link_visible_at"]', el => el.value), '2026-12-31T22:15');
    await page.screenshot({ path: 'storage/framework/event-schedule-desktop.png', fullPage: true });
    await page.setViewport({ width: 390, height: 844 });
    await page.waitForFunction(() => document.documentElement.scrollWidth <= window.innerWidth + 1, { timeout: 5000 });
    await page.waitForFunction(() => document.querySelector('input[type="date"]').getBoundingClientRect().width >= 200, { timeout: 5000 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), true, 'Schedule form fits mobile width');
    assert.equal(await page.$eval('input[type="date"]', el => el.getBoundingClientRect().width >= 200), true, 'Mobile date control has room for the full date');
    await page.screenshot({ path: 'storage/framework/event-schedule-mobile.png', fullPage: true });
    await page.setViewport({ width: 1440, height: 1000 });
    stage = 'connector access drawer';
    await page.goto(`${base}/connector/${connector}/seminars/${event}`, { waitUntil: 'networkidle2' });
    await drawer();
    await page.setViewport({ width: 390, height: 844 });
    stage = 'mobile access drawer';
    await drawer();
    await page.goto(`${base}/connector/${connector}/seminars/${event}/attendance`, { waitUntil: 'networkidle2' });
    stage = 'attendance roster';
    assert.ok(await page.$('[data-participant-profile]'), 'Roster participant identity is rendered');
    await page.screenshot({ path: 'storage/framework/event-attendance-mobile.png', fullPage: true });
    const adminContext = await browser.createBrowserContext();
    await page.close();
    page = await adminContext.newPage();
    page.on('pageerror', error => errors.push(error.message));
    stage = 'admin login';
    await login('admin@test.local', '/secure-panel-access');
    await page.setViewport({ width: 1440, height: 1000 });
    stage = 'admin review and drawer';
    await page.goto(`${base}/admin/seminars/${event}`, { waitUntil: 'networkidle2' });
    await drawer();
    assert.equal(await page.$('[name="attendance_share_code"]'), null);
    await page.screenshot({ path: 'storage/framework/event-admin-review-desktop.png', fullPage: true });
    assert.deepEqual(errors, []);
    console.log('PASS: schedule suggestions/manual clear, desktop/mobile layout, host/admin drawer focus/Escape/layering, roster identity; no event changes submitted.');
} catch (error) {
    console.error('Browser check failed at:', stage, 'URL:', page.url(), 'JavaScript errors:', errors);
    console.error('Overflowing elements:', await page.evaluate(() => [...document.querySelectorAll('body *')].filter(el => { const r = el.getBoundingClientRect(); return r.width > 0 && r.right > innerWidth + 1; }).slice(0, 8).map(el => ({ tag: el.tagName, class: el.className, right: el.getBoundingClientRect().right, width: el.getBoundingClientRect().width }))).catch(() => []));
    await page.screenshot({ path: 'storage/framework/event-refinements-browser-failure.png', fullPage: true }).catch(() => {});
    throw error;
} finally {
    await browser.close();
}
