import assert from 'node:assert/strict';
import puppeteer from 'puppeteer';

// Uses the documented local test account; never submits listing or purchase changes.
const browser = await puppeteer.launch({ headless: true });
const page = await browser.newPage();
const errors = [];
const failedRequests = [];
page.on('response', response => {
    if (response.status() >= 400) failedRequests.push({ status: response.status(), url: response.url() });
});
let stage = 'login';
page.on('pageerror', error => errors.push(error.message));
try {
    await page.setViewport({ width: 1366, height: 900 });
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'domcontentloaded' });
    await page.type('#email', 'adult@test.local');
    await page.type('#password', process.env.CC_QA_PASSWORD ?? 'password123');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        page.click('button[type="submit"]'),
    ]);
    stage = 'module page';
    await page.goto('http://127.0.0.1:8000/instructor/modules/3', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('[aria-controls="connector-listing-drawer"]');
    stage = 'open drawer';
    await page.click('[aria-controls="connector-listing-drawer"]');
    await page.waitForFunction(() => document.activeElement?.id === 'connector-listing-drawer');
    stage = 'keyboard and seats';
    assert.equal(await page.evaluate(() => document.body.style.overflow), 'hidden');
    await page.keyboard.press('Tab');
    assert.equal(await page.evaluate(() => document.activeElement?.getAttribute('aria-label')), 'Close connector listing');
    await page.keyboard.down('Shift');
    await page.keyboard.press('Tab');
    await page.keyboard.up('Shift');
    assert.equal(await page.evaluate(() => document.activeElement?.value), 'publish');
    await page.keyboard.press('Tab');
    assert.equal(await page.evaluate(() => document.activeElement?.getAttribute('aria-label')), 'Close connector listing');
    await page.evaluate(() => {
        const input = document.querySelector('#marketplace-seats');
        input.value = '100000';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
    await page.waitForFunction(() => document.querySelector('[role="status"]')?.getClientRects().length);
    await page.evaluate(() => {
        const buttons = document.querySelector('[aria-label="Seat package suggestions"]').querySelectorAll('button');
        buttons[2].click();
    });
    await page.waitForFunction(() => document.querySelector('#marketplace-seats').value === '50');
    await page.evaluate(() => [...document.querySelectorAll('button')].find(button => button.textContent.trim() === 'Add package').click());
    await page.waitForSelector('input[name="seat_packages[0][seat_capacity]"]');
    await page.evaluate(() => {
        for (const [name, value] of [['seat_capacity', '25'], ['price_amount', '4000']]) {
            const input = document.querySelector(`input[name="seat_packages[0][${name}]"]`);
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
    const submission = await page.evaluate(() => {
        const form = document.querySelector('#connector-listing-form');
        const button = document.querySelector('button[form="connector-listing-form"][value="publish"]');
        return Object.fromEntries(new FormData(form, button));
    });
    assert.equal(submission.action, 'publish');
    assert.equal(submission.seat_capacity, '50');
    assert.equal(submission.currency, 'PHP');
    assert.equal(submission['seat_packages[0][seat_capacity]'], '25');
    assert.equal(submission['seat_packages[0][price_amount]'], '4000');
    await page.screenshot({ path: 'storage/framework/connector-marketplace-desktop.png' });
    await page.evaluate(() => [...document.querySelectorAll('button')].find(button => button.textContent.trim() === 'Remove package').click());
    await page.waitForSelector('input[name="seat_packages[0][seat_capacity]"]', { hidden: true });
    stage = 'close and mobile';
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => document.activeElement?.getAttribute('aria-controls') === 'connector-listing-drawer');
    await page.waitForSelector('#connector-listing-drawer', { hidden: true });
    assert.equal(await page.evaluate(() => document.body.style.overflow), '');
    await page.click('[aria-controls="connector-listing-drawer"]');
    await page.waitForFunction(() => document.activeElement?.id === 'connector-listing-drawer');
    await page.waitForFunction(() => document.querySelector('#connector-listing-drawer').getBoundingClientRect().left <= innerWidth - 512);
    stage = 'backdrop close';
    await page.mouse.click(20, 250);
    await page.waitForFunction(() => document.activeElement?.getAttribute('aria-controls') === 'connector-listing-drawer');
    await page.waitForSelector('#connector-listing-drawer', { hidden: true });
    stage = 'mobile';
    await page.setViewport({ width: 390, height: 844 });
    await page.click('[aria-controls="connector-listing-drawer"]');
    await page.waitForFunction(() => document.activeElement?.id === 'connector-listing-drawer');
    await page.waitForFunction(() => document.querySelector('#connector-listing-drawer').getBoundingClientRect().left < 1);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await page.screenshot({ path: 'storage/framework/connector-marketplace-mobile.png' });
    await page.click('[aria-label="Close connector listing"]');
    await page.waitForFunction(() => document.activeElement?.getAttribute('aria-controls') === 'connector-listing-drawer');
    assert.deepEqual(failedRequests.filter(({ status, url }) => status !== 403 || new URL(url).pathname !== '/chat/conversations'), []);
    assert.deepEqual(errors.filter(error => !error.startsWith('AxiosError: Request failed with status code 403')), []);
    if (failedRequests.length) console.warn('Existing page request failures:', JSON.stringify(failedRequests));
    console.log('PASS: desktop/mobile drawer, focus trap, Escape/backdrop, scroll lock, seat presets, package add/remove, capacity warning, and form submission fields.');
} catch (error) {
    await page.screenshot({ path: 'storage/framework/connector-marketplace-browser-failure.png' });
    console.error(JSON.stringify({ stage, url: page.url(), errors, failedRequests }));
    throw error;
} finally {
    await browser.close();
}
