const { test, expect } = require('@playwright/test');

const sizes = [16, 24, 32, 39, 40, 41, 64];
const sections = ['shortcode-full', 'widget-full', 'shortcode-compact', 'widget-compact'];

test.beforeEach(async ({ page, baseURL }) => {
  await page.route('**/*', route => {
    const url = new URL(route.request().url());
    if (url.origin === 'https://s.w.org' && url.pathname.startsWith('/images/core/emoji/')) {
      return route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><rect width="16" height="16" fill="#246"/></svg>' });
    }
    return url.origin === new URL(baseURL).origin ? route.continue() : route.abort();
  });
});

async function checkRows(page, { size, flag, region, direction }) {
  for (const id of sections) {
    const rows = await page.locator(`#${id} tbody th`).evaluateAll(cells => cells.map(cell => {
      const rect = node => {
        if (!node) return null;
        const r = node.getBoundingClientRect();
        return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, height: r.height, width: r.width, center: r.top + r.height / 2 };
      };
      return { root: rect(cell.firstElementChild), flag: rect(cell.querySelector('img')),
        title: rect(cell.querySelector('.text-truncate:not(.small)')), region: rect(cell.querySelector('.small')) };
    }));
    expect(rows, id).toHaveLength(3); // Includes the base-currency row.
    for (const row of rows) {
      const edge = direction === 'rtl' ? 'right' : 'left';
      expect(Boolean(row.flag)).toBe(flag !== 'none');
      expect(Boolean(row.region)).toBe(region);
      if (row.flag) {
        expect(row.flag.width).toBe(size);
        expect(row.flag.height).toBe(flag === 'rectangular' ? size * 0.75 : size);
        const center = region && size >= 40 ? (row.title.top + row.region.bottom) / 2 : row.title.center;
        expect(Math.abs(row.flag.center - center), `${id} ${size}px: flag vertical center`).toBeLessThanOrEqual(1);
      } else {
        expect(Math.abs(row.title[edge] - row.root[edge]), `${id}: no empty flag slot`).toBeLessThanOrEqual(1);
      }
      if (row.region) {
        const compact = row.flag && size < 40;
        expect(Math.abs(row.region[edge] - (compact ? row.flag[edge] : row.title[edge])), `${id}: region edge`).toBeLessThanOrEqual(1);
        expect(row.region.top, `${id}: region below its group`).toBeGreaterThanOrEqual(compact ? Math.max(row.title.bottom, row.flag.bottom) : row.title.bottom);
      } else {
        expect(Math.abs(row.root.height - Math.max(row.title.height, row.flag ? row.flag.height : 0)), `${id}: no empty second row`).toBeLessThanOrEqual(1);
      }
    }
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'no document overflow').toBeTruthy();
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 1064]) {
    for (const flag of ['rectangular', 'square', 'circular', 'emoji', 'none']) {
      for (const region of [true, false]) {
        test(`${direction} ${width}px ${flag} region=${region}: hybrid boundaries and custom text`, async ({ page }, testInfo) => {
          test.setTimeout(60000);
          await page.setViewportSize({ width, height: 900 });
          const errors = [];
          page.on('pageerror', error => errors.push(error.message));
          for (const size of sizes) {
            const query = new URLSearchParams({ 'er-bidi-test': '1', labels: '1', names: '1', direction, flag, size: String(size), ...(region ? {} : { 'no-region': '1' }) });
            expect((await page.goto(`/?${query}`)).ok()).toBeTruthy();
            for (const font of [16, 22]) {
              await page.locator('.cr-exchange-rates').evaluateAll((nodes, value) => nodes.forEach(node => { node.style.fontSize = `${value}px`; }), font);
              await checkRows(page, { size, flag, region, direction });
            }
            if (direction === 'rtl' && flag === 'rectangular' && [16, 39, 40, 64].includes(size)) {
              await testInfo.attach(`hybrid-${width}-${size}-region-${region}`, { body: await page.locator('#widget-full').screenshot(), contentType: 'image/png' });
            }
          }
          expect(errors).toEqual([]);
        });
      }
    }
  }
}

for (const direction of ['ltr', 'rtl']) {
  test(`${direction}: wrapped long labels keep the hybrid center`, async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 900 });
    for (const size of [16, 40, 64]) {
      await page.goto(`/?er-bidi-test=1&labels=1&names=1&language=long&direction=${direction}&size=${size}`);
      await page.locator('.cr-exchange-rates').evaluateAll(nodes => nodes.forEach(node => { node.style.fontSize = '22px'; }));
      await checkRows(page, { size, flag: 'rectangular', region: true, direction });
    }
  });
}
