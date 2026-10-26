const { test, expect } = require('@playwright/test');

function table(page, name) {
  return page.locator(`[data-table-fixture="${name}"]`);
}

function prices(container) {
  return container.locator('tbody td[data-rate]');
}

async function snapshot(container) {
  // Observe rate controls, excluding captions that WordPress emoji scripts rewrite.
  return container.locator('table, .badge-leaders, .widget-exchange-rates-currency-converter').evaluateAll(elements => elements.map(element => ({
    html: element.innerHTML,
    values: Array.from(element.querySelectorAll('input'), input => input.value),
  })));
}

async function images(container) {
  return prices(container).locator('img').evaluateAll(elements => elements.map(image => image.outerHTML));
}

async function openFixture(page, scenario) {
  const response = await page.goto(`/?pagename=exchange-rates-table-${scenario}`);
  expect(response.ok()).toBeTruthy();
  await expect(page.locator('.widget-exchange-rates-currency-converter .result')).toHaveText('1.25');
  await expect(table(page, 'controls').locator('.badge-leaders')).toHaveCount(1);
  await expect(prices(table(page, 'first'))).toHaveText(['2.5', '2.4']);
  if (scenario !== 'single') {
    await expect(table(page, 'static').locator('table')).toHaveCount(1);
    await expect(table(page, 'static').locator('input')).toHaveCount(0);
  }
}

test.beforeEach(async ({ page, baseURL }) => {
  // Keep the browser fixture independent of external fonts, avatars and services.
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
});

test('a single table retains rounding, previous rates and trend images', async ({ page }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await openFixture(page, 'single');
  const first = table(page, 'first');
  const beforeImages = await images(first);
  expect(beforeImages.length).toBeGreaterThan(0);
  await first.locator('input').fill('1234.56');
  await expect(prices(first)).toHaveText(['1,543.2', '1,481.47']);
  await first.locator('input').blur();
  await expect(first.locator('input')).toHaveValue('1234.56');
  expect(await images(first)).toEqual(beforeImages);
  expect(errors).toEqual([]);
});

test('click and blur without editing restore the saved amount', async ({ page }) => {
  await openFixture(page, 'single');
  const first = table(page, 'first');
  const before = await snapshot(first);
  await first.locator('input').click();
  await expect(first.locator('input')).toHaveValue('');
  await first.locator('input').blur();
  expect(await snapshot(first)).toEqual(before);
});

test('empty, zero and negative input retain the existing amount-one fallback', async ({ page }) => {
  await openFixture(page, 'single');
  const first = table(page, 'first');
  for (const value of ['', '0', '-5']) {
    await first.locator('input').fill(value);
    await expect(prices(first)).toHaveText(['1.25', '1.2']);
    await first.locator('input').blur();
    await expect(first.locator('input')).toHaveValue('1');
  }
});

for (const scenario of ['mixed', 'reversed']) {
  test(`${scenario}: editing the shortcode leaves the widget, static table and other controls unchanged`, async ({ page }) => {
    await openFixture(page, scenario);
    const untouched = ['second', 'static', 'controls'];
    const before = await Promise.all(untouched.map(name => snapshot(table(page, name))));
    const first = table(page, 'first');
    const beforeImages = await images(first);
    await first.locator('input').fill('1234.56');
    await expect(prices(first)).toHaveText(['1,543.2', '1,481.47']);
    expect(await Promise.all(untouched.map(name => snapshot(table(page, name))))).toEqual(before);
    expect(await images(first)).toEqual(beforeImages);
  });

  test(`${scenario}: editing the saved widget uses its own format, precision and symbol position`, async ({ page }) => {
    await openFixture(page, scenario);
    const untouched = ['first', 'static', 'controls'];
    const before = await Promise.all(untouched.map(name => snapshot(table(page, name))));
    const second = table(page, 'second');
    // The existing PHP renderer omits the symbol in the previous-close cell.
    await expect(prices(second)).toHaveText(['15£', '14']);
    const beforeImages = await images(second);
    expect(beforeImages.length).toBeGreaterThan(0);
    await second.locator('input').fill('1234.56');
    await expect(prices(second)).toHaveText(['1.851,84£', '1.728,384£']);
    await second.locator('input').blur();
    await expect(second.locator('input')).toHaveValue('1234.56');
    expect(await Promise.all(untouched.map(name => snapshot(table(page, name))))).toEqual(before);
    expect(await images(second)).toEqual(beforeImages);
  });
}

test('alternating edits keep both tables independent over repeated input events', async ({ page }) => {
  await openFixture(page, 'mixed');
  const first = table(page, 'first');
  const second = table(page, 'second');
  const staticBefore = await snapshot(table(page, 'static'));
  await first.locator('input').fill('3');
  await second.locator('input').fill('4');
  await expect(prices(first)).toHaveText(['3.75', '3.6']);
  await expect(prices(second)).toHaveText(['6£', '5,6£']);
  await first.locator('input').fill('1000');
  await expect(prices(first)).toHaveText(['1,250', '1,200']);
  await expect(prices(second)).toHaveText(['6£', '5,6£']);
  await expect(second.locator('input')).toHaveValue('4');
  expect(await snapshot(table(page, 'static'))).toEqual(staticBefore);
});
