# Validation and packaging

Read the [development contract](../CONTRIBUTING.md) before preparing changes or a release.

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

Revert repository changes with `git revert` of the selected commit through a PR, without force-pushing. A GitHub rollback does not change plugins installed on sites or the published WordPress.org release.

For the update-state fix, rollback changes plugin files only: option names and shapes, widget data and page content remain compatible, with no migration. Reverting restores the old state-persistence defect. Before release, verify a saved 1.3.2 fixture through upgrade and rollback as described in [persisted update-state regressions](development.md#persisted-update-state-regressions). This check is separate from live-provider acceptance and publication authorization.
