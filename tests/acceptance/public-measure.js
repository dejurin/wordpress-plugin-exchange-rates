() => {
  const root = document.querySelector('[data-exchange-rates-version]');
  const host = document.querySelector('.er-public-host');
  if (!root || !host) throw new Error('Missing isolated public composition');
  const origin = root.getBoundingClientRect();
  const round = value => Math.round(value * 1000) / 1000;
  const properties = ['font-family','font-size','font-weight','font-style','line-height','letter-spacing','text-transform','color','background-color','padding-top','padding-right','padding-bottom','padding-left','border-top','border-right','border-bottom','border-left','border-radius','text-align','white-space'];
  const text = node => {
    if (node.matches('input,select')) return node.value;
    const copy = node.cloneNode(true);
    copy.querySelectorAll('.er-a11y-sr-only').forEach(item => item.remove());
    return copy.textContent.replace(/\s+/g, ' ').trim();
  };
  const box = node => {
    const r = node.getBoundingClientRect();
    return {x:round(r.x-origin.x),y:round(r.y-origin.y),width:round(r.width),height:round(r.height)};
  };
  const selector = root.matches('.shortcode-exchange-rates-badge')
    ? '.badge-leaders,.badge-leaders > span:not(.er-a11y-sr-only)'
    : root.querySelector('table') ? 'table,tr,th,td,input' : 'b,h2,input,button,select,.result';
  const nodes = [...root.querySelectorAll(selector)].filter(n => !n.closest('.er-a11y-sr-only'));
  const footer = host.querySelector('.er-footer-slot');
  const dialog = document.querySelector('#exchange-rates-information');
  const bodyBottom = Math.max(...nodes.map(n => n.getBoundingClientRect().bottom));
  const hostRect = host.getBoundingClientRect();
  return {
    version:root.dataset.exchangeRatesVersion,viewport:{width:innerWidth,height:innerHeight,dpr:devicePixelRatio},
    body:nodes.map(node=>({tag:node.matches('b,h2')?'HEADING':node.tagName,class:node.className,text:text(node),box:box(node),computed:Object.fromEntries(properties.map(p=>[p,getComputedStyle(node).getPropertyValue(p)]))})),
    footer:footer?{box:box(footer),overlapsBody:footer.getBoundingClientRect().top<bodyBottom-1,
      overflow:[...footer.querySelectorAll('a,button')].some(n=>{const r=n.getBoundingClientRect();return r.left<hostRect.left-1||r.right>hostRect.right+1;}),
      links:[...footer.querySelectorAll('a')].map(a=>({text:text(a),href:a.href,rel:a.rel,decoration:getComputedStyle(a).textDecorationLine}))}:null,
    dialog:dialog?{hidden:dialog.hidden,display:getComputedStyle(dialog).display,open:dialog.open}:null,
    neighborOverlaps:document.querySelector('[data-er-public-neighbor]').getBoundingClientRect().top<bodyBottom-1,
    bodyOverflow:nodes.some(n=>n.scrollWidth>n.clientWidth+1&&n.clientWidth>0&&n.matches('table,.badge-leaders')),
  };
}
