async function captionHtml(page) {
  return page.locator('.er-footer-slot').evaluateAll(nodes => nodes.map(node => {
    const clone = node.cloneNode(true);
    // WordPress may replace emoji text asynchronously; compare its same visible character.
    clone.querySelectorAll('img.emoji').forEach(image => image.replaceWith(document.createTextNode(image.alt)));
    return clone.outerHTML;
  }));
}

module.exports = { captionHtml };
