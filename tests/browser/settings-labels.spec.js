const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const path = require('node:path');

const settingsPath = '/wp-admin/options-general.php?page=exchange-rates-general';
const fields = ['source_id', 'currency_format', 'decimals'];
let credentials;

// Authenticated traces can contain cookies/nonces. Keep credentials and sessions in memory.
test.use({ trace: 'off' });

function field(page, name) {
  return page.locator(`#exchange-rates-${name}`);
}

function label(page, name) {
  return field(page, name).locator('xpath=ancestor::tr[1]').locator('th label');
}

async function values(page) {
  return Promise.all(fields.map(name => field(page, name).inputValue()));
}

async function setDecimals(page, value) {
  const input = field(page, 'decimals');
  await input.focus();
  await input.press('Home');
  for (let i = 0; i < Number(value); i++) await input.press('ArrowRight');
  await expect(input).toHaveValue(String(value));
  await expect(page.locator('#exchange-rates-decimals-show')).toHaveText(String(value));
}

async function save(page) {
  // Core removes settings-updated from the address bar; inspect the response instead.
  const [response] = await Promise.all([
    page.waitForResponse(response => response.request().isNavigationRequest()
      && new URL(response.url()).searchParams.get('settings-updated') === 'true'),
    page.locator('form[action="options.php"] input[type="submit"]').click(),
  ]);
  expect(response.ok()).toBeTruthy();
  await page.waitForLoadState('load');
  await page.goto(settingsPath);
}

test.beforeAll(async ({ baseURL }) => {
  try {
    credentials = JSON.parse(execFileSync('docker', [
      'compose', '--env-file', 'wordpress/.env', 'run', '--rm', '--no-deps',
      'cli', 'eval-file', '/opt/tests/browser-admin.php', baseURL,
    ], { cwd: path.resolve(__dirname, '../..'), timeout: 30000, stdio: ['ignore', 'pipe', 'pipe'] }).toString());
  } catch {
    // Do not expose captured stdout or credentials through a child-process error.
    throw new Error('Cannot prepare browser authentication. Initialize and select the matching disposable Compose project.');
  }
});

test.beforeEach(async ({ page, baseURL }) => {
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
  try {
    await page.request.get('/wp-login.php');
    await page.request.post('/wp-login.php', { form: {
      log: credentials.username,
      pwd: credentials.password,
      testcookie: '1',
      redirect_to: new URL(settingsPath, baseURL).href,
    } });
  } catch {
    throw new Error('The disposable administrator could not sign in.');
  }
  await page.goto(settingsPath);
  await expect(page).toHaveURL(new URL(settingsPath, baseURL).href);
  await expect(field(page, 'source_id')).toBeVisible();
});

for (const name of fields) {
  test(`${name}: the label points to the existing control without changing its submitted name`, async ({ page }) => {
    await expect(field(page, name)).toHaveCount(1);
    await expect(field(page, name)).toHaveAttribute('name', `exchange-rates[${name}]`);
    expect(await label(page, name).evaluate(element => element.control && element.control.id)).toBe(`exchange-rates-${name}`);
  });
}

for (const name of ['currency_format', 'decimals']) {
  test(`${name}: clicking the label focuses its control without changing values`, async ({ page }) => {
    const before = await values(page);
    await label(page, name).click();
    await expect(field(page, name)).toBeFocused();
    expect(await values(page)).toEqual(before);
  });
}

for (const method of ['mouse', 'keyboard']) {
  test(`Sources still navigates to the source table by ${method} without editing settings`, async ({ page }) => {
    const before = await values(page);
    const link = label(page, 'source_id').getByRole('link', { name: 'Sources', exact: true });
    await expect(link).toHaveAttribute('href', '#source-table');
    await expect(page.locator('#source-table')).toHaveCount(1);
    if (method === 'mouse') {
      await link.click();
    } else {
      await link.focus();
      await expect(link).toBeFocused();
      await link.press('Enter');
    }
    await expect(page).toHaveURL(/#source-table$/);
    await expect(page.locator('#source-table')).toBeInViewport();
    await expect(field(page, 'source_id')).not.toBeFocused();
    expect(await values(page)).toEqual(before);
  });
}

test('saved settings survive reopening and a save/restore round trip through the existing form', async ({ page }) => {
  const before = await values(page);
  await page.reload();
  expect(await values(page)).toEqual(before);
  await field(page, 'currency_format').selectOption('0');
  await setDecimals(page, '5');
  await save(page);
  expect(await values(page)).toEqual([before[0], '0', '5']);
  await field(page, 'currency_format').selectOption(before[1]);
  await setDecimals(page, before[2]);
  await save(page);
  expect(await values(page)).toEqual(before);
});
