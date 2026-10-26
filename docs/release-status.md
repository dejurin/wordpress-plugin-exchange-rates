# Release preparation: 1.4.2

## Pending input and cancellation fixes (October 26)

The numeric-input audit found defects in the candidate below. The focused
`codex/amount-input-validation` patch fixes converter and table validation,
negative/scientific number formatting, converter precision/separator metadata,
small nonzero value display, and cancellation of in-flight updates on deactivation.
The changes are tracked in [PR #39](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/39).
Exact-head CI must pass before merge. See the historical
[input acceptance report](reviews/amount-input-1.4.2.md) and the
[follow-up review and acceptance](reviews/remaining-fixes-1.4.2.md).

The patched local candidate is
`dist/pr39-final-review-20261026/exchange-rates.1.4.2.zip`, SHA-256
`813028842039f6bb51badb0c22271cd3192bd7d8b9c2b5597d89a79b809f248d` (930 files).
It differs from the runtime-accepted follow-up ZIP only in `readme.txt`, which
adds the two changelog entries. Executable code, assets and catalogs are identical.
The earlier packages, including `dist/exchange-rates.1.4.2.zip`, are preserved as
historical artifacts. The canonical ZIP predates these corrections. No package
is published by this task.

## Previously reviewed preparation

Prepared October 25, 2026, from `main` revision
`dfb307962b9a045763aad70f77933f2e65d721e2` on `codex/prepare-1.4.2`.
This is a candidate only; no publication, tag, release asset or production-site
change is authorized by this preparation.

## Published baseline

The [WordPress.org API](https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=exchange-rates)
and read-only SVN inspection identify **1.4.1** as the published version.
The official downloaded ZIP contains 929 files and has SHA-256
`843447e33f951b17e69e34c5013b8c023c3e153d1bbc45120dae2b05b7c8f4f7`.
Its contents differ from the reviewed `main` above only in the public stylesheet
and converter shortcode. The visual fixes therefore belong in a new **1.4.2**
release, rather than replacing the published 1.4.1 package.

## Previously reviewed candidate identity

| Item | Value |
| --- | --- |
| Header, runtime constant, Stable tag and catalog version | `1.4.2` |
| Installable Git tree | `a8103c2ec0b3db7e855a40f627ef6244a54cc749` |
| Installable ZIP | `dist/exchange-rates.1.4.2.zip` |
| ZIP inventory | 929 files under `exchange-rates/` |
| ZIP SHA-256 | `0ad817576d52591936e92fe6ecb538e57de120a2f217ad30654cb5c7ba602192` |
| Declared minimums | WordPress 5.6 / PHP 5.6 |
| Tested up to metadata | WordPress 7.1 |

The code fixes are already reviewed and merged in
[PR #37](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/37).
The preparation changes version metadata, release notes and catalog version
headers; it adds no calculation, storage, scheduling or public API changes.
Table-border changes remain deferred. Translation messages and plural rules are
unchanged; compiled catalog byte changes are confined to the version header.

The current validation results and remaining limits are recorded in
[the 1.4.2 candidate report](releases/1.4.2.md). It records completed local checks. The exact candidate commit CI is tracked on
the candidate PR and must pass before merge or publication.

## Build and verify

```sh
set -euo pipefail
git status --short
git rev-parse HEAD:plugin/trunk
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
(cd dist && shasum -a 256 -c exchange-rates.1.4.2.zip.sha256)
```

A matching filename does not establish package identity. The packager compares
every ZIP entry against `plugin/trunk/`. Packaging must preserve the reviewed
source; dependency updates and source changes require their own checks.

## Historical evidence and release boundary

The [1.4.1 acceptance](releases/1.4.1.md) and
[visual-fix acceptance](reviews/visual-regressions-1.4.1.md) retain their original
versions and checksums. The locally built 1.4.1 visual-fix ZIP was never published
by this task and is not the official 1.4.1 upgrade baseline. Do not reuse older
Beta/native-update/live-provider acceptance for a different payload.

WordPress.org SVN remains read-only. Publication requires separate explicit
authorization and a reviewed package. Preserve the official 1.4.1 ZIP for rollback,
all QA evidence and unrelated development volumes. See
[packaging and rollback](release.md) and the future
[1.5.0 roadmap](roadmaps/1.5.0.md).
