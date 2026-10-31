import { test, expect } from '@playwright/test';

test('application and document routes stay behind authentication', async ({ page }) => {
    await page.goto('/instructor-application/application');
    await expect(page).toHaveURL(/\/login$/);

    await page.goto('/instructor-application/application/1/documents/clearance');
    await expect(page).toHaveURL(/\/login$/);
});
