() => {
  const props = ['display','box-sizing','width','height','font-family','font-size','font-weight','font-style','line-height','letter-spacing','text-transform','color','background-color','padding-top','padding-right','padding-bottom','padding-left','margin-top','margin-right','margin-bottom','margin-left','border-top','border-right','border-bottom','border-left','border-radius','text-align','vertical-align','white-space','overflow','gap','flex-direction'];
  const styles = node => Object.fromEntries(props.map(key => [key, getComputedStyle(node).getPropertyValue(key)]));
  const rect = node => { const r = node.getBoundingClientRect(); return {x:r.x,y:r.y,width:r.width,height:r.height,documentY:r.y+scrollY}; };
  const roots = [...document.querySelectorAll('[data-exchange-rates-version]')];
  const nodes = new Set();
  const widgets = roots.map(root => {
    const ancestors = []; let node = root.parentElement;
    while (node) { ancestors.push({tag:node.tagName,id:node.id,class:node.className,style:node.getAttribute('style'),computed:styles(node),box:rect(node)});nodes.add(node);node=node.parentElement; }
    [root,...root.querySelectorAll('*')].forEach(n=>nodes.add(n));
    return {version:root.dataset.exchangeRatesVersion,html:root.outerHTML,box:rect(root),ancestors,
      computed:[root,...root.querySelectorAll('table,tr,th,td,input,select,button,.result,.badge-leaders, .badge-leaders > span,h2,b')].map(n=>({tag:n.tagName,class:n.className,text:n.matches('select')?n.value:n.textContent.trim(),computed:styles(n),box:rect(n)}))};
  });
  const sheets = [], inaccessible = [];
  function relevant(rules) {
    const found=[];
    for (const rule of rules) {
      if (rule.selectorText) {
        // Include theme rules for elements introduced by the new footer/modal,
        // even when no such element exists in the released widget's current DOM.
        const newElements=/(^|[\s>,+~:(])(?:button|h1|h2|h3|dt|dd|dialog|footer|time)(?=[\s#.\[,:)>+~]|$)/.test(rule.selectorText);
        const resting=rule.selectorText.replace(/:(?:hover|active|focus-visible|focus-within|focus|visited|link)\b/g,'');
        try { if (newElements || [...nodes].some(n=>n.matches(resting))) found.push(rule.cssText); } catch (_) {}
      } else if (rule.type===5) found.push(rule.cssText);
      else if (rule.cssRules) { const inner=relevant(rule.cssRules); if(inner.length) found.push(rule.cssText.slice(0,rule.cssText.indexOf('{')+1)+inner.join('\n')+'}'); }
    }
    return found;
  }
  for (const sheet of document.styleSheets) {
    if ((sheet.href || '').includes('/plugins/exchange-rates/')) continue;
    try { const rules=relevant(sheet.cssRules); if(rules.length) sheets.push({href:sheet.href,owner:sheet.ownerNode?.id||'',rules}); }
    catch (_) { inaccessible.push(sheet.href); }
  }
  return {url:location.href,capturedAt:new Date().toISOString(),viewport:{width:innerWidth,height:innerHeight,dpr:devicePixelRatio},language:document.documentElement.lang,direction:document.documentElement.dir,title:document.title,widgets,sheets,inaccessible};
}
