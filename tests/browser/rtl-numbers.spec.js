const { test, expect } = require('@playwright/test');

// Explicit output expectations, independent of the plugin formatter.
const formats = [
  ['1.234,56', '1.543,2', '1.481,47', '246,91', '234,57'],
  ['1 234.56', '1 543.2', '1 481.47', '246.91', '234.57'],
  ['1 234,56', '1 543,2', '1 481,47', '246,91', '234,57'],
  ['1,234.56', '1,543.2', '1,481.47', '246.91', '234.57'],
  ["1'234.56", "1'543.2", "1'481.47", '246.91', '234.57'],
  ["1'234,56", "1'543,2", "1'481,47", '246,91', '234,57'],
  ['1234.56', '1543.2', '1481.47', '246.91', '234.57'],
  ['1234,56', '1543,2', '1481,47', '246,91', '234,57'],
];

// Text assertions alone cannot detect bidi reordering. Read each character's
// actual rendered position, excluding trend images and their alternative text.
async function expectVisualOrder(locator, expected) {
  await expect(locator).toHaveText(expected);
  const positions = await locator.evaluate(element => {
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
    const result = [];
    let node;
    while ((node = walker.nextNode())) {
      for (let index = 0; index < node.length; index++) {
        const range = document.createRange();
        range.setStart(node, index);
        range.setEnd(node, index + 1);
        const rect = range.getBoundingClientRect();
        result.push({ char: node.textContent[index], x: rect.x, y: rect.y });
      }
    }
    return result;
  });
  expect(positions.map(item => item.char).join('')).toBe(expected);
  for (let index = 1; index < positions.length; index++) {
    expect(positions[index].x, `Visual order of ${JSON.stringify(expected)} at character ${index}`).toBeGreaterThanOrEqual(positions[index - 1].x - 0.1);
    expect(positions[index].y).toBeCloseTo(positions[0].y, 0);
  }
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [1064, 320]) {
    for (const after of [false, true]) {
      for (let format = 0; format < formats.length; format++) {
        test(`${direction} ${width}px format ${format} symbols ${after ? 'after' : 'before'}`, async ({ page, baseURL }) => {
          await page.setViewportSize({ width, height: 900 });
          await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
          const errors = [];
          page.on('pageerror', error => errors.push(error.message));
          const response = await page.goto(`/?er-bidi-test=1&direction=${direction}&format=${format}&after=${after ? 1 : 0}`);
          expect(response.ok()).toBeTruthy();
          await expect(page.locator('html')).toHaveAttribute('dir', direction);
          const [amount, eur, previousEur, brl, previousBrl] = formats[format];
          const symbol = (value, sign) => after ? value + sign : sign + value;
          const unchangedBadge = await page.locator('#badges .badge-leaders').evaluateAll(elements => elements.map(element => element.outerHTML));
          for (const id of ['shortcode-table', 'widget-table']) {
            // The table shortcode has never supported the symbol attribute.
            const sign = (value, currency) => id === 'widget-table' ? symbol(value, currency) : value;
            const values = [sign(eur, '€'), previousEur, sign(brl, 'R$'), previousBrl];
            const table = page.locator(`#${id} table`);
            await expect(table).toHaveCSS('direction', direction);
            const cells = table.locator('td[data-rate]');
            await expect(cells).toHaveCount(4);
            for (let index = 0; index < values.length; index++) {
              await expectVisualOrder(cells.nth(index), values[index]);
            }
            const input = table.locator('input');
            await expect(input).toHaveValue(amount);
            // Native input text is not exposed as DOM text nodes. Check its
            // direction as well as unchanged values and click/blur behavior.
            await expect(input).toHaveCSS('direction', 'ltr');
            await input.click();
            await expect(input).toHaveValue('');
            await input.blur();
            await expect(input).toHaveValue(amount);
            await input.fill('1234.56');
            await input.blur();
            await expect(input).toHaveValue('1234.56');
            const updated = [sign(eur, '€'), sign(previousEur, '€'), sign(brl, 'R$'), sign(previousBrl, 'R$')];
            for (let index = 0; index < updated.length; index++) {
              await expectVisualOrder(cells.nth(index), updated[index]);
            }
          }
          await expectVisualOrder(page.locator('#static-table td.active.text-right'), amount);
          for (const id of ['shortcode-converter', 'widget-converter']) {
            const converter = page.locator(`#${id} .widget-exchange-rates-currency-converter`);
            await expect(converter).toHaveCSS('direction', direction);
            await expectVisualOrder(converter.locator('.result'), symbol(brl, 'R$'));
            await expect(converter.locator('input')).toHaveCSS('direction', 'ltr');
            await converter.locator('input').fill('1234.56');
            await converter.locator('select').nth(1).selectOption('EUR');
            await expectVisualOrder(converter.locator('.result'), symbol(eur, '€'));
            await converter.locator('select').nth(1).selectOption('BRL');
            await expectVisualOrder(converter.locator('.result'), symbol(brl, 'R$'));
            await converter.locator('input').fill('-12.34');
            await expectVisualOrder(converter.locator('.result'), symbol([0, 2, 5, 7].includes(format) ? '-2,47' : '-2.47', 'R$'));
          }
          expect(await page.locator('#badges .badge-leaders').evaluateAll(elements => elements.map(element => element.outerHTML))).toEqual(unchangedBadge);
          const badges = page.locator('#badges .badge-leaders > span + span');
          await expectVisualOrder(badges.nth(0), symbol(eur, '€'));
          await expectVisualOrder(badges.nth(1), symbol(brl, 'R$'));
          expect(errors).toEqual([]);
        });
      }
    }
  }
}

for (const direction of ['ltr', 'rtl']) {
  test(`${direction} composite R$ remains a contiguous suffix`, async ({ page, baseURL }) => {
    await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
    await page.goto(`/?er-bidi-test=1&direction=${direction}&format=3&after=1`);
    await expectVisualOrder(page.locator('#shortcode-converter .result'), '246.91R$');
  });
}
