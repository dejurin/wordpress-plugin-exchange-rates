const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const accepted = require('../acceptance/public-accepted-differences');

function tableFixture(padding = 6.4) {
  const before = [
    { tag: 'TABLE', class: '', parentIndex: -1, tableIndex: 0 },
    { tag: 'TR', class: '', parentIndex: -1, tableIndex: 0 },
    { tag: 'TH', class: 'text-right', parentIndex: 1, tableIndex: 0, computed: { 'padding-right': `${padding}px` } },
    { tag: 'TR', class: '', parentIndex: -1, tableIndex: 0 },
    { tag: 'TD', class: 'active text-right', parentIndex: 3, tableIndex: 0, computed: { 'padding-right': `${padding}px` } },
    { tag: 'INPUT', class: '', parentIndex: 4, tableIndex: 0, box: { width: 76.172 } },
  ];
  const after = structuredClone(before);
  after[0].class = 'exchange-rates-table-trends';
  for (const index of [2, 4]) {
    after[index].class += ' exchange-rates-table-price';
    after[index].computed['padding-right'] = `${padding + 18}px`;
  }
  after[5].box.width = 58.172;
  const changes = [
    { index: 2, property: 'padding-right', before: `${padding}px`, after: `${padding + 18}px` },
    { index: 4, property: 'padding-right', before: `${padding}px`, after: `${padding + 18}px` },
    { index: 5, property: 'width', before: 76.172, after: 58.172 },
  ];
  return { before, after, changes };
}

test('accepted numeric geometry remains DESIGN REVIEW evidence without rewriting raw differences', () => {
  for (const padding of [4, 6.4]) {
    const { before, after, changes } = tableFixture(padding);
    const original = JSON.stringify({ before, after, changes });
    const result = accepted.partition(changes, before, after, 'table');
    expect(result.numericAxis).toEqual(changes);
    expect(result.review).toEqual(changes);
    expect(result.unexpected).toEqual([]);
    expect(JSON.stringify({ before, after, changes })).toBe(original);
    expect(result.review[0]).toBe(changes[0]);
  }
});

for (const delta of [16, 19]) {
  test(`rejects a ${delta}px padding or input-width change`, () => {
    const { before, after, changes } = tableFixture();
    after[2].computed['padding-right'] = `${6.4 + delta}px`;
    changes[0].after = after[2].computed['padding-right'];
    expect(accepted.classify(changes[0], before, after, 'table')).toBeNull();
    after[5].box.width = changes[2].after = changes[2].before - delta;
    expect(accepted.classify(changes[2], before, after, 'table')).toBeNull();
  });
}

test('rejects other cells, properties, surfaces, missing markers and missing node identity', () => {
  const { before, after, changes } = tableFixture();
  for (const surface of ['converter', 'badge']) expect(accepted.classify(changes[0], before, after, surface)).toBeNull();
  for (const property of ['padding-left', 'padding-top', 'width', 'text-align', 'height']) {
    expect(accepted.classify({ ...changes[0], property }, before, after, 'table')).toBeNull();
  }
  for (const mutation of [
    nodes => { nodes[2].class = 'text-right'; },
    nodes => { nodes[2].class = 'text-right not-exchange-rates-table-price'; },
    nodes => { nodes[0].class = ''; },
    nodes => { nodes[2].tableIndex = -1; },
    nodes => { nodes[2].parentIndex = -1; },
    nodes => { nodes[2].tag = 'SPAN'; },
  ]) {
    const changed = structuredClone(after); mutation(changed);
    expect(accepted.classify(changes[0], before, changed, 'table')).toBeNull();
  }
  expect(accepted.classify({ property: 'node-count', before: 6, after: 5 }, before, after.slice(1), 'table')).toBeNull();
});

test('base input allowance requires its actual active Price cell and matching table', () => {
  const { before, after, changes } = tableFixture();
  for (const mutation of [
    nodes => { nodes[5].parentIndex = 2; },
    nodes => { nodes[5].parentIndex = 3; },
    nodes => { nodes[5].parentIndex = -1; },
    nodes => { nodes[4].class = 'text-right exchange-rates-table-price'; },
    nodes => { nodes[4].computed['padding-right'] = '6.4px'; },
    nodes => { nodes[5].tableIndex = -1; },
    nodes => { nodes[5].tag = 'SELECT'; },
  ]) {
    const changed = structuredClone(after); mutation(changed);
    expect(accepted.classify(changes[2], before, changed, 'table')).toBeNull();
  }
});

test('unrelated geometry remains unexpected beside accepted padding', () => {
  const { before, after, changes } = tableFixture();
  const extra = { index: 4, property: 'width', before: 88, after: 110 };
  const result = accepted.partition([...changes, extra], before, after, 'table');
  expect(result.review).toEqual(changes);
  expect(result.unexpected).toEqual([extra]);
});

test('accepted converter border changes remain narrow and separate', () => {
  const before = [{ tag: 'INPUT' }], after = [{ tag: 'INPUT' }];
  const change = { index: 0, property: 'border-right', before: '1px solid rgba(0, 0, 0, 0.05)', after: '1px solid rgb(133, 139, 147)' };
  expect(accepted.classify(change, before, after, 'converter')).toBe('converter-border');
  expect(accepted.classify(change, before, after, 'table')).toBeNull();
  expect(accepted.classify({ ...change, after: '2px solid rgb(133, 139, 147)' }, before, after, 'converter')).toBeNull();
  expect(accepted.classify({ ...change, property: 'background-color' }, before, after, 'converter')).toBeNull();
});

test('native browser and Node use the identical classifier with native index keys', () => {
  const context = {};
  vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../acceptance/public-accepted-differences.js'), 'utf8'), context);
  const { before, after, changes } = tableFixture();
  for (const { index, ...change } of changes) {
    const nativeChange = { i: index, ...change };
    expect(context.erPublicAcceptedDifferences.classify(nativeChange, before, after, 'table')).toBe(accepted.classify(nativeChange, before, after, 'table'));
  }
});
