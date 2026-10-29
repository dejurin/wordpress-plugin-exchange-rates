/* Shared, narrow allowances for explicitly accepted public-composition changes. */
(function (root) {
  'use strict';
  const hasClass = (node, name) => String(node?.class || '').split(/\s+/).includes(name);
  const close = (a, b) => Number.isFinite(a) && Number.isFinite(b) && Math.abs(a - b) < 0.01;
  const pixels = value => typeof value === 'string' && /^\d+(?:\.\d+)?px$/.test(value) ? Number(value.slice(0, -2)) : NaN;

  function priceCell(index, before, after) {
    const old = before[index], next = after[index];
    if (!old || !next || !['TH', 'TD'].includes(old.tag) || old.tag !== next.tag ||
        !hasClass(old, 'text-right') || !hasClass(next, 'text-right') || !hasClass(next, 'exchange-rates-table-price')) return false;
    const table = next.tableIndex, parent = next.parentIndex;
    return Number.isInteger(table) && table >= 0 && table === old.tableIndex &&
      before[table]?.tag === 'TABLE' && after[table]?.tag === 'TABLE' && hasClass(after[table], 'exchange-rates-table-trends') &&
      Number.isInteger(parent) && parent >= 0 && parent === old.parentIndex &&
      before[parent]?.tag === 'TR' && after[parent]?.tag === 'TR' &&
      close(pixels(next.computed?.['padding-right']) - pixels(old.computed?.['padding-right']), 18);
  }

  function classify(change, before, after, surface) {
    const index = change.index ?? change.i;
    if (!Array.isArray(before) || !Array.isArray(after) || before.length !== after.length || !Number.isInteger(index)) return null;
    const old = before[index], next = after[index];
    if (!old || !next || old.tag !== next.tag) return null;
    if (surface === 'converter' && ['INPUT', 'SELECT', 'BUTTON'].includes(next.tag) &&
        /^border-(top|right|bottom|left)$/.test(change.property) &&
        change.before === '1px solid rgba(0, 0, 0, 0.05)' && change.after === '1px solid rgb(133, 139, 147)') return 'converter-border';
    if (surface !== 'table') return null;
    if (change.property === 'padding-right' && priceCell(index, before, after) &&
        change.before === old.computed['padding-right'] && change.after === next.computed['padding-right']) return 'numeric-axis';
    const parent = next.parentIndex;
    if (change.property === 'width' && old.tag === 'INPUT' && Number.isInteger(parent) && parent === old.parentIndex &&
        priceCell(parent, before, after) && after[parent].tag === 'TD' &&
        hasClass(before[parent], 'active') && hasClass(after[parent], 'active') &&
        next.tableIndex === after[parent].tableIndex && old.tableIndex === before[parent].tableIndex &&
        close(change.before, old.box?.width) && close(change.after, next.box?.width) &&
        close(change.before - change.after, 18)) return 'numeric-axis';
    return null;
  }

  function partition(changes, before, after, surface) {
    const result = { review: [], border: [], numericAxis: [], unexpected: [] };
    for (const change of changes) {
      const rule = classify(change, before, after, surface);
      if (!rule) { result.unexpected.push(change); continue; }
      result.review.push(change);
      result[rule === 'converter-border' ? 'border' : 'numericAxis'].push(change);
    }
    return result;
  }

  const api = { classify, partition };
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.erPublicAcceptedDifferences = api;
})(typeof globalThis !== 'undefined' ? globalThis : window);
