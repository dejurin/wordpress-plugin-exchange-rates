const { expect } = require('@playwright/test');

async function rateText(locator) {
  return locator.evaluateAll(nodes => nodes.map(node => {
    const clone = node.cloneNode(true);
    clone.querySelectorAll('.er-a11y-sr-only').forEach(item => item.remove());
    return clone.textContent.trim();
  }));
}
async function expectRateText(locator, expected) {
  await expect.poll(() => rateText(locator)).toEqual(Array.isArray(expected) ? expected : [expected]);
}
module.exports = { rateText, expectRateText };
