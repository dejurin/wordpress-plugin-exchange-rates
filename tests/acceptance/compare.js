/* Reusable visual review UI. Configuration is emitted by the local acceptance setup. */
'use strict';
const controls = ['surface', 'theme', 'width', 'source', 'case'].map(id => document.getElementById(id));
const frames = [document.getElementById('left'), document.getElementById('right')];
const variants = {
  table: { standard: {}, native: {native: '1'}, static: {static: '1'}, history: {history: '1', trends: 'mixed'}, inverse: {inverse: '1'}, names: {names: '1'}, rtl: {direction: 'rtl'}, multiple: {multi: '1'}, zero: {amount: '0'} },
  converter: { standard: {}, native: {native: '1'}, 'EUR to GBP': {base: 'EUR'}, rtl: {direction: 'rtl'}, multiple: {multi: '1'}, zero: {amount: '0'} },
  badge: { '3 currencies': {count: '3'}, '1 currency': {count: '1'}, '5 currencies': {count: '5'}, inverse: {inverse: '1', count: '5'}, names: {names: '1', count: '5'}, rtl: {direction: 'rtl', count: '5'}, narrow: {narrow: '1', count: '5'} }
};
let config, pending, running = false;
const history = [];
function cases() {
  controls[4].replaceChildren(...Object.keys(variants[controls[0].value]).map(name => new Option(name, name)));
}
function displayResults() { document.getElementById('results').textContent = JSON.stringify(history, null, 2); }
function show() {
  if (pending) { clearTimeout(pending.timer); pending.resolve({status: 'SUPERSEDED'}); pending = null; }
  const [surface, theme, width, source, variant] = controls.map(control => control.value);
  const token = crypto.randomUUID();
  const params = {surface, theme, source, ...variants[surface][variant], er_acceptance_probe: token, er_acceptance_origin: location.origin};
  frames.forEach((frame, index) => {
    const endpoint = index ? config.candidate : config.baseline;
    const url = new URL(endpoint.url);
    Object.entries({...params, page_id: endpoint.page_id}).forEach(([key, value]) => url.searchParams.set(key, value));
    if (!index) url.searchParams.set('er_view', 'baseline');
    url.hash = 'lab-fixture'; frame.style.width = `${width}px`; frame.parentElement.style.width = `${Number(width) + 2}px`;
    document.getElementById(index ? 'right-link' : 'left-link').href = url.href;
    frame.src = url.href;
  });
  document.getElementById('status').textContent = `Measuring ${surface} · ${theme} · ${width}px · ${source} · ${variant}…`;
  return new Promise(resolve => {
    const item = {token, scenario: {surface, theme, width: Number(width), source, variant}, responses: [], resolve};
    item.timer = setTimeout(() => finish(item, 'NOT RUN: frame probe timed out'), 20000); pending = item;
  });
}
function finish(item, error) {
  if (pending !== item) return;
  clearTimeout(item.timer); pending = null;
  let differences = [];
  if (!error) {
    const [before, after] = item.responses;
    if (JSON.stringify(before.values) !== JSON.stringify(after.values)) differences.push('Displayed values differ');
    if (JSON.stringify(before.geometry) !== JSON.stringify(after.geometry)) differences.push('Widget body geometry/styles differ');
    if (after.overflow) differences.push('Candidate widget overflows');
    if (!before.count || !after.count) differences.push('Missing widget body');
  }
  const result = {...item.scenario, status: error || (differences.length ? 'REVIEW' : 'MATCH: measured body only'), differences, baseline: item.responses[0], candidate: item.responses[1]};
  history.push(result); displayResults();
  document.getElementById('status').textContent = `${result.status}${differences.length ? ' — ' + differences.join('; ') : ''}. Human visual approval remains required.`;
  item.resolve(result);
}
window.addEventListener('message', event => {
  if (!pending || event.data?.type !== 'er-acceptance-probe' || event.data.token !== pending.token) return;
  const index = frames.findIndex(frame => frame.contentWindow === event.source);
  if (index < 0 || event.origin !== new URL(index ? config.candidate.url : config.baseline.url).origin) return;
  pending.responses[index] = event.data.result;
  if (pending.responses[0] && pending.responses[1]) finish(pending);
});
document.getElementById('controls').addEventListener('submit', event => event.preventDefault());
controls.forEach((control, index) => control.addEventListener('change', () => { if (index === 0) cases(); show(); }));
async function runMatrix(extended) {
  if (running) return; running = true;
  controls.forEach(c => c.disabled = true);
  document.querySelectorAll('[data-run-matrix]').forEach(b => b.disabled = true);
  const start = history.length;
  const themes = extended ? ['astra', 'kadence', 'twentytwentyfive', 'generatepress', 'blocksy'] : ['astra', 'kadence'];
  try {
    for (const theme of themes) for (const surface of Object.keys(variants)) for (const width of ['320', '390', '640']) for (const source of ['eu-ecb', 'currencyrate']) {
      controls[0].value = surface; controls[1].value = theme; controls[2].value = width; controls[3].value = source; cases();
      for (const variant of (extended && ['astra','kadence'].includes(theme) ? Object.keys(variants[surface]) : [Object.keys(variants[surface])[0]])) {
        controls[4].value = variant;
        await show();
      }
    }
    const run = history.slice(start);
    document.getElementById('status').textContent = `Matrix complete: ${run.length}/${extended ? 318 : 36} measured; ${run.filter(r => r.status.startsWith('MATCH')).length} body matches; ${run.filter(r => r.status === 'REVIEW').length} need review; ${run.filter(r => r.status.startsWith('NOT RUN')).length} not run. Human approval remains pending.`;
  } finally { running = false; controls.forEach(c => c.disabled = false); document.querySelectorAll('[data-run-matrix]').forEach(b => b.disabled = false); }
}
document.getElementById('run').addEventListener('click', () => runMatrix(false));
document.getElementById('extended').addEventListener('click', () => runMatrix(true));
fetch('config.json', {cache: 'no-store'}).then(response => { if (!response.ok) throw new Error('Missing comparison configuration'); return response.json(); }).then(data => {
  for (const endpoint of [data.baseline, data.candidate]) if (!['127.0.0.1','localhost'].includes(new URL(endpoint.url).hostname)) throw new Error('Comparison requires loopback endpoints');
  config = data; document.getElementById('baseline-label').textContent = `${config.baseline.version} · released payload`; document.getElementById('candidate-label').textContent = `${config.candidate.version || config.candidate.target} · installed candidate`; document.getElementById('identity').textContent = `Reference SHA-256: ${config.baseline.sha256}. Candidate: ${config.candidate.label}; SHA-256: ${config.candidate.sha256 || "not recorded"}.`;
  cases(); show();
}).catch(error => { document.getElementById('status').textContent = error.message; });
