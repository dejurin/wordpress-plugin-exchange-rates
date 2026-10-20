#!/usr/bin/env node
'use strict';

// Render the static marketing example; never load WordPress or a rate provider.
const assert = require('node:assert/strict');
const { createHash } = require('node:crypto');
const fs = require('node:fs/promises');
const http = require('node:http');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const entry = '/examples/banner/index.html';
const mounts = new Map([
	['/examples/banner/', path.join(root, 'examples/banner')],
	['/plugin/trunk/assets/', path.join(root, 'plugin/trunk/assets')],
]);
const mimeTypes = {
	'.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8',
	'.svg': 'image/svg+xml', '.png': 'image/png', '.ttf': 'font/ttf',
};

async function serve(request, response) {
	if (!['GET', 'HEAD'].includes(request.method)) {
		response.writeHead(405).end();
		return;
	}
	try {
		const pathname = decodeURIComponent(new URL(request.url, 'http://localhost').pathname);
		for (const [prefix, directory] of mounts) {
			if (!pathname.startsWith(prefix)) continue;
			const file = await fs.realpath(path.resolve(directory, pathname.slice(prefix.length)));
			if (!file.startsWith(directory + path.sep) || !mimeTypes[path.extname(file)]) break;
			const content = await fs.readFile(file);
			response.writeHead(200, {
				'Content-Type': mimeTypes[path.extname(file)],
				'Cache-Control': 'no-store',
				'X-Content-Type-Options': 'nosniff',
				'Content-Security-Policy': "default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self'; base-uri 'none'; frame-ancestors 'none'",
			});
			response.end(request.method === 'HEAD' ? undefined : content);
			return;
		}
	} catch (error) {
		if (!['ENOENT', 'ENOTDIR'].includes(error.code) && !(error instanceof URIError)) {
			console.error('Asset request failed:', error.message);
		}
	}
	response.writeHead(404).end('Not found');
}

async function inspect(page, width, height) {
	await page.evaluate(async () => {
		await document.fonts.ready;
		await Promise.all([...document.images].map(image => image.decode()));
	});
	const geometry = await page.evaluate(() => {
		const box = selector => {
			const { x, y, width, height } = document.querySelector(selector).getBoundingClientRect();
			return { x, y, width, height };
		};
		const clippedText = [];
		for (const element of document.querySelectorAll('h1, h2, .brand__benefit')) {
			const range = document.createRange();
			range.selectNodeContents(element);
			const text = range.getBoundingClientRect();
			const parent = element.closest('.panel, .banner').getBoundingClientRect();
			if (element.scrollWidth > element.clientWidth + 1 || text.left < parent.left - 1 || text.right > parent.right + 1 || text.top < parent.top - 1 || text.bottom > parent.bottom + 1) {
				clippedText.push(element.textContent.trim());
			}
		}
		return {
			banner: box('.banner'), showcase: box('.showcase'),
			converter: box('.converter'), table: box('.rates'),
			fontLoaded: [...document.fonts].some(font => font.family === 'Geist' && font.status === 'loaded')
				&& document.fonts.check('800 65px Geist'),
			images: [...document.images].map(image => ({ src: new URL(image.src).pathname, loaded: image.naturalWidth > 0 })),
			rows: document.querySelectorAll('.rates-row').length,
			illustrations: [...document.querySelectorAll('.product-illustration')].map(element => ({
				role: element.getAttribute('role'), label: element.getAttribute('aria-label'),
			})),
			currencyFlags: [...document.querySelectorAll('.flag')].map(image => ({
				src: new URL(image.src).pathname,
				circular: getComputedStyle(image).borderRadius === '50%',
			})),
			concreteData: document.querySelector('.banner').innerText.match(/\d|\b(?:USD|EUR|GBP|JPY|CAD|CHF|dollar|euro|yen|pound|franc)\b/gi) || [],
			clippedText,
		};
	});
	assert.equal(geometry.banner.width, width);
	assert.equal(geometry.banner.height, height);
	assert.ok(geometry.fontLoaded, 'The bundled font did not load.');
	assert.ok(geometry.images.every(image => image.loaded), 'An image did not load.');
	assert.equal(geometry.illustrations.length, 2);
	assert.ok(geometry.illustrations.every(illustration => illustration.role === 'img' && illustration.label));
	assert.equal(geometry.currencyFlags.length, 7);
	assert.ok(geometry.currencyFlags.every(flag => flag.circular && flag.src.startsWith('/plugin/trunk/assets/flags/circular/')),
		'Use original circular repository flags.');
	assert.deepEqual(geometry.concreteData, [], 'The abstract illustration must not contain concrete currency data.');
	assert.equal(geometry.rows, 5);
	assert.deepEqual(geometry.clippedText, [], 'Text is clipped.');
	for (const property of ['width', 'height', 'y']) {
		assert.ok(Math.abs(geometry.converter[property] - geometry.table[property]) < 0.1, `Panel ${property} differs.`);
	}
	const share = geometry.showcase.width / width;
	assert.ok(share >= 0.60 && share <= 0.65, 'The product cards must occupy 60–65% of the width.');
	return geometry;
}

