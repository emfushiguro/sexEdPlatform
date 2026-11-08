import { test, expect } from '@playwright/test';

test.describe('direct instructor public entry points', () => {
    test('shows factual recruitment content and guest actions', async ({ page }) => {
        await page.goto('/be-our-partner');

        await expect(page.getByRole('heading', { name: 'Be Our Partner' })).toBeVisible();
        await expect(page.getByText('Government ID plus Selfie')).toBeVisible();
        await expect(page.getByText('Sandbox is simulated where applicable')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Start your application' })).toHaveAttribute('href', /\/instructor\/apply$/);
        await expect(page.getByRole('link', { name: 'Sign in to resume' }).first()).toHaveAttribute('href', /\/login$/);
    });

    test('keeps the landing-page partner entry point available', async ({ page }) => {
        await page.goto('/');
        await expect(page.getByRole('link', { name: 'Be Our Partner' }).first()).toHaveAttribute('href', /\/be-our-partner$/);
    });

    test('mobile partner entry point remains reachable', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/');
        await expect(page.getByRole('link', { name: 'Be Our Partner' }).first()).toHaveAttribute('href', /\/be-our-partner$/);
    });

    test('@firefox-smoke recruitment page keeps the primary action visible', async ({ page }) => {
        await page.goto('/be-our-partner');
        await expect(page.getByRole('link', { name: 'Start your application' })).toBeVisible();
    });
});
