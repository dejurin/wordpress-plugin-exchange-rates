# Validation and packaging

Read the [development contract](../CONTRIBUTING.md) before preparing changes or a release.

The current development target is **1.5.0**. The [release status](release-status.md) identifies the last fully tested package, later source revisions and remaining release gates. The [1.4.2 candidate receipt](releases/1.4.2-final-candidate.md) is preserved separately. Older acceptance reports retain their historical versions and checksums.

## Source and dependencies

All installable code is in `plugin/trunk/`. The `vendor/` directory is part of the published plugin and is tracked in Git.
The imported release does not require running Composer. Do not run `composer update` during packaging: the upstream release has no `composer.lock`, so reinstalling may change dependencies.
Dependency upgrades and introducing a lock file require a separate change and compatibility checks.

## Checks before a PR

Before packaging an already reviewed version, preserve its ZIP and checksum in
an ignored evidence/archive directory. `package` replaces the canonical ZIP;
never relabel an earlier receipt with the new checksum. For 1.5.0 the completed
matrix belongs to `f511fa42…48f4`. The intermediate `bfc05332…ba91` ZIP changed
only four POT/PO source catalogs, with all runtime files and compiled translations
unchanged; its [catalog refresh receipt](reviews/1.5.0-release-docs.md) remains
historical evidence for that package.

The current ZIP is `e34b2ce0a26200e8d75f54bb25f771dd0599d64bb9c98c64ca913d71fab77a2e`
(1,198 files). The [local emoji receipt](reviews/1.5.0-local-emoji.md) records 259
bundled SVGs, two notices and the table/badge path change. Local source and Docker
installation/state checks passed. Plugin Check confirms both offloaded-asset
findings are resolved. Focused Chromium, real desktop Safari/Firefox/Chrome and
both LocalWP checks passed within the new receipt's scope; new CI and its exact
artifact must still be confirmed on the reviewed head.

The prior `5a812eda…65c7` ZIP (937 files) retained the published Check:/Latest
change: labels and passed [CI run 40843663026](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/runs/40843663026)
on `546b070…`: five jobs and 531 Chromium cases. Artifact `12884285591` was
byte-identical to that prior ZIP. Its [date-label receipt](reviews/1.5.0-date-labels.md)
and [focused acceptance](reviews/1.5.0-final-gates.md) remain separate from the
new emoji payload and earlier full matrix. VoiceOver was explicitly excluded
from the rerun. Do not transfer prior native, rollback, lifecycle, VoiceOver or
CI results to a different ZIP.

```sh
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
```

`check` compares Version in `Install.php`, Stable tag in `readme.txt`, `Plugin::VERSION`, minimum requirements and license metadata. It verifies the exact case of the main filename and rejects symlinks, `.env`, archives and unexpected local files in the plugin tree.
`lint` requires PHP on PATH. Run the runtime smoke test using the [development instructions](development.md).

`package` uses only `plugin/trunk/` and creates `dist/exchange-rates.<version>.zip` plus `.sha256`.
The archive excludes the extra `plugin/trunk/` path level, repository documentation, Docker, tests and CI.
Filenames are sorted; ZIP entry timestamps and permissions are fixed. Rebuilding the same tree with the same Python/zlib produces the same ZIP.
After writing, the script extracts the archive in memory and compares every file against the source. Only generated files in `dist/` are replaced.

GitHub Actions retains the verified ZIP and checksum for 14 days. The Actions artifact is an outer wrapper: install the `exchange-rates.<version>.zip` contained inside it in WordPress.

## WordPress Plugin Check

