const { test, expect } = require('@playwright/test');

async function renderedText(page) {
  return page.locator('section').evaluateAll(sections => sections.map(section => {
    const clone = section.cloneNode(true);
    // WordPress may replace decorative emoji text with images after page load.
    for (const image of clone.querySelectorAll('img.emoji')) image.replaceWith(image.alt);
    return clone.textContent;
  }));
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 1064]) {
    for (const source of ['currencyrate', 'eu-ecb']) {
      for (const mismatch of [false, true]) {
        test(`${direction} ${width}px ${source}: ${mismatch ? 'retained source after failed selection' : 'legacy matching-source contract'}`, async ({ page }) => {
          await page.setViewportSize({ width, height: 900 });
          const errors = [];
          page.on('pageerror', error => errors.push(error.message));
          const url = `/?er-source-test=1&source=${source}&direction=${direction}`;
          const rate = source === 'currencyrate' ? 1.25 : 2;
          const caption = source === 'currencyrate' ? 'CurrencyRate' : 'ECB';
          await page.goto(url);
          const before = await renderedText(page);
          if (mismatch) {
            await page.goto(`${url}&mismatch=1`);
            expect(await renderedText(page)).toEqual(before);
          }
          for (const id of ['table-normal', 'table-widget']) {
            const table = page.locator(`#${id} table`);
            await expect(table.locator('td[data-rate]').first()).toHaveAttribute('data-rate', String(rate));
            await table.locator('input').fill('10');
            await expect(table.locator('td[data-rate]').first()).toHaveText(String(rate * 10));
          }
          await expect(page.locator('#table-inverse td[data-rate]').first()).toHaveAttribute('data-rate', String(1 / rate));
          for (const id of ['converter', 'converter-widget']) {
            const widget = page.locator(`#${id} .widget-exchange-rates-currency-converter`);
            await expect(widget).toHaveAttribute('data-base-currency', source === 'currencyrate' ? 'USD' : 'EUR');
            await expect(widget.locator('.result')).toHaveText(String(rate));
            await widget.locator('input').fill('10');
            await expect(widget.locator('.result')).toHaveText(String(rate * 10));
            await widget.locator('button.swap').click();
            await expect(widget.locator('.result')).toHaveText(String(10 / rate));
          }
          for (const id of ['table-normal', 'table-inverse', 'table-widget', 'converter', 'converter-widget', 'badge-normal', 'badge-inverse']) {
            await expect(page.locator(`#${id} .exchange-rates-caption`)).toContainText(caption);
            await expect(page.locator(`#${id} .exchange-rates-caption`)).toContainText('02 Jan');
          }
          expect(errors).toEqual([]);
        });
      }
    }
  }
}
