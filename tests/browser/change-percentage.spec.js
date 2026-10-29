const { test, expect } = require('@playwright/test');

const ids = ['shortcode-full', 'widget-full', 'shortcode-compact', 'widget-compact'];
async function open(page, baseURL, direction, format = 3, precision = 2) {
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
  await page.goto(`/?er-trend-test=1&changes=1&direction=${direction}&format=${format}&precision=${precision}`);
  return errors;
}
function cells(table, index) {
  const row = table.locator('tbody tr').nth(index);
  return { price: row.locator('td').nth(0), previous: row.locator('td').nth(1), change: row.locator('td').nth(2) };
}
async function change(table, index, text, trend, label) {
  const row = cells(table, index);
  await expect(row.change).toHaveText(text);
  await expect(row.change).toHaveAttribute('aria-label', label);
  await expect(row.change.locator('img,svg')).toHaveCount(0);
  await expect(row.price.locator('img')).toHaveCount(trend ? 1 : 0);
  if (trend) {
    await expect(row.price.locator('img')).toHaveAttribute('alt', '');
    await expect(row.price.locator('img')).toHaveAttribute('aria-hidden', 'true');
    await expect(row.price.locator('.er-a11y-trend')).toHaveText('; ' + label);
    await expect(row.price.locator('img')).toHaveCSS('width', '14px');
    await expect(row.change).toHaveClass(new RegExp(trend === 'up' ? 'table-success' : 'table-danger'));
  } else {
    await expect(row.change).not.toHaveClass(/table-success|table-danger/);
  }
}
for (const direction of ['ltr', 'rtl']) {
  for (const width of [1280, 320]) {
    for (const format of [0, 3]) {
      test(`${direction} ${width}px format ${format}: percentage, rounding, amount isolation`, async ({ page, baseURL }) => {
        await page.setViewportSize({ width, height: 900 });
        const errors = await open(page, baseURL, direction, format);
        const decimal = text => format === 0 ? text.replace('.', ',') : text;
        for (const id of ids) {
          const table = page.locator(`#${id} table`);
          await change(table, 0, decimal('4.17%'), 'up', `Increase: ${decimal('4.17%')}`);
          await change(table, 1, '10%', 'down', 'Decrease: 10%');
          await change(table, 2, decimal('0.16%'), 'up', `Increase: ${decimal('0.16%')}`);
          await change(table, 3, '–', null, 'Change is below display precision.');
          await change(table, 4, '–', null, 'No change.');
          await change(table, 5, '–', null, 'Previous rate unavailable.');
          await change(table, 6, decimal('<0.01%'), 'up', `Increase: ${decimal('<0.01%')}`);
          const otherId = id === ids[0] ? ids[1] : ids[0];
          const other = await page.locator(`#${otherId} table`).innerHTML();
          for (const amount of ['100', '1', '100', '1']) {
            await table.locator('input').fill(amount);
            if (amount === '100') await change(table, 3, decimal('11.1%'), 'up', `Increase: ${decimal('11.1%')}`);
            else await change(table, 3, '–', null, 'Change is below display precision.');
            await change(table, 0, decimal('4.17%'), 'up', `Increase: ${decimal('4.17%')}`);
            await change(table, 1, '10%', 'down', 'Decrease: 10%');
            await change(table, 4, '–', null, 'No change.');
            await change(table, 5, '–', null, 'Previous rate unavailable.');
            expect(await page.locator(`#${otherId} table`).innerHTML()).toBe(other);
          }
        }
        expect(errors).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
      });
    }
  }
  for (const precision of [0, 4, 7]) {
    test(`${direction} precision ${precision}: server/client visibility parity`, async ({ page, baseURL }) => {
      const errors = await open(page, baseURL, direction, 3, precision);
      const table = page.locator('#shortcode-full table');
      const before = await table.locator('tbody tr').evaluateAll(rows => rows.slice(0, -1).map(row => ({
        change: row.cells[3].textContent, label: row.cells[3].getAttribute('aria-label'),
        color: row.cells[3].className, icon: row.cells[1].querySelector('img')?.getAttribute('src') || null,
      })));
      await table.locator('input').fill('1');
      const after = await table.locator('tbody tr').evaluateAll(rows => rows.slice(0, -1).map(row => ({
        change: row.cells[3].textContent, label: row.cells[3].getAttribute('aria-label'),
        color: row.cells[3].className, icon: row.cells[1].querySelector('img')?.getAttribute('src') || null,
      })));
      expect(after).toEqual(before);
      await change(table, 3, precision === 0 ? '–' : '11.1%', precision === 0 ? null : 'up', precision === 0 ? 'Change is below display precision.' : 'Increase: 11.1%');
      expect(errors).toEqual([]);
    });
  }
}