Run the official [Plugin Check (PCP)](https://wordpress.org/plugins/plugin-check/)
against the exact installed release candidate on a dedicated isolated WordPress
site. This is separate from `scripts/plugin.py check`, which validates repository
metadata and package paths. Install the checker as test tooling on that site;
do not bundle it with Exchange Rates.

Record the checker version, WordPress/PHP versions, candidate ZIP SHA-256 and
enabled check categories. Include the Plugin repo category and the available
static/runtime checks; retain errors and warnings in the original report.
By default WP-CLI runs only static checks. After installing the pinned checker,
run from the test site's WordPress root with its runtime bootstrap and the
existing-plugin update mode documented in the [official CLI reference](https://github.com/WordPress/plugin-check/blob/trunk/docs/CLI.md):

```sh
wp plugin check ./wp-content/plugins/exchange-rates \
  --require=./wp-content/plugins/plugin-check/cli.php \
  --mode=update --format=json \
  --include-low-severity-errors --include-low-severity-warnings
```

Preserve raw JSON, stderr and exit status under the ignored acceptance evidence
directory. Record default exclusions (including `vendor`), enabled categories
and any unavailable or skipped checks; do not claim a scan of all packaged files.
Do not enable AI analysis or add result/file suppression flags for this run.
Resolve errors
or document evidence-backed false positives; review warnings individually and
record fixes or accepted/deferred findings. Do not hide findings using blanket
exclusions or report an unrun check as passed. Any resulting plugin changes need
a new package identity and checks appropriate to the affected behavior.

PCP results supplement manual review and browser/accessibility acceptance; they
do not establish WCAG/EAA certification or authorize publication. The current
release's execution status is tracked in [release status](release-status.md).

For the prior `5a812eda…65c7` ZIP, [Plugin Check 2.1.0 was executed](reviews/1.5.0-plugin-check.md)
in both static and runtime modes: each reported 9 errors and 22 warnings,
compared with 4 errors and 22 warnings in the released 1.4.2 baseline. Five new
footer escaping reports and two inherited SQL reports have documented analyzer
limitations; 22 inherited warnings have recorded dispositions. Two real external
emoji SVG findings required a fix. Static and runtime checks on the new local
SVG ZIP each report seven errors and 22 warnings: both OffloadedContent errors
are absent, with the remaining groups/counts unchanged. This resolves the two
real findings but is not a clean raw checker result. No suppressions were added;
retain both package identities and raw reports with their manual dispositions.

## Imported 1.3.2 baseline

```sh
python3 scripts/plugin.py baseline
```

This command compares the entire tree against the immutable [official 1.3.2 manifest](baseline-1.3.2.json), including extra or missing files.
After intentional plugin changes, it is expected to report differences; do not rewrite the historical manifest to make the check pass.
Therefore, `baseline` verifies import provenance and is not a mandatory CI test for future versions.

The ZIP built here may have a different SHA-256 from the WordPress.org ZIP because of metadata and compression. File inventory and individual SHA-256 hashes establish code equality.

## Release and rollback

The main branch represents the verified project state. Changes go through a branch, PR, CI and checks appropriate to their scope under the development contract; runtime changes require smoke validation.
For the next release, update the main file's version, runtime constant, Stable tag and changelog together; preserve the plugin identity, text domain and installation paths.
Do not copy SVN `tags/` directories into Git; Git retains the history.

**The current workflow does not publish anything to WordPress.org.** SVN is used only for read-only inspection and comparison.
Publishing the next version is a separate, explicitly authorized operation after ZIP verification. This repository has no automatic SVN deployment or corresponding secrets.

For the initial 1.4.0 candidate and known issues, see the [historical acceptance report](releases/1.4.0.md). Later release work is documented in the [CSS cleanup acceptance](releases/1.4.0-css-audit.md), including the merged RTL/SVG reference and its separate candidate identity. Match the evidence to the exact payload being reviewed.

### Updating a site with caches

1. Back up the site's database and files and verify the package identity on a staging copy before replacing the installed plugin. Preserve settings, saved shortcodes and widget instances.
2. Install the reviewed plugin ZIP through WordPress's upload/replace flow. Do not install the repository ZIP or recreate widgets as an upgrade workaround.
3. Purge HTML/page caches and any CDN HTML caches, then regenerate cached optimizer/minifier bundles. A version change updates asset URLs in fresh HTML; it cannot rewrite older HTML retained by another cache.
4. Reload an existing page and the plugin settings screen in a previously used browser. Verify that the new versioned JavaScript is fetched and that changing an amount affects only its own table. Purging only the browser cache does not establish that stale HTML has been removed.
5. Check the converter, table, badge, saved widgets, settings round-trip, manual update and PHP/browser logs. Keep the previous verified ZIP and backup available for rollback.

The isolated 1.4.0 acceptance reproduced the old table behavior when a page cache deliberately retained 1.3.2 HTML. Purging that HTML cache restored the corrected behavior in the same warm browser. Actual CDN and optimizer policies vary; test the site's configuration rather than assuming query-string versioning handles every cache layer.

### Isolated acceptance environments

Always pass an explicit `docker compose -p <dedicated-test-project>` for release tests. An env-file may define `COMPOSE_PROJECT_NAME` and override a Compose file's `name`. Before starting a new environment, inspect the resolved project, port, mounts and named volumes without printing credentials. Never reuse ordinary development site/database volumes for release fixtures. Preserve full debug logs and sanitized receipts before deleting only the disposable test resources.

### Rollback

For the historical `f511fa42…48f4` 1.5.0 package, the isolated native matrix verified direct
upgrade and rollback from 1.3.2, 1.4.0, 1.4.1 and 1.4.2 on both the minimum and
current runtimes. LocalWP additionally verified 1.4.2 → 1.5.0 → 1.4.2 → 1.5.0
on WordPress 7.1.3 and pinned 7.2 Beta 2 without database reseeding or migration.
Keep the verified released 1.4.2 ZIP and the site's normal backup for a future,
separately authorized update. The [integration](reviews/1.5.0-table-integration.md)
and [final recheck](reviews/1.5.0-final-recheck.md) record the exact artifact and
limits; later package changes must retain their own evidence.

Revert repository changes with `git revert` of the selected commit through a PR, without force-pushing. A GitHub rollback does not change plugins installed on sites or the published WordPress.org release.

When restoring a site backup, verify file ownership and permissions for the destination web server as well as database contents and file hashes. Archive user-name mapping can differ between container images even when the restored bytes match.

For the update-state fix, rollback changes plugin files only: option names and shapes, widget data and page content remain compatible, with no migration. Reverting restores the old state-persistence defect. Before release, verify a saved 1.3.2 fixture through upgrade and rollback as described in [persisted update-state regressions](development.md#persisted-update-state-regressions). This check is separate from live-provider acceptance and publication authorization.

### Rolling back 1.4.1 to 1.4.0

Deactivate 1.4.1 before replacing its files with the verified 1.4.0 ZIP, then
reactivate 1.4.0 and verify one default hourly `exchange-rates_data_update` event.
Keep user settings, saved rates, widgets and posts. File replacement alone is not
a guaranteed schedule rollback: 1.4.0 accepts an existing event without checking
its recurrence. Let in-flight background requests finish before deactivation;
the existing worker/deactivation race can recreate a single retry after cleanup.
Verify both hooks after the operation. This release does not fix that lifecycle
race or authorize publication. Test upgrade and rollback on a disposable fixture
before using this procedure on any real site.

For the frozen 1.4.1 ZIP, WordPress 7.2 Beta/minimum-runtime evidence and native upgrade/rollback matrix, see the [1.4.1 release acceptance](releases/1.4.1.md).
