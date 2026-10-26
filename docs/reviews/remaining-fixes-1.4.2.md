# Small-value display and in-flight cancellation

This focused follow-up builds on `883d0e970e69e634cdc027c688af4ae4a5324e1b`.
It changes the unpublished 1.4.2 candidate. No production site or WordPress.org
publication is part of this work.

## Display contract

A valid nonzero value that rounds to zero now displays a localized bound:
`<0.0001` at precision four, or `>-0.0001` for a negative converter value.
The requested precision is preserved; the formatter does not invent additional
digits. PHP-rendered current/previous values and badges agree with subsequent
JavaScript table and converter updates. Genuine zero displays `0`; unavailable
rates and invalid input still display an en dash. Existing table input handling
for empty, zero and negative amounts is unchanged.

`Currency::for_format()` retains its existing general-purpose rounding contract.
Only displayed rate values use the bound; amount fields and percentage/change
formatters retain their established behavior. The public `get_rate_format()`
zero-amount result intentionally changes from an unavailable marker to `0`.

## Cancellation contract

The plugin captures a per-site cancellation generation when it loads.
Deactivation replaces that generation before clearing cron, backoff and leases.
Already-loaded callbacks and provider completions check the persisted value,
bypassing option caches. Both rate and settings writes include the generation in
their existing atomic SQL conditions, including the first insert. A worker
cancelled during HTTP neither records a provider failure nor schedules a retry.
Reactivation starts new work without admitting the cancelled response.

The additive, non-autoloaded `exchange-rates_refresh_generation` option contains
only an opaque cancellation token. It does not modify user settings, cached
rates, widget instances or content. An absent marker supports upgrades without
reactivation. Older plugin versions ignore it. It remains after deactivation to
fence requests still executing. A failure to persist cancellation stops
deactivation with a localized error rather than silently reporting success.

## Regression coverage

- `tests/small-values.php`: 516 assertions through the public Currency model;
  all eight formats, precision 0–7, signs, real zero, missing rates, previous close
  explicit badge precision, non-finite arithmetic, underflow and negative zero.
- `tests/browser/small-values.spec.js`: 72 scenarios covering both shortcodes and
  native widgets, initial PHP versus interactive JS, badges, negative and invalid
  converter input, recovery, independent instances, symbol positions, LTR/RTL and
  320/1064 px.
- `tests/deactivation-cancellation-wordpress.php`: independent WordPress processes
  exercise normal success/failure, cancellation before work and during HTTP,
  cancellation at SQL update/insert boundaries, reactivation, stale option caches,
  explicit cancellation-storage failure, and exact test-state restoration.
- Existing amount-input expectations now distinguish tiny values from zero.
  Existing WordPress formatting coverage explicitly checks real zero separately
  from unchanged zero-change and unavailable-rate sentinels.

Before the fix, the focused PHP suite failed 320 of 512 assertions, the browser
comparison expected `<0.0001` but received an en dash, and the initial cancellation
suite failed 62 of 201 assertions. These are retained as red evidence.
An additional review regression rejected three arithmetic edge cases before the
PHP result guard was added; all 516 assertions pass with the guard.

Local evidence is retained under `dist/remaining-fixes-142-20261026/`.
The preceding reproduction remains under `dist/open-issues-142-20261026/`.
Final runtime, package and upgrade results are recorded in the new evidence report.

## Final local validation

The final installable ZIP was verified on WordPress 5.6 / PHP 5.6.40 and
WordPress 7.1.3 / PHP 8.3.35. Results on each environment:

- Smoke plus 12 WordPress integration suites passed, including all 204
  cancellation checks.
- 7,639 localization assertions and 116 saved-formatting checks passed.
- 497 distinct Chromium scenarios passed across the full run and focused
  confirmations. This was not one uninterrupted green run: eight newly added
  tests initially assumed an unavailable BTC symbol and unsupported shortcode
  symbol behavior. The corrected fixture uses EUR and checks the existing public
  contracts. All 72 new cases then passed in both environments.
- The minimum environment also encountered a transient parse error while the
  test-only MU fixture was being edited. Its four first-load scenarios passed
  after edits stopped. No installable plugin source changed between these runs.
- Native upgrades from 1.3.2, 1.4.0 and 1.4.1 passed without manual reactivation
  or resaving. Raw settings, rates, widget instances and saved content remained
  unchanged. Rollback to official 1.4.1 and reinstalling this candidate passed.

Standalone suites passed 2,775 assertions on each of PHP 5.6.40, 8.3.35 and
8.4.27. Repository checks, PHP syntax validation and package creation passed.
The side-by-side preview passed 16 control/viewport combinations, a dark-theme
check and manual desktop/mobile screenshot inspection.

The evidence report retains initial failures and their focused confirmations.
The candidate contains 930 files; source, ZIP and installed manifests agree.
SHA-256: `96c8c17f423712b89dcd15a2c29d318ab23f6a9b1e2ae927c3d23f408843f697`.
It is saved as `dist/remaining-fixes-142-20261026/packages/exchange-rates.1.4.2.zip`.
The earlier canonical ZIP is preserved. These are local acceptance results;
remote CI belongs to the exact PR head, and merge and publication remain separate.

Final review found no blocking implementation issue in the combined diff against
`1834771695a97236fc8f5c8d2eb477f5d6e30479`. The review reconciled stale development
and release documentation and added the two missing changelog entries. The only
installable difference from the accepted ZIP above is `readme.txt`; executable
code, assets and catalogs are unchanged. The final review package and its identity
are listed in [release status](../release-status.md).

## Scope and rollback

Rollback replaces plugin files; there is no settings/content migration. Preserve
the cancellation token while old requests drain. Rolling back restores the old
display and cancellation defects. The legacy table-border option is unchanged.

Synthetic-provider tests do not establish live-provider or production/CDN
acceptance. Chromium coverage does not certify Firefox, Safari, physical devices,
multisite, persistent object caches or arbitrary themes. The cancellation tests
cover the recorded provider and persistence interleavings; they are not an
exhaustive proof of every possible WordPress scheduling interleaving.
