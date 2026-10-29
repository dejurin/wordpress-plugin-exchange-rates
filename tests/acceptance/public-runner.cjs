/* Repeatable package comparison on loopback-only, explicitly isolated fixtures. */
'use strict';
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const { chromium } = require('../browser/node_modules/@playwright/test');
const root = path.resolve(__dirname, '../..');
const args = Object.fromEntries(process.argv.slice(2).map(arg => {
  const at = arg.indexOf('=');
  if (at < 0) throw new Error('Use --name=value arguments');
  return [arg.slice(2, at), arg.slice(at + 1)];
}));
const baseline = args.baseline || 'http://127.0.0.1:19334';
const candidate = args.candidate || 'http://127.0.0.1:19335';
const baselineVersion = args['baseline-version'] || '1.4.2';
const candidateVersion = args['candidate-version'] || '1.5.0';
for (const value of [baseline,candidate]) {
  const url = new URL(value);
  if (url.protocol !== 'http:' || !['127.0.0.1','localhost'].includes(url.hostname)) throw new Error('Only isolated loopback WordPress is allowed');
}
if (!args.output) throw new Error('--output is required');
const out = path.resolve(args.output);
fs.mkdirSync(out, {recursive:false});
const measure = fs.readFileSync(path.join(__dirname, 'public-measure.js'),'utf8');
const cases = fs.readdirSync(path.join(__dirname,'public-cases')).filter(f=>f.endsWith('.json')).sort().map(f=>JSON.parse(fs.readFileSync(path.join(__dirname,'public-cases',f))));
const total = cases.reduce((sum,c)=>sum+Object.keys(c.environments).length,0);
const normalize = text => text.replace(/\s+/g,' ').trim();
function differences(before, after, compareText = true) {
  if (before.length !== after.length) return [{property:'node-count',before:before.length,after:after.length}];
  const found = [];
  before.forEach((node,index)=>{
    const next = after[index];
    if (node.tag !== next.tag) found.push({index,property:'tag',before:node.tag,after:next.tag});
    if (compareText && normalize(node.text) !== normalize(next.text) && node.tag !== 'INPUT') found.push({index,property:'text',before:node.text,after:next.text});
    for (const key of ['x','y','width','height']) if (Math.abs(node.box[key]-next.box[key])>1) found.push({index,property:key,before:node.box[key],after:next.box[key]});
    for (const key of Object.keys(next.computed)) if (node.computed[key] !== next.computed[key]) found.push({index,property:key,before:node.computed[key],after:next.computed[key]});
  });
  return found;
}
function referenceNodes(c,env) {
  const ref=env.reference, origin=ref[0].box;
  return ref.slice(1).filter(n=>c.surface==='badge'?n.class==='badge-leaders'||n.tag==='SPAN':c.surface==='table'?['TABLE','TR','TH','TD','INPUT'].includes(n.tag):true)
    .map(n=>({...n,tag:n.tag==='B'?'HEADING':n.tag,box:{x:n.box.x-origin.x,y:n.box.y-origin.y,width:n.box.width,height:n.box.height}}));
}
async function run() {
  const browser = await chromium.launch({headless:true});
  const context = await browser.newContext({deviceScaleFactor:2,locale:'en-US',timezoneId:'Europe/Kyiv'});
  const summary={startedAt:new Date().toISOString(),browser:browser.version(),baseline,candidate,cases:[],errors:[],humanAcceptance:'PENDING'};
  const started=Date.now(); let done=0;
  try {
    for (const c of cases) for (const [width,env] of Object.entries(c.environments)) {
      const record={id:c.id,surface:c.surface,width:Number(width),versions:{}};
      for (const [side,base,expected] of [['baseline',baseline,baselineVersion],['candidate',candidate,candidateVersion]]) {
        const page=await context.newPage();
        await page.setViewportSize({width:Number(width),height:900});
        const jsErrors=[];page.on('pageerror',error=>jsErrors.push(error.message));
        // Frozen fonts and installed plugin assets must suffice; unexpected network is a fixture defect.
        const external=[];
        await page.route('**/*',route=>{
          const url=new URL(route.request().url());
          if (!['127.0.0.1','localhost'].includes(url.hostname) && !['data:','about:'].includes(url.protocol)) {external.push(url.href);return route.abort();}
          return route.continue();
        });
        try {
          const response=await page.goto(`${base}/?er_public_case=${c.id}&er_public_size=${width}`,{waitUntil:'networkidle',timeout:20000});
          if (!response.ok()) throw new Error(`HTTP ${response.status()}`);
          await page.evaluate(()=>document.fonts.ready);
          const result=await page.evaluate('('+measure+')()');
          if(result.version!==expected)throw new Error(`Wrong package ${result.version}; expected ${expected}`);
          result.jsErrors=jsErrors;result.external=external;
          result.screenshot=`${c.id}-${width}-${side}.png`;
          await page.locator('.er-public-host').screenshot({path:path.join(out,result.screenshot)});
          if(c.surface==='converter') {
            const input=page.locator('.widget-exchange-rates-currency-converter input');
            await input.fill('2');await input.dispatchEvent('input');
            result.twice=await page.locator('.result').innerText();
            await input.fill('0');await input.dispatchEvent('input');
            result.zero=await page.locator('.result').innerText();
            await input.fill('bad');await input.dispatchEvent('input');
            result.invalid=await page.locator('.result').innerText();
            await input.fill('1');await input.dispatchEvent('input');
            result.recovered=await page.locator('.result').innerText();
          }
          if(side==='candidate') {
            result.linkStates=[];
            async function checkLinks(selector) {
              for(const link of await page.locator(selector).all()) {
                await link.hover();
                const hover=await link.evaluate(a=>getComputedStyle(a).textDecorationLine);
                await link.focus();
                const focus=await link.evaluate(a=>getComputedStyle(a).textDecorationLine);
                result.linkStates.push({selector,href:await link.getAttribute('href'),hover,focus});
              }
            }
            await checkLinks('.er-footer-slot a');
            await page.locator('[data-er-info]').click();
            const dialog=page.locator('#exchange-rates-information');
            await dialog.waitFor({state:'visible',timeout:2500});
            result.modal=await dialog.evaluate(d=>({open:d.open,topLayer:d.matches(':modal'),rect:{x:d.getBoundingClientRect().x,y:d.getBoundingClientRect().y,width:d.getBoundingClientRect().width,height:d.getBoundingClientRect().height},width:innerWidth,height:innerHeight,links:[...d.querySelectorAll('a[href]')].map(a=>({rel:a.rel,decoration:getComputedStyle(a).textDecorationLine}))}));
            result.modalScreenshot=`${c.id}-${width}-modal.png`;
            await page.screenshot({path:path.join(out,result.modalScreenshot)});
            await checkLinks('#exchange-rates-information a');
            await page.keyboard.press('Escape');
            result.closed=await dialog.evaluate(d=>!d.open&&getComputedStyle(d).display==='none');
            result.failureModes={};
            for(const mode of ['no-javascript','no-dialog','throw-dialog']) {
              const failureContext=await browser.newContext({viewport:{width:Number(width),height:900},javaScriptEnabled:mode!=='no-javascript'});
              try {
                const failurePage=await failureContext.newPage();
                if(mode==='no-dialog')await failurePage.addInitScript(()=>{HTMLDialogElement.prototype.showModal=undefined;});
                if(mode==='throw-dialog')await failurePage.addInitScript(()=>{HTMLDialogElement.prototype.showModal=function(){throw new Error('Simulated unavailable dialog');};});
                await failurePage.goto(`${base}/?er_public_case=${c.id}&er_public_size=${width}`,{waitUntil:'load',timeout:15000});
                if(mode==='throw-dialog')await failurePage.locator('[data-er-info]').click({timeout:2500});
                const disabled=mode!=='no-dialog'||await failurePage.locator('[data-er-info]').isDisabled();
                result.failureModes[mode]=disabled&&await failurePage.locator('#exchange-rates-information').evaluate(d=>!d.open&&getComputedStyle(d).display==='none');
              } finally {await failureContext.close();}
            }
          }
          record.versions[side]=result;
        } catch(error) {record.versions[side]={error:error.message};summary.errors.push({id:c.id,width,side,error:error.message});}
        finally {await page.close();}
      }
      const before=record.versions.baseline,after=record.versions.candidate;
      if(before.body&&after.body){
        record.fidelity=differences(referenceNodes(c,env),before.body);
        record.changes=differences(before.body,after.body);
        record.knownBorderReview=record.changes.filter(d=>d.property.startsWith('border-')&&String(d.before).includes('rgba(0, 0, 0, 0.05)')&&String(d.after).includes('rgb(133, 139, 147)'));
        record.unexpected=record.changes.filter(d=>!record.knownBorderReview.includes(d));
        record.failures=[];
        if(record.unexpected.length)record.failures.push('Unexpected body differences');
        if(after.footer?.overflow||after.footer?.overlapsBody||after.neighborOverlaps)record.failures.push('Footer or following content overlaps');
        if(!after.dialog?.hidden||after.dialog.display!=='none')record.failures.push('Closed modal is visible');
        if(!after.modal?.open||!after.modal.topLayer||!after.closed)record.failures.push('Modal open/close failed');
        if(Object.values(after.failureModes||{}).some(passed=>!passed))record.failures.push('Modal failure leaks an inline fragment');
        if(after.modal && (after.modal.rect.x<0||after.modal.rect.y<0||after.modal.rect.x+after.modal.rect.width>after.modal.width+1||after.modal.rect.y+after.modal.rect.height>after.modal.height+1))record.failures.push('Modal exceeds viewport');
        if(after.jsErrors.length||after.external.length)record.failures.push('JavaScript error or external dependency');
        if(after.footer?.links.some(l=>l.rel.includes('nofollow')||l.decoration!=='none')||after.modal?.links.some(l=>!l.rel.includes('nofollow')||l.decoration!=='none'))record.failures.push('Link contract changed');
        if(after.linkStates.some(l=>l.hover!=='none'||l.focus!=='none'))record.failures.push('Theme adds link decoration on hover or focus');
        if(c.surface==='converter'&&['twice','zero','recovered'].some(k=>before[k]!==after[k]))record.failures.push('Conversion differs');
        record.status=record.failures.length?'FAIL':record.fidelity.length?'FIDELITY REVIEW':record.knownBorderReview.length?'DESIGN REVIEW':'PASS';
      } else record.status='ERROR';
      summary.cases.push(record);done++;
      const seconds=(Date.now()-started)/1000;
      console.log(`[${done}/${total}] ${Math.round(done/total*100)}% ${c.id} ${width}px: ${record.status}; elapsed ${Math.round(seconds)}s; ETA ${Math.round(seconds/done*(total-done))}s`);
      fs.writeFileSync(path.join(out,'results.json'),JSON.stringify(summary,null,2)+'\n');
    }
  } finally {await browser.close();}
  summary.finishedAt=new Date().toISOString();
  summary.counts=summary.cases.reduce((counts,c)=>(counts[c.status]=(counts[c.status]||0)+1,counts),{});
  summary.fixtureHashes=Object.fromEntries(fs.readdirSync(path.join(__dirname,'public-cases')).map(file=>[file,crypto.createHash('sha256').update(fs.readFileSync(path.join(__dirname,'public-cases',file))).digest('hex')]));
  fs.writeFileSync(path.join(out,'results.json'),JSON.stringify(summary,null,2)+'\n');
  console.log(JSON.stringify({counts:summary.counts,errors:summary.errors.length,output:out}));
  process.exitCode=summary.cases.some(c=>['ERROR','FAIL'].includes(c.status))?1:0;
}
run().catch(error=>{console.error(error);process.exitCode=1;});
