# WordPress directory banner

Editable HTML/CSS source and approved PNG exports for the Exchange Rates banner. This is a static marketing composition for image rendering, not a working converter or a screenshot of the plugin's default styling. It does not initialize WordPress, read stored rates, call providers, or publish anything.

## Ready-to-upload files

| File | Dimensions | Purpose |
| --- | --- | --- |
| [banner-772x250.png](exports/banner-772x250.png) | 772 × 250 | Standard banner |
| [banner-1544x500.png](exports/banner-1544x500.png) | 1544 × 500 | High-resolution banner |

Both PNGs are below the 4 MB limit and use the exact filenames required by the [WordPress.org asset guidelines](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/). The high-resolution file supplements the standard file; keep both. Their eventual WordPress.org destination is the top-level SVN `assets/` directory, alongside `trunk/`, not the plugin's internal assets directory. Publication is a separate maintainer action; this workflow does not write to SVN.

[exports/manifest.json](exports/manifest.json) records the render environment, checks and SHA-256 hashes. Only the two PNG files are banner uploads.

## Files and editing

- `index.html`: editable headings and low-fidelity converter/table illustrations.
- `banner.css`: layout, typography and colors. The `--u` variable scales one logical pixel on the 1544 × 500 artboard.
- `assets/`: local font, background and supporting icons with upstream licenses.
- `reference.png`: the initial selected composition reference, 2170 × 725. The final approved exports intentionally use abstract data and original plugin assets.
- `design-qa.md`: visual verification and intentional differences from that reference.
- `../../scripts/render-banner.cjs`: local preview and PNG renderer.

The left brand block is vertically centered, with the original 108-pixel bank SVG, the two-line 70-pixel Geist wordmark and the benefit line sharing one horizontal center. The title uses weight 720 and 1.02 line height. The two equal panels occupy 63.2% of the canvas width. The table has edge-to-edge rows. The converter has a black vertical swap icon and no divider above its result. The widgets combine original circular repository flags with neutral gray-blue bars instead of amounts, currency names, rates or percentages. Only the brand, benefit and two widget headings remain readable text.

This is a fixed-ratio banner that scales as one composition. It does not reflow into a mobile website. The fields and table rows are static graphic elements, deliberately without interactive button/select or data-table semantics. Each widget illustration has a descriptive image role; its decorative children are hidden from assistive technology. No loading animation or busy state is used.

## Render

Run from the repository root using Node.js 20+ and the repository's pinned Playwright dependency. If the browser tooling is not installed yet:

```sh
npm ci --prefix tests/browser
node tests/browser/node_modules/playwright/cli.js install chromium
```

Generate both directory sizes:

```sh
node scripts/render-banner.cjs
```

Each run creates a new timestamped directory under `dist/banner/` and prints its absolute path. Existing exports are not replaced. Outputs:

- `banner-1544x500.png`
- `banner-772x250.png`
- `comparison.png`: the approved reference and HTML render, both normalized to 1544 × 500.
- `manifest.json`: browser/Playwright versions, geometry checks, source-asset and PNG SHA-256 hashes, and error counts.

The renderer briefly serves only the banner directory and plugin assets on an ephemeral loopback port, then closes the server and Chromium. All assets, including the font, are local. External requests are blocked and fail the run. It waits for the font and images, checks exact output dimensions and the 4 MB size limit, five abstract rows, two labeled illustrations, seven original circular repository flags and absence of currency names and numerical data, equal panel dimensions, the 60–65% layout constraint and clipped text. Browser/resource errors fail the render.

New renders do not overwrite the approved files in `exports/`. After reviewing a changed design, replace both tracked PNGs and their manifest together in a focused commit.

The font and asset versions are fixed. PNG byte identity still depends on the browser and operating-system rendering environment; the manifest records the browser version for comparison. No wall-clock time is painted into the banner.

## Preview

```sh
node scripts/render-banner.cjs --preview
```

Open <http://127.0.0.1:18974/examples/banner/index.html>. Stop with Ctrl+C. If the port is already occupied, the command fails explicitly; it does not stop another process. The source HTML can also be opened directly from this checkout in a modern browser.

The preview server exposes only the example's HTML/CSS/images/fonts and the existing plugin assets. It does not serve the rest of the repository or its local configuration files.

## Asset provenance

The following resources are referenced directly from the installable tree, without copying or modifying them:

- Logo: `plugin/trunk/assets/img/bank.svg`.
- Flags: `plugin/trunk/assets/flags/circular/{us,eu,gb,jp,ca,ch}.svg`. The converter uses US/EU; the table uses EU/GB/JP/CA/CH. The EU asset is shared between both widgets.

Supporting local resources:

- `up.svg` and `down.svg`: exact vertical SVG arrows from `plugin/trunk/assets/img/` at plugin revision `55e4cfcb99d140d7f4f48d6b232d630fafcb661a`. The corresponding `src/Service/CurrencyTable.php` uses these SVGs. Their original `LICENSE-lucide.txt` is included. These assets are pinned locally to keep the banner reproducible without changing plugin code.
- `swap.svg`: the existing path from `plugin/trunk/assets/js/public/common.js`, wrapped in an external SVG with a 24 × 24 viewBox; no redrawing. Path equality was also verified against plugin revision `55e4cfcb99d140d7f4f48d6b232d630fafcb661a`.
- `chevron-down.svg`: decoded verbatim from the selector background in `plugin/trunk/assets/css/public/style.css`.
- `phosphor-key-bold.svg`: unmodified [Phosphor key-bold SVG](https://github.com/phosphor-icons/core/blob/2b75f3ad12b420c9504ef05df8d2564a28f8500e/assets/bold/key-bold.svg), pinned to that upstream commit. MIT license in `assets/Phosphor-LICENSE.txt`; colored green through a square CSS mask without distorting or rotating its geometry.
- `Geist-Variable.ttf`: [Geist Sans variable font](https://github.com/google/fonts/blob/718e1db4deb9e4d9d85a0ead1b9f5fde2761ccfd/ofl/geist/Geist%5Bwght%5D.ttf), pinned to that Google Fonts commit. SIL Open Font License in `assets/Geist-OFL.txt`. Font version: 1.800.
- `background.png`: background-only derivative generated with the built-in image generation tool from the selected reference. Prompt: remove all foreground text, cards, flags and logos, preserving the quiet off-white/mint background and broad translucent green curves. This raster is decorative; all visible banner text and UI are HTML/CSS.

## Illustration style and limits

The HTML uses simple rounded gray-blue UI bars and a graphite result mark, without concrete numbers or currency names. Each original round repository flag occupies 32 × 32 logical pixels, preserves the original SVG colors and geometry, and has a one-pixel neutral border to keep light regions visible. There are no filters, opacity reductions, internal rings or shadows on the flags.

Layout bars use neutral colors; green and red trend cues retain their directional meaning. Swap, chevrons, trend arrows and the brand logo also use their real SVG assets.

There are no sample rates, currency codes, percentages, nominal amounts or calculations in the rendered composition. The original bank logo is preserved, including its existing monetary symbol.

The two headings identify the converter and exchange-rate table. The table silhouette retains five rows and both trend directions. Green/red change marks suggest direction without implying a specific observation. No bid/sell, chart or live-data promise is introduced.

These files live outside `plugin/trunk/` and are not installable plugin code. No database or plugin release changes are needed. Rollback consists only of reverting this example, the render script and the repository README link through a focused PR. A Git rollback does not change assets already uploaded to WordPress.org.
