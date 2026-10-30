# Release preparation: 1.5.0

## Current merged candidate: package frozen, publication deferred

The [current release handoff](releases/1.5.0-handoff.md) is the publication
reference. [PR #42](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/42)
is merged into `main` at `0cd1eb3075ca0013d7266b35b4f5dc5dce4157b1`, with plugin
subtree `540f9e6f69e1a29dfc24e273b5643cea70e0a57b`.

- ZIP: `dist/exchange-rates.1.5.0.zip`, preserved as
  `dist/pr-42-merge-20261030/ci-41090544732/artifact/exchange-rates.1.5.0.zip`;
  1,198 files, 1,761,283 bytes.
- SHA-256: `f8da0df6f0f14339bf8a0bd9208e9128a2dd9e4b31fe7eff7c10af929ce37ad1`.
- Exactly five packaged files changed from `8e7b629c…c6dac`: readme, POT and
  three PO files. All other 1,193 files, including PHP/JS/CSS/MO, are identical.
- Readme now documents zero and positive input and rejection of comma/space
  grouping. All 44 stale source coordinates are corrected; keys, translations,
  headers and order remain unchanged. All three compiled MO files reproduce.
- Metadata, 58 PHP syntax checks, deterministic packaging and 8,024 standalone
  localization assertions passed. The installed ZIP completed native
  update/rollback/restore with state preserved and all 1,198 file hashes matching
  each selected ZIP;
  fresh WordPress gettext passed 8,038 assertions.
- Official Plugin Check static/runtime each retain seven errors and 22 warnings,
  with no added or removed finding. This remains a reviewed-findings result,
  not a clean raw checker pass.
- [Main CI 41090544732](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/runs/41090544732)
  passed all five jobs and 556/556 Chromium tests on that exact merge commit;
  all five job checkout SHAs were verified. Downloaded artifact `12994530692`
  contains the byte-identical inner ZIP, with all 1,198 file hashes matching.
  CI PHP syntax/unit runtimes were 5.6.40, 8.3.36 and 8.4.27; WordPress gettext
  passed 8,038 assertions on PHP 8.3.35. Post-browser state, HTTP/assets and CI
  cleanup passed.
- Full native/LocalWP acceptance remains tied to the preceding `8e7b629c…c6dac`
  ZIP. Its runtime and compiled MO files are byte-identical to this payload;
  fresh current-ZIP checks are retained in the
  [catalog/readme receipt](reviews/1.5.0-catalog-readme-ci.md). The handoff records
  these distinct scopes and the original CI identities.
- Documentation-only handoff updates preserve the frozen payload; main CI
  remains attributed to `0cd1eb3`, rather than a later documentation commit.
- Merge is complete. WordPress.org publication, release/tag creation and
  customer-site updates require separate authorization; SVN remains read-only.

## Previous working candidate: October 30 footer refinement

The maintainer approved date-only visible captions, a reference-only disclaimer
and placing `[WP_ExchangeRates]` below the modal heading. The
[focused receipt](reviews/1.5.0-footer-refinement.md) records the new package,
regressions and in-place update of the existing side-by-side preview.

- Previous ZIP: `dist/marker-heading-20261030/candidate.zip`; 1,198 files.
- SHA-256: `8e7b629cd6c85134722e27afe8d3f956f2d024c0d7dcb5b8cfcb6102d41c6dac`.
- The unchanged released 1.4.2 remains on the left at port 19345; the updated
  candidate remains on the right at port 19344, using the existing comparison
  URL. No production update or publication occurred.
- The [full local regression](reviews/1.5.0-full-regression-20261030.md) now
  supersedes the focused checkpoint: 556/556 Chromium, 190/190 native
  Safari/Firefox technical cases, 264 LocalWP cases / 3,872 assertions,
  16 WordPress integration stages, eight direct historical update/rollback
  round trips and two fresh installations passed. Saved state, core/config,
  cron and exact installed hashes were verified after the browser runs.
- Backlinks: 2,064 differential combinations and 258 real WordPress source-URL
  checks passed. Fresh POT/loading checks passed 8,038 assertions; all three
  MO files reproduce. Syntax/unit checks include PHP 5.6, 8.3 and 8.4 locally.
- All three browsers retain four PASS/eight DESIGN REVIEW composition labels,
  with no unexpected difference or functional failure. Previously accepted
  border/numeric-axis decisions and raw human-review status remain visible.
- Open nonruntime findings: inaccurate packaged-readme input wording and 44
  stale POT/PO source-line references. Plugin Check static/runtime each retain
  seven reviewed errors and 22 warnings, with zero new semantic findings.
- Current-payload GitHub CI was not run; the older CI below belongs to another
  ZIP. VoiceOver remains excluded. The plugin/test source and ZIP were not
  changed during this run. No publication, push or merge occurred.

## Previous fully verified package: October 29

Updated October 29, 2026. The maintainer accepted the numeric-axis correction.
Final verification uses one frozen ZIP across the three release phases and
official Plugin Check. Native package round trips, general Safari/Firefox,
phase-two compositions and LocalWP Chromium/Safari/Firefox/lifecycle checks
completed with no unexplained regression. Final source/CI/artifact comparison
and independent review passed. The maintainer authorized documentation and
GitHub integration; publication is deferred to a separate explicit instruction.

### Final candidate verification

- ZIP: `dist/rc-1.5.0-20261029/candidate.zip`, also preserved as
  `dist/numeric-axis-20261029/candidate.zip`; 1,198 files, 1,761,628 bytes.
- SHA-256: `c4f8f597c89c330269392ca3650cb9f0cbbfcd789a993a1a7959375d8b9e26a8`.
- Archive: `dist/archive/1.5.0/c4f8f597c89c330269392ca3650cb9f0cbbfcd789a993a1a7959375d8b9e26a8/exchange-rates.1.5.0.zip`.
- Compared with `e34b2ce0…7a2e`: only `src/Service/CurrencyTable.php` and
  `assets/css/public/style.css` changed; 1,196 files unchanged, none added or
  removed. No API, timestamp, saved-data, JavaScript, catalog or emoji change.
- A separate trend lane aligns Price headings, quoted values and static/editable
  base amounts. Tables with no trend retain their existing content inset.
- Metadata/package checks, 58 PHP lint checks, 9 PHP unit suites and 8 Python
  tooling tests passed. Chromium passed 217 cases: 10 numeric-axis, 182 adjacent
  cases and 25 published-CSS parity cases. The new regression first failed with
  an 18 px misalignment; green checks require at most 1 px.
- Native Safari/Firefox RTL desktop before/after review passed at 100% zoom.
  The successful Docker native-installer retry has 1,198/1,198 hashes equal to
  the ZIP, with persisted state unchanged. The initial failure was caused by
  ownership of two prototype files; both attempts remain in the evidence.
- The [numeric-axis receipt](reviews/1.5.0-numeric-axis.md) retains the focused
  correction checks. The maintainer accepted this difference and retained
  right alignment in RTL; see [visual decisions](reviews/1.5.0-visual-decisions.md).
- Fresh final phase-one matrix: eight native upgrade/rollback rows passed from
  1.3.2, 1.4.0, 1.4.1 and 1.4.2 on WordPress 5.6 / PHP 5.6.40 and WordPress
  7.1.3 / PHP 8.3.31. Saved state and all 1,198 final installed files match.
  Two fresh-candidate installations and lifecycle checks also passed; all ten
  final inventories match the ZIP, with 1,100 server-render assertions and
  exactly one recurring/no retry event each. Matrix containers are stopped,
  with volumes preserved.
- Native Safari 27.0.1 and Firefox 159.0: 86 cases each, 172 total, zero failures
  on the installed ZIP, with baseline/candidate identity and state preserved.
- Both LocalWP sites (7.1.3 and pinned 7.2 Beta 2) completed
  1.4.2 → 1.5.0 → 1.4.2 → 1.5.0 without reseeding. Chromium passed 156 cases /
  2,116 assertions; initial values and raw saved configuration match the released
  baseline. Lifecycle/cron passed separately, with only the expected refresh
  timestamp change. Both sites end with 1,198 matching files, unchanged core and
  ordinary configuration, one recurring event and no retry event.
- Fresh Safari/Firefox on both LocalWP sites passed 108 cases / 1,756
  assertions. The LocalWP total is 264 cases / 3,872 assertions; post-browser
  checks confirm the same saved state, core/configuration and exact ZIP files,
  with one recurring/no retry event. Earlier native reports are preserved.
- Phase two: each of Chromium, Safari and Firefox completed six frozen
  existing-site compositions at 1280/390 px: four PASS and eight DESIGN REVIEW,
  with no unexpected differences or functional failures. Review labels retain
  only the accepted converter borders and numeric-axis differences. The shared
  classifier has eight tests; original raw differences and earlier results are
  preserved. These are local reproductions, not customer-site updates.
- Plugin Check 2.1.0 static/runtime on this exact ZIP each report seven errors
  and 22 warnings, identical to the preceding emoji ZIP: zero new findings.
  Raw reports, default exclusions and reviewed dispositions remain visible.
  This is not a clean raw checker pass.
- Runtime-source commit: `78d7a47fa962e955230044d36588f541b5225565`;
  repository tree: `7cd60cba7e941ea27f68a65ed63ef145e474c64c`;
  plugin subtree: `e8ec51ecf2ccdc47e810d2561ff68925eb55f44c`.
- [CI run 40872883274](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/runs/40872883274)
  passed five jobs and 553/553 Chromium tests, with no failed, skipped or flaky
  tests. Post-browser state, HTTP/assets and CI cleanup passed. Artifact
  `12895534953` is byte-identical to the frozen ZIP, with all 1,198 files matching.
- Independent final review found no actionable P1/P2 issue; there were no open
  PR review threads. The [final candidate receipt](reviews/1.5.0-rc.md) and
  `dist/rc-1.5.0-20261029/` preserve these results. The
  [preparation record](releases/1.5.0-preparation.md) separates the tested runtime
  source from subsequent documentation-only commits. [PR #41](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/41)
  records GitHub integration and its current checks; publication remains separate.
  VoiceOver is explicitly excluded.

## Previous verified package: local emoji SVGs

- Preserved ZIP: `dist/emoji-local-20261029/candidate.zip`;
  1,198 files, 1,761,425 bytes. The canonical filename now identifies the newer
  candidate above.
- SHA-256: `e34b2ce0a26200e8d75f54bb25f771dd0599d64bb9c98c64ca913d71fab77a2e`.
- Archive: `dist/archive/1.5.0/e34b2ce0a26200e8d75f54bb25f771dd0599d64bb9c98c64ca913d71fab77a2e/exchange-rates.1.5.0.zip`.
- Compared with `5a812eda…65c7`: 261 added files (259 exact SVGs and two notices),
  three changed files (readme and table/badge renderers), 934 unchanged, none
  removed. No CSS, JavaScript, modal, catalog or stored-data changes.
- Local checks: metadata/package verification, 58 PHP lint checks, 9/9 unit
  suites, 8/8 Python tooling tests and 259 upstream SVG byte comparisons passed.
  The regression catalog contains 39 registered groups with no pending entries;
  registration is not a claim that every group ran in this revision.
- Docker stand `127.0.0.1:19335`: all 1,198 installed hashes match; persisted
  state and file hashes remain unchanged after browser checks. The installed
  Plugin Check target also matches all 1,198 candidate files.
- Plugin Check 2.1.0 static/runtime: each reports seven errors and 22 warnings,
  down from nine errors and 22 warnings. Both OffloadedContent findings are
  absent; remaining finding groups/counts are unchanged. This is not a clean
  raw checker pass. Evidence: `dist/emoji-local-20261029/pcp-summary.json`.
- Focused Chromium: 92 existing cases passed; four new emoji/pixel cases passed
  after correcting their test selector (20 composition comparisons, no pixel
  differences). The initial test-only failures remain in the evidence.
- Real Safari, Firefox and Chrome: LTR and RTL desktop checks at 100% zoom,
  each decoding all 14 same-origin flag images. Narrow 320/1064 px coverage is
  Chromium evidence; no native mobile viewport test is claimed.
- Both LocalWP sites: native update, 1,198/1,198 files equal the ZIP, saved
  state/core/config unchanged, one recurring event each. Headless Chromium
  passed eight emoji cases (40 composition comparisons) and eight saved
  converter/modal smoke cases across the two sites; no reseeding or rollback
  rerun. These are new scoped results, not the previous native 108-case matrix.
- [CI run 40852210023](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/runs/40852210023):
  PASS, five jobs and 535 Chromium cases on source head
  `7b5d28ef5fcae135642a11d86d952c36db95043a`.
- Artifact `12888195025` is byte-identical to this previous ZIP, with all 1,198
  files matching. Evidence: `dist/emoji-local-20261029/ci-40852210023/summary.json`
  and `comparison.json`; see the [local emoji receipt](reviews/1.5.0-local-emoji.md).
  These results do not verify the subsequent numeric-axis payload.

## Previous verified package: before local emoji SVGs

- Historical ZIP: 937 files; use the preserved archive below rather than the
  mutable canonical filename.
- SHA-256: `5a812edadb81e9947e6eeefd0efcdb07496f3369d5a7e56fccf5f46d66e365c7`.
- Preserved copy: `dist/archive/1.5.0/5a812edadb81e9947e6eeefd0efcdb07496f3369d5a7e56fccf5f46d66e365c7/exchange-rates.1.5.0.zip`.
- Compared with the intermediate `bfc05332…ba91` package: eight changed files
  (`src/Service/Footer.php`, POT and three PO/MO pairs), no added/removed files;
  the remaining 929 files are byte-identical.
- Restores **Check:** followed by **Latest change:**, including their existing
  1.4.2 English, Ukrainian and Bulgarian translations. Timestamp values and
  explanatory titles retain their existing meaning; no API or storage change.
- The [date-label restoration receipt](reviews/1.5.0-date-labels.md) records the
  exact change and focused footer, gettext and installed-ZIP Chromium checks.
  Results: 1,651 footer assertions, 8,038 WordPress localization assertions and
  three modal scenarios at 320/390/1064 px passed. All 937 installed file hashes
  match that ZIP; 58 PHP files pass lint.
- Branch: `codex/release-1.5.0-final`.
- Source-payload commit: `f3c21788901a6bedf3fd3a027718294240642e0d`;
  Git tree: `7cf91a44d269faf08aa00f4cad7316069786b2e3`. Its installable source
  matches all 937 files in the reviewed ZIP. Subsequent test/documentation
  commits are separate.
- [CI run 40843663026](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/runs/40843663026):
  **PASS: 5/5 jobs, 531 Chromium cases, zero failures.** Reviewed head
  `546b070d17a7965c439d427f9df482912270cd76`, tree
  `a4384b96ccd0be6ace8dd62a76e3c92613343287`.
- Actions artifact `12884285591`: inner ZIP bytes and all 937 file hashes equal
  `5a812eda…65c7`. Evidence:
  `dist/final-gates-20261029/ci-40843663026/comparison.json` and `summary.json`.
- [CI run 40840217684](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/runs/40840217684):
  **FAILED.** Package and PHP 5.6/8.3/8.4 syntax/unit jobs succeeded, as did
  WordPress integration steps. Chromium reported 507 passed and 24 failed;
  all failures were CSS-parity cases. The test-only correction passed locally:
  25 release-parity cases and 36 current layout/history/modal cases. The plugin
  ZIP was unchanged by that test correction. This first failed run is historical;
  the subsequent successful run is recorded above.
- Downloaded Actions artifact `12882018816`: its inner plugin ZIP has the exact
  reviewed SHA-256 above and is byte-identical, with 937/937 file hashes matching.
  Evidence: `dist/final-gates-20261029/ci-40840217684/comparison.json`.

### Previous `5a812…65c7` ZIP: October 29 verification

| Layer | Result |
| --- | --- |
| Completed CI on PR head `546b070…` | PASS: 5/5 jobs, 531 Chromium cases, zero failures |
| First CI on PR head `645ae79…` | Historical FAIL: four jobs passed; Chromium 507 passed / 24 CSS-parity failures, corrected before the successful rerun |
| CI package comparison | Artifact `12884285591`: inner ZIP bytes and 937 file hashes match this previous candidate |
| Restored date labels in native Safari / Firefox | 27 cases / 513 assertions per browser; 54 / 1,026 total; PASS |
| LocalWP 7.1.3 and pinned 7.2 Beta 2, Safari / Firefox | Four runs of 27 cases / 439 assertions; 108 / 1,756 total; PASS |
| Native LocalWP update and persisted state | Both sites: 937 installed files match; settings, rates, widgets, sidebars and pages unchanged after browser tests |
| Real Escape on LocalWP | Safari and Firefox on both sites; modal closes; focus return separately covered by the runners |
| Official Plugin Check 2.1.0, static and runtime | 9 errors / 22 warnings in each mode; **open findings**, not a clean pass |
| VoiceOver | **Not rerun: explicitly excluded by the maintainer**; no current-ZIP speech pass claimed |

See the [previous verification receipt](reviews/1.5.0-final-gates.md),
[LocalWP receipt](reviews/1.5.0-final-gates-localwp.md) and
[Plugin Check dispositions](reviews/1.5.0-plugin-check.md). Five new footer
EscapeOutput reports and two inherited SQL reports have documented analyzer
limitations; all 22 inherited warnings were reviewed. Two inherited external
emoji SVG findings were unresolved in that ZIP and were fixed in the later
`e34b2ce0…7a2e` revision. No findings were suppressed.

Only acceptance tooling changed during that rerun; the ZIP stayed unchanged.
The permanent unit runner passed 8/8 suites, including the asset-revision
regression. The earlier full matrix below was not repeated: historical
upgrade/rollback, lifecycle and VoiceOver results retain their original SHA.

## Intermediate catalog-only package

The previous `bfc05332b8ab13865a0847cebe0bfa02395ccba3cb0faa2d05df69ee7846ba91`
ZIP (937 files) is preserved at
`dist/archive/1.5.0/bfc05332b8ab13865a0847cebe0bfa02395ccba3cb0faa2d05df69ee7846ba91/exchange-rates.1.5.0.zip`.
Relative to the full-matrix `f511fa42…48f4` package, it changed only four POT/PO
source catalogs; the other 933 files, including compiled translations, were
byte-identical. All 530 messages, translations, contexts, plural forms and flags
were unchanged. Its metadata/inventory, 58 PHP lint checks, package/source
equality, gettext compilation and 8,009 localization assertions passed.
The [catalog refresh receipt](reviews/1.5.0-release-docs.md) is historical evidence
for that intermediate package, not the current labels or compiled catalogs.

## Reviewed package identity

The following identity belongs to the completed table-integration and final
recheck receipts. Keep it separate from any later build carrying the same
version number.

| Item | Value |
| --- | --- |
| Header, runtime constant, Stable tag and catalog version | `1.5.0` |
| Reviewed ZIP SHA-256 | `f511fa424a18d61b06f23800992d2ea2cf55a2c41e752cbebbbf241862cd48f4` |
| Original canonical output | `dist/exchange-rates.1.5.0.zip` (may be replaced by later packaging) |
| Preserved verified copy | `dist/archive/1.5.0/f511fa424a18d61b06f23800992d2ea2cf55a2c41e752cbebbbf241862cd48f4/exchange-rates.1.5.0.zip` |
| ZIP inventory | 937 files under `exchange-rates/` |
| Exact-package receipts | [Table integration](reviews/1.5.0-table-integration.md), [final recheck](reviews/1.5.0-final-recheck.md) |
| Declared minimums | WordPress 5.6 / PHP 5.6 |
| Tested up to metadata | WordPress 7.1 |
| Additional pinned local core | WordPress `7.2-beta2`; not final 7.2 compatibility |
| Original acceptance branch | `codex/zero-converter-accessibility` |
| Source commit for this historical matrix | Not recorded at the time; later payload commits are recorded separately above |
| PR / CI for this historical matrix | Not recorded |

At completion of the recorded recheck, every source file and all installed
candidate files on the Docker and two LocalWP stands matched the reviewed ZIP.
That evidence does not assert equality with later edits. The branch's starting
HEAD `de3387b48190fc04edab8cc18c3670df2aa939b8` is the earlier documentation merge,
not a commit containing the 1.5.0 payload. Ignored `dist/` paths identify local
artifacts, not files shipped in the repository or plugin.

## Date-label decision

The maintainer clarified that the original labels mean the published **1.4.2**
wording. The current dialog therefore restores **Check:** (`put_time`) first and
**Latest change:** (`local_time`) second, replacing the interim Rate date/Saved
presentation. The verified 1.4.2 baseline ZIP has SHA-256
`813028842039f6bb51badb0c22271cd3192bd7d8b9c2b5597d89a79b809f248d`.

`put_time` still means when the data service saved an entry; `local_time` still
means source data time. The existing explanatory titles preserve these meanings.
These fields do not establish a separate successful source-verification time or
the last numerical rate change. Restoring historical wording does not add those
claims, remap timestamps or change the API.

## Included 1.5.0 changes

- Compact attribution footers for tables, converters and badges, with source
  abbreviations and the agreed service links.
- Shared data-details dialog with source, distinct data timestamps, disclaimer,
  plugin identity/version and API attribution; hidden fallback when dialog
  support is missing or fails.
- Visitor-local footer dates with UTC retained in details.
- Accessible control names, keyboard focus and modal focus return, live result
  and error announcements, badge-list text and Price-cell trend descriptions.
- Explicit zero handling and valid input recovery when previous JPY data is
  unavailable; no fabricated historical zero.
- Accepted narrow-table layout and accessible scrolling for oversized values,
  preserving full accessible headings and custom headings.
- Removal of unused jQuery UI Autocomplete, synchronized localization catalogs,
  and reusable regression, package-upgrade and visual-acceptance tooling.
- Local copies of the exact emoji flag SVGs, with their license and attribution,
  replacing the table and badge's direct offsite image URLs.
- Consistent numeric alignment of Price headings, quote values and base amounts,
  including unchanged/missing-history rows and zero/invalid input recovery.

Saved option schemas, widget IDs and shortcode contracts remain compatible.
There is no required data migration or widget recreation. The changelog and
Upgrade Notice are present in `plugin/trunk/readme.txt`.

## Acceptance recorded for the reviewed SHA

| Layer | Result | Receipt |
| --- | --- | --- |
| Repository metadata check, lint and packaging | 937 files; 58 PHP files lint clean | [Integration](reviews/1.5.0-table-integration.md) |
| PHP unit suites / gettext catalogs | 7/7 suites; 8,023 localization assertions | [Integration](reviews/1.5.0-table-integration.md) |
| Affected Chromium regressions | 160 distinct scenarios passed | [Integration](reviews/1.5.0-table-integration.md) |
| Installed narrow-table matrix | 63/63 in each of Chromium, native Safari and Firefox | [Integration](reviews/1.5.0-table-integration.md) |
| Native general widget/modal matrix | Safari 86/86; Firefox 86/86 | [Integration](reviews/1.5.0-table-integration.md) |
| Native historical upgrades and rollbacks | 8/8 from 1.3.2/1.4.0/1.4.1/1.4.2 on WP 5.6/PHP 5.6 and WP 7.1.3/PHP 8.3 | [Integration](reviews/1.5.0-table-integration.md) |
| Fresh native installation | 2/2 on minimum/current runtimes | [Integration](reviews/1.5.0-table-integration.md) |
| Public-site compositions | 12 in each of Chromium, Safari and Firefox; zero unexpected differences | [Final recheck](reviews/1.5.0-final-recheck.md) |
| LocalWP stable and pinned beta | 264 cases / 3,648 assertions; upgrade, rollback, widgets/modal, state, activation and cron | [Final recheck](reviews/1.5.0-final-recheck.md) |
| Native VoiceOver in Safari | Real speech for all three widgets, input/error/recovery, modal content and Escape/focus return | [Final recheck](reviews/1.5.0-final-recheck.md) |
| Final source/installed/ZIP identity | All 937 files agreed on the reviewed stands | [Final recheck](reviews/1.5.0-final-recheck.md) |

Phase-two reports retain four DESIGN REVIEW cases per browser for converter
borders already accepted by the maintainer. The accepted beta emoji difference
and narrow-table proposal are documented in [visual decisions](reviews/1.5.0-visual-decisions.md).
These decisions do not imply blanket visual approval of every remaining
composition or a different package.

The beta core is pinned to official WordPress 7.2 Beta 2, SHA-256
`b1e1257fb208b6a399a457182f20d96726f2812ff003eadeb60bb9232f623e81`;
all 3,516 archive files outside `wp-content` matched. Keep `Tested up to: 7.1`
until an eligible RC exists. Additional accessibility refinements, including
redundant percentage speech in the optional Changes column, remain assigned to
[1.5.6](roadmaps/1.5.6.md); Gutenberg remains assigned to [1.6.0](roadmaps/1.6.0.md).
No WCAG/EAA certification is claimed.

## Previous preparation checkpoint: October 29

This checkpoint describes `c4f8f597…26a8`. The current corrected candidate,
its new CI and separate integration boundary are listed at the top of this page.

The source identity, runtime CI, matching artifact and final independent review
are recorded above. Documentation-only preparation does not change the plugin
payload. GitHub integration is tracked in [PR #41](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/41);
its final head must retain the reviewed plugin subtree and pass its required
checks. These are integration checks, not missing runtime acceptance results.

That ZIP's phase-one, phase-two and LocalWP rows are complete. Do not
transfer earlier ZIP results or reopen the already accepted numeric alignment,
converter borders or emoji differences. Official Plugin Check has been executed
on this ZIP with the unchanged documented findings listed above.

At that checkpoint, the remaining release action was a separate publication
instruction. Until separately authorized,
WordPress.org SVN remains read-only; no release tag, GitHub Release, production
update or WordPress.org write is part of preparation. The
[publication handoff](releases/1.5.0-preparation.md#publication-handoff)
records the exact package, post-publication checks and rollback references.

VoiceOver is outside the explicitly authorized final rerun. Its omission is
recorded as a coverage limit, not a successful current-package test.

These checks used isolated synthetic environments. They do not prove live API
availability, all CDN/optimizer policies, arbitrary third-party themes/plugins,
multisite or installed customer-site health.

## Preserve, rebuild and verify

The packager replaces the canonical filename. Keep the preserved reviewed copy
and its recorded checksum before running it. Use [packaging and rollback](release.md)
and the [three-phase acceptance contract](acceptance/release-gates.md); do not
reuse old receipt hashes after source changes.

```sh
set -euo pipefail
shasum -a 256 dist/archive/1.5.0/f8da0df6f0f14339bf8a0bd9208e9128a2dd9e4b31fe7eff7c10af929ce37ad1/exchange-rates.1.5.0.zip
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
shasum -a 256 dist/exchange-rates.1.5.0.zip
```

Both checksums must equal `f8da0df6…37ad1` for the current recorded source;
investigate any difference before reusing this receipt. Earlier archived ZIPs
and their historical checksums remain preserved. Packaging verifies entry-by-entry
source equality, but does not itself complete runtime or visual acceptance.

## Historical release records

The former status page is preserved as the [1.4.2 final candidate receipt](releases/1.4.2-final-candidate.md),
including its source commit, CI and ZIP identity. The earlier [1.4.2 preparation](releases/1.4.2.md),
[1.4.1 acceptance](releases/1.4.1.md) and [1.5.0 checkpoint](reviews/1.5.0-checkpoint.md)
remain historical records. Their results apply to their stated artifacts, not
implicitly to the current working tree.
