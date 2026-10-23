# Current release status: 1.4.1

Repository consolidation date: October 23, 2026. The final candidate remains **1.4.1**. `main` is the canonical development branch; `plugin/trunk/` is the only installable source tree. No additional release branch or source copy is required.

## Candidate identity

| Item | Expected value |
| --- | --- |
| Plugin header, runtime version and Stable tag | `1.4.1` |
| Plugin Git tree | `aa5bf2170b77032fe43ecd08bb65cbdf1b238467` |
| Installable ZIP | `dist/exchange-rates.1.4.1.zip` |
| ZIP inventory | 929 files under `exchange-rates/` |
| ZIP SHA-256 | `53f0a3d8407000dab9c644c4f9fd73ed0b2b3ec9959896c23352184399b51e8d` |
| Declared minimums | WordPress 5.6 / PHP 5.6 |
| Tested up to metadata | WordPress 7.1 |

This is the same plugin payload accepted in [PR #33](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/33) and the [October 21 acceptance report](releases/1.4.1.md), present in `main` at `5f5f5e24dc03115bb960f8a46001fef4d959774a`. Subsequent repository documentation, banner and test changes do not change its Git tree or ZIP. If the tree or checksum differs, treat it as a different candidate and investigate before reusing this acceptance.

From the repository root, verify the current checkout and build locally:

```sh
set -euo pipefail
git status --short
git rev-parse HEAD:plugin/trunk
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
shasum -a 256 dist/exchange-rates.1.4.1.zip
```

Package only from the reviewed clean checkout. Ignored `dist/` directories may contain older artifacts; a matching filename alone does not establish identity. GitHub source archives are not installable plugin packages. ZIP byte identity assumes the same Python/zlib packaging environment; the plugin tree and per-entry content parity remain independent checks.

## Consolidated work and evidence

- [PR #4](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/4): production compatibility and regression requirements in the canonical development contract.
- [PR #32](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/32): editable banner, approved PNGs, licenses and render manifest outside the installable tree. See the [banner instructions](../examples/banner/README.md).
- [PR #34](https://github.com/dejurin/wordpress-plugin-exchange-rates/pull/34): additional constructor/conversion and WordPress rendering tests, CI and documentation. Its duplicate percentage implementation was removed; the runtime correction already exists in `main`.
- [CI #99](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/runs/39943328454): all five jobs passed on PR #34 head `0b486da0272cdc43c654bb6ae0aee592e7cd5411`, including PHP 5.6/8.3/8.4 checks and 318 Chromium cases. Its local compatibility evidence covers 110 new calculation assertions on PHP 5.6.40/8.4.27 and 30 rendering assertions on WordPress 5.6/PHP 5.6 and WordPress 7.1.3/PHP 8.3/8.4.

Require successful [CI](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/workflows/ci.yml?query=branch%3Amain) on the consolidated revision as well. Historical checks validate their recorded revisions, not arbitrary later changes. The [release acceptance](releases/1.4.1.md) records upgrade/rollback, saved-state and WordPress 7.2 Beta 1 checks plus their limits; it does not certify later Beta/RC builds or every browser/theme/cache combination.

## Scope and history

This consolidation is GitHub-only. It creates no WordPress.org publication, SVN tag, site update or automatic deployment. Publication and production acceptance require separate explicit authorization. The WordPress.org badge in the root README tracks the external published version independently of the candidate above.

Older `docs/releases/1.4.0*.md` files and pinned 1.3.2/1.4.0 regression references remain historical evidence. Do not rename them to 1.4.1 or rewrite the immutable baseline manifest. The [1.5.0 Gutenberg/Elementor roadmap](roadmaps/1.5.0.md) is planning only and is outside the 1.4.1 payload.

Local editorial drafts and browser captures are not release inputs. Preserve them separately when cleaning a checkout; do not apply old readme edits over the accepted plugin payload. Remove branches only after their commits are reachable from `main` and their references have been backed up. Revert repository changes through a reviewed PR; a Git revert does not change deployed sites or published assets. See the [release and rollback procedure](release.md#rollback).
