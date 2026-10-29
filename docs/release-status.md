# Release preparation: 1.5.0

Updated October 29, 2026. The earlier reviewed runtime completed the recorded
local technical acceptance. The current ZIP restores the date labels and order
from published 1.4.2 and has passed focused native browser and LocalWP checks.
Official Plugin Check still has two unresolved external-asset findings. Current
evidence remains separate from the earlier matrix. Nothing in this status
authorizes publication or claims that uncommitted changes passed final CI.

## Current package

- ZIP: `dist/exchange-rates.1.5.0.zip`; 937 files.
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
  match the current ZIP; 58 PHP files pass lint.
- Current branch: `codex/release-1.5.0-final`.
- Local source-payload commit: `f3c21788901a6bedf3fd3a027718294240642e0d`;
  Git tree: `7cf91a44d269faf08aa00f4cad7316069786b2e3`. Its installable source
  matches all 937 files in the reviewed ZIP. Subsequent documentation-only
  commits are separate; remote PR review and final CI remain pending.

### Current ZIP: October 29 verification

| Layer | Result |
| --- | --- |
| Restored date labels in native Safari / Firefox | 27 cases / 513 assertions per browser; 54 / 1,026 total; PASS |
| LocalWP 7.1.3 and pinned 7.2 Beta 2, Safari / Firefox | Four runs of 27 cases / 439 assertions; 108 / 1,756 total; PASS |
| Native LocalWP update and persisted state | Both sites: 937 installed files match; settings, rates, widgets, sidebars and pages unchanged after browser tests |
| Real Escape on LocalWP | Safari and Firefox on both sites; modal closes; focus return separately covered by the runners |
| Official Plugin Check 2.1.0, static and runtime | 9 errors / 22 warnings in each mode; **open findings**, not a clean pass |
| VoiceOver | **Not rerun: explicitly excluded by the maintainer**; no current-ZIP speech pass claimed |

See the [current verification receipt](reviews/1.5.0-final-gates.md),
[LocalWP receipt](reviews/1.5.0-final-gates-localwp.md) and
[Plugin Check dispositions](reviews/1.5.0-plugin-check.md). Five new footer
EscapeOutput reports and two inherited SQL reports have documented analyzer
limitations; all 22 inherited warnings were reviewed. Two inherited external
emoji SVG findings remain unresolved. No findings were suppressed.

Only acceptance tooling changed during this rerun; the ZIP stayed unchanged.
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
| Source commit for this historical matrix | Not recorded at the time; the current payload commit is recorded above |
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

## Remaining release gates

1. Resolve or explicitly document the policy disposition of the two real
   external emoji SVG findings from [Plugin Check](reviews/1.5.0-plugin-check.md).
   Their presence in 1.4.2 is not approval; a plugin fix requires a new ZIP and
   checks appropriate to the changed behavior.
2. Record the maintainer's remaining overall visual acceptance for the final
   reviewed payload, without reopening already accepted individual differences.
3. Complete remote PR review and obtain green CI on
   the final reviewed head. Record that head/tree and reconfirm that its plugin
   payload matches the recorded source-payload commit and accepted ZIP; retain
   current and historical receipts with their respective identities.
4. Obtain separate publication authorization. WordPress.org SVN remains read-only
   in this workflow; neither local tests nor a merge publish the plugin.

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
shasum -a 256 dist/archive/1.5.0/f511fa424a18d61b06f23800992d2ea2cf55a2c41e752cbebbbf241862cd48f4/exchange-rates.1.5.0.zip
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
shasum -a 256 dist/exchange-rates.1.5.0.zip
```

The first checksum must equal the reviewed `f511fa42…48f4` identity. The last
checksum must equal `5a812eda…65c7` for the current recorded source; investigate
any difference before reusing this receipt. Packaging verifies entry-by-entry
source equality, but does not itself complete runtime or visual acceptance.

## Historical release records

The former status page is preserved as the [1.4.2 final candidate receipt](releases/1.4.2-final-candidate.md),
including its source commit, CI and ZIP identity. The earlier [1.4.2 preparation](releases/1.4.2.md),
[1.4.1 acceptance](releases/1.4.1.md) and [1.5.0 checkpoint](reviews/1.5.0-checkpoint.md)
remain historical records. Their results apply to their stated artifacts, not
implicitly to the current working tree.
