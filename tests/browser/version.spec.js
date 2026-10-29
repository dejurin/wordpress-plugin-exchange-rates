const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

const source = fs.readFileSync(path.resolve(__dirname, '../../plugin/trunk/src/Plugin.php'), 'utf8');
const version = source.match(/const VERSION = '([^']+)'/)[1];
const sections = ['shortcode-table', 'widget-table', 'static-table', 'shortcode-converter', 'widget-converter', 'badges'];
const selector = '[data-exchange-rates-version]';

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 1064]) {
    test(direction + ' ' + width + 'px: version survives rendering, spoiler and amount changes', async ({ page, baseURL }) => {
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
      await page.setViewportSize({ width, height: 900 });
      const response = await page.goto('/?' + new URLSearchParams({ 'er-bidi-test': '1', direction }));
      expect(response.ok()).toBeTruthy();
      expect((await response.text()).split('data-exchange-rates-version="' + version + '"')).toHaveLength(7);
      await expect(page.locator('#shortcode-converter .result')).not.toBeEmpty();
      for (const id of sections) {
        const section = page.locator('#' + id);
        await expect(section.locator(selector)).toHaveCount(1);
        await expect(section.locator(selector)).toHaveAttribute('data-exchange-rates-version', version);
        await expect(section.locator('[data-er-info]')).toHaveAttribute('aria-controls', 'exchange-rates-information');
      }
      const info = page.locator('#exchange-rates-information');
      await expect(info).toHaveCount(1);
      await expect(info.locator('.er-dialog-version')).toContainText(version);
      for (const id of ['shortcode-table', 'widget-table']) {
        const section = page.locator('#' + id);
        await expect(info).toBeHidden();
        await section.locator('[data-er-info]').click();
        await expect(info).toBeVisible();
        await expect(info.locator('.er-dialog-version')).toContainText(version);
        await info.locator('button').click();
        await expect(info).toBeHidden();
        await section.locator('input').fill('10');
      }
      for (const id of ['shortcode-converter', 'widget-converter']) {
        const converter = page.locator('#' + id + ' .widget-exchange-rates-currency-converter');
        await converter.locator('input').fill('10');
        await converter.locator('button.swap').click();
      }
      await expect(page.locator(selector)).toHaveCount(6);
      for (const id of sections) {
        await expect(page.locator('#' + id + ' ' + selector)).toHaveAttribute('data-exchange-rates-version', version);
      }
      expect(errors).toEqual([]);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    });
  }
}
