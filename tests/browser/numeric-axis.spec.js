const { test, expect } = require('@playwright/test');
const { rateText } = require('./rate-text');

const ids = ['full', 'compact'].flatMap(layout => ['static', 'editable'].flatMap(mode =>
  ['shortcode', 'widget'].map(kind => `${kind}-${layout}-${mode}`)));

// Measure visible text, not the combined number/icon box or implementation classes.
async function measureAxis(table) {
  return table.evaluate(element => {
    const textBounds = node => {
      const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT);
      const boxes = [];
      let text;
      while ((text = walker.nextNode())) {
        if (!text.textContent.trim() || text.parentElement.closest('.er-a11y-sr-only')) continue;
        const range = document.createRange();
        range.selectNodeContents(text);
        boxes.push(range.getBoundingClientRect());
      }
      return { right: Math.max(...boxes.map(box => box.right)), left: Math.min(...boxes.map(box => box.left)) };
    };
    const prices = [...element.querySelectorAll('tbody tr')].map(row => {
      const cell = row.querySelector('td');
      const input = cell.querySelector('input');
      const icon = cell.querySelector('img');
      const bounds = cell.getBoundingClientRect();
      let right;
      if (input) {
        const style = getComputedStyle(input);
        right = input.getBoundingClientRect().right - parseFloat(style.borderRightWidth) - parseFloat(style.paddingRight);
      } else {
        right = textBounds(cell).right;
      }
      const previous = cell.nextElementSibling;
      return { right, input: Boolean(input), inputAlign: input && getComputedStyle(input).textAlign, previous: previous && previous.textContent.trim() ? {
        right: textBounds(previous).right, cellRight: previous.getBoundingClientRect().right,
      } : null, icon: icon && {
        left: icon.getBoundingClientRect().left, right: icon.getBoundingClientRect().right,
        width: icon.getBoundingClientRect().width,
      }, cellLeft: bounds.left, cellRight: bounds.right };
    });
    return { heading: textBounds(element.querySelector('thead th:nth-child(2)')), prices };
  });
}

async function expectAxis(table, context) {
  const { heading, prices } = await measureAxis(table);
  expect(prices, `${context}: quote rows and base row`).toHaveLength(6);
  const edges = prices.map(price => price.right);
  expect(Math.max(...edges) - Math.min(...edges), `${context}: price/base number right edges`).toBeLessThanOrEqual(1);
  expect(Math.abs(heading.right - edges[0]), `${context}: Price heading shares the numeric axis`).toBeLessThanOrEqual(1);
  for (const price of prices) {
    if (price.input) expect(price.inputAlign, `${context}: editable amount text uses the measured right content edge`).toBe('right');
    expect(price.right).toBeGreaterThan(price.cellLeft);
    expect(price.right).toBeLessThanOrEqual(price.cellRight);
    if (price.icon) {
      expect(price.icon.width).toBe(14);
      expect(price.icon.left - price.right, `${context}: separate arrow does not overlap the number`).toBeCloseTo(4, 0);
      expect(price.icon.right).toBeLessThanOrEqual(price.cellRight);
    }
  }
}

async function open(page, baseURL, direction, width, noScripts = false, extra = {}) {
  await page.setViewportSize({ width, height: 1000 });
  await page.route('**/*', route => {
    if (new URL(route.request().url()).origin !== new URL(baseURL).origin) return route.abort();
    if (noScripts && route.request().resourceType() === 'script') return route.abort();
    return route.continue();
  });
  const query = new URLSearchParams({ 'er-trend-test': '1', axis: '1', direction, ...extra });
  expect((await page.goto(`/?${query}`)).ok()).toBe(true);
  await expect(page.locator('html')).toHaveAttribute('dir', direction);
  await expect(page.locator('section table')).toHaveCount(8);
}

async function expectInitial(page) {
  for (const id of ids) {
    const table = page.locator(`#${id} table`);
    const prices = table.locator('tbody tr:not(:last-child) td:first-of-type');
    expect(await rateText(prices), id).toEqual(['1.25', '1.5', '0.75', '0.2', '0.01']);
    expect(await prices.locator('img').evaluateAll(images => images.map(image => new URL(image.src).pathname.split('/').pop())), id).toEqual(['up.svg', 'down.svg']);
    await expect(table.locator('tbody tr:nth-child(5) td').nth(1)).toHaveText('–');
    if (id.endsWith('-editable')) await expect(table.locator('input')).toHaveValue('1');
    else expect(await rateText(table.locator('tbody tr:last-child td').first()), id).toEqual(['1']);
    await expectAxis(table, id);
  }
}

test('unchanged-rate tables keep the existing numeric content width', async ({ page, baseURL }) => {
  for (const direction of ['ltr', 'rtl']) {
    await open(page, baseURL, direction, 288, false, { 'axis-no-trends': '1' });
    for (const id of ids) {
      const table = page.locator(`#${id} table`);
      await expect(table.locator('td img')).toHaveCount(0);
      await expectAxis(table, id);
      const { prices } = await measureAxis(table);
      for (const price of prices.filter(price => price.previous)) {
        const priceInset = price.cellRight - price.right;
        const previousInset = price.previous.cellRight - price.previous.right;
        expect(Math.abs(priceInset - previousInset), `${id}: unchanged prices have the same content inset as ordinary previous values`).toBeLessThanOrEqual(1);
      }
    }
  }
});

test('long saved Price headings retain the numeric axis at 220 and 288px', async ({ page, baseURL }) => {
  for (const direction of ['ltr', 'rtl']) {
    for (const width of [220, 288]) {
      await open(page, baseURL, direction, width, false, { 'axis-long-header': '1' });
      await expectInitial(page);
      for (const id of ids) await expect(page.locator(`#${id} thead th`).nth(1)).toHaveText('Saved current exchange rate');
    }
  }
});

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 390, 1064]) {
    test(`${direction} ${width}px: prices and base amounts share one numeric axis`, async ({ page, baseURL }) => {
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await open(page, baseURL, direction, width);
      await expectInitial(page);
      for (const id of ids.filter(id => id.endsWith('-editable'))) {
        const table = page.locator(`#${id} table`);
        const input = table.locator('input');
        for (const amount of ['0', 'invalid', '1']) {
          await input.fill(amount);
          if (amount === 'invalid') await expect(input).toHaveAttribute('aria-invalid', 'true');
          else await expect(input).not.toHaveAttribute('aria-invalid', 'true');
          await expectAxis(table, `${id} amount=${amount}`);
          if (amount === '0' || amount === 'invalid') await expect(table.locator('td img')).toHaveCount(0);
        }
      }
      await expectInitial(page);
      expect(errors).toEqual([]);
    });
  }
  test(`${direction} 390px: server-rendered numeric axis works without JavaScript`, async ({ page, baseURL }) => {
    await open(page, baseURL, direction, 390, true);
    await expectInitial(page);
  });
}
