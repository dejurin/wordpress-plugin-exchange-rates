const { test, expect } = require('@playwright/test');

for (const inverse of [false, true]) {
  test(`missing JPY history does not invalidate amounts (${inverse ? 'inverse' : 'normal'})`, async ({ page }) => {
    await page.goto(`/?er-trend-test=1&inverse=${inverse ? 1 : 0}`);
    for (const id of ['shortcode-full', 'widget-full', 'shortcode-compact', 'widget-compact']) {
      const wrapper = page.locator(`#${id} .widget-exchange-rates-currency-table`);
      const input = wrapper.locator('input');
      const other = page.locator(`#${id === 'shortcode-full' ? 'widget-full' : 'shortcode-full'} table`);
      const untouched = await other.innerHTML();
      const previous = wrapper.locator('[data-currency="JPY"][data-rate-period="previous"]');
      await expect(previous).toHaveText('–');
      for (const value of ['2', 'invalid', '1', '0', '1e999', '3']) {
        await input.fill(value);
        const invalid = ['invalid', '1e999'].includes(value);
        if (invalid) {
          await expect(input).toHaveAttribute('aria-invalid', 'true');
          await expect(wrapper.locator('.er-a11y-error')).toBeVisible();
        } else {
          await expect(input).not.toHaveAttribute('aria-invalid', 'true');
          await expect(wrapper.locator('.er-a11y-error')).toBeHidden();
          await expect(wrapper.locator('[role="status"]')).toContainText('JPY previous close');
          await expect(wrapper.locator('[role="status"]')).not.toContainText('Unable to calculate');
          const current = wrapper.locator('td[data-currency="JPY"]:not([data-rate-period])');
          const number = await current.evaluate(node => node.firstChild.textContent.replace(/[^\d.]/g, ''));
          expect(Number(number)).toBeCloseTo(Number(value) * (inverse ? 100 : 0.01), 2);
        }
        await expect(previous).toHaveText('–');
      }
      expect(await other.innerHTML()).toBe(untouched);
    }
  });
}
