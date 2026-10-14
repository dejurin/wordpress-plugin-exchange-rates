const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { prepareAdministrator, signIn, settingsPath } = require('./helpers/admin');

let credentials;
let seeded = false;
function fixture(mode) {
  try {
    return JSON.parse(execFileSync('docker', ['compose', '--env-file', 'wordpress/.env', 'run', '--rm', '--no-deps',
      'cli', 'eval-file', '/opt/tests/browser-update.php', mode],
    { cwd: path.resolve(__dirname, '../..'), timeout: 30000, stdio: ['ignore', 'pipe', 'pipe'] }).toString());
  } catch {
    throw new Error(`Browser update fixture failed: ${mode}`);
  }
}

// Authenticated traces may contain cookies and nonces.
test.use({ trace: 'off' });
test.beforeAll(async ({ baseURL }) => {
  credentials = prepareAdministrator(baseURL);
  expect(fixture('seed').seeded).toBe(true);
  seeded = true;
});
test.afterAll(() => { if (seeded) expect(fixture('restore').restored).toBe(true); });

test('the real update button persists success and denies an immediate second HTTP request', async ({ page, baseURL }) => {
  await signIn(page, baseURL, credentials);
  const responses = [];
  // Capture the actual HTTP body before the plugin reloads the page; forward it unchanged.
  await page.route('**/wp-admin/admin-ajax.php', async route => {
    if (!(route.request().postData() || '').includes('action=exchange_rates_force_data_update')) return route.fallback();
    const response = await route.fetch();
    responses.push(await response.json());
    await route.fulfill({ response });
  });
  await page.goto(settingsPath);
  const button = page.locator('#exchange-rates-force-data-update-button');
  await button.click();
  await expect.poll(() => responses.length).toBe(1);
  expect(responses[0]).toEqual({ success: true, data: 'Successful updating data.' });
  await expect(page.getByText('Last successful update:', { exact: true })).toBeVisible();
  const saved = fixture('verify');
  expect(saved.valid).toBe(true);
  await button.click();
  await expect.poll(() => responses.length).toBe(2);
  const denied = responses[1];
  expect(denied.success).toBe(false);
  expect(denied.data).toMatch(/^Please wait before updating again\. Timeout: \d+ second\(s\)\.$/);
  await expect(page.locator('#exchange-rates-force-data-update-message')).toHaveText(denied.data);
  expect(fixture('verify')).toEqual(saved);
});
