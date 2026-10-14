const { defineConfig } = require('@playwright/test');

const baseURL = process.env.WP_TEST_URL || 'http://127.0.0.1:18973';
const target = new URL(baseURL);
if (target.protocol !== 'http:' || !['127.0.0.1', 'localhost'].includes(target.hostname)) {
  throw new Error('Browser regressions require an isolated WordPress site on loopback HTTP.');
}

module.exports = defineConfig({
  testDir: __dirname,
  testMatch: '*.spec.js',
  forbidOnly: Boolean(process.env.CI),
  workers: 1,
  retries: 0,
  timeout: 15000,
  expect: { timeout: 3000 },
  reporter: 'list',
  use: {
    baseURL,
    browserName: 'chromium',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
});
