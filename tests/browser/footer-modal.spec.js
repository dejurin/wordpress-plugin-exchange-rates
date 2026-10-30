const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const disclaimer = 'Exchange rates are provided for reference only and may be delayed. Displayed rates may differ from those available for actual transactions. Confirm the final rate and applicable fees with your bank, exchange service or payment provider before making a transaction.';
const footerScript = fs.readFileSync(path.resolve(__dirname, '../../plugin/trunk/assets/js/public/footer.js'), 'utf8');

async function checkCompactCaption(opener) {
  const caption = await opener.evaluate(button => {
    const time = button.querySelector('time');
    const formatter = new Intl.DateTimeFormat(document.documentElement.lang || undefined, {
      day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    });
    const parts = Object.fromEntries(formatter.formatToParts(new Date(time.dateTime)).map(part => [part.type, part.value]));
    return {
      compact: time.textContent, expected: `${parts.day} ${parts.month}`, source: button.dataset.sourceName,
      details: `${parts.day} ${parts.month} ${parts.year} ${parts.hour}:${parts.minute} (${formatter.resolvedOptions().timeZone})`,
      title: button.title, label: button.getAttribute('aria-label'),
    };
  });
  expect(caption.compact).toBe(caption.expected);
  expect(caption.title).toContain(caption.source);
  expect(caption.title).toContain(caption.details);
  expect(caption.label).toBe(caption.title);
}

test.beforeEach(async ({ page, baseURL }) => {
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
});

