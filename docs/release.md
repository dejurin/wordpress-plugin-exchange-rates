# Validation and packaging

Read the [development contract](../CONTRIBUTING.md) before preparing changes or a release.

The current final candidate is **1.4.1**. The [release status](release-status.md) is the entry point for its source, package identity and verification references. Older acceptance reports retain their historical versions and checksums.

## Source and dependencies

All installable code is in `plugin/trunk/`. The `vendor/` directory is part of the published plugin and is tracked in Git.
The imported release does not require running Composer. Do not run `composer update` during packaging: the upstream release has no `composer.lock`, so reinstalling may change dependencies.
Dependency upgrades and introducing a lock file require a separate change and compatibility checks.

## Checks before a PR

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
