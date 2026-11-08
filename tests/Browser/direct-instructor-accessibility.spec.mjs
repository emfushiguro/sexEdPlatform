import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test('recruitment page exposes a main landmark and no detected axe violations', async ({ page }) => {
    await page.goto('/be-our-partner');
    await expect(page.locator('main#partner-main')).toBeVisible();

    const results = await new AxeBuilder({ page }).analyze();
    expect(results.violations).toEqual([]);
});
