# Amount input correction: 1.4.2

This report records the first correction at `883d0e9`. The subsequent
[small-value and cancellation follow-up](remaining-fixes-1.4.2.md) supersedes its
remaining-issue statements and package identity; the original evidence below is
retained for traceability.

## Scope and exact payload

Prepared on October 26, 2026, on `codex/amount-input-validation`, based on
`1834771695a97236fc8f5c8d2eb477f5d6e30479`. This is a focused correction to the
unpublished 1.4.2 candidate, not a publication or production-site update.

The candidate ZIP at
`dist/amount-input-142-20261026/packages/exchange-rates.1.4.2.zip` has 929 files and
SHA-256 `d70d1562f983e71b729a885eb35e2a000fe2d4dfddbe66c3a10d2b9097814a96`.
All installed files were compared with its manifest after fresh installation,
upgrades and rollback/restore. Only public `common.js`, `CurrencyConverter.php`
and `readme.txt` differ from the earlier 1.4.2 payload. The previous ZIP and its
checksum remain preserved at their original paths.

## Defects and intended differences

The tests were written before the installable source changed. Six selected cases
failed on the original candidate: converter text produced `NaN`; malformed table
input silently calculated from one; zero converter precision fell back to two;
an empty grouping separator fell back to commas; converter and cached-table
overflow produced `In,fin,ity`. The earlier focused run also exposed negative
hundreds rendered as `-,125` and scientific notation grouped as `1,e-7`.

Both inputs now use complete signed decimal/scientific input validation. Empty
converter input still means zero; empty, zero and negative table inputs still
calculate from one. Partial numbers, comma/group separators, alternate radices,
non-finite amounts and arithmetic overflow/underflow display an en dash and set
`aria-invalid`. The table retains invalid input on blur instead of inventing a
valid amount. Valid input clears the error and restores values and trends.

Grouping operates on unsigned decimal digits. Existing rounding and fractional
zero trimming remain. Converter metadata explicitly retains zero precision and
an empty thousands separator. The tests deliberately replace the old invalid
amount-one expectation and the old format 6/7 comma fallback; these were defects,
not supported numeric results.

No stored settings, rates, widget IDs, shortcode attributes, provider calls,
dependencies, cron code or public PHP signatures change. Table-border work stays
deferred. Rollback replaces plugin files, requires no data migration and restores
the old input defects.

## Validation

| Check | Result |
| --- | --- |
| Metadata/package checks, canonical PHP lint, JS syntax, diff whitespace | PASS |
| PHP 5.6.40 / 8.3.35 / 8.4.27 | 89 syntax checks and 2,259 standalone assertions each |
| WordPress 5.6 + PHP 5.6.40 | 425 Chromium tests; 0 failures, skips or retries |
| WordPress 7.1.3 + PHP 8.3.35 | 425 Chromium tests; 0 failures, skips or retries |
| WordPress integration | 12 suites on each runtime |
| Localization / saved formatting | 7,624 / 116 assertions on each runtime |
| Native direct upgrades | 1.3.2, 1.4.0 and 1.4.1 to this ZIP on both runtimes: 6/6 |
| Native rollback / restore | 1.4.2 → official 1.4.1 → candidate 1.4.2 on both runtimes |

The 76 new browser tests cover both controls as shortcodes and native widgets,
all eight format IDs and precision 0–7, LTR/RTL, 320/1064 px, malformed input,
negative/zero values, finite scientific notation, overflow and underflow, swaps,
blur, independent instances, old cached trend markup and recovery. The full suite
also checks existing visual layout, CSS/pixel parity, administrative inputs,
permissions, provider failure, persisted update state and source changes.

Each upgrade seeded synthetic settings, rates, timestamps, custom option data,
both widget types, sidebar assignments and nine saved shortcode pages while the
released version was active. Native `Plugin_Upgrader::bulk_upgrade()` updated it
without manual reactivation or resaving. Raw option bytes, content, metadata and
valid rendering contracts matched before/after. Browser profiles were reused;
both 1064 px and 390 px flows confirmed invalid-input correction and negative
formatting after each update. No new PHP warnings appeared during upgrades.
Retained full-suite logs contain only the intentional missing-POT negative-test
warnings.

Local evidence (ignored, not shipped):

- `dist/amount-input-142-20261026/report.html` and `report.json`.
- `comparison.html` and `visual/`: identical real WordPress markup with frozen
  old JS versus the installed candidate JS; this isolates the JS corrections.
- `stable/red-expanded.json`: six failures before the fix, with screenshots/traces.
- `{stable,minimum}/full/`: complete suite results and installed-file verification.
- `{stable,minimum}/{1.3.2,1.4.0,1.4.1}/`: native upgrade logs, snapshots and browser evidence.
- `{stable,minimum}/rollback/`: verified rollback and candidate restoration.

## Manual smoke and remaining limits

On a disposable site with synthetic rates, open both converter and editable table
instances. Enter `abc`, `12abc` and `1e309`: unavailable values must replace the
old `NaN`, partial calculation or grouped infinity. Enter a valid amount again:
values/trends must recover and other instances must stay unchanged. Check signed
converter input, precision zero, ungrouped formats and scientific notation.

Testing uses Chromium and two real WordPress/PHP combinations. It does not certify
Firefox, Safari, physical devices, multisite, persistent object caches, arbitrary
themes, production caches or live-provider acceptance. Tiny representable values
still round according to selected display precision; that separate policy and
the previously documented in-flight deactivation race are not changed here.
Old cached converter HTML must refresh to receive the two corrected attributes.
Both local candidates carry 1.4.2, so clear candidate-test asset caches when
replacing the older local build. Normal released-version upgrades change the
asset version to 1.4.2.

Local acceptance does not replace PR review and exact-head CI. Merge and plugin
publication are separate steps and are not performed by this correction task.
