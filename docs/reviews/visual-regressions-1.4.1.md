# Visual widget fixes: final acceptance

Date: October 25, 2026. Base revision: `7923b7042819d897c73e669b6d53fc5876ac7b34`.
Branch: `codex/visual-regressions-141`. Repository change only; no plugin publication or deployment.

## Behavior and scope

The installable patch touches only `assets/css/public/style.css` and
`src/Shortcodes/CurrencyConverter.php`:

- Center table body header/data cells under theme baseline styles. Render table
  amount inputs as blocks so their inline baseline gap cannot offset them when
  table text is larger than the input's original font.
- Keep badge labels, flags and values inside their container. When they do not
  fit on one line, the value moves to a full line. The outer row clips its shared
  corner radius; original colors, flag dimensions and RTL numeric order/alignment
  are retained.
- Allow source captions to wrap inside narrow containers, including 22px text in
  a 320px viewport, while retaining their links, labels and source IDs.
- Reserve the select arrow's 16px width plus an 8px gap beyond the existing edge
  padding, using logical padding for LTR/RTL without changing control height.
- Resolve a missing converter title as translated `Currency Converter` during
  rendering, after locale initialization. Explicit custom and empty titles are
  retained. The shipped catalogs already contain this string.

Calculations, stored data, public attributes, widget identifiers, JavaScript,
dependencies, catalogs and declared WordPress/PHP minimums are unchanged. Dormant
table-border options remain out of scope to preserve existing default appearance.

## Exact final payload

- Metadata remains `1.4.1`; this is not a new published version.
- ZIP: `dist/exchange-rates.1.4.1.zip`, 929 files.
- ZIP SHA-256: `dddf78896884fd63092025df29655457d5c1795c837fec6e36ad554221b605aa`.
- Every ZIP entry was compared byte-for-byte with the current `plugin/trunk/`.
- Final logs and machine-readable results: ignored `dist/visual-final-pr-20261025/`.

The final complete run below includes the input follow-up. Earlier runs on the
preceding visual payload are not substituted for this acceptance.

## Final verification

| Check | Actual result |
| --- | --- |
| Metadata, installable syntax, package inventory/content parity | Passed; 56 installable PHP files, 929 ZIP entries |
| PHP 5.6.40 / 8.3.35 / 8.4.27 | 89 repository PHP files linted and 2259 standalone assertions passed on each runtime |
| WordPress 7.1.3 / PHP 8.3.35, complete Chromium suite | 347 passed, 0 failed/skipped/flaky, including input at 16/22px table text |
| WordPress 5.6 / PHP 5.6.40, fresh final ZIP and focused Chromium suite | Smoke passed; 25 passed, 0 failed/skipped/flaky |
| Integration suites on both WordPress runtimes | Payload 322, directory 168, source identity 73, manual update 28, update state 306, interval 50, background refresh 51, native cron 38, concurrent source 91 passed |
| Rendering and saved formatting on both runtimes | 30 percentage, 79 version and 116 formatting assertions passed |
| Fresh POT extraction and shipped PO/MO loading on both runtimes | 7624 assertions passed on each; missing-POT negative check rejected the invalid input |
| Saved state after browser interaction | Settings, cached rates, saved widget and all three pages unchanged |
| HTTP smoke and shipped CSS/JavaScript assets | Passed on the modern runtime |
| Active upgrade from previously published 1.4.0 to the exact final ZIP | Saved state unchanged without reactivation or reseeding |
| Code rollback to the audited original 1.4.1, then final ZIP restored | The same saved state remained unchanged after both transitions |

The minimum-runtime legacy CLI generator uses explicit `date.timezone=UTC` for
POT extraction; this process setting does not change the WordPress site's timezone.
The upgrade source is the previously verified WordPress.org 1.4.0 ZIP, SHA-256
`da9875d8532a6cbf9ae82dd29573e2c3497b0f6929822539d50265fe0c42dbfb`.
The code rollback tests this visual patch against its original 1.4.1 base, not the
separate scheduling downgrade from 1.4.1 to 1.4.0.

## Regression evidence and visual comparison

The initial unmodified base produced 17 failing focused tests while 8 adjacent
cases passed. A separate real WordPress input preview exposed a 2.5px inline
baseline gap at 22px table text; the final measured input center offset is 0px at
720px and 320px. Entering 100 recalculates only its own table and persists on blur.

The focused tests cover LTR/RTL, 320/900px viewports, 16/22px text, 16/32/64px flags,
regions on/off, static/editable amounts, real shortcodes/native widgets, badge
content and dimensions, caption bounds, arrow spacing and explicit saved titles.
The immutable CSS parity reference stays pinned. Only documented intentional
corrections are normalized; every other computed property and pixel comparison
remains checked. The independent geometry tests establish the original defects.

The task's `visual-fixes-141/side-by-side.html` artifact contains five original
before/after pairs plus two editable-input pairs for amounts 1 and 100, captured
from real WordPress with identical viewport, font, data and theme baseline. The
input comparison identifies the preceding visual payload separately. At 320px
with 48px flags and 22px text, existing horizontal table scrolling is retained;
focusing the input brings its column into view.

## Rollback and remaining coverage

Revert the two installable files through a reviewed change, or restore the
reviewed pre-patch package in an isolated environment. No database migration or
data restoration is needed for this visual patch. Preserve saved settings,
widgets and shortcode content instead of recreating them.

The original screenshot's exact theme rule is unavailable. A generic baseline
rule reproduces the same misalignment on real WordPress. Synthetic tests and
selected inherited theme styles do not certify arbitrary themes, live providers,
Safari, Firefox, physical devices, CDN caching, multisite or full accessibility.
Online CI is a separate commit-level check. No local result authorizes merging,
WordPress.org publication, release assets or installed-site changes.
