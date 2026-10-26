const { test, expect } = require('@playwright/test');

// Explicit decimal strings keep expected rounding independent of the formatter.
const roundedEur = ['1|235', '1|234:6', '1|234:57', '1|234:568', '1|234:5679', '1|234:5679', '1|234:567901', '1|234:5679012'];
const roundedPrevious = ['1|185', '1|185:2', '1|185:19', '1|185:185', '1|185:1852', '1|185:18519', '1|185:185185', '1|185:1851852'];
const separators = [['.', ','], [' ', '.'], [' ', ','], [',', '.'], ["'", '.'], ["'", ','], ['', '.'], ['', ',']];
const invalidInputs = ['abc', 'NaN', 'Infinity', '-Infinity', '1e309', '-1e309', '1e-400', '1e', '1.2.3', '12abc', '1,5', '1,234.5', '0x10', '0b10', '1_000', '--1', '<img src=x onerror=alert(1)>'];

function output(value, format) {
  const [group, decimal] = separators[format];
  return value.replace(/\|/g, group).replace(':', decimal);
}

async function openFixture(page, baseURL, options = {}) {
  const { direction = 'ltr', width = 1064, format = 3, decimals = 3, after = false } = options;
  await page.setViewportSize({ width, height: 900 });
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
  const response = await page.goto(`/?er-bidi-test=1&direction=${direction}&format=${format}&decimals=${decimals}&after=${after ? 1 : 0}&changes=1`);
  expect(response.ok()).toBeTruthy();
  await expect(page.locator('#shortcode-converter input')).toBeVisible();
}

async function untouched(page, selected) {
  return page.locator('body > section').evaluateAll((sections, active) => sections.filter(section => section.id !== active).map(section => ({
    id: section.id, html: [...section.querySelectorAll('table,.widget-exchange-rates-currency-converter,.badge-leaders')].map(node => node.outerHTML), inputs: [...section.querySelectorAll('input')].map(input => input.value),
  })), selected);
}

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 1064]) {
    for (const kind of ['converter', 'table']) {
      test(`${kind}: invalid amounts stay explicit and recover independently, ${direction} ${width}px`, async ({ page, baseURL }) => {
        test.setTimeout(60000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await openFixture(page, baseURL, { direction, width });
        for (const host of ['shortcode', 'widget']) {
          const id = `${host}-${kind}`;
          const section = page.locator('#' + id);
          const input = section.locator('input');
          if (kind === 'converter') await section.locator('select').nth(1).selectOption('EUR');
          const before = await untouched(page, id);
          for (const value of invalidInputs) {
            await input.fill(value);
            if (kind === 'converter') {
              await expect(section.locator('.result')).toHaveText('–');
              await section.locator('button.swap').click();
              await expect(section.locator('.result')).toHaveText('–');
              await section.locator('button.swap').click();
            } else {
              await expect(section.locator('td[data-rate]')).toHaveText(['–', '–', '–', '–']);
              await expect(section.locator('td[data-change]')).toHaveText(['–', '–']);
              await expect(section.locator('td[data-rate] img')).toHaveCount(0);
              await input.blur();
              await expect(input).toHaveValue(value);
            }
            await expect(input).toHaveAttribute('aria-invalid', 'true');
            expect(await untouched(page, id)).toEqual(before);
            await input.fill('8');
            await expect(input).not.toHaveAttribute('aria-invalid', 'true');
            if (kind === 'converter') {
              await expect(section.locator('.result')).toHaveText('€10');
            } else {
              await expect(section.locator('td[data-rate]').nth(0)).toHaveText(host === 'widget' ? '€10' : '10');
              await expect(section.locator('td[data-rate]').nth(1)).toHaveText(host === 'widget' ? '€9.6' : '9.6');
              await expect(section.locator('td[data-change]').first()).not.toHaveText('–');
              expect(await section.locator('td[data-rate] img').count()).toBeGreaterThan(0);
            }
          }
        }
        expect(errors).toEqual([]);
      });
    }
  }
}

for (let format = 0; format < 8; format++) {
  for (let decimals = 0; decimals < 8; decimals++) {
    test(`valid rounding and signed grouping: format ${format}, precision ${decimals}`, async ({ page, baseURL }) => {
      const after = decimals % 2 === 1;
      await openFixture(page, baseURL, { format, decimals, after, direction: format % 2 ? 'rtl' : 'ltr', width: decimals % 2 ? 320 : 1064 });
      const symbol = value => after ? value + '€' : '€' + value;
      for (const host of ['shortcode', 'widget']) {
        const converter = page.locator(`#${host}-converter`);
        await converter.locator('select').nth(1).selectOption('EUR');
        const minimum = ['1', '0:1', '0:01', '0:001', '0:0001', '0:00001', '0:000001', '0:0000001'][decimals];
        for (const [amount, expected] of [['987.654321', roundedEur[decimals]], ['-100', '-125'], ['-1000', '-1|250'], ['0', '0'], ['-0.000000001', '>-' + minimum]]) {
          await converter.locator('input').fill(amount);
          await expect(converter.locator('.result')).toHaveText(symbol(output(expected, format)));
          await expect(converter.locator('input')).not.toHaveAttribute('aria-invalid', 'true');
        }
        const table = page.locator(`#${host}-table`);
        await table.locator('input').fill('987.654321');
        const sign = value => host === 'widget' ? symbol(value) : value;
        await expect(table.locator('td[data-rate]').nth(0)).toHaveText(sign(output(roundedEur[decimals], format)));
        await expect(table.locator('td[data-rate]').nth(1)).toHaveText(sign(output(roundedPrevious[decimals], format)));
        await expect(table.locator('input')).not.toHaveAttribute('aria-invalid', 'true');
      }
    });
  }
}

