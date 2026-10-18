const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const { createHash } = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const stylesheet = 'plugin/trunk/assets/css/public/style.css';
const references = {
  release: 'cb097af1d04153dd8132a6a0214642491b5c5557',
  legacy: 'e2fa9407b8068a3c8b34400a938da4962d30c43f',
};
const reference = process.env.CSS_REFERENCE || 'release';
if (!Object.hasOwn(references, reference)) throw new Error('Unknown CSS reference.');
// Normalize the reference's known converter box-model defect only. The
// dedicated converter tests independently assert 40px geometry and behavior.
// All other legacy computed styles and pixels must still match exactly.
const beforeCSS = execFileSync('git', ['show', `${references[reference]}:${stylesheet}`], { cwd: root }).toString() + `
.cr-exchange-rates .widget-exchange-rates-currency-converter input,
.cr-exchange-rates .widget-exchange-rates-currency-converter select,
.cr-exchange-rates .widget-exchange-rates-currency-converter button.swap { box-sizing: border-box; }
`;
const afterCSS = fs.readFileSync(path.join(root, stylesheet), 'utf8');
const digest = value => createHash('sha256').update(value).digest('hex');
const directions = reference === 'legacy' ? ['ltr'] : ['ltr', 'rtl'];
const scenarios = [
  { name: 'tables converters badges', query: { 'er-bidi-test': '1', format: '3', after: '1' } },
  { name: 'flags names regions', query: { 'er-bidi-test': '1', labels: '1', names: '1', flag: 'rectangular' } },
  { name: 'trends full compact', query: { 'er-trend-test': '1', after: '1' } },
];

async function replaceCSS(page, css) {
  await page.evaluate(value => {
    let style = document.querySelector('style[data-css-parity]');
    if (!style) {
      const link = document.querySelector('link[id="plugin-exchange-rates-widgets-css"]');
      if (!link) throw new Error('The real plugin stylesheet was not enqueued.');
      style = document.createElement('style');
      style.dataset.cssParity = 'true';
      link.replaceWith(style);
    }
    style.textContent = value;
  }, css);
}

async function capture(page) {
  // Finish transitions before collecting pixel and computed-style evidence.
  const options = { fullPage: true, animations: 'disabled', caret: 'hide' };
  const pixels = await page.screenshot(options);
  const elements = await page.locator('.cr-exchange-rates, .cr-exchange-rates *').evaluateAll(nodes => nodes.map(node => {
    const style = getComputedStyle(node);
    const bounds = node.getBoundingClientRect();
    return {
      tag: node.tagName, className: node.getAttribute('class'),
      text: node.textContent, value: 'value' in node ? node.value : null,
      bounds: [bounds.x, bounds.y, bounds.width, bounds.height],
      styles: Object.fromEntries(Array.from(style, name => [name, style.getPropertyValue(name)])),
    };
  }));
  return { pixels, elements };
}

async function compare(page, testInfo, state) {
  await replaceCSS(page, beforeCSS);
  const before = await capture(page);
  await replaceCSS(page, afterCSS);
  const after = await capture(page);
  if (digest(before.pixels) !== digest(after.pixels) || JSON.stringify(before.elements) !== JSON.stringify(after.elements)) {
    await testInfo.attach(`${state}-before`, { body: before.pixels, contentType: 'image/png' });
    await testInfo.attach(`${state}-after`, { body: after.pixels, contentType: 'image/png' });
    await testInfo.attach(`${state}-styles`, {
      body: Buffer.from(JSON.stringify({ before: before.elements, after: after.elements }, null, 2)),
      contentType: 'application/json',
    });
  }
  expect(digest(JSON.stringify(after.elements)), `${state}: all computed styles, values and geometry`).toBe(digest(JSON.stringify(before.elements)));
  // Generate the expected image from immutable Git CSS, never from the candidate.
  // Allow only small color/antialiasing differences; styles and geometry above
  // remain exact. Chromium can rasterize two SVG edge pixels differently even
  // after two stable paints with identical CSS (observed RGB delta <= 8).
  const snapshotName = `${state}.png`;
  const snapshotPath = testInfo.snapshotPath(snapshotName);
  fs.mkdirSync(path.dirname(snapshotPath), { recursive: true });
  fs.writeFileSync(snapshotPath, before.pixels);
  expect(after.pixels).toMatchSnapshot(snapshotName, { threshold: 0.05, maxDiffPixels: 0 });
}

