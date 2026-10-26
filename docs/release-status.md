# Final release candidate: 1.4.2

Rebuilt October 26, 2026, from `main` after
[PR #39](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/39) merged
as `4555660d7eb50a5c90e76828e02950eb5f60801a`.
[CI on that merge commit](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/runs/40384805712)
passed all five jobs. No known blocker remains in the tested matrix. This is an
unpublished candidate in this workflow; no production site has been updated.

## Candidate identity

| Item | Value |
| --- | --- |
| Header, runtime constant, Stable tag and catalog version | `1.4.2` |
| Source commit on main | `4555660d7eb50a5c90e76828e02950eb5f60801a` |
| Installable Git tree | `338d479c10ca108a0494ee6fb2b5b31878ea1525` |
| Canonical local ZIP | `dist/exchange-rates.1.4.2.zip` |
| Preserved final copy | `dist/release-1.4.2-main-4555660-20261026/exchange-rates.1.4.2.zip` |
| ZIP inventory | 930 files under `exchange-rates/` |
| ZIP SHA-256 | `813028842039f6bb51badb0c22271cd3192bd7d8b9c2b5597d89a79b809f248d` |
| Declared minimums | WordPress 5.6 / PHP 5.6 |
| Tested up to metadata | WordPress 7.1 |

Every ZIP entry matches the source commit; CRC and checksum verification passed.
The rebuild is byte-for-byte identical to the reviewed final ZIP and the package
produced by main CI. Paths under `dist/` identify ignored local artifacts, not
files distributed through this Git repository. The final local manifest and
receipt are in `dist/release-1.4.2-main-4555660-20261026/`.

## Included fixes

- Table and editable-base alignment, narrow-layout badge/caption wrapping, and
  converter title/select spacing from
  [PR #37](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/37),
  prepared as version 1.4.2 in
  [PR #38](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/38).
- Converter and table input validation, signed/scientific number formatting,
  converter zero precision and empty grouping separators. Nonzero values below
  display precision use localized bounds consistently in PHP and JavaScript;
  genuine zero remains `0` and unavailable values remain an en dash.
- Cancellation of already-loaded callbacks and in-flight provider responses
  after deactivation, including guarded rates/settings writes and retry handling.
  The additive cancellation token preserves compatibility without a user-data
  migration. Its storage error is translated in the synchronized POT/PO/MO catalogs.

The latter two groups were merged in PR #39. Existing settings, rates, widgets
and saved content remain compatible. Table-border changes remain deferred, and
the thirty-minute refresh interval is unchanged.

## Verification and limits

| Check | Result |
| --- | --- |
| Main CI at `4555660` | 5/5 jobs passed: PHP 5.6/8.3/8.4, packaging, WordPress integration and Chromium |
| Main Chromium run | 497/497 passed in one uninterrupted run |
| Standalone PHP suites | 2,775 assertions on each of PHP 5.6.40, 8.3.35 and 8.4.27 |
| Local WordPress matrix | WordPress 5.6 / PHP 5.6.40 and WordPress 7.1.3 / PHP 8.3.35 |
| Integration on each local runtime | Smoke plus 12 suites, including 204 cancellation checks |
| Localization / saved formatting on each runtime | 7,639 / 116 assertions passed |
| Local browser acceptance | 497 distinct scenarios per runtime, including focused confirmations |
| Native direct upgrades | 1.3.2, 1.4.0 and 1.4.1 to the corrected 1.4.2 on both runtimes: 6/6 passed |
| Native rollback and restore | 1.4.2 to official 1.4.1 and back on both runtimes: 2/2 passed |
| Final rebuild | `check`, `lint`, `package`, all 930 Git/file comparisons and checksum verification passed |

The native fresh-install, upgrade and rollback evidence used the follow-up ZIP
with SHA-256 `96c8c17f423712b89dcd15a2c29d318ab23f6a9b1e2ae927c3d23f408843f697`.
The final ZIP differs from it only in `readme.txt`, which adds two changelog
entries. Executable code, assets and catalogs are identical. Native upgrades
preserved settings, rates, widget instances and saved content without manual
reactivation or resaving; they were not rerun solely for those readme entries.

The [input acceptance](reviews/amount-input-1.4.2.md) and
[small-value/cancellation review](reviews/remaining-fixes-1.4.2.md) retain the
red-before-fix evidence and details of corrected test fixtures and focused
confirmations. The later main CI result above establishes a complete green
browser run for the final source. Local evidence is retained in
`dist/remaining-fixes-142-20261026/` and `dist/pr39-final-review-20261026/`.

Chromium and synthetic-provider tests do not certify Safari, Firefox, physical
devices, multisite, persistent object caches, arbitrary themes, live providers
or production/CDN behavior. WordPress 7.2 was not revalidated on this payload.
Cancellation tests cover the recorded provider and SQL persistence interleavings,
not every possible WordPress scheduling interleaving. These limits remain part
of the release assessment.

## Build and verify

Preserve an accepted ZIP and its checksum before rebuilding: the packager
replaces the canonical output in `dist/`. Documentation-only commits may change
the repository revision without changing the installable Git tree above.

```sh
set -euo pipefail
git status --short
git rev-parse HEAD:plugin/trunk
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
(cd dist && shasum -a 256 -c exchange-rates.1.4.2.zip.sha256)
```

A matching filename does not establish package identity. Compare the checksum
with the candidate table above. The packager compares every entry against
`plugin/trunk/`; dependency updates and source changes require their own checks.

## Historical evidence and release boundary

The earlier 929-file candidate with SHA-256
`0ad817576d52591936e92fe6ecb538e57de120a2f217ad30654cb5c7ba602192` is preserved at
`dist/archive/1.4.2/0ad817576d52591936e92fe6ecb538e57de120a2f217ad30654cb5c7ba602192/exchange-rates.1.4.2.zip`.
The [October 25 candidate report](releases/1.4.2.md),
[1.4.1 acceptance](releases/1.4.1.md) and
[visual-fix acceptance](reviews/visual-regressions-1.4.1.md) are historical
snapshots. Their package paths, scope and checksums describe those snapshots;
use the candidate identity above for the final 1.4.2 package.

The official 1.4.1 ZIP used as the upgrade/rollback baseline has SHA-256
`843447e33f951b17e69e34c5013b8c023c3e153d1bbc45120dae2b05b7c8f4f7`.
It is distinct from the locally built, unpublished 1.4.1 visual-fix ZIP.
Preserve that official baseline, all QA evidence and unrelated development volumes.

WordPress.org SVN remains read-only. A merged PR, green CI and a verified ZIP do
not publish the plugin or authorize a production-site update. Publication requires
separate explicit authorization. See [packaging and rollback](release.md) and the
future [1.5.0 roadmap](roadmaps/1.5.0.md).
