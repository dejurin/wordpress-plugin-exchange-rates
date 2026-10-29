const { test, expect } = require('@playwright/test');

for (const direction of ['ltr', 'rtl']) {
  test(`history table adapts to its container and exposes overflow (${direction})`, async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 900 });
    await page.goto(`/?er-layout-test=1&fit=1&direction=${direction}`);
    for (const kind of ['shortcode', 'widget']) {
      const wrapper = page.locator(`#${kind}-fit .widget-exchange-rates-currency-table`);
      await expect(wrapper).toHaveClass(/exchange-rates-table-history/);
      await expect.poll(() => wrapper.evaluate(node => node.scrollWidth - node.clientWidth)).toBeLessThanOrEqual(1);
      await expect(wrapper.locator('thead th').last()).toHaveAccessibleName('Changes');
      await expect(wrapper.locator('thead th').nth(2)).toHaveAccessibleName('Previous Close');
      await wrapper.locator('input').fill('123456789012345');
      await expect(wrapper).toHaveAttribute('tabindex', '0');
      await expect(wrapper).toHaveAttribute('role', 'region');
      await expect(page.locator(`#${kind}-fit .exchange-rates-table-scroll-note`)).toBeVisible();
      expect(await wrapper.evaluate(node => {
        node.style.scrollBehavior = 'auto';
        node.scrollLeft = getComputedStyle(node).direction === 'rtl' ? -node.scrollWidth : node.scrollWidth;
        const box = node.getBoundingClientRect(), cell = node.querySelector('thead th:last-child').getBoundingClientRect();
        return cell.left >= box.left - 1 && cell.right <= box.right + 1;
      })).toBeTruthy();
      await wrapper.locator('input').fill('1');
      await expect(wrapper).not.toHaveAttribute('tabindex', '0');
      await expect(page.locator(`#${kind}-fit .exchange-rates-table-scroll-note`)).toBeHidden();
    }
    await page.setViewportSize({ width: 1000, height: 900 });
    await expect(page.locator('.exchange-rates-table-reflow')).toHaveCount(0);
    await expect(page.locator('.exchange-rates-table-short-title').first()).toBeHidden();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
  });
}

test('custom history headings and non-JavaScript output retain full text', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 900 });
  await page.goto('/?er-layout-test=1&fit=1&custom=1');
  await expect(page.locator('thead th').filter({ hasText: 'Saved previous rate' })).toHaveCount(2);
  await expect(page.locator('thead th').filter({ hasText: 'Saved movement' })).toHaveCount(2);
  await expect(page.locator('abbr')).toHaveCount(0);
});

test('full history names remain visible without JavaScript', async ({ browser, baseURL }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 320, height: 900 } });
  const page = await context.newPage();
  await page.goto(baseURL + '/?er-layout-test=1&fit=1');
  await expect(page.locator('.exchange-rates-table-full-title').first()).toBeVisible();
  await expect(page.locator('.exchange-rates-table-short-title').first()).toBeHidden();
  await context.close();
});