async function render(baseURL) {
	let chromium;
	try {
		({ chromium } = require('../tests/browser/node_modules/playwright'));
	} catch (error) {
		if (error.code !== 'MODULE_NOT_FOUND') throw error;
		throw new Error('Install the pinned renderer first: npm ci --prefix tests/browser');
	}
	const stamp = new Date().toISOString().replace(/[:.]/g, '-');
	const output = path.join(root, 'dist/banner', stamp);
	await fs.mkdir(output, { recursive: true });
	const started = Date.now();
	const browser = await chromium.launch({ headless: true });
	try {
		const context = await browser.newContext({ deviceScaleFactor: 1, locale: 'en-US', timezoneId: 'UTC', colorScheme: 'light', reducedMotion: 'reduce' });
		const errors = [];
		const requested = new Set();
		await context.route('**/*', async route => {
			const url = new URL(route.request().url());
			if (url.origin !== baseURL) {
				errors.push(`Unexpected external request: ${url.origin}`);
				await route.abort();
				return;
			}
			requested.add(url.pathname);
			await route.continue();
		});
		const page = await context.newPage();
		page.on('pageerror', error => errors.push(error.message));
		page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
		page.on('requestfailed', request => errors.push(`Request failed: ${new URL(request.url()).pathname}`));
		page.on('response', response => { if (response.status() >= 400) errors.push(`HTTP ${response.status()}: ${new URL(response.url()).pathname}`); });
		const captures = [];
		for (const [index, [width, height]] of [[1544, 500], [772, 250]].entries()) {
			await page.setViewportSize({ width, height });
			await page.goto(baseURL + entry, { waitUntil: 'load' });
			const geometry = await inspect(page, width, height);
			assert.deepEqual(errors, [], 'Browser or asset errors occurred.');
			const filename = `banner-${width}x${height}.png`;
			const png = await page.locator('.banner').screenshot({ path: path.join(output, filename), animations: 'disabled' });
			assert.equal(png.readUInt32BE(16), width, 'Unexpected PNG width.');
			assert.equal(png.readUInt32BE(20), height, 'Unexpected PNG height.');
			assert.ok(png.length < 4 * 1024 * 1024, 'The PNG exceeds the WordPress.org 4 MB limit.');
			captures.push({ filename, width, height, bytes: png.length, sha256: createHash('sha256').update(png).digest('hex'), geometry });
			console.log(`[${index + 1}/2] ${(index + 1) * 50}% — ${filename} — ${((Date.now() - started) / 1000).toFixed(1)}s elapsed`);
		}
		const reference = await fs.readFile(path.join(root, 'examples/banner/reference.png'));
		const implementation = await fs.readFile(path.join(output, captures[0].filename));
		const comparison = await context.newPage();
		await comparison.setViewportSize({ width: 1544, height: 1080 });
		await comparison.setContent(`<!doctype html><html lang="en"><title>Banner comparison</title>
			<style>body{margin:0;background:white;font:16px sans-serif}p{height:40px;margin:0;padding:10px 20px;box-sizing:border-box}img{display:block;width:1544px;height:500px}</style>
			<p>Approved reference — normalized to 1544 × 500</p><img alt="Approved reference" src="data:image/png;base64,${reference.toString('base64')}">
			<p>HTML implementation — abstract converter and rate-table illustrations</p><img alt="HTML implementation" src="data:image/png;base64,${implementation.toString('base64')}"></html>`);
		await comparison.evaluate(() => Promise.all([...document.images].map(image => image.decode())));
		await comparison.screenshot({ path: path.join(output, 'comparison.png') });
		await comparison.close();
		const assets = {};
		for (const requestPath of [...requested].sort()) {
			const file = path.join(root, decodeURIComponent(requestPath));
			assets[requestPath.slice(1)] = createHash('sha256').update(await fs.readFile(file)).digest('hex');
		}
		const manifest = {
			generatedAt: new Date().toISOString(), browser: browser.version(),
			playwright: require('../tests/browser/node_modules/playwright/package.json').version,
			deviceScaleFactor: 1, externalRequests: 0, errors, captures, assets,
		};
		await fs.writeFile(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
		console.log(`Complete: 2 banner PNGs, 1 comparison, ${requested.size} local resources, 0 errors, 0 external requests.\nOutput: ${output}`);
	} finally {
		await browser.close();
	}
}

async function main() {
	const arguments_ = process.argv.slice(2);
	if (arguments_.some(argument => argument !== '--preview') || arguments_.length > 1) {
		throw new Error('Usage: node scripts/render-banner.cjs [--preview]');
	}
	const preview = arguments_.includes('--preview');
	const server = http.createServer((request, response) => { void serve(request, response); });
	await new Promise((resolve, reject) => {
		server.once('error', reject);
		server.listen(preview ? 18974 : 0, '127.0.0.1', resolve);
	});
	const baseURL = `http://127.0.0.1:${server.address().port}`;
	if (preview) {
		console.log(`Local banner preview: ${baseURL}${entry}\nOnly banner and plugin assets are served. Press Ctrl+C to stop.`);
		return;
	}
	try {
		await render(baseURL);
	} finally {
		server.closeAllConnections();
		await new Promise(resolve => server.close(resolve));
	}
}

main().catch(error => {
	console.error(`Banner rendering failed: ${error.message}`);
	process.exitCode = 1;
});