test('finite decimal and scientific notation stay decimal; zero policies are preserved', async ({ page, baseURL }) => {
  await openFixture(page, baseURL, { decimals: 7 });
  const cases = [
    ['  +.5e1  ', '5', '6.25'], ['0012.50', '12.5', '15.625'], ['5.', '5', '6.25'],
    ['.5', '0.5', '0.625'], ['1e3', '1,000', '1,250'],
    ['1e-7', '0.0000001', '0.0000001'], ['0.00000001', '<0.0000001', '<0.0000001'],
    ['1e21', '1,000,000,000,000,000,000,000', '1,250,000,000,000,000,000,000'],
    ['', '0', '1.25'], ['   ', '0', '1.25'], ['-0', '0', '1.25'], ['-10', '-10', '1.25'],
    ['0e999', '0', '1.25'],
  ];
  for (const host of ['shortcode', 'widget']) {
    const converter = page.locator(`#${host}-converter`);
    await converter.locator('select').nth(1).selectOption('USD');
    const table = page.locator(`#${host}-table`);
    for (const [input, converted, rate] of cases) {
      await converter.locator('input').fill(input);
      await expect(converter.locator('.result')).toHaveText('$' + converted);
      await table.locator('input').fill(input);
      await expect(table.locator('td[data-rate]').first()).toHaveText((host === 'widget' ? '€' : '') + rate);
      await expect(converter.locator('input')).not.toHaveAttribute('aria-invalid', 'true');
      await expect(table.locator('input')).not.toHaveAttribute('aria-invalid', 'true');
    }
  }
});

test('finite amount with overflowing result is unavailable in both components', async ({ page, baseURL }) => {
  await openFixture(page, baseURL);
  for (const host of ['shortcode', 'widget']) {
    const converter = page.locator(`#${host}-converter`);
    await converter.locator('select').nth(1).selectOption('EUR');
    const table = page.locator(`#${host}-table`);
    for (const component of [converter, table]) {
      await component.locator('input').fill('1.79e308');
    }
    await expect(converter.locator('.result')).toHaveText('–');
    await expect(table.locator('td[data-rate]').nth(0)).toHaveText('–');
    await expect(table.locator('td[data-rate]').nth(1)).toHaveText('–');
    await expect(table.locator('td[data-change]').first()).toHaveText('–');
    for (const component of [converter, table]) await expect(component.locator('input')).toHaveAttribute('aria-invalid', 'true');
  }
});

test('a nonzero amount whose product underflows is unavailable and recovers', async ({ page, baseURL }) => {
  await openFixture(page, baseURL, { decimals: 7 });
  for (const host of ['shortcode', 'widget']) {
    const converter = page.locator(`#${host}-converter`);
    await converter.locator('select').nth(1).selectOption('BRL');
    const table = page.locator(`#${host}-table`);
    for (const component of [converter, table]) await component.locator('input').fill('5e-324');
    await expect(converter.locator('.result')).toHaveText('–');
    await expect(table.locator('td[data-rate]').nth(2)).toHaveText('–');
    await expect(table.locator('td[data-change]').nth(1)).toHaveText('–');
    for (const component of [converter, table]) {
      await expect(component.locator('input')).toHaveAttribute('aria-invalid', 'true');
      await component.locator('input').fill('1e-307');
      await expect(component.locator('input')).not.toHaveAttribute('aria-invalid', 'true');
    }
    // Representable tiny results still follow the configured display precision.
    await expect(converter.locator('.result')).toHaveText('R$<0.0000001');
    await expect(table.locator('td[data-rate]').nth(2)).toHaveText(host === 'widget' ? 'R$<0.0000001' : '<0.0000001');
  }
});

test('cached table markup restores its existing trend image after invalid-input recovery', async ({ page, baseURL }) => {
  await openFixture(page, baseURL);
  const table = page.locator('#shortcode-table table');
  await table.locator('td[data-rate]').evaluateAll(cells => cells.forEach(cell => {
    for (const attribute of [...cell.attributes]) {
      if (attribute.name.startsWith('data-change-')) cell.removeAttribute(attribute.name);
    }
  }));
  const images = table.locator('td[data-rate] img');
  const sources = await images.evaluateAll(nodes => nodes.map(node => node.src));
  expect(sources.length).toBeGreaterThan(0);
  await table.locator('input').fill('1e309');
  await expect(table.locator('td[data-rate]').first()).toHaveText('–');
  for (const image of await images.all()) await expect(image).toBeHidden();
  await table.locator('input').fill('8');
  await expect(table.locator('td[data-rate]').first()).toHaveText('10');
  expect(await images.evaluateAll(nodes => nodes.map(node => node.src))).toEqual(sources);
  for (const image of await images.all()) await expect(image).toBeVisible();
});
