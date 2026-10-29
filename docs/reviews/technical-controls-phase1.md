# Explicit zero, converter names and frontend dependencies

This local patch starts from `de3387b48190fc04edab8cc18c3670df2aa939b8`
on `codex/zero-converter-accessibility`. It implements only the agreed first
technical phase. Conditional asset loading, caption/disclaimer changes, source
links, timestamp corrections and server-rendering the converter are deferred.
Version metadata remains 1.4.2 for local validation; this is not a new release.

## Behavior and scope

- An explicit zero entered in either editable table produces zero current and
  previous amounts. Signed zero and scientific zero behave consistently through
  input, blur and click-without-edit. Invalid-input handling, positive amounts,
  rounding, precision and instance isolation are preserved. Empty/whitespace and
  negative table input retain their existing amount-one policy.
- Converter amount, from/to currency and swap controls receive accessible names
  from translated, escaped `data-label-*` metadata. The same renderer serves
  shortcodes and native widgets. Existing Amount translations are reused; three
  new labels are included in the English, Ukrainian and Bulgarian PO/MO catalogs
  and the POT. No visible labels or CSS are added.
- The public script depends on jQuery instead of jQuery UI Autocomplete. The
  existing enqueue timing and site-wide loading behavior are unchanged.

The installable changes are three runtime files and seven catalog files. No
options, stored rates, widget instances or saved shortcode content are migrated.
`Models/Dev.php`, caption links, disclaimer wording and public CSS are unchanged.
All 12 caption/detail blocks in the synthetic browser fixture match the saved
pre-fix HTML, normalizing only WordPress's equivalent emoji-image replacement.

## Verification on October 27, 2026

| Check | Actual result |
| --- | --- |
| Pre-fix focused reproduction | All three targeted tests failed: table zero, accessible names and unused jQuery UI requests |
| WordPress 7.1.3 / PHP 8.3.35 Chromium | 235/235 passed in one final uninterrupted run |
| WordPress 5.6 / PHP 5.6.40 Chromium | 235/235 passed in one final uninterrupted run |
| Converter WordPress integration, each runtime | 63/63 passed: three locales, escaping, widget sanitization, dependency and stored state |
| Catalog coverage and WordPress loading, each runtime | 7,693 assertions passed |
| Existing version/caption WordPress checks, each runtime | 79 assertions passed |
| Synthetic smoke, each runtime | 17 checks passed |
| Standalone suites, PHP 5.6.40 and 8.5.10 | 2,775 assertions passed on each |
| Repository checks | `check`, `lint` and `package` passed; 57 PHP files without syntax errors |
| Package/source/native installation comparison | All 930 entries match; zero differences |
| Native ZIP replacement, rollback and restore on WP 7.1.3 | Passed; settings, rates, widget options and saved pages remain byte-identical |

The browser run includes amount input, named controls, table isolation, converter
box sizing, small values, change percentages and CSS parity. It covers LTR/RTL,
320/1064 px named controls, all number formats/precisions in the existing amount
and small-value matrices, keyboard swaps, recovery, unchanged captions and old
cached converter markup without the new label metadata.

The initial combined browser run exposed test assumptions about the existing
Ukrainian Amount translation and asynchronous WordPress emoji replacement. Those
expectations were corrected before the final green runs. The native harness also
needed its guarded MU fixtures mounted in CLI; the new integration test explicitly
loads its synthetic helper when needed. Initial logs are retained alongside final
results. WordPress.org update probes are blocked by the synthetic HTTP fixture
and produce expected warnings during native ZIP installation; local installation
and the state comparisons succeed.

## Artifact identity

Ignored local evidence is retained under
`dist/technical-controls-phase1-20261027/`, including the red/final logs, the
before/after captions, a zero-amount screenshot and `receipt.json`.

- Preserved accepted baseline ZIP SHA-256:
  `813028842039f6bb51badb0c22271cd3192bd7d8b9c2b5597d89a79b809f248d`.
- Local patched ZIP SHA-256:
  `c1b4865588efdbbcb6f65609849eae74808b24d9e3cc6b42bdb023de67a507f3`.
- Native baseline/updated/rollback/restored public-state SHA-256:
  `dee74336c9bbc18d86522eb36e16fa0fd78fb724cba7c860c817c944bca9b42e`.

## Limits and rollback

Old cached HTML still calculates and swaps with the new script, but the new
accessible names require refreshed HTML. No full VoiceOver/screen-reader,
Safari/Firefox, arbitrary-theme, live-provider or production/CDN acceptance is
claimed. Accessible naming is not full WCAG or Google Search certification.
Remote CI has not been run on this local patch. No push, merge, deployment or
WordPress.org publication is part of this work.

Revert the focused code/catalog patch to restore the previous zero policy and
dependency. No data rollback or settings resave is necessary; native rollback
and restoration preserve the recorded synthetic public state. A future release
still needs coordinated version/changelog metadata and its own release review.
