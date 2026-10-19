const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page, baseURL }) => {
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
});

async function open(page, direction, extra = {}) {
  const response = await page.goto('/?' + new URLSearchParams({ 'er-layout-test': '1', direction, ...extra }));
  expect(response.ok()).toBeTruthy();
  await expect(page.locator('html')).toHaveAttribute('dir', direction);
}

async function grid(table) {
  return table.evaluate(element => {
    const rect = node => { const r = node.getBoundingClientRect(); return { left: r.left, right: r.right, width: r.width }; };
    const rows = [...element.querySelectorAll('tbody tr')];
    const last = rows.at(-1);
    return {
      columns: rows[0].cells.length,
      spans: [...last.cells].map(cell => cell.hasAttribute('colspan') ? Number(cell.getAttribute('colspan')) : 1),
      table: rect(element), first: rect(last.cells[0]), last: rect(last.cells[last.cells.length - 1]),
      colors: [...last.cells].map(cell => getComputedStyle(cell).backgroundColor),
      input: rect(last.querySelector('input')), inputCell: rect(last.querySelector('input').parentElement),
    };
  });
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 900]) {
    test(`${direction} ${width}px: compact source follows the start edge only`, async ({ page }) => {
      await page.setViewportSize({ width, height: 900 });
      await open(page, direction);
      for (const kind of ['shortcode', 'widget']) {
        const compact = page.locator(`#${kind}-compact`);
        await expect(compact.locator('.exchange-rates-caption')).toHaveCSS('justify-content', 'flex-start');
        await expect(page.locator(`#${kind}-regions .exchange-rates-caption`)).toHaveCSS('justify-content', 'flex-end');
        const geometry = await compact.evaluate(section => {
          const table = section.querySelector('table').getBoundingClientRect();
          const box = section.querySelector('.table-responsive').getBoundingClientRect();
          const caption = section.querySelector('.exchange-rates-caption');
          const range = document.createRange(); range.selectNodeContents(caption.firstChild);
          const text = range.getBoundingClientRect(), edge = document.dir === 'rtl' ? 'right' : 'left';
          return { gap: Math.abs(text[edge] - box[edge]), tableWidth: table.width, width: box.width };
        });
        expect(geometry.gap).toBeLessThan(8);
        if (width === 900) expect(geometry.tableWidth).toBeLessThan(geometry.width - 40);
        const caption = compact.locator('.exchange-rates-caption');
        const href = await caption.locator('a').getAttribute('href');
        expect(href).toBe('https://currencyconvert.online/usd');
        const info = compact.locator('.exchange-rates-info-caption');
        await expect(info).toBeHidden();
        await compact.locator('.exchange-rates-caption-button').click();
        await expect(info).toBeVisible();
        await compact.locator('.exchange-rates-caption-button').click();
        await expect(info).toBeHidden();
      }
    });

    test(`${direction} ${width}px: flags center on the currency name and regions are secondary`, async ({ page }) => {
      await page.setViewportSize({ width, height: 900 });
      await open(page, direction);
      for (const kind of ['shortcode', 'widget']) {
        for (const mode of ['regions', 'full']) {
          const rows = await page.locator(`#${kind}-${mode} tbody th`).evaluateAll(cells => cells.map(cell => {
            const flag = cell.querySelector('img'), title = cell.querySelector('.text-truncate'), region = cell.querySelector('.small');
            const r = flag.getBoundingClientRect(), t = title.getBoundingClientRect();
            const a = getComputedStyle(title), b = getComputedStyle(region);
            return { dy: r.top + r.height / 2 - t.top - t.height / 2, flag: r.width, titleSize: parseFloat(a.fontSize), regionSize: parseFloat(b.fontSize), weight: b.fontWeight };
          }));
          for (const row of rows) {
            expect(row.flag).toBe(mode === 'full' ? 32 : 16);
            expect(Math.abs(row.dy)).toBeLessThanOrEqual(1);
            expect(row.regionSize / row.titleSize).toBeLessThanOrEqual(0.82);
            expect(row.weight).toBe('400');
          }
        }
      }
    });

    test(`${direction} ${width}px: names remain readable inside the scrolling table`, async ({ page }) => {
      await page.setViewportSize({ width, height: 900 });
      await open(page, direction);
      for (const kind of ['shortcode', 'widget']) {
        for (const mode of ['full', 'amount']) {
          const section = page.locator(`#${kind}-${mode}`);
          const rows = await section.locator('tbody th .text-truncate').evaluateAll(labels => labels.map(label => {
            const style = getComputedStyle(label), box = label.getBoundingClientRect();
            const range = document.createRange(); range.selectNodeContents(label); const text = range.getBoundingClientRect();
            return { width: box.width, overflow: style.overflow, ellipsis: style.textOverflow, whiteSpace: style.whiteSpace,
              contained: text.left >= box.left - 1 && text.right <= box.right + 1 };
          }));
          for (const row of rows) {
            expect(row.width).toBeGreaterThanOrEqual(90);
            expect(row.overflow).toBe('visible');
            expect(row.ellipsis).toBe('clip');
            expect(row.whiteSpace).toBe('normal');
            expect(row.contained).toBeTruthy();
          }
          const size = await section.locator('.table-responsive').evaluate(el => ({ viewport: el.clientWidth, content: el.scrollWidth, table: el.querySelector('table').getBoundingClientRect().width }));
          if (width === 320) expect(size.content).toBeGreaterThan(size.viewport);
          else expect(Math.abs(size.table - size.viewport)).toBeLessThanOrEqual(1);
        }
      }
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    });

    test(`${direction} ${width}px: base row covers the grid and amount editing stays intact`, async ({ page }) => {
      await page.setViewportSize({ width, height: 900 });
      await open(page, direction);
      const errors = []; page.on('pageerror', error => errors.push(error.message));
      for (const kind of ['shortcode', 'widget']) {
        const table = page.locator(`#${kind}-amount table`);
        const before = await grid(table);
        expect(before.spans.every(span => span > 0)).toBeTruthy();
        expect(before.spans.reduce((a, b) => a + b, 0)).toBe(before.columns);
        expect(Math.min(before.first.left, before.last.left)).toBeCloseTo(before.table.left, 0);
        expect(Math.max(before.first.right, before.last.right)).toBeCloseTo(before.table.right, 0);
        expect(new Set(before.colors).size).toBe(1);
        expect(before.input.width).toBeLessThan(before.table.width / 2);
        await table.locator('input').fill('100');
        const cells = await table.locator('td[data-change-trend]').allTextContents();
        expect(cells.map(text => text.replace(/[^\d.]/g, ''))).toEqual(['125', '90', '1', '100']);
        expect(await table.locator('td[data-change]').allTextContents()).toEqual(['4,17%', '10%', '11,1%', '–']);
        const after = await grid(table);
        expect(after.spans).toEqual(before.spans);
      }
      expect(errors).toEqual([]);
    });
  }

  for (const history of [true, false]) {
    test(`${direction} history=${history}: every visible-column combination has a valid base row`, async ({ page }) => {
      test.setTimeout(45000);
      await open(page, direction, { grid: '1', ...(history ? {} : { 'no-history': '1' }) });
      for (const kind of ['shortcode', 'widget']) {
        for (let mask = 0; mask < 32; mask++) {
          const table = page.locator(`#${kind}-grid-${mask} table`);
          const result = await grid(table);
          const columns = 2 + Number(Boolean(mask & 1)) + (history ? Number(Boolean(mask & 2)) + Number(Boolean(mask & 4)) : 0);
          expect(result.columns, `${kind} mask ${mask}`).toBe(columns);
          expect(result.spans.every(span => span > 0), `${kind} mask ${mask}: positive colspan`).toBeTruthy();
          expect(result.spans.reduce((a, b) => a + b, 0), `${kind} mask ${mask}: complete row`).toBe(columns);
        }
      }
    });
  }
}
