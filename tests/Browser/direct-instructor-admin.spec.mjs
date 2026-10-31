import { test, expect } from '@playwright/test';
import { fixture, instructorFixtures } from './fixtures/instructor-application.mjs';

test('administrator can open the instructor identity queue', async ({ page }) => {
    test.skip(!instructorFixtures, 'Requires a guarded Playwright fixture alias file.');
    const account = fixture('adminReviewer');

    await page.goto('/secure-panel-access');
    await page.getByLabel('Email').fill(account.email);
    await page.getByLabel('Password').fill(account.password);
    await page.getByRole('button', { name: /login/i }).click();
    await expect(page).toHaveURL(/\/admin\/dashboard$/);
    await page.goto('/admin/instructor-applications/identities');
    await expect(page.getByRole('heading', { name: 'Identity Review' })).toBeVisible();
});
