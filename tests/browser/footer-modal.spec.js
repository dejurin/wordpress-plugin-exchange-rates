const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');

test.beforeEach(async ({ page, baseURL }) => {
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
});

for (const width of [320, 390, 1064]) {
  test(`shared information dialog: all widgets, ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/?er-bidi-test=1');
    const dialog = page.locator('#exchange-rates-information');
    await expect(dialog).toHaveCount(1);
    await expect(dialog).toBeHidden();
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(new Set(ids).size).toBe(ids.length);
    const openers = page.locator('[data-er-info]');
    await expect(openers).toHaveCount(6);
    for (const opener of await openers.all()) {
      const date = await opener.locator('time').getAttribute('datetime');
      await opener.press('Enter');
      await expect(dialog).toBeVisible();
      expect(await dialog.evaluate(node => node.matches(':modal'))).toBe(true);
      await expect(dialog.locator('h2')).toBeFocused();
      await expect(dialog.locator('[data-er-modal-date]')).toHaveAttribute('datetime', new Date(date).toISOString());
      await expect(dialog.locator('[data-er-modal-date]')).toContainText('UTC');
      await expect(dialog.locator('.er-dialog-details dt')).toHaveText(['Source', 'Check:', 'Latest change:']);
      await expect(dialog.locator('[data-er-modal-recorded]')).toHaveAttribute('datetime', new Date(await opener.getAttribute('data-recorded-at')).toISOString());
      for (const row of await dialog.locator('.er-dialog-details > div').all()) {
        expect(await row.evaluate(node => {
          const label = node.querySelector('dt').getBoundingClientRect();
          const value = node.querySelector('dd').getBoundingClientRect();
          const bounds = node.getBoundingClientRect();
          return label.right <= bounds.right + 1 && value.right <= bounds.right + 1 &&
            (label.right <= value.left + 1 || label.bottom <= value.top + 1);
        })).toBe(true);
      }
      await expect(dialog.locator('.er-dialog-disclaimer')).toContainText('[ExchangeRatesWP]');
      for (const link of await dialog.locator('a[href]').all()) {
        await expect(link).toHaveAttribute('rel', /\bnofollow\b/);
        await expect(link).toHaveCSS('text-decoration-line', 'none');
      }
      await dialog.locator('h2').press('Shift+Tab');
      await expect(dialog.locator('a').last()).toBeFocused();
      await dialog.locator('a').last().press('Tab');
      await expect(dialog.locator('button')).toBeFocused();
      await dialog.locator('button').press('Escape');
      await expect(dialog).toBeHidden();
      await expect(opener).toBeFocused();
    }
    for (const link of await page.locator('.er-footer-slot a').all()) {
      await expect(link).not.toHaveAttribute('rel', /\bnofollow\b/);
      await expect(link).toHaveCSS('text-decoration-line', 'none');
      await link.hover();
      await expect(link).toHaveCSS('text-decoration-line', 'none');
    }
  });
}

for (const mode of ['unsupported', 'throws', 'not-modal', 'no-scripts']) {
  test(`information stays hidden when ${mode}`, async ({ page }) => {
    await page.route('**/*', async route => {
      if (mode === 'no-scripts' && route.request().resourceType() === 'script') return route.abort();
      if (route.request().resourceType() !== 'document') return route.fallback();
      const response = await route.fetch();
      const code = mode === 'unsupported' ? 'HTMLDialogElement.prototype.showModal = undefined;' :
        mode === 'throws' ? 'HTMLDialogElement.prototype.showModal = function(){throw new Error("simulated failure")};' :
        mode === 'not-modal' ? 'HTMLDialogElement.prototype.showModal = function(){this.setAttribute("open", "")};' : '';
      await route.fulfill({ response, body: (await response.text()).replace('<head>', '<head><script>' + code + '</script>') });
    });
    await page.goto('/?er-bidi-test=1');
    const opener = page.locator('[data-er-info]').first();
    if (await opener.isEnabled()) await opener.click();
    else await expect(opener).toBeDisabled();
    const dialog = page.locator('#exchange-rates-information');
    await expect(dialog).toBeHidden();
    expect(await dialog.evaluate(node => node.getClientRects().length)).toBe(0);
    await expect(dialog).toHaveAttribute('hidden', '');
  });
}

test('new markup with published 1.4.2 JavaScript keeps modal hidden and calculations usable', async ({ page }) => {
  const legacy = execFileSync('git', ['show', '4555660:plugin/trunk/assets/js/public/common.js'], { encoding: 'utf8' });
  await page.route('**/assets/js/public/common.js?*', route => route.fulfill({ contentType: 'text/javascript', body: legacy }));
  await page.route('**/assets/js/public/footer.js?*', route => route.abort());
  await page.goto('/?er-bidi-test=1');
  await page.locator('#shortcode-converter input').fill('100');
  await expect(page.locator('#shortcode-converter .result')).toHaveText('R$20');
  await page.locator('[data-er-info]').first().click();
  await expect(page.locator('#exchange-rates-information')).toBeHidden();
});

test('cached table without new metadata continues to calculate with new JavaScript', async ({ page }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('**/*', async route => {
    if (route.request().resourceType() !== 'document') return route.fallback();
    const response = await route.fetch();
    const body = (await response.text()).replace(/ data-(?:base-currency|inverse|currency|rate-period)="[^"]*"/g, '');
    await route.fulfill({ response, body });
  });
  await page.goto('/?er-bidi-test=1&format=3');
  await page.locator('#shortcode-table input').fill('8');
  await expect(page.locator('#shortcode-table td[data-rate]').first()).toContainText('10');
  await page.locator('#shortcode-converter input').fill('100');
  await expect(page.locator('#shortcode-converter .result')).toHaveText('R$20');
  expect(errors).toEqual([]);
});

test('converter announces invalid amount and recovery without duplicating the visible result', async ({ page }) => {
  await page.goto('/?er-bidi-test=1&format=3');
  const converter = page.locator('#shortcode-converter .widget-exchange-rates-currency-converter');
  await converter.locator('input').fill('invalid');
  await expect(converter.locator('input')).toHaveAttribute('aria-invalid', 'true');
  await expect(converter.locator('.er-a11y-error')).toBeVisible();
  await expect(converter.getByRole('status')).toHaveText('Unable to calculate. Enter a valid amount.');
  await converter.locator('input').fill('100');
  await expect(converter.locator('.er-a11y-error')).toBeHidden();
  await expect(converter.getByRole('status')).toHaveText('100 USD equals 20 BRL');
  await expect(converter.locator('.result')).toHaveAttribute('aria-hidden', 'true');
});
