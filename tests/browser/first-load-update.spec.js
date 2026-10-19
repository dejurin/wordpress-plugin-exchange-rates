const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { prepareAdministrator, signIn, settingsPath } = require('./helpers/admin');

let credentials;
let seeded = false;
function fixture(mode) {
  try {
    return JSON.parse(execFileSync('docker', ['compose', '--env-file', 'wordpress/.env', 'run', '--rm', '--no-deps',
      'cli', 'eval-file', '/opt/tests/browser-empty-update.php', mode],
    { cwd: path.resolve(__dirname, '../..'), timeout: 30000, stdio: ['ignore', 'pipe', 'pipe'] }).toString());
  } catch {
    throw new Error(`First-load fixture failed: ${mode}`);
  }
}

// Never record authenticated requests, cookies or nonces in artifacts.
test.use({ trace: 'off', screenshot: 'off' });
test.beforeAll(async ({ baseURL }) => { credentials = prepareAdministrator(baseURL); });
test.beforeEach(() => { expect(fixture('seed').seeded).toBe(true); seeded = true; });
test.afterEach(() => { if (seeded) { expect(fixture('restore').restored).toBe(true); seeded = false; } });

for (const direction of ['ltr', 'rtl']) {
  for (const width of [390, 1280]) {
    test(`first provider failure can be retried without changing settings: ${direction} ${width}px`, async ({ page, baseURL }) => {
      test.setTimeout(90000);
      await page.setViewportSize({ width, height: 900 });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await signIn(page, baseURL, credentials);
      const responses = [];
      // Forward the real WordPress AJAX endpoint, including its permission/nonce checks.
      await page.route('**/wp-admin/admin-ajax.php', async route => {
        if (!(route.request().postData() || '').includes('action=exchange_rates_force_data_update')) return route.fallback();
        const response = await route.fetch();
        responses.push(await response.json());
        await route.fulfill({ response });
      });
      await page.goto(`${settingsPath}&er-test-direction=${direction}`);
      expect(await page.locator('html').evaluate(element => getComputedStyle(element).direction)).toBe(direction);
      const button = page.locator('#exchange-rates-force-data-update-button');
      const message = page.locator('#exchange-rates-force-data-update-message');
      await expect(button).toBeVisible();
      await expect(button).toHaveCount(1);
      await expect(message).toHaveCount(1);
      await expect(page.getByText('Last successful update:', { exact: true })).toHaveCount(0);
      await expect(page.getByText(/Press the "Save" button to force/)).toHaveCount(0);
      const empty = fixture('state');
      expect(empty).toMatchObject({ has_rates: false, available: false, timestamp: 0, preferences_unchanged: true, widgets_unchanged: true });

      await button.click();
      await expect.poll(() => responses.length).toBe(1);
      expect(responses[0].success).toBe(false);
      await expect(message).toHaveText(responses[0].data);
      await expect(button).toBeEnabled();
      expect(fixture('state')).toEqual({ ...empty, calls: empty.calls + 1 });

      expect(fixture('healthy').ready).toBe(true);
      await button.click();
      await expect.poll(() => responses.length).toBe(2);
      expect(responses[1]).toEqual({ success: true, data: 'Successful updating data.' });
      await expect(page.getByText('Last successful update:', { exact: true })).toBeVisible();
      const saved = fixture('state');
      expect(saved).toMatchObject({ has_rates: true, available: true, valid_rates: true, preferences_unchanged: true, widgets_unchanged: true, calls: empty.calls + 2 });
      expect(saved.timestamp).toBeGreaterThan(0);

      await button.click();
      await expect.poll(() => responses.length).toBe(3);
      expect(responses[2].success).toBe(false);
      expect(responses[2].data).toMatch(/^Please wait before updating again\. Timeout: \d+ second\(s\)\.$/);
      await expect(message).toHaveText(responses[2].data);
      expect(fixture('state')).toEqual(saved);

      // A later outage must keep the first good data and successful timestamp intact.
      expect(fixture('expire').ready).toBe(true);
      expect(fixture('failure').ready).toBe(true);
      const beforeFailure = fixture('state');
      await button.click();
      await expect.poll(() => responses.length).toBe(4);
      expect(responses[3].success).toBe(false);
      await expect(message).toHaveText(responses[3].data);
      await expect(button).toBeEnabled();
      expect(fixture('state')).toEqual({ ...beforeFailure, available: false, calls: beforeFailure.calls + 1 });
      expect(errors).toEqual([]);
    });
  }
}
