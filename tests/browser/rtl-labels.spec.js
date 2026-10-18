const { test, expect } = require('@playwright/test');

const variants = [
  { name: 'rectangular codes', flag: 'rectangular' },
  { name: 'square names', flag: 'square', names: '1' },
  { name: 'circular codes without regions', flag: 'circular', 'no-region': '1' },
  { name: 'emoji codes', flag: 'emoji' },
  { name: 'no flags', flag: 'none' },
  { name: 'large flags', flag: 'rectangular', size: '64' },
  { name: 'English names', names: '1' },
  { name: 'Arabic names', names: '1', language: 'ar' },
  { name: 'Hebrew names', names: '1', language: 'he' },
  { name: 'long names', names: '1', language: 'long' },
];

// Use glyph coordinates: the full-width label element can touch a flag while
// its visible text is incorrectly aligned hundreds of pixels away.
async function labelGeometry(table) {
  return table.locator('tbody th').evaluateAll(cells => cells.map(cell => {
    const flag = cell.querySelector('img');
    const label = cell.querySelector('.text-truncate');
    const region = cell.querySelector('.text-truncate.small');
    const rect = element => {
      const bounds = element.getBoundingClientRect();
      return { left: bounds.left, right: bounds.right, width: bounds.width };
    };
    const textRect = element => {
      const range = document.createRange();
      range.selectNodeContents(element);
      return rect(range);
    };
    const characters = [...label.firstChild.textContent].map((character, index) => {
      const range = document.createRange();
      range.setStart(label.firstChild, index);
      range.setEnd(label.firstChild, index + 1);
      return { character, x: range.getBoundingClientRect().x };
    });
    return { flag: flag && rect(flag), label: textRect(label), box: rect(label),
      region: region && textRect(region), text: label.textContent, characters,
      overflow: getComputedStyle(label).overflow, ellipsis: getComputedStyle(label).textOverflow };
  }));
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [1064, 320]) {
    for (const { name, ...parameters } of variants) {
      test(`${direction} ${width}px ${name}: flags stay adjacent to labels`, async ({ page, baseURL }) => {
        await page.setViewportSize({ width, height: 900 });
        await page.route('**/*', route => {
          const url = new URL(route.request().url());
          // Emoji flags are external in the plugin. Supply a deterministic local
          // image only in tests; preserve the production img attributes and size.
          if (url.origin === 'https://s.w.org' && url.pathname.startsWith('/images/core/emoji/')) {
            return route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><rect width="16" height="16" fill="#246"/></svg>' });
          }
          return url.origin === new URL(baseURL).origin ? route.continue() : route.abort();
        });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const query = new URLSearchParams({ 'er-bidi-test': '1', labels: '1', direction, ...parameters });
        const response = await page.goto(`/?${query}`);
        expect(response.ok()).toBeTruthy();
        await expect(page.locator('html')).toHaveAttribute('dir', direction);
        for (const id of ['shortcode-full', 'widget-full', 'shortcode-compact', 'widget-compact']) {
          const table = page.locator(`#${id} table`);
          await expect(table).toHaveCSS('direction', direction);
          await expect(table.locator('tbody th')).toHaveCount(3);
          const geometry = await labelGeometry(table);
          if (!parameters.names) {
            expect(geometry.map(row => row.text)).toEqual(['EUR', 'BRL', 'USD']);
          }
          for (const row of geometry) {
            const edge = direction === 'rtl' ? 'right' : 'left';
            // Short names must start at the label's leading edge, including the
            // no-flag variant and the base-currency row.
            expect(Math.abs(row.label[edge] - row.box[edge]), `${id}: label leading edge`).toBeLessThanOrEqual(1);
            expect(Boolean(row.flag)).toBe(parameters.flag !== 'none');
            expect(Boolean(row.region)).toBe(!parameters['no-region']);
            if (row.flag) {
              const gap = direction === 'rtl' ? row.flag.left - row.label.right : row.label.left - row.flag.right;
              expect(gap, `${id}: flag/text gap`).toBeGreaterThanOrEqual(0);
              expect(gap, `${id}: flag/text gap`).toBeLessThan(8);
              expect(row.flag.width).toBe(Number(parameters.size || 16));
            }
            if (row.region) {
              // Allow one pixel of cross-engine glyph rounding. Keep this soft
              // so an unrelated region-layout failure cannot hide label checks.
              expect.soft(Math.abs(row.region[edge] - row.label[edge]), `${id}: region leading edge`).toBeLessThanOrEqual(1);
            }
            expect(row.overflow).toBe('visible');
            expect(row.ellipsis).toBe('clip');
            if (!parameters.names) {
              for (let i = 1; i < row.characters.length; i++) {
                expect(row.characters[i].x).toBeGreaterThan(row.characters[i - 1].x);
              }
            }
          }
        }
        // Existing responsive wrappers contain wide tables; the document must
        // not acquire horizontal overflow because labels are long or flags large.
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
        expect(errors).toEqual([]);
      });
    }
  }
}
