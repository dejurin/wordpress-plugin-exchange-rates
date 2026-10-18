const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page, baseURL }) => {
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
});

async function columns(table) {
  return table.evaluate(element => {
    const rectangle = node => { const box = node.getBoundingClientRect(); return { left: box.left, right: box.right, width: box.width }; };
    const content = node => { const range = document.createRange(); range.selectNodeContents(node); return rectangle(range); };
    const rows = [...element.querySelectorAll('tbody tr')];
    const headers = [...element.querySelectorAll('thead th')];
    const direction = getComputedStyle(element).direction;
    const measurements = [...rows[0].cells].map((cell, index) => {
      const cells = rows.slice(0, -1).map(row => row.cells[index]);
      if (headers[index]) cells.push(headers[index]);
      const style = getComputedStyle(cell);
      return { width: rectangle(cell).width,
        required: Math.max(...cells.map(node => content(node).width)) + parseFloat(style.paddingLeft) + parseFloat(style.paddingRight),
        contained: cells.every(node => { const text = content(node), box = rectangle(node); return text.left >= box.left - 1 && text.right <= box.right + 1; }),
        leading: cells.map(node => content(node)[direction === 'rtl' ? 'right' : 'left']),
      };
    });
    return { width: rectangle(element).width, measurements,
      values: rows.map(row => row.textContent),
      numericHeaders: headers.slice(1).filter(node => node.classList.contains('text-right')).map(node => getComputedStyle(node).textAlign),
    };
  });
}

for (const direction of ['ltr', 'rtl']) {
  for (const code of [true, false]) {
    for (const headers of [true, false]) {
      for (const history of [true, false]) {
        test(`${direction} code=${code} headers=${headers} history=${history}: Currency absorbs spare width; Code and Price stay compact`, async ({ page }) => {
          test.setTimeout(30000);
          const query = new URLSearchParams({ 'er-layout-test': '1', columns: '1', direction,
            ...(code ? {} : { 'no-code': '1' }), ...(headers ? {} : { 'no-headers': '1' }), ...(history ? { history: '1' } : {}) });
          await page.setViewportSize({ width: 320, height: 900 });
          const response = await page.goto('/?' + query);
          expect(response.ok()).toBeTruthy();
          const snapshots = {};
          for (const width of [320, 480, 900]) {
            await page.setViewportSize({ width, height: 900 });
            for (const kind of ['shortcode', 'widget']) {
              const table = page.locator(`#${kind}-columns table`);
              const result = await columns(table);
              expect(result.measurements).toHaveLength(2 + Number(code) + 2 * Number(history));
              for (const [index, cell] of result.measurements.entries()) {
                if (index === 0) continue;
                expect(cell.width, `${kind} ${width}px column ${index} width`).toBeLessThanOrEqual(cell.required + 2);
                expect(cell.contained, `${kind} ${width}px column ${index} text stays inside its cell`).toBeTruthy();
              }
              if (code) {
                const edges = result.measurements[1].leading;
                expect(Math.max(...edges) - Math.min(...edges), `${kind}: Code header and values share a leading edge`).toBeLessThanOrEqual(1);
              }
              expect(result.numericHeaders.every(align => align === 'right')).toBeTruthy();
              const previous = snapshots[kind];
              if (previous) {
                expect(result.values).toEqual(previous.values);
                const extraWidth = result.width - previous.width;
                const labelGrowth = result.measurements[0].width - previous.measurements[0].width;
                expect(Math.abs(extraWidth - labelGrowth), `${kind}: only Currency absorbs spare width`).toBeLessThanOrEqual(2);
              }
              snapshots[kind] = result;
            }
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
          }
          for (const kind of ['shortcode', 'widget']) {
            const table = page.locator(`#${kind}-columns table`);
            await table.locator('input').fill('1234567.89');
            const result = await columns(table);
            for (const cell of result.measurements.slice(1)) {
              expect(cell.contained).toBeTruthy();
              expect(cell.width).toBeLessThanOrEqual(cell.required + 2);
            }
          }
        });
      }
    }
  }
}
