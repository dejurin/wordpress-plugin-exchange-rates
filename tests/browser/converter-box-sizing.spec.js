const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const legacy = process.env.CONVERTER_REFERENCE === 'legacy';
const reference = 'e2fa9407b8068a3c8b34400a938da4962d30c43f';
const asset = name => legacy
  ? execFileSync('git', ['show', `${reference}:plugin/trunk/assets/${name}`], { cwd: root }).toString()
  : fs.readFileSync(path.join(root, 'plugin/trunk/assets', name), 'utf8');
const css = asset('css/public/style.css');
const js = asset('js/public/common.js');
const capturedThemes = process.env.CONVERTER_THEME_CSS_DIR;

// These reset patterns reproduce Astra's inherited box model and the site's
// existing 44px minimum. Optional captured CSS runs the identical assertions
// against the actual Astra/customizer/page styles without a network dependency.
function themeCSS(theme) {
  if (theme === 'bare') return '';
  if (capturedThemes) {
    const names = ['astra-main.css', 'astra-settings.css', 'fonts-local.css', 'wp-global.css'];
    if (theme === 'site') names.push('yuri-page.css');
    return names.map(name => fs.readFileSync(path.join(capturedThemes, name), 'utf8')).join('\n');
  }
  const astra = 'html{box-sizing:border-box}*,::before,::after{box-sizing:inherit}button,input,optgroup,select,textarea{font:inherit}';
  return astra + (theme === 'site' ? '.yp-story *{box-sizing:border-box}.yp-story .cr-exchange-rates input,.yp-story .cr-exchange-rates select{min-height:44px;min-width:0}.yp-story .cr-exchange-rates button.swap{min-height:44px;min-width:44px}' : '');
}

async function openFixture(page, baseURL, direction, width, theme) {
  await page.setViewportSize({ width, height: 900 });
  await page.route('**/*', route => {
    const url = new URL(route.request().url());
    if (url.origin !== new URL(baseURL).origin) return route.abort();
    if (url.pathname.endsWith('/exchange-rates/assets/css/public/style.css')) return route.fulfill({ contentType: 'text/css', body: css });
    if (url.pathname.endsWith('/exchange-rates/assets/js/public/common.js')) return route.fulfill({ contentType: 'application/javascript', body: js });
    if (capturedThemes && /^\/theme\/font-\d+\.ttf$/.test(url.pathname)) {
      return route.fulfill({ contentType: 'font/ttf', body: fs.readFileSync(path.join(capturedThemes, path.basename(url.pathname))) });
    }
    return route.continue();
  });
  const response = await page.goto(`/?er-bidi-test=1&direction=${direction}&format=3&after=0`);
  expect(response.ok()).toBeTruthy();
  await expect(page.locator('#shortcode-converter .result')).toHaveText('R$246.91');
  await page.evaluate(({ css, theme, themeStyle }) => {
    // Isolate the real WP-rendered fragment from the active QA theme.
    document.querySelectorAll('style,link[rel="stylesheet"]').forEach(node => node.remove());
    for (const value of [css, themeStyle, 'body{margin:16px}body>section{max-width:640px;margin-bottom:24px}', ...(theme === 'bare' ? ['body{font:15px/1.5 Arial,sans-serif;color:#17283e}'] : [])]) {
      const style = document.createElement('style'); style.textContent = value; document.head.append(style);
    }
    if (theme === 'site') {
      const story = document.createElement('div'); story.className = 'yp-story'; story.dataset.product = 'exchange-rates';
      const widget = document.createElement('div'); widget.className = 'yp-widget';
      while (document.body.firstChild) widget.append(document.body.firstChild);
      story.append(widget); document.body.append(story);
    }
    // Detect accidental global resets outside the converter scope.
    const control = document.createElement('input'); control.id = 'outside-converter';
    control.style.cssText = 'height:20px;padding:5px;border:1px solid';
    document.body.append(control);
  }, { css, theme, themeStyle: themeCSS(theme) });
  await page.evaluate(async () => { await document.fonts.ready; });
}

