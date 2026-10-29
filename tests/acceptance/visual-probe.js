(() => {
  'use strict';
  const token = document.currentScript.dataset.erAcceptanceProbe;
  const origin = document.currentScript.dataset.erAcceptanceOrigin;
  window.addEventListener('load', async () => {
    await document.fonts.ready;
    await new Promise(resolve => setTimeout(resolve, 300));
    const fixture = document.querySelector('#lab-fixture');
    if (!fixture) return;
    const bodies = [...fixture.querySelectorAll('.widget-exchange-rates-currency-table, .widget-exchange-rates-currency-converter, .badge-leaders')];
    const round = value => Math.round(value * 100) / 100;
    const visibleText = node => {
      const clone = node.cloneNode(true);
      clone.querySelectorAll('.er-a11y-sr-only').forEach(hidden => hidden.remove());
      return clone.textContent.trim();
    };
    const values = bodies.map(body => [...body.querySelectorAll('td[data-rate], .result, input, select, .badge-leaders > span')].filter(node => !node.closest('.er-a11y-sr-only')).map(node => node.value ?? visibleText(node)));
    // Badge rows are themselves the body; expose their visible child values.
    bodies.forEach((body, i) => { if (body.matches('.badge-leaders')) values[i] = [...body.children].filter(node => !node.matches('.er-a11y-sr-only')).map(node => visibleText(node)); });
    const geometry = bodies.map(body => {
      const rect = body.getBoundingClientRect();
      const nodes = [body, ...body.querySelectorAll('input,select,td[data-rate],.result')];
      return nodes.map(node => {
        const r = node.getBoundingClientRect(), s = getComputedStyle(node);
        return {tag: node.tagName, x: round(r.x - rect.x), y: round(r.y - rect.y), width: round(r.width), height: round(r.height), font: s.font, color: s.color, padding: s.padding, border: s.border};
      });
    });
    const overflow = bodies.some(body => body.scrollWidth > body.clientWidth + 1);
    // Permit identical top alignment even when a short widget is near the page end.
    // Add space only after measuring; do not alter the widget or theme wrappers.
    const spacer = document.createElement('div');
    spacer.setAttribute('aria-hidden', 'true');
    spacer.style.height = `${innerHeight}px`;
    document.body.appendChild(spacer);
    fixture.scrollIntoView({block: 'start'});
    parent.postMessage({type:'er-acceptance-probe', token, result:{count:bodies.length, width:innerWidth, values, geometry, overflow}}, origin);
  });
})();
