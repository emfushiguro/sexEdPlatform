import assert from 'node:assert/strict';
import puppeteer from 'puppeteer';

// Uses documented local test accounts. Only GETs marketplace/checkout; never pays or saves offers.
const browser = await puppeteer.launch({ headless: true });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
try {
    await page.setViewport({ width: 1366, height: 900 });
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'domcontentloaded' });
    await page.type('#email', process.env.CC_CONNECTOR_QA_EMAIL ?? 'teen@test.local');
    await page.type('#password', process.env.CC_QA_PASSWORD ?? 'password123');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('button[type="submit"]')]);
    const connector = process.env.CC_CONNECTOR_QA_ID ?? '2';
    await page.goto(`http://127.0.0.1:8000/connector/${connector}/modules`, { waitUntil: 'networkidle2' });
    await page.waitForFunction(() => window.Alpine?.store('sidebar'));
    assert.equal(await page.evaluate(() => document.documentElement.classList.contains('dark')), false, 'Light Connector workspace must not inherit dark-only learner cards');
    await page.waitForSelector('a[href*="/marketplace/"]');
    assert.equal(await page.$eval('body', el => el.innerText.includes('Created by:')), true);
    assert.equal(await page.$eval('body', el => el.innerText.includes('Start Learning')), false);
    assert.equal(await page.$('[role="progressbar"]'), null);
    await page.screenshot({ path: 'storage/framework/connector-marketplace-cards-desktop.png', fullPage: true });
    const detailUrl = await page.evaluate(() => [...document.querySelectorAll('a')].find(a => a.textContent.trim() === 'View details')?.href);
    assert.ok(detailUrl);
    await page.goto(detailUrl, { waitUntil: 'networkidle2' });
    await page.waitForSelector('#organization-license');
    const text = await page.$eval('body', el => el.innerText);
    for (const label of ['About this module', 'Organization license', 'Eligible learners', 'Non-refundable', 'Assign seats after purchase']) {
        assert.ok(text.includes(label), label);
    }
    await page.screenshot({ path: 'storage/framework/connector-marketplace-details-desktop.png', fullPage: true });
    const packageSeats = await page.$eval('input[name="seat_capacity"]:checked', el => el.value);
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.click('#organization-license button[type="submit"]')]);
    assert.equal(await page.$eval('#payment-form input[name="seat_capacity"]', el => el.value), packageSeats);
    assert.ok((await page.$eval('body', el => el.innerText)).includes('Non-refundable'));
    await page.setViewport({ width: 390, height: 844 });
    await page.goto(detailUrl, { waitUntil: 'networkidle2' });
    await page.waitForSelector('#organization-license');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await page.screenshot({ path: 'storage/framework/connector-marketplace-details-mobile.png', fullPage: true });
    await page.goto(`http://127.0.0.1:8000/connector/${connector}/modules`, { waitUntil: 'networkidle2' });
    await page.waitForSelector('a[href*="/marketplace/"]');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await page.screenshot({ path: 'storage/framework/connector-marketplace-cards-mobile.png', fullPage: true });
    assert.deepEqual(errors, []);
    console.log('PASS: Connector desktop/mobile cards and details, license information, no personal progress, package selection and checkout summary. No purchases submitted.');
} catch (error) {
    await page.screenshot({ path: 'storage/framework/connector-marketplace-presentation-failure.png', fullPage: true });
    console.error(JSON.stringify({ url: page.url(), errors }));
    throw error;
} finally {
    await browser.close();
}
