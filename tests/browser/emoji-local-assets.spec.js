const { test, expect } = require('@playwright/test');
const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const provenance = require('../../docs/third-party/twemoji.json');

const assets = new Map(provenance.assets.map(asset => [asset.filename, asset]));
const sections = ['shortcode-full', 'widget-full', 'shortcode-compact', 'widget-compact', 'emoji-badges'];
const digest = bytes => crypto.createHash('sha256').update(bytes).digest('hex');

for (const direction of ['ltr', 'rtl']) {
  for (const width of [320, 1064]) {
    test(`${direction} ${width}px: bundled emoji preserve CDN artwork and pixels`, async ({ page, baseURL }, testInfo) => {
      const origin = new URL(baseURL).origin;
      const externalEmoji = [];
      const errors = [];
      let baseline = false;
      const verifiedArtwork = new Map();
      page.on('pageerror', error => errors.push(error.message));
      await page.route('**/*', route => {
        const url = new URL(route.request().url());
        if (url.origin === origin) return route.continue();
        if (url.origin === 'https://s.w.org' && url.pathname.startsWith('/images/core/emoji/14.0.0/svg/')) {
          if (!baseline) externalEmoji.push(url.href);
          const artwork = verifiedArtwork.get(path.basename(url.pathname));
          if (baseline && artwork) return route.fulfill({ contentType: 'image/svg+xml', body: artwork });
        }
        return route.abort();
      });
      await page.setViewportSize({ width, height: 1000 });
      const query = new URLSearchParams({ 'er-bidi-test': '1', labels: '1', flag: 'emoji', 'emoji-assets': '1', direction });
      expect((await page.goto(`/?${query}`)).ok()).toBeTruthy();
      await expect(page.locator('#emoji-assets-status')).toHaveText('Local emoji assets: PASS — 14/14 decoded; 4 tables and 2 badges; all flag URLs are same-origin.');
      const images = page.locator(sections.map(id => id === 'emoji-badges' ? `#${id} img` : `#${id} tbody th img`).join(', '));
      await expect(images).toHaveCount(14);
      const sources = await images.evaluateAll(nodes => [...new Set(nodes.map(image => image.src))]);
      for (const source of sources) {
        const url = new URL(source);
        expect(url.origin).toBe(origin);
        expect(url.pathname).toMatch(/\/assets\/flags\/emoji\/[a-f0-9-]+\.svg$/);
        const filename = path.basename(url.pathname);
        const asset = assets.get(filename);
        expect(asset, `${filename}: pinned upstream record`).toBeTruthy();
        expect(asset.identical).toBe(true);
        expect(asset.cdn.sha256).toBe(asset.upstream.sha256);
        const response = await page.request.get(source);
        expect(response.ok(), filename).toBeTruthy();
        expect(response.headers()['content-type'], filename).toContain('image/svg+xml');
        const body = await response.body();
        expect(digest(body), `${filename}: actual served bytes equal the immutable CDN/upstream hash`).toBe(asset.upstream.sha256);
        verifiedArtwork.set(filename, body);
      }
      expect(externalEmoji, 'Plugin flags never request the WordPress emoji CDN').toEqual([]);
      const localScreenshots = new Map();
      for (const id of sections) {
        localScreenshots.set(id, await page.locator(`#${id}`).screenshot({ animations: 'disabled' }));
      }

      // Recreate only the published image URLs, using bytes already checked
      // against immutable upstream/CDN hashes. Never allow a live CDN request.
      baseline = true;
      await images.evaluateAll(async nodes => {
        await Promise.all(nodes.map(image => {
          image.src = `https://s.w.org/images/core/emoji/14.0.0/svg/${new URL(image.src).pathname.split('/').pop()}`;
          return image.decode();
        }));
      });
      for (const id of sections) {
        const before = await page.locator(`#${id}`).screenshot({ animations: 'disabled' });
        const after = localScreenshots.get(id);
        const snapshot = `emoji-${direction}-${width}-${id}.png`;
        const snapshotPath = testInfo.snapshotPath(snapshot);
        fs.mkdirSync(path.dirname(snapshotPath), { recursive: true });
        fs.writeFileSync(snapshotPath, before);
        await testInfo.attach(`${id}-published-cdn`, { body: before, contentType: 'image/png' });
        await testInfo.attach(`${id}-bundled`, { body: after, contentType: 'image/png' });
        expect(after).toMatchSnapshot(snapshot, { threshold: 0.05, maxDiffPixels: 0 });
      }
      expect(errors).toEqual([]);
    });
  }
}