async function geometry(converter) {
  return converter.evaluate(el => {
    const box = node => { const r = node.getBoundingClientRect(); return { x:r.x,y:r.y,width:r.width,height:r.height }; };
    const controls = [...el.querySelectorAll('input,button.swap,select')].map(node => ({ ...box(node), sizing:getComputedStyle(node).boxSizing }));
    const input = el.querySelector('input'),button = el.querySelector('button.swap'),svg = button.querySelector('svg');
    return { controls, icon:box(svg), button:box(button), input:box(input), root:box(el), dir:getComputedStyle(el).direction };
  });
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [1064, 390, 320]) {
    for (const theme of ['bare', 'astra', 'site']) {
      test(`${direction} ${width}px ${theme}: equal converter control heights`, async ({ page, baseURL }) => {
        await openFixture(page, baseURL, direction, width, theme);
        for (const id of ['shortcode-converter', 'widget-converter']) {
          const converter = page.locator(`#${id} .widget-exchange-rates-currency-converter`);
          const result = await geometry(converter);
          const expected = theme === 'site' ? 44 : 40;
          expect(result.controls).toHaveLength(4);
          for (const control of result.controls) {
            expect(control.sizing).toBe('border-box');
            expect(control.height).toBeCloseTo(expected, 1);
          }
          expect(result.input.y).toBeCloseTo(result.button.y, 1);
          // Existing flex shrinking may narrow the SVG in constrained themes.
          // Its 24px height and vertical alignment must remain intact.
          expect(result.icon.width).toBeGreaterThan(0);
          expect(result.icon.width).toBeLessThanOrEqual(24);
          expect(result.icon.height).toBe(24);
          expect(result.icon.y + 12).toBeCloseTo(result.button.y + result.button.height / 2, 1);
          expect(result.dir).toBe(direction);
          for (const control of result.controls) {
            expect(control.x).toBeGreaterThanOrEqual(result.root.x - 1);
            expect(control.x + control.width).toBeLessThanOrEqual(result.root.x + result.root.width + 1);
          }
        }
        await expect(page.locator('#outside-converter')).toHaveCSS('box-sizing', theme === 'bare' ? 'content-box' : 'border-box');
        expect((await page.locator('#outside-converter').boundingBox()).height).toBe(theme === 'bare' ? 32 : 20);
      });
    }
  }
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 1064]) {
    test(`${direction} ${width}px: legacy contract for amounts, swap, caption and link`, async ({ page, baseURL }) => {
      const errors = []; page.on('pageerror', error => errors.push(error.message));
      await openFixture(page, baseURL, direction, width, 'bare');
      const untouched = await page.locator('#badges .badge-leaders,#shortcode-table table,#widget-table table').evaluateAll(nodes => nodes.map(node => node.outerHTML));
      for (const id of ['shortcode-converter', 'widget-converter']) {
        const section = page.locator('#' + id);
        const converter = section.locator('.widget-exchange-rates-currency-converter');
        const caption = await section.locator('.exchange-rates-caption').innerText();
        const link = await section.locator('.exchange-rates-caption a').getAttribute('href');
        const other = page.locator(`#${id === 'shortcode-converter' ? 'widget-converter' : 'shortcode-converter'} .result`);
        const otherValue = await other.innerText();
        await converter.locator('input').fill('10');
        await expect(converter.locator('.result')).toHaveText('R$2');
        await converter.locator('button.swap').click();
        await expect(converter.locator('select').nth(0)).toHaveValue('BRL');
        await expect(converter.locator('select').nth(1)).toHaveValue('USD');
        await expect(converter.locator('.result')).toHaveText('$50');
        await converter.locator('select').nth(0).selectOption('USD');
        await converter.locator('select').nth(1).selectOption('EUR');
        await expect(converter.locator('.result')).toHaveText('€12.5');
        await converter.locator('input').fill('0');
        await expect(converter.locator('.result')).toHaveText('€0');
        await expect(other).toHaveText(otherValue);
        await expect(section.locator('.exchange-rates-caption')).toHaveText(caption);
        await expect(section.locator('.exchange-rates-caption a')).toHaveAttribute('href', link);
      }
      expect(await page.locator('#badges .badge-leaders,#shortcode-table table,#widget-table table').evaluateAll(nodes => nodes.map(node => node.outerHTML))).toEqual(untouched);
      expect(errors).toEqual([]);
    });
  }
}
