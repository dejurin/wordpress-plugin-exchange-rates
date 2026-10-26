const { test, expect } = require('@playwright/test');

async function verify(page, baseURL, options) {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('**/*', route => new URL(route.request().url()).origin === baseURL ? route.continue() : route.abort());
  await page.goto('/?' + new URLSearchParams({ 'er-small-test': '1', matrix: '1', ...options }));
  const precision = Number(options.precision);
  const point = [0, 2, 5, 7].includes(Number(options.format)) ? ',' : '.';
  const limit = precision ? '0' + point + '0'.repeat(precision - 1) + '1' : '1';
  const decorate = value => options.symbol === '1' ? (options.after === '1' ? value + '€' : '€' + value) : value;
  for (const host of ['shortcode', 'widget']) {
    const table = page.locator(`#${host}-table`);
    const cell = table.locator('td[data-rate]').first();
    const input = table.locator('input');
    // The table shortcode has no symbol attribute; native widgets do.
    const tableValue = value => host === 'widget' ? decorate(value) : value;
    await expect(cell).toHaveText(tableValue('<' + limit));
    const raw = await cell.getAttribute('data-rate');
    for (const amount of ['2', '1', '16', '1']) {
      await input.fill(amount);
      await input.blur();
      await expect(cell).toHaveText(tableValue(amount === '16' ? limit.replace('1', '2') : '<' + limit));
      await expect(input).not.toHaveAttribute('aria-invalid', 'true');
      expect(await cell.getAttribute('data-rate')).toBe(raw);
    }
    await expect(table.locator('td[data-change]').first()).toHaveText('–');
    await expect(cell.locator('img')).toHaveCount(0);
    const converter = page.locator(`#${host}-converter`);
    await expect(converter.locator('.result')).toHaveText(decorate('<' + limit));
    for (const [amount, expected] of [['-1', '>-' + limit], ['0', '0'], ['abc', '–'], ['1', '<' + limit]]) {
      await converter.locator('input').fill(amount);
      await expect(converter.locator('.result')).toHaveText(amount === 'abc' ? '–' : decorate(expected));
    }
  }
  await expect(page.locator('#badge')).toContainText('<' + limit);
  expect(errors).toEqual([]);
}
for (let format = 0; format < 8; format++) {
  for (let precision = 0; precision < 8; precision++) {
    test(`small values: format ${format}, precision ${precision}`, async ({ page, baseURL }) => {
      await verify(page, baseURL, { format: String(format), precision: String(precision) });
    });
  }
}
for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 1064]) {
    for (const after of ['0', '1']) {
      test(`small values: ${direction}, ${width}px, symbol position ${after}`, async ({ page, baseURL }) => {
        await page.setViewportSize({ width, height: 950 });
        await verify(page, baseURL, { format: '0', precision: '4', direction, symbol: '1', after });
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
      });
    }
  }
}
