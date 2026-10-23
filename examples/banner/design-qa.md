# Banner design QA

Result: passed for the approved exports on 2026-10-20.

## Deliverables

- [Standard PNG](exports/banner-772x250.png): 772 × 250, 93,388 bytes.
- [High-resolution PNG](exports/banner-1544x500.png): 1544 × 500, 297,701 bytes.
- [Render manifest](exports/manifest.json): source and output SHA-256 hashes, browser versions and geometry checks.
- Editable source: `index.html`, `banner.css` and local `assets/`.

Both PNGs meet the WordPress.org dimensions, naming and size requirements. The final render was produced from base `eb1e91fe6801144d89bc80e300e9b1cb77f9145a` with these banner files added. Both PNG hashes match the previously accepted preview byte-for-byte.

## Visual inspection

Both exported PNGs were inspected at their native sizes.

- The original plugin logo, two-line title and benefit are centered as one left-hand group.
- Equal-height, equal-width product panels occupy 63.2% of the canvas width on the right. Their vertical alignment matches.
- Converter and five-row table use neutral gray-blue marks instead of currency names, amounts, rates or percentages. Trend marks retain green/red directional cues.
- All seven flag instances use the original circular repository SVGs, with consistent sizing and a neutral border. No synthetic flags, color filters or opacity reductions are used.
- The exact plugin swap, chevron and vertical trend arrows, genuine Phosphor key and bundled Geist font are retained.
- No text clipping, missing assets or overflow was observed in either export.

## Automated verification

Run from the repository root:

```sh
node --check scripts/render-banner.cjs
node scripts/render-banner.cjs
git diff --check -- . ':(exclude)examples/banner/assets/Geist-OFL.txt'
```

All checks passed. The diff check excludes the bundled upstream OFL text, which is preserved verbatim and contains one trailing space. The render verified the two exact dimensions, PNG size limits, font and image loading, five table rows, two labeled illustration roles, seven circular flags, absence of concrete currency data, equal panel geometry, the 60–65% panel-width constraint and text clipping. It requested 16 local resources with zero external requests and zero browser/resource errors.

Environment: Playwright 1.64.1, Chromium 156.0.8078.4, device scale factor 1, light color scheme, reduced motion, en-US locale and UTC timezone. PNG bytes may differ on another browser or operating system.

The copied vertical arrows and their license were also checked byte-for-byte against the current plugin assets. The browser preview of the accepted design was previously checked with zero console errors or warnings; the final PNGs are identical to that design.

## Scope and rollback

This is a static marketing illustration, not an interactive converter or a literal screenshot of the plugin's default appearance. `reference.png` preserves the initial composition reference; the approved exports intentionally simplify its data and use the real plugin assets.

No installable plugin code, database, provider or release metadata changed. Plugin runtime tests were not rerun locally for this banner-only change. WordPress.org publication is a separate maintainer action.

Rollback is a focused revert PR covering the example, exports, renderer and repository README additions. Reverting Git files does not revert an asset already uploaded to WordPress.org.