for (const width of [320, 390, 1064]) {
  test(`shared information dialog: all widgets, ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/?er-bidi-test=1');
    const dialog = page.locator('#exchange-rates-information');
    await expect(dialog).toHaveCount(1);
    await expect(dialog).toBeHidden();
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(new Set(ids).size).toBe(ids.length);
    const openers = page.locator('[data-er-info]');
    await expect(openers).toHaveCount(6);
    for (const opener of await openers.all()) {
      const date = await opener.locator('time').getAttribute('datetime');
      await checkCompactCaption(opener);
      await opener.press('Enter');
      await expect(dialog).toBeVisible();
      expect(await dialog.evaluate(node => node.matches(':modal'))).toBe(true);
      await expect(dialog.locator('h2')).toBeFocused();
      await expect(dialog.locator('[data-er-modal-date]')).toHaveAttribute('datetime', new Date(date).toISOString());
      await expect(dialog.locator('[data-er-modal-date]')).toContainText('UTC');
      await expect(dialog.locator('.er-dialog-details dt')).toHaveText(['Source', 'Check:', 'Latest change:']);
      await expect(dialog.locator('[data-er-modal-recorded]')).toHaveAttribute('datetime', new Date(await opener.getAttribute('data-recorded-at')).toISOString());
      for (const row of await dialog.locator('.er-dialog-details > div').all()) {
        expect(await row.evaluate(node => {
          const label = node.querySelector('dt').getBoundingClientRect();
          const value = node.querySelector('dd').getBoundingClientRect();
          const bounds = node.getBoundingClientRect();
          return label.right <= bounds.right + 1 && value.right <= bounds.right + 1 &&
            (label.right <= value.left + 1 || label.bottom <= value.top + 1);
        })).toBe(true);
      }
      await expect(dialog.locator('.er-dialog-disclaimer p')).toHaveText(disclaimer);
      await expect(dialog.locator('.er-dialog-disclaimer')).not.toContainText('[WP_ExchangeRates]');
      await expect(dialog.locator('.er-dialog-head .er-dialog-heading .er-dialog-search-marker')).toHaveText('[WP_ExchangeRates]');
      await expect(dialog.locator('h2')).toHaveText('Data details');
      await expect(dialog.locator('footer')).not.toContainText('[WP_ExchangeRates]');
      expect((await dialog.textContent()).split('[WP_ExchangeRates]')).toHaveLength(2);
      await expect(dialog).not.toContainText('[ExchangeRatesWP]');
      await expect(dialog.locator('a').filter({ hasText: '[WP_ExchangeRates]' })).toHaveCount(0);
      expect(await dialog.locator('.er-dialog-heading').evaluate(heading => {
        const title = heading.querySelector('h2');
        const marker = heading.querySelector('.er-dialog-search-marker');
        const titleBounds = title.getBoundingClientRect();
        const markerBounds = marker.getBoundingClientRect();
        const bounds = heading.getBoundingClientRect();
        return Boolean(title.compareDocumentPosition(marker) & Node.DOCUMENT_POSITION_FOLLOWING) &&
          markerBounds.top >= titleBounds.bottom - 1 && markerBounds.height > 0 &&
          markerBounds.left >= bounds.left - 1 && markerBounds.right <= bounds.right + 1 &&
          markerBounds.bottom <= bounds.bottom + 1 && heading.scrollWidth <= heading.clientWidth + 1;
      })).toBe(true);
      expect(await dialog.locator('.er-dialog-plugin-identity').evaluate(identity => {
        const bounds = identity.getBoundingClientRect();
        return identity.scrollWidth <= identity.clientWidth + 1 && [...identity.children].every(child => {
          const rect = child.getBoundingClientRect();
          return rect.left >= bounds.left - 1 && rect.right <= bounds.right + 1;
        });
      })).toBe(true);
      for (const link of await dialog.locator('a[href]').all()) {
        await expect(link).toHaveAttribute('rel', /\bnofollow\b/);
        await expect(link).toHaveCSS('text-decoration-line', 'none');
      }
      await dialog.locator('h2').press('Shift+Tab');
      await expect(dialog.locator('a').last()).toBeFocused();
      await dialog.locator('a').last().press('Tab');
      await expect(dialog.locator('button')).toBeFocused();
      await dialog.locator('button').press('Escape');
      await expect(dialog).toBeHidden();
      await expect(opener).toBeFocused();
    }
    for (const link of await page.locator('.er-footer-slot a').all()) {
      await expect(link).not.toHaveAttribute('rel', /\bnofollow\b/);
      await expect(link).toHaveCSS('text-decoration-line', 'none');
      await link.hover();
      await expect(link).toHaveCSS('text-decoration-line', 'none');
    }
  });
}

for (const mode of ['unsupported', 'throws', 'not-modal', 'no-scripts']) {
  test(`information stays hidden when ${mode}`, async ({ page }) => {
    await page.route('**/*', async route => {
      if (mode === 'no-scripts' && route.request().resourceType() === 'script') return route.abort();
      if (route.request().resourceType() !== 'document') return route.fallback();
      const response = await route.fetch();
      const code = mode === 'unsupported' ? 'HTMLDialogElement.prototype.showModal = undefined;' :
        mode === 'throws' ? 'HTMLDialogElement.prototype.showModal = function(){throw new Error("simulated failure")};' :
        mode === 'not-modal' ? 'HTMLDialogElement.prototype.showModal = function(){this.setAttribute("open", "")};' : '';
      await route.fulfill({ response, body: (await response.text()).replace('<head>', '<head><script>' + code + '</script>') });
    });
    await page.goto('/?er-bidi-test=1');
    for (const control of await page.locator('[data-er-info]').all()) {
      if (mode === 'no-scripts') {
        const caption = await control.evaluate(button => {
          const time = button.querySelector('time');
          const parts = Object.fromEntries(new Intl.DateTimeFormat('en-GB', {
            day: '2-digit', month: 'short', timeZone: 'UTC',
          }).formatToParts(new Date(time.dateTime)).map(part => [part.type, part.value]));
          return { text: time.textContent, expected: `${parts.day} ${parts.month}`, title: button.title,
            label: button.getAttribute('aria-label'), source: button.dataset.sourceName };
        });
        expect(caption.text).toBe(caption.expected);
        expect(caption.title).toContain(caption.source);
        expect(caption.title).toContain('UTC');
        expect(caption.label).toBe(caption.title);
      } else {
        await checkCompactCaption(control);
      }
    }
    const opener = page.locator('[data-er-info]').first();
    if (await opener.isEnabled()) await opener.click();
    else await expect(opener).toBeDisabled();
    const dialog = page.locator('#exchange-rates-information');
    await expect(dialog).toBeHidden();
    expect(await dialog.evaluate(node => node.getClientRects().length)).toBe(0);
    await expect(dialog).toHaveAttribute('hidden', '');
  });
}

test('new markup with published 1.4.2 JavaScript keeps modal hidden and calculations usable', async ({ page }) => {
  const legacy = execFileSync('git', ['show', '4555660:plugin/trunk/assets/js/public/common.js'], { encoding: 'utf8' });
  await page.route('**/assets/js/public/common.js?*', route => route.fulfill({ contentType: 'text/javascript', body: legacy }));
  await page.route('**/assets/js/public/footer.js?*', route => route.abort());
  await page.goto('/?er-bidi-test=1');
  await page.locator('#shortcode-converter input').fill('100');
  await expect(page.locator('#shortcode-converter .result')).toHaveText('R$20');
  await page.locator('[data-er-info]').first().click();
  await expect(page.locator('#exchange-rates-information')).toBeHidden();
});

for (const scenario of [
  { locale: 'en-GB', timezoneId: 'America/Los_Angeles' },
  { locale: 'uk-UA', timezoneId: 'Europe/Kyiv' },
  { locale: 'bg-BG', timezoneId: 'Asia/Tokyo' },
]) {
  test.describe(`compact local dates ${scenario.locale} ${scenario.timezoneId}`, () => {
    test.use(scenario);
    test('preserves source instants, local day boundaries and complete accessible details', async ({ page }) => {
      const instants = ['2025-12-31T23:30:00Z', '2026-01-01T00:15:00Z', '2026-06-30T23:45:00Z'];
      const captions = instants.map((instant, index) => `<div class="er-footer-slot er-footer-${['table', 'converter', 'badge'][index]}"><a href="https://currencyrate.today/" target="_blank" rel="noreferrer noopener">CurrencyRate</a><button data-er-info data-source-name="European Central Bank" title="UTC source details" aria-label="UTC source details"><time datetime="${instant}">UTC source date</time></button></div>`).join('');
      await page.setContent(`<html lang="${scenario.locale}"><body>${captions}<div class="er-footer-slot"><button data-er-info title="Unavailable source date" aria-label="Unavailable source date"><span>Not available</span></button><button data-er-info title="Invalid source date" aria-label="Invalid source date"><time datetime="invalid">Not available</time></button></div></body></html>`);
      const linksBefore = await page.locator('a').evaluateAll(links => links.map(link => link.outerHTML));
      await page.addScriptTag({ content: footerScript });
      const openers = page.locator('[data-er-info]');
      for (const [index, instant] of instants.entries()) {
        await checkCompactCaption(openers.nth(index));
        await expect(openers.nth(index).locator('time')).toHaveAttribute('datetime', instant);
      }
      await expect(openers.nth(3)).toHaveText('Not available');
      await expect(openers.nth(3)).toHaveAttribute('title', 'Unavailable source date');
      await expect(openers.nth(4).locator('time')).toHaveAttribute('datetime', 'invalid');
      await expect(openers.nth(4)).toHaveText('Not available');
      expect(await page.locator('a').evaluateAll(links => links.map(link => link.outerHTML))).toEqual(linksBefore);
    });
  });
}

test('cached table without new metadata continues to calculate with new JavaScript', async ({ page }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('**/*', async route => {
    if (route.request().resourceType() !== 'document') return route.fallback();
    const response = await route.fetch();
    const body = (await response.text()).replace(/ data-(?:base-currency|inverse|currency|rate-period)="[^"]*"/g, '');
    await route.fulfill({ response, body });
  });
  await page.goto('/?er-bidi-test=1&format=3');
  await page.locator('#shortcode-table input').fill('8');
  await expect(page.locator('#shortcode-table td[data-rate]').first()).toContainText('10');
  await page.locator('#shortcode-converter input').fill('100');
  await expect(page.locator('#shortcode-converter .result')).toHaveText('R$20');
  expect(errors).toEqual([]);
});

test('converter announces invalid amount and recovery without duplicating the visible result', async ({ page }) => {
  await page.goto('/?er-bidi-test=1&format=3');
  const converter = page.locator('#shortcode-converter .widget-exchange-rates-currency-converter');
  await converter.locator('input').fill('invalid');
  await expect(converter.locator('input')).toHaveAttribute('aria-invalid', 'true');
  await expect(converter.locator('.er-a11y-error')).toBeVisible();
  await expect(converter.getByRole('status')).toHaveText('Unable to calculate. Enter a valid amount.');
  await converter.locator('input').fill('100');
  await expect(converter.locator('.er-a11y-error')).toBeHidden();
  await expect(converter.getByRole('status')).toHaveText('100 USD equals 20 BRL');
  await expect(converter.locator('.result')).toHaveAttribute('aria-hidden', 'true');
});
