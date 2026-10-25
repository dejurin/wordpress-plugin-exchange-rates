const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page, baseURL }) => {
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
});

async function open(page, direction, width, surface, extra = {}) {
  await page.setViewportSize({ width, height: 1000 });
  const response = await page.goto('/?' + new URLSearchParams({ 'er-visual-test': '1', surface, direction, ...extra }));
  expect(response.ok()).toBeTruthy();
  await expect(page.locator('[data-exchange-rates-version]').first()).toHaveAttribute('data-exchange-rates-version', '1.4.1');
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 900]) {
    test(`${direction} ${width}px: table cells center beside flags and base amounts`, async ({ page }) => {
      test.setTimeout(60000);
      for (const font of [16, 22]) {
        for (const size of [16, 32, 64]) {
          for (const region of [false, true]) {
            for (const editable of [false, true]) {
              await open(page, direction, width, 'table', { font: String(font), size: String(size), ...(region ? { region: '1' } : {}), ...(editable ? { editable: '1' } : {}) });
              for (const kind of ['shortcode', 'widget']) {
                const rows = await page.locator(`#${kind}-table tbody tr`).evaluateAll(nodes => nodes.map(row => {
                  const label = row.querySelector('.exchange-rates-table-label').getBoundingClientRect();
                  const center = label.top + label.height / 2;
                  return Array.from(row.cells).slice(1).map(cell => {
                    const input = cell.querySelector('input');
                    const range = document.createRange(); range.selectNodeContents(cell);
                    const box = input ? input.getBoundingClientRect() : range.getBoundingClientRect();
                    return Math.abs(box.top + box.height / 2 - center);
                  });
                }));
                expect(rows).toHaveLength(3);
                for (const row of rows) for (const gap of row) expect(gap, `${kind} ${font}px text ${size}px flag region=${region} editable=${editable}`).toBeLessThanOrEqual(1);
                const flags = await page.locator(`#${kind}-table tbody th img`).evaluateAll(nodes => nodes.map(node => node.getBoundingClientRect().width));
                expect(flags).toEqual([size, size, size]);
              }
            }
          }
        }
      }
    });

    test(`${direction} ${width}px: long badges keep names, flags and values within their row`, async ({ page }) => {
      await open(page, direction, width, 'badge');
      const rows = await page.locator('.badge-leaders').evaluateAll(nodes => nodes.map(row => {
        const bounds = row.getBoundingClientRect();
        return { text: row.textContent.trim(), width: row.clientWidth, scroll: row.scrollWidth,
          flags: Array.from(row.querySelectorAll('img')).map(node => node.getBoundingClientRect().width),
          contained: Array.from(row.children).every(span => {
            const range = document.createRange(); range.selectNodeContents(span);
            return Array.from(range.getClientRects()).every(r => r.left >= bounds.left - 1 && r.right <= bounds.right + 1);
          }) };
      }));
      expect(rows).toHaveLength(4);
      for (const row of rows) {
        expect(row.scroll).toBeLessThanOrEqual(row.width + 1);
        expect(row.contained).toBeTruthy();
        expect(row.flags).toEqual([20]);
      }
      expect(rows.map(row => row.text.replace(/\s/g, ''))).toEqual([
        'ConvertibleMarkKM2,222,222.202', 'SouthAfricanRandR21,358,024.497',
        'USDollar/ConvertibleMarkKM2,222,222.202', 'USDollar/SouthAfricanRandR21,358,024.497',
      ]);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    });

    for (const font of [16, 22]) {
      test(`${direction} ${width}px ${font}px: source captions stay inside the viewport`, async ({ page }) => {
        await open(page, direction, width, 'converter', { font: String(font) });
        const parts = await page.locator('.exchange-rates-caption').evaluateAll(nodes => nodes.flatMap(node => {
          const bounds = node.getBoundingClientRect();
          return Array.from(node.childNodes).flatMap(child => {
            const range = document.createRange(); range.selectNodeContents(child);
            return Array.from(range.getClientRects()).map(r => ({ left: r.left, right: r.right, start: bounds.left, end: bounds.right, viewport: innerWidth }));
          });
        }));
        expect(parts.length).toBeGreaterThan(0);
        for (const part of parts) {
          expect(part.left).toBeGreaterThanOrEqual(Math.max(0, part.start) - 1);
          expect(part.right).toBeLessThanOrEqual(Math.min(part.viewport, part.end) + 1);
        }
        await expect(page.locator('#converter-default .result')).toHaveText('R11,865,569.165');
      });

      test(`${direction} ${width}px ${font}px: selected text has a separate arrow lane`, async ({ page }) => {
        await open(page, direction, width, 'converter', { font: String(font) });
        const controls = await page.locator('select').evaluateAll(nodes => nodes.map(node => {
          const s = getComputedStyle(node);
          const arrow = parseFloat(s.backgroundSize);
          const edge = Number(s.backgroundPositionX.match(/([\d.]+)px/)[1]);
          const padding = parseFloat(s.direction === 'rtl' ? s.paddingLeft : s.paddingRight);
          return { padding, arrow, edge, height: node.getBoundingClientRect().height };
        }));
        expect(controls).toHaveLength(8);
        for (const control of controls) {
          expect(control.padding).toBeGreaterThanOrEqual(control.arrow + control.edge + 4);
          expect(control.height).toBe(40);
        }
        const converter = page.locator('#converter-default .widget-exchange-rates-currency-converter');
        await converter.locator('input').fill('10');
        await expect(converter.locator('.result')).toHaveText('R96.1111');
        await converter.locator('button.swap').click();
        await expect(converter.locator('.result')).toHaveText('KM1.0405');
      });
    }
  }
}

test('default converter title is translated while explicit saved titles stay intact', async ({ page }) => {
  await open(page, 'ltr', 900, 'converter');
  await expect(page.locator('#converter-default .widget-exchange-rates-currency-converter > b')).toHaveText('Currency Converter');
  await expect(page.locator('#converter-custom .widget-exchange-rates-currency-converter > b')).toHaveText('Saved custom title');
  await expect(page.locator('#converter-empty .widget-exchange-rates-currency-converter > b')).toBeEmpty();
  await expect(page.locator('#converter-widget .widget-exchange-rates-currency-converter > b')).toHaveText('Saved widget title');
  await open(page, 'ltr', 900, 'converter', { language: 'uk' });
  await expect(page.locator('#converter-default .widget-exchange-rates-currency-converter > b')).toHaveText('Конвертер валют');
});

for (const [filter, title] of [['custom', 'Site converter'], ['empty', '']]) {
  test(`shortcode title filter preserves ${filter} defaults`, async ({ page }) => {
    for (const language of ['en', 'uk']) {
      await open(page, 'ltr', 900, 'converter', { language, 'title-filter': filter });
      await expect(page.locator('#converter-default .widget-exchange-rates-currency-converter > b')).toHaveText(title);
      await expect(page.locator('#converter-custom .widget-exchange-rates-currency-converter > b')).toHaveText('Saved custom title');
      await expect(page.locator('#converter-empty .widget-exchange-rates-currency-converter > b')).toBeEmpty();
      await expect(page.locator('#converter-widget .widget-exchange-rates-currency-converter > b')).toHaveText('Saved widget title');
      await expect(page.locator('#converter-default .result')).toHaveText('R11,865,569.165');
    }
  });
}
