/* Preview-only adaptive spacing and accessible overflow. */
(function () {
  'use strict';
  function init() {
    document.querySelectorAll('.er-table-fit-history').forEach(function (wrapper) {
      var shortNames = { 'Previous Close': 'Prev.', 'Changes': '\u0394 %' };
      wrapper.querySelectorAll('thead th').forEach(function (heading) {
        var label = heading.textContent.trim();
        if (!shortNames[label]) { return; }
        var full = document.createElement('span');
        full.className = 'er-table-fit-full-title';
        full.textContent = label;
        var short = document.createElement('abbr');
        short.className = 'er-table-fit-short-title';
        short.title = label;
        short.textContent = shortNames[label];
        short.setAttribute('aria-hidden', 'true');
        heading.setAttribute('aria-label', label);
        heading.textContent = '';
        heading.append(full, short);
      });
      var note = document.createElement('p');
      note.className = 'er-table-fit-scroll-note';
      note.textContent = 'Scroll horizontally to see all columns.';
      note.hidden = true;
      wrapper.insertAdjacentElement('afterend', note);
      function update() {
        wrapper.classList.remove('er-table-fit-reflow', 'er-table-fit-compact', 'er-table-fit-short');
        var needsFit = wrapper.scrollWidth > wrapper.clientWidth + 1;
        wrapper.classList.toggle('er-table-fit-reflow', needsFit);
        wrapper.classList.toggle('er-table-fit-compact', needsFit && wrapper.clientWidth < 320);
        wrapper.classList.toggle('er-table-fit-short', wrapper.scrollWidth > wrapper.clientWidth + 1);
        var overflow = wrapper.scrollWidth > wrapper.clientWidth + 1;
        note.hidden = !overflow;
        if (overflow) {
          wrapper.setAttribute('tabindex', '0');
          wrapper.setAttribute('role', 'region');
          wrapper.setAttribute('aria-label', 'Exchange rates, horizontally scrollable');
        } else {
          wrapper.removeAttribute('tabindex');
          wrapper.removeAttribute('role');
          wrapper.removeAttribute('aria-label');
        }
      }
      update();
      if (window.ResizeObserver) { new ResizeObserver(update).observe(wrapper); }
      window.addEventListener('resize', update);
      wrapper.addEventListener('input', function () { requestAnimationFrame(update); });
      if (document.fonts) { document.fonts.ready.then(update); }
    });
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
}());
