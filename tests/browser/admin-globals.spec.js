const { test, expect } = require('@playwright/test');
const { prepareAdministrator, signIn, settingsPath } = require('./helpers/admin');

const badgeForm = 'form[data-shortcode-generator="exchange-rates_badge"]';
const outputSelector = 'textarea[name="shortcode-generator"]';
const moreButton = 'button[data-id="exchange-rates-currency-table-1"]';
const widgetShortcodes = {
  table: '[exchange-rates_currency-table amount="10" base_currency="USD" flag_size="16" flag_type="none" full_width="full_width" amount_active="amount_active" base_show="base_show" code="code" symbol="symbol" after="after" table_headers_show="table_headers_show" table_headers_name="Currency" table_headers_code="Code" table_headers_mid="Price" table_headers_previous_close_show="table_headers_previous_close_show" table_headers_previous_close="Previous Close" table_headers_changes="Changes" id="$ID" currency_list="GBP"]',
  converter: '[exchange-rates_currency-converter title="Test converter" amount="7" from="USD" to="EUR" symbol="symbol" border="border" id="$ID" ]',
};
let credentials;
let pageErrors;

// Authenticated page traces may contain cookies and nonces.
test.use({ trace: 'off' });
test.beforeAll(async ({ baseURL }) => { credentials = prepareAdministrator(baseURL); });
test.beforeEach(async ({ page, baseURL }) => {
  pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  await signIn(page, baseURL, credentials);
});
test.afterEach(() => { expect(pageErrors).toEqual([]); });

async function openSettings(page) {
  await page.goto(settingsPath);
  await expect(page.locator(`${badgeForm} ${outputSelector}`)).toHaveValue(/^\[exchange-rates_badge /);
}

async function globalState(page, name) {
  return page.evaluate(key => ({
    exists: Object.prototype.hasOwnProperty.call(window, key),
    value: window[key],
  }), name);
}

for (const name of ['i', 'value']) {
  for (const existing of [false, true]) {
    test(`${name}: ${existing ? 'preserves another script\'s global' : 'does not create a global'} on load and repeated interactions`, async ({ page }) => {
      await page.addInitScript(({ name, existing }) => {
        if (existing) window[name] = `other-script-${name}`;
        else delete window[name];
      }, { name, existing });
      await openSettings(page);
      const expected = { exists: existing, value: existing ? `other-script-${name}` : undefined };
      expect.soft(await globalState(page, name)).toEqual(expected);
      for (const amount of ['17', '2300.50']) {
        await page.locator('#shortcode-badge-amount').fill(amount);
        await expect(page.locator(`${badgeForm} ${outputSelector}`)).toHaveValue(new RegExp(`amount="${amount.replace('.', '\\.')}"`));
        await page.locator(moreButton).click();
      }
      expect(await globalState(page, name)).toEqual(expected);
    });
  }
}

test('badge generator preserves exact shortcode text, currency lists and range feedback', async ({ page }) => {
  await openSettings(page);
  const form = page.locator(badgeForm);
  const id = await form.locator('input[name="id"]').inputValue();
  const initial = `[exchange-rates_badge color="#024bf4" amount="1" base_currency="USD" flag_type="none" decimals="2" id="${id}" currency_list="EUR"]`;
  await expect(form.locator(outputSelector)).toHaveValue(initial);
  await form.locator('[name="amount"]').fill('1000.50');
  await form.locator('[name="currency_list"]').selectOption(['USD', 'GBP']);
  await form.locator('[name="code"]').check();
  await form.locator('[name="decimals"]').focus();
  await form.locator('[name="decimals"]').press('Home');
  await form.locator('[name="decimals"]').press('ArrowRight');
  await expect(page.locator('#shortcode-badge-decimals-show')).toHaveText('1');
  await expect(form.locator(outputSelector)).toHaveValue(`[exchange-rates_badge color="#024bf4" amount="1000.50" base_currency="USD" flag_type="none" decimals="1" code="on" id="${id}" currency_list="USD,GBP"]`);
  await form.getByRole('button', { name: 'Generate', exact: true }).click();
  await expect(page).toHaveURL(/page=exchange-rates-general$/);
});

test('row expansion preserves both tables, the five-row limit and the hidden short-table button', async ({ page }) => {
  await openSettings(page);
  const rows = page.locator('#exchange-rates-currency-table-1 tbody tr');
  const sourceRows = page.locator('#exchange-rates-source-table-1 tbody tr');
  const original = await rows.allTextContents();
  const source = await sourceRows.allTextContents();
  await expect(rows).toHaveCount(6);
  await expect(sourceRows).toHaveCount(1);
  await expect(page.locator('button[data-id="exchange-rates-source-table-1"]')).toBeHidden();
  await expect(page.locator(moreButton)).toHaveText('Show More/Less (1)');
  for (let cycle = 0; cycle < 2; cycle++) {
    await expect(rows.nth(4)).toBeVisible();
    await expect(rows.nth(5)).toBeHidden();
    await page.locator(moreButton).click();
    await expect(rows.nth(5)).toBeVisible();
    await page.locator(moreButton).click();
    await expect(rows.nth(5)).toBeHidden();
  }
  expect(await rows.allTextContents()).toEqual(original);
  expect(await sourceRows.allTextContents()).toEqual(source);
  await expect(sourceRows.first()).toBeVisible();
});

for (const kind of ['table', 'converter']) {
  test(`${kind}: real widget forms preserve nested field parsing and independent shortcode output`, async ({ page }) => {
    await page.goto('/wp-admin/tools.php?page=exchange-rates-test-widget-forms');
    const form = page.locator(`#test-widget-${kind}`);
    const other = page.locator(`#test-widget-${kind === 'table' ? 'converter' : 'table'}`);
    await other.locator('input[type="text"]').first().click();
    const otherOutput = await other.locator(outputSelector).inputValue();
    expect(otherOutput).toMatch(/^\[exchange-rates_currency-/);
    await form.locator('input[type="text"]').first().click();
    const id = await form.locator('input[name="id"]').inputValue();
    const expected = widgetShortcodes[kind].replace('$ID', id);
    await expect(form.locator(outputSelector)).toHaveValue(expected);
    const amount = form.locator('input[name$="[amount]"]');
    const before = await amount.inputValue();
    for (const value of ['1250.50', '7', before]) {
      await amount.fill(value);
      await expect(form.locator(outputSelector)).toHaveValue(expected.replace(`amount="${before}"`, `amount="${value}"`));
      await expect(other.locator(outputSelector)).toHaveValue(otherOutput);
    }
    if (kind === 'table') {
      await form.locator('select[multiple]').selectOption(['USD', 'EUR']);
      await expect(form.locator(outputSelector)).toHaveValue(expected.replace('currency_list="GBP"', 'currency_list="USD,EUR"'));
    } else {
      await form.locator('select[name$="[to]"]').selectOption('GBP');
      await expect(form.locator(outputSelector)).toHaveValue(expected.replace('to="EUR"', 'to="GBP"'));
    }
    await expect(other.locator(outputSelector)).toHaveValue(otherOutput);
  });
}
