const { test, expect } = require('@playwright/test');
const { captionHtml } = require('./captions');

const labels = {
  en: ['Amount in BAM', 'From currency', 'To currency', 'Swap currencies'],
  uk: ['Сума в BAM', 'Початкова валюта', 'Цільова валюта', 'Поміняти валюти місцями'],
  bg_BG: ['Сума в BAM', 'Начална валута', 'Целева валута', 'Размяна на валутите'],
};

for (const [language, names] of Object.entries(labels)) {
  for (const direction of ['ltr', 'rtl']) {
    for (const width of [320, 1064]) {
      test(`converter controls: ${language}, ${direction}, ${width}px`, async ({ page, baseURL }) => {
        await page.setViewportSize({ width, height: 900 });
        await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
        await page.goto('/?' + new URLSearchParams({ 'er-visual-test': '1', surface: 'converter', language, direction }));
        await expect(page.locator('#converter-default .result')).toBeVisible();
        const footers = await captionHtml(page);
        for (const id of ['converter-default', 'converter-custom', 'converter-empty', 'converter-widget']) {
          const section = page.locator('#' + id);
          const amount = section.getByRole('textbox', { name: names[0], exact: true });
          const from = section.getByRole('combobox', { name: names[1], exact: true });
          const to = section.getByRole('combobox', { name: names[2], exact: true });
          const swap = section.getByRole('button', { name: names[3], exact: true });
          await expect(amount).toHaveCount(1);
          await expect(from).toHaveValue('BAM');
          await expect(to).toHaveValue('ZAR');
          await amount.fill('100');
          const original = await section.locator('.result').innerText();
          await amount.press('Tab');
          await expect(swap).toBeFocused();
          await swap.press('Enter');
          await expect(from).toHaveValue('ZAR');
          await expect(to).toHaveValue('BAM');
          await expect(section.locator('.result')).not.toHaveText(original);
          await swap.press('Space');
          await expect(from).toHaveValue('BAM');
          await expect(to).toHaveValue('ZAR');
          await expect(section.locator('.result')).toHaveText(original);
        }
        expect(await captionHtml(page)).toEqual(footers);
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
      });
    }
  }
}

test('frontend uses jQuery without loading unused jQuery UI', async ({ page, baseURL }) => {
  const requests = [];
  page.on('request', request => requests.push(request.url()));
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
  await page.goto('/?er-bidi-test=1');
  await expect(page.locator('#shortcode-converter .result')).toBeVisible();
  expect(requests.some(url => /\/jquery\/jquery\.min\.js/.test(url))).toBe(true);
  expect(requests.filter(url => /\/jquery\/ui\/(?:core|menu|autocomplete)(?:\.min)?\.js/.test(url))).toEqual([]);
  await page.locator('#shortcode-converter input').fill('100');
  await expect(page.locator('#shortcode-converter .result')).toHaveText('R$20');
});

test('cached converter markup without label metadata still calculates and swaps', async ({ page, baseURL }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('**/*', async route => {
    if (new URL(route.request().url()).origin !== new URL(baseURL).origin) return route.abort();
    if (route.request().resourceType() !== 'document') return route.continue();
    const response = await route.fetch();
    const body = (await response.text()).replace(/ data-label-(?:amount|from|to|swap)="[^"]*"/g, '');
    return route.fulfill({ response, body });
  });
  await page.goto('/?er-bidi-test=1');
  for (const host of ['shortcode', 'widget']) {
    const converter = page.locator(`#${host}-converter`);
    await converter.locator('input').fill('100');
    await expect(converter.locator('.result')).toHaveText('R$20');
    await converter.locator('button.swap').click();
    await expect(converter.locator('.result')).toHaveText('$500');
  }
  expect(errors).toEqual([]);
});
