'use strict';
const ids = ['theme', 'width', 'variant', 'source', 'direction'];
const controls = ids.map(id => document.getElementById(id));
const frames = ['before', 'after'].map(id => document.getElementById(id));
const records = [];
let generation = 0;
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
const round = n => Math.round(n * 100) / 100;
function measure(frame) {
  const doc = frame.contentDocument, win = frame.contentWindow;
  const wrapper = doc.querySelector('.widget-exchange-rates-currency-table');
  if (!wrapper) throw new Error('Missing table');
  const table = wrapper.querySelector('table'), box = wrapper.getBoundingClientRect();
  const visibleText = node => { const copy = node.cloneNode(true); copy.querySelectorAll('.er-a11y-sr-only').forEach(el => el.remove()); return copy.textContent.trim(); };
  const cells = [...table.querySelectorAll('th,td')];
  const rect = table.getBoundingClientRect();
  const numbers = [...table.querySelectorAll('td[data-rate],td[data-change]')];
  const last = [...table.querySelectorAll('thead th')].pop().getBoundingClientRect();
  const previousScroll = wrapper.scrollLeft;
  // Measure the final scroll position, not an intermediate smooth-scroll frame.
  const previousBehavior = wrapper.style.scrollBehavior;
  wrapper.style.scrollBehavior = 'auto';
  wrapper.scrollLeft = (win.getComputedStyle(wrapper).direction === 'rtl' ? -1 : 1) * wrapper.scrollWidth;
  const end = [...table.querySelectorAll('thead th')].pop().getBoundingClientRect();
  const lastReachable = end.left >= box.left - 1 && end.right <= box.right + 1;
  wrapper.scrollLeft = previousScroll;
  wrapper.style.scrollBehavior = previousBehavior;
  return {
    version: wrapper.closest('[data-exchange-rates-version]')?.dataset.exchangeRatesVersion,
    viewport: win.innerWidth, available: wrapper.clientWidth, table: round(rect.width), overflow: wrapper.scrollWidth > wrapper.clientWidth + 1,
    lastVisible: last.left >= box.left - 1 && last.right <= box.right + 1,
    lastReachable, scrollFocusable: wrapper.tabIndex === 0,
    scrollNote: wrapper.nextElementSibling?.classList.contains('exchange-rates-table-scroll-note') && !wrapper.nextElementSibling.hidden,
    overflowX: win.getComputedStyle(wrapper).overflowX,
    values: numbers.map(visibleText), input: table.querySelector('input')?.value,
    font: numbers.map(node => win.getComputedStyle(node).font), numericWrap: numbers.map(node => win.getComputedStyle(node).whiteSpace),
    geometry: cells.map(node => {const r=node.getBoundingClientRect();return [round(r.x-rect.x),round(r.y-rect.y),round(r.width),round(r.height)];}),
    pageOverflow: doc.documentElement.scrollWidth > win.innerWidth + 1,
    scrollbar: win.getComputedStyle(wrapper).scrollbarWidth
  };
}
async function load(frame, stage, config) {
  const url = new URL(location.href); url.search = '';
  Object.entries({page_id:4, surface:'table', er_table_fit:stage, fit_case:config.variant, theme:config.theme, source:config.source, direction:config.direction, trends:'mixed'}).forEach(([key,value]) => url.searchParams.set(key,value));
  frame.style.width = config.width+'px';
  frame.parentElement.style.width = (Number(config.width)+2)+'px';
  await new Promise((resolve,reject) => { const timeout=setTimeout(()=>reject(new Error('Frame timed out')),15000); frame.onload=()=>{clearTimeout(timeout);resolve();}; frame.src=url.href; });
  await frame.contentDocument.fonts.ready;
  await pause(250);
  const result=measure(frame);
  const spacer=frame.contentDocument.createElement('div');spacer.style.height='500px';spacer.setAttribute('aria-hidden','true');frame.contentDocument.body.append(spacer);
  frame.contentDocument.getElementById('lab-fixture').scrollIntoView({block:'start'});
  return result;
}
async function show(record=false) {
  const ticket=++generation;
  const config=Object.fromEntries(ids.map((id,i)=>[id,controls[i].value]));
  document.getElementById('status').textContent='Measuring…';
  const before=baselineCases.find(row=>ids.every(id=>String(row[id])===String(config[id])))?.before;
  if(!before)throw new Error('Missing pre-fix measurement');
  const after=await load(frames[1],'before',config);
  if(frames[1].contentDocument.getElementById('er-table-fit-proposal'))throw new Error('Prototype overlay is forbidden');
  if(ticket!==generation)return;
  const checks={sameValues:JSON.stringify(before.values)===JSON.stringify(after.values),sameInput:before.input===after.input,sameFontSize:before.font.every((font,i)=>Math.abs(parseFloat(font.match(/[0-9.]+px/)[0])-parseFloat(after.font[i].match(/[0-9.]+px/)[0]))<0.01),numbersUnbroken:after.numericWrap.every(x=>x==='nowrap'),pageContained:!after.pageOverflow};
  checks.lastColumnReachable=after.lastReachable;
  if(['history','native','flags'].includes(config.variant)) checks.allColumnsVisible=after.lastVisible&&!after.overflow;
  checks.overflowDiscoverable=!after.overflow||(after.scrollFocusable&&after.scrollNote&&after.overflowX==='auto'&&after.scrollbar!=='none');
  // The retained pixel baseline was captured in Chromium; other engines use reachability checks.
  if(!before.overflow && navigator.userAgent.includes('Chrome/')) checks.unchangedGeometry=JSON.stringify(before.geometry)===JSON.stringify(after.geometry);
  const layoutPass=Object.values(checks).every(Boolean);
  if(record) {
    const edited=[];
    for(const frame of [frames[1]]) {
      const field=frame.contentDocument.querySelector('#lab-fixture input');
      const original=field.closest('table').dataset.amount;
      field.value='2';field.dispatchEvent(new frame.contentWindow.Event('input',{bubbles:true}));
      await pause(40);edited.push(measure(frame).values);
      field.value='invalid';field.dispatchEvent(new frame.contentWindow.Event('input',{bubbles:true}));
      checks['invalid-'+frame.id]=field.getAttribute('aria-invalid')==='true';
      field.value=original;field.dispatchEvent(new frame.contentWindow.Event('input',{bubbles:true}));
      await pause(40);checks['recovery-'+frame.id]=field.getAttribute('aria-invalid')!=='true';
    }
    checks.amountEditing=edited[0][0]==='1.84';
  }
  const result={...config,before,after,checks,layoutPass,pass:Object.values(checks).every(Boolean)};
  if(record)records.push(result);
  ['before','after'].forEach((id,i)=>{const r=i?after:before;document.getElementById(id+'-facts').textContent=`Available ${r.available}px · table ${r.table}px · ${r.lastVisible?'all columns visible':r.lastReachable?'last column reachable by scrolling':'last column clipped'}${r.overflow&&!r.scrollNote?' · scrolling undisclosed':''}`;});
  document.getElementById('status').textContent=(result.pass?'Checks pass':'Review required')+' · '+config.theme+' · '+config.width+'px · '+config.variant;
  document.getElementById('results').textContent=JSON.stringify(result,null,2);
  return result;
}
document.getElementById('controls').onsubmit=event=>event.preventDefault();
controls.forEach(control=>control.onchange=()=>show().catch(error=>document.getElementById('status').textContent=error.message));
document.getElementById('run').onclick=async()=>{
  const button=document.getElementById('run');button.disabled=true;controls.forEach(c=>c.disabled=true);records.length=0;
  const matrix=[];
  for(const theme of ['astra','kadence','twentytwentyfive','generatepress','blocksy'])for(const width of ['320','390','640'])for(const source of ['eu-ecb','currencyrate'])matrix.push({theme,width,source,variant:'history',direction:'ltr'});
  for(const theme of ['astra','kadence'])for(const width of ['320','390','640'])for(const variant of ['standard','native','names','flags','large'])matrix.push({theme,width,source:'eu-ecb',variant,direction:'ltr'});
  for(const width of ['320','390','640'])matrix.push({theme:'astra',width,source:'eu-ecb',variant:'history',direction:'rtl'});
  try{
    for(const config of matrix){ids.forEach((id,i)=>controls[i].value=config[id]);await show(true);document.getElementById('summary').textContent=`${records.length}/${matrix.length} · ${records.filter(r=>!r.pass).length} failures`;}
    const response=await fetch(location.href,{method:'POST',headers:{'Content-Type':'application/json','X-Acceptance-Nonce':reportNonce},body:JSON.stringify({userAgent:navigator.userAgent,cases:records})});
    const saved=await response.json();if(!response.ok||!saved.success)throw new Error('Report save failed');
    document.getElementById('summary').textContent+=` · saved ${saved.data.file}`;
  }catch(error){document.getElementById('summary').textContent+=' · '+error.message;}
  finally{button.disabled=false;controls.forEach(c=>c.disabled=false);}
};
show().catch(error=>document.getElementById('status').textContent=error.message);
