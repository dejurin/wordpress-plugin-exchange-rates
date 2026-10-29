const { test, expect } = require('@playwright/test');

const tables = ['shortcode-full', 'widget-full', 'shortcode-compact', 'widget-compact'];
const directions = ['ltr', 'rtl'];

async function openFixture(page, baseURL, direction, inverse, after = false) {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
  const query = new URLSearchParams({ 'er-trend-test': '1', direction, inverse: inverse ? '1' : '0', after: after ? '1' : '0' });
  expect((await page.goto(`/?${query}`)).ok()).toBeTruthy();
  await expect(page.locator('html')).toHaveAttribute('dir', direction);
  return errors;
}

async function expectSemantics(table, inverse) {
  const rows = table.locator('tbody tr');
  await expect(rows).toHaveCount(6);
  // Expected values are independent of the renderer; inverse pairs flip trends.
  const values = inverse ? ['0.8', '0.67', '1.33', '5', '100'] : ['1.25', '1.5', '0.75', '0.2', '0.01'];
  const trends = inverse ? ['down', 'up', null, 'up', null, null] : ['up', 'down', null, 'down', null, null];
  for (let index = 0; index < trends.length; index++) {
    const cells = rows.nth(index).locator('td');
    const icon = cells.nth(0).locator('img');
    await expect(icon).toHaveCount(trends[index] ? 1 : 0);
    if (trends[index]) {
      await expect(icon).toHaveAttribute('alt', '');
      await expect(icon).toHaveAttribute('aria-hidden', 'true');
      await expect(icon).toHaveAttribute('src', new RegExp('/' + trends[index] + '\\.svg$'));
      await expect(cells.nth(0).locator('.er-a11y-trend')).toHaveCount(1);
    }
    if (index < values.length) {
      const symbol = await cells.nth(0).getAttribute('data-symbol');
      expect((await cells.nth(0).evaluate(node => { const copy = node.cloneNode(true); copy.querySelectorAll('.er-a11y-sr-only').forEach(n => n.remove()); return copy.textContent; })).replace(symbol || '\0', '')).toBe(values[index]);
    }
  }
  await expect(rows.nth(4).locator('td').nth(1)).toHaveText('–');
  await expect(table.locator('input')).toHaveValue('1');
}

// These contracts must also pass on published 1.3.2 without any new asset assertions.
for (const direction of directions) {
  for (const inverse of [false, true]) {
    test(`legacy contract ${direction} ${inverse ? 'inverse' : 'normal'}: trend meaning and values`, async ({ page, baseURL }) => {
      const errors = await openFixture(page, baseURL, direction, inverse);
      for (const id of tables) await expectSemantics(page.locator(`#${id} table`), inverse);
      expect(errors).toEqual([]);
    });
  }
}

async function expectIconGeometry(table) {
  const icons = table.locator('td img');
  await expect(icons).toHaveCount(3);
  const measurements = await icons.evaluateAll(images => images.map(img => {
    const rect = img.getBoundingClientRect();
    const text = document.createRange();
    text.selectNodeContents(img.parentElement.firstChild);
    const number = text.getBoundingClientRect();
    const cell = img.parentElement.getBoundingClientRect();
    return {
      width: rect.width, height: rect.height, gap: rect.left - number.right,
      centreOffset: Math.abs((rect.top + rect.bottom - number.top - number.bottom) / 2),
      inCell: rect.left >= cell.left && rect.right <= cell.right && rect.top >= cell.top && rect.bottom <= cell.bottom,
      loaded: img.complete && img.naturalWidth > 0,
    };
  }));
  for (const result of measurements) {
    expect(result.loaded).toBeTruthy();
    expect(result.width).toBe(14);
    expect(result.height).toBe(14);
    expect(result.gap).toBeCloseTo(4, 0);
    expect(result.centreOffset).toBeLessThanOrEqual(3);
    expect(result.inCell).toBeTruthy();
  }
  // Trend-only styling must not resize adjacent currency flags.
  for (const flag of await table.locator('th img').all()) {
    await expect(flag).toHaveCSS('width', '16px');
    await expect(flag).toHaveCSS('height', '12px');
  }
}

for (const direction of directions) {
  for (const width of [1064, 320, 360]) {
    for (const inverse of [false, true]) {
      for (const after of [false, true]) {
        test(`presentation ${direction} ${width}px ${inverse ? 'inverse' : 'normal'} symbols ${after ? 'after' : 'before'}`, async ({ page, baseURL }) => {
          await page.setViewportSize({ width, height: 900 });
          const errors = await openFixture(page, baseURL, direction, inverse, after);
          const rowsBefore = await page.locator('tr').evaluateAll(rows => rows.map(row => row.getBoundingClientRect().height));
          for (const id of tables) {
            const table = page.locator(`#${id} table`);
            await expectSemantics(table, inverse);
            await expectIconGeometry(table);
            const icons = table.locator('td img');
            const original = await icons.evaluateAll(images => images.map(img => img.outerHTML));
            for (const icon of await icons.all()) {
              const src = await icon.getAttribute('src');
              const trend = (await icon.getAttribute('src')).match(/\/(up|down)\.svg$/)[1];
              expect(new URL(src).origin).toBe(new URL(baseURL).origin);
              expect(src).toMatch(new RegExp(`/assets/img/${trend}\\.svg$`));
              const response = await page.request.get(src);
              expect(response.ok()).toBeTruthy();
              expect(response.headers()['content-type']).toContain('image/svg+xml');
            }
            const otherId = id === tables[0] ? tables[1] : tables[0];
            const other = await page.locator(`#${otherId} table`).innerHTML();
            for (const amount of ['1234.56', '1000', '1']) {
              await table.locator('input').fill(amount);
              await expectIconGeometry(table);
              expect(await icons.evaluateAll(images => images.map(img => img.outerHTML))).toEqual(original);
              expect(await page.locator(`#${otherId} table`).innerHTML()).toBe(other);
            }
          }
          expect(await page.locator('tr').evaluateAll(rows => rows.map(row => row.getBoundingClientRect().height))).toEqual(rowsBefore);
          expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
          expect(errors).toEqual([]);
        });
      }
    }
  }
}
