/* Runs only in a guarded disposable fixture, never in the installable plugin. */
addEventListener('load', async () => {
  const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
  const parameters = new URLSearchParams(location.search);
  const checks = [];
  const check = (pass, name) => checks.push({name, pass: Boolean(pass)});
  let result;
  try {
    await document.fonts.ready;
    // jQuery ready callbacks may run after the window load event in Safari.
    await pause(180);
    if (document.querySelector('.widget-exchange-rates-currency-converter') && !document.querySelector('.widget-exchange-rates-currency-converter input')) throw new Error('Converter is not initialized');
    result = window.erPublicMeasure();
    result.checks = checks;
    const root = document.querySelector('[data-exchange-rates-version]');
    const modal = document.querySelector('#exchange-rates-information');
    const info = document.querySelector('[data-er-info]');
    if (parameters.has('er_public_failure')) {
      info.click();
      await pause(60);
      check(!modal.open && getComputedStyle(modal).display === 'none', 'Unsupported modal stays hidden');
    } else {
      const values = () => [...root.querySelectorAll('td[data-rate],.result')].map(n => {
        const copy = n.cloneNode(true);
        copy.querySelectorAll('.er-a11y-sr-only').forEach(n => n.remove());
        return copy.textContent.trim();
      });
      const input = root.querySelector('input');
      if (input) {
        result.input = {initial: values()};
        for (const [name, value] of [['twice','2'],['zero','0'],['invalid','bad'],['recovered','1']]) {
          input.value = value;
          input.dispatchEvent(new Event('input', {bubbles:true}));
          input.dispatchEvent(new KeyboardEvent('keyup', {bubbles:true,key:'1'}));
          await pause(430);
          result.input[name] = values();
          if (result.version === '1.5.0' && name === 'invalid') {
            check(input.getAttribute('aria-invalid') === 'true', 'Invalid amount is marked');
            check(Boolean(document.getElementById(input.getAttribute('aria-describedby'))), 'Error is associated');
          }
        }
        check(JSON.stringify(result.input.initial) !== JSON.stringify(result.input.twice), 'Amount changes the result');
        if (result.version === '1.5.0') check(result.input.zero.every(value => /0/.test(value) && !/[1-9]/.test(value)), 'Zero remains zero');
        check(JSON.stringify(result.input.initial) === JSON.stringify(result.input.recovered), 'Valid amount recovers');
        const swap = root.querySelector('button');
        if (swap) {
          const selected = () => [...root.querySelectorAll('select')].map(n => n.value);
          const initial = selected(); swap.click(); await pause(40);
          check(JSON.stringify(selected()) === JSON.stringify([...initial].reverse()), 'Swap reverses currencies');
          swap.click(); await pause(40);
          check(JSON.stringify(selected()) === JSON.stringify(initial), 'Swap recovers currency order');
        }
      }
      if (result.version === '1.5.0') {
        check(!result.footer.overflow && !result.footer.overlapsBody && !result.neighborOverlaps, 'Footer fits and does not overlap');
        check(result.dialog.hidden && result.dialog.display === 'none', 'Closed modal has no layout fragment');
        const ids = [...document.querySelectorAll('[id]')].map(n => n.id);
        check(ids.length === new Set(ids).size, 'IDs are unique');
        for (const link of document.querySelectorAll('.er-footer-slot a')) {
          link.focus();
          check(!link.rel.split(/\s+/).includes('nofollow') && getComputedStyle(link).textDecorationLine === 'none', 'Visible attribution rel and focus style');
        }
        info.focus(); info.click(); await pause(60);
        check(modal.open && modal.matches(':modal'), 'Modal uses top layer');
        check(document.activeElement === modal.querySelector('h2'), 'Modal heading initially focused');
        const box = modal.getBoundingClientRect();
        result.modal = {x:box.x,y:box.y,width:box.width,height:box.height};
        check(box.left >= 0 && box.top >= 0 && box.right <= innerWidth + 1 && box.bottom <= innerHeight + 1, 'Modal fits viewport');
        check(modal.querySelector('[data-er-modal-source-name]').textContent === info.dataset.sourceName, 'Modal source belongs to this widget');
        for (const link of modal.querySelectorAll('a[href]')) {
          link.focus();
          check(link.rel.includes('nofollow') && getComputedStyle(link).textDecorationLine === 'none', 'Modal attribution rel and focus style');
        }
        modal.querySelector('h2').focus();
        document.activeElement.dispatchEvent(new KeyboardEvent('keydown', {key:'Tab',shiftKey:true,bubbles:true,cancelable:true}));
        check(Boolean(document.activeElement.closest('.er-dialog-api')), 'Reverse tab stays in modal');
        document.activeElement.dispatchEvent(new KeyboardEvent('keydown', {key:'Tab',bubbles:true,cancelable:true}));
        check(document.activeElement === modal.querySelector('.er-dialog-close'), 'Forward tab stays in modal');
        modal.querySelector('.er-dialog-close').click(); await pause(40);
        check(!modal.open && getComputedStyle(modal).display === 'none', 'Modal closes and hides');
        check(document.activeElement === info, 'Focus returns to date control');
      }
    }
    check(!(window.erPublicErrors || []).length, 'No uncaught JavaScript errors');
  } catch (error) { result = result || {}; result.error = error.message; }
  parent.postMessage({type:'er-public-native',id:parameters.get('er_public_case'),width:Number(parameters.get('er_public_size')),result}, 'http://127.0.0.1:19335');
});