test.beforeEach(async ({ page, baseURL }) => {
  await page.route('**/*', route => {
    const url = new URL(route.request().url());
    if (url.origin !== new URL(baseURL).origin) return route.abort();
    if (url.pathname.endsWith('/exchange-rates/assets/css/public/style.css')) {
      return route.fulfill({ contentType: 'text/css', body: beforeCSS });
    }
    return route.continue();
  });
});

for (const direction of directions) {
  for (const width of [320, 1064]) {
    for (const theme of ['default', 'custom properties and theme rules']) {
      for (const scenario of scenarios) {
        test(`${reference} ${direction} ${width}px ${theme}: ${scenario.name}`, async ({ page }, testInfo) => {
          test.setTimeout(45000);
          await page.setViewportSize({ width, height: 900 });
          const errors = [];
          page.on('pageerror', error => errors.push(error.message));
          const response = await page.goto(`/?${new URLSearchParams({ ...scenario.query, direction })}`);
          expect(response.ok()).toBeTruthy();
          await expect(page.locator('.cr-exchange-rates').first()).toBeVisible();
          await expect(page.locator('html')).toHaveAttribute('dir', direction);
          await page.evaluate(async () => {
            await document.fonts.ready;
            await Promise.all(Array.from(document.images, image => image.decode().catch(() => {})));
          });
          // Preserve exact CSS parity for cached legacy markup. Current table
          // layout intentionally changes and has dedicated table-layout tests.
          await page.evaluate(() => {
            for (const name of ['exchange-rates-table-label', 'exchange-rates-table-region', 'exchange-rates-table-full', 'exchange-rates-caption-start']) {
              document.querySelectorAll('.' + name).forEach(node => node.classList.remove(name));
            }
          });
          if (theme !== 'default') {
            await page.addStyleTag({ content: ':root{--bs-light:rgba(30,70,110,.2);--bs-light-hover:rgba(30,70,110,.4);--radius:12px;--bs-table-striped-bg:rgba(30,70,110,.1)} .cr-exchange-rates input,.cr-exchange-rates select{background-color:#eee;padding:9px} .cr-exchange-rates .border{border-color:#607d8b!important}' });
          }
          await compare(page, testInfo, 'initial');
          const inputs = page.locator('.cr-exchange-rates input');
          for (const input of await inputs.all()) await input.fill('1234.56');
          const info = page.locator('.exchange-rates-caption-button');
          if (await info.count()) await info.first().click();
          await compare(page, testInfo, 'amounts and expanded source');
          if (await inputs.count()) {
            await inputs.first().hover();
            await inputs.first().focus();
            await compare(page, testInfo, 'input hover and focus');
          }
          const converter = page.locator('.widget-exchange-rates-currency-converter').first();
          if (await converter.count()) {
            await converter.locator('input').hover();
            await converter.locator('input').focus();
            await compare(page, testInfo, 'converter input hover and focus');
            await converter.locator('select').first().hover();
            await compare(page, testInfo, 'select hover');
            await converter.locator('button.swap').hover();
            await compare(page, testInfo, 'swap hover');
            await converter.locator('button.swap').click();
            await compare(page, testInfo, 'swapped currencies');
          }
          expect(errors).toEqual([]);
        });
      }
    }
  }
}

test('comparison detects a deliberate converter geometry regression', async ({ page }) => {
  await page.goto('/?er-bidi-test=1&direction=ltr&format=3');
  await expect(page.locator('.widget-exchange-rates-currency-converter input').first()).toBeVisible();
  await replaceCSS(page, beforeCSS);
  const before = await capture(page);
  await replaceCSS(page, beforeCSS + '\n.cr-exchange-rates .widget-exchange-rates-currency-converter input { height: 61px !important; }');
  const changed = await capture(page);
  expect(digest(JSON.stringify(changed.elements))).not.toBe(digest(JSON.stringify(before.elements)));
  expect(digest(changed.pixels)).not.toBe(digest(before.pixels));
});
