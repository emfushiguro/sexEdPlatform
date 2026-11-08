import { test, expect } from '@playwright/test';
import { fixture, instructorFixtures } from './fixtures/instructor-application.mjs';

test.describe('direct instructor applicant workflow', () => {
    test('unauthenticated applicants are sent to the sign-in gate', async ({ page }) => {
        await page.goto('/instructor-application');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('direct applicant can open the status hub and identity action', async ({ page }) => {
        test.skip(!instructorFixtures, 'Requires a guarded Playwright fixture alias file.');
        const account = fixture('directApplicant');

        await page.goto('/login');
        await page.getByLabel('Email or Username').fill(account.email);
        await page.getByLabel('Password').fill(account.password);
        await page.getByRole('button', { name: 'Login' }).click();
        await expect(page).toHaveURL(/\/instructor-application$/);
        await expect(page.getByRole('heading', { name: 'Your instructor application' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Start identity verification' })).toBeVisible();
    });
});
