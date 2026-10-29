# Accumulated regression collection

Start with the [mandatory release gates](../../docs/acceptance/release-gates.md). This collection extends the existing tests; it does not replace them or certify release readiness.

## Registry and dependency-free suites

```sh
set -euo pipefail
python3 scripts/regressions.py check
python3 -m unittest discover -s tests/regressions -p 'test_*.py'
python3 scripts/regressions.py run-unit --output "dist/regression-units-$(date -u +%Y%m%dT%H%M%SZ)"
```

The registry validates stable IDs, test paths, explicit coverage gaps and ownership of all browser specs/WordPress integration suites. CI validates it without running a second copy of the existing test framework. Add a registry entry or extend an existing contract when adding a regression; do not delete historical cases to obtain green. `registered` means a test exists, not that it passed.

The unit command runs the seven PHP suites, saves their complete logs, hashes the primary plugin and test files, detects edits during the run and reports skipped layers. It refuses to overwrite an earlier evidence directory. This command is intentionally **not** the full WordPress/browser/release matrix.

For the existing WordPress/browser matrix use [development.md](../../docs/development.md) and the maintained [CI sequence](../../.github/workflows/ci.yml). Keep its fixture order: smoke/integration tests, formatting seed/verify, table seed, browser tests, table verify. Upgrade preservation is a separate sequence: seed before replacement and never reseed between replacement and verification.

## Reusable native installer

`native-upgrade.php` accepts `install`, `seed`, `snapshot`, `upgrade` and `verify`, followed by a local package basename. It uses the real core `Plugin_Upgrader` bulk update path, verifies the complete extracted file inventory, checks active/runtime state and compares raw stored settings, rates, both widgets, sidebars and pages. It also renders saved shortcodes/widgets from a fresh process. Package versions/file counts are read from the archive rather than hardcoded to a release.

Required environment:

- Dedicated disposable WordPress, `EXCHANGE_RATES_TEST_MODE=1`, `WP_ENVIRONMENT_TYPE=local`, a loopback site URL and a unique `ER_ACCEPTANCE_ID`.
- Writable isolated plugin volume; no bind mount of the working source over the install directory.
- Reviewed local ZIPs mounted read-only at `/acceptance/packages/`, tests at `/opt/tests/` and the normal synthetic HTTP fixture.
- No lab filters replacing saved options in the WP-CLI process: they would invalidate the persisted-data proof.

Inside that environment, with the CLI wrapper already selecting its explicit project:

```sh
wp --skip-plugins eval-file /opt/tests/regressions/native-upgrade.php install baseline
wp plugin activate exchange-rates
wp eval-file /opt/tests/regressions/native-upgrade.php seed baseline
wp eval-file /opt/tests/regressions/native-upgrade.php verify baseline
wp eval-file /opt/tests/regressions/native-upgrade.php upgrade candidate
wp eval-file /opt/tests/regressions/native-upgrade.php verify candidate
wp eval-file /opt/tests/regressions/native-upgrade.php upgrade baseline
wp eval-file /opt/tests/regressions/native-upgrade.php verify baseline
```

The first activation belongs to fresh installation. There is no manual reactivation/resave after upgrades. `seed` rejects an existing snapshot. Run each historical starting version in its own fresh site. This PHP script is not a provisioner or scheduler/cache test or a substitute for minimum-runtime/native-browser acceptance. Keep failed attempts and do not silently repair saved data after a failed verification.

For a repeat run on an already owned disposable site, first install the reviewed
baseline without reseeding. Select a new `ER_ACCEPTANCE_STATE_OPTION` matching
`er_acceptance_saved_state_[a-z0-9]+`, then use `snapshot baseline`. This captures
the existing raw configuration and refuses to overwrite a snapshot. LocalWP maps
its test-only `ER_LOCALWP_TEST_RUN` constant to that option and supports the
`checkpoint` action. Preserve old reports and use a new evidence/packages directory.
Never repurpose this mode to accept changed data halfway through an upgrade test.

The test-only `asset-revision.php` helper adds a content revision to plugin asset
URLs so native browser reruns cannot reuse an older package with the same version.
It is guarded by local/test mode and is not shipped in the plugin.

For real speech, `scripts/voiceover-acceptance.py OUTPUT.json --move right --count 5`
records native VoiceOver phrases while Safari is foreground on localhost:19335.
It requires explicit permission for AppleScript control, checks the URL before
actions/readings, refuses existing output files and never enables that permission.
Use CUA for page navigation; `--input 0|2|bad` is restricted to a verified amount
field, and `--escape` exercises dialog dismissal. Exclude setup/focus failures
from acceptance, preserve the files, and verify VoiceOver and temporary access
are off after the run. See the [exact-ZIP recheck](../../docs/reviews/1.5.0-final-recheck.md).

## Visual comparison

The [comparison UI](../acceptance/index.html) loads a generated `config.json` with the released endpoint/version/package SHA-256 and candidate endpoint/target/identity. It compares real WordPress renderers in separate frames of the **same CSS width**, without scaling. Copy the UI assets to a local report server and install `visual-probe.php` plus `visual-probe.js` as test-only MU files in both disposable environments. They run only when the explicit comparison query and local test guard are present. Keep them outside the shipped plugin.

The core matrix has 36 combinations: three widgets, two sources, three widths and Astra/Kadence. The extended matrix has 318: all variants on Astra/Kadence and the standard compositions on Twenty Twenty-Five, GeneratePress and Blocksy. Native widgets, static/history/inverse tables, names, RTL, multiple instances, zero amounts and badge counts are included. Existing wide historical tables and deliberate converter border changes remain REVIEW findings; they are never silently accepted.

The probe checks visible values, body/control geometry, selected styles and horizontal body overflow. After measuring, it adds blank space after the theme footer solely to align both widgets at the top of their frame; it never changes the widget body or its wrappers. It excludes only the known visually hidden accessibility description spans from visible text, and keeps the intentionally changed footer out of the body equality assertion. It does **not** prove screenshot pixel equality, absence of every footer overflow, correct interactions, or accessible output. The separate existing browser/native suites and human visual review remain necessary. `REVIEW`, `NOT RUN` and `MATCH: measured body only` are deliberately distinct.

The October 28 start and exact boundaries are recorded in [the phase-one receipt](../../docs/reviews/1.5.0-phase1-start.md).

## Repeatable installed-package runs

`run-wordpress` runs the ordered integration steps from `catalog.json`, checks the local/test environment before writing synthetic fixtures, hashes the tested inputs and refuses an existing output directory. Run it before browser tests; these suites deliberately seed and replace test-only options and posts.

```sh
python3 scripts/regressions.py run-wordpress \
  --compose-file dist/acceptance-1.5.0-integrated-20261028/compose.json \
  --env-file dist/acceptance-1.5.0-integrated-20261028/private.env \
  --project er-integrated150-20261028 \
  --site http://127.0.0.1:19335 \
  --output "dist/wordpress-regressions-$(date -u +%Y%m%dT%H%M%SZ)"

python3 scripts/regressions.py run-browser \
  --compose-file dist/acceptance-1.5.0-integrated-20261028/compose.json \
  --env-file dist/acceptance-1.5.0-integrated-20261028/private.env \
  --project er-integrated150-20261028 \
  --site http://127.0.0.1:19335 \
  --output "dist/browser-regressions-$(date -u +%Y%m%dT%H%M%SZ)"
```

`run-browser` seeds the required table-isolation fixtures, sets `WP_TEST_ENV_FILE` to this disposable project, runs every browser spec with progress output, then verifies persistent settings and saved widgets. Do not skip the seed after the formatting suite; its deliberately changed settings are not the browser baseline. Never print that file or reuse an ordinary development database. The example paths identify the current local acceptance deployment; later runs must provision their own explicit project/configuration.

`scripts/native-acceptance.py` provisions independent Docker projects, refuses pre-existing project containers/volumes, runs the actual WordPress updater and stops its projects afterward without removing volumes. It publishes no ports and uses internal networks. Baselines are a JSON array of `{ "path": "dist/...zip", "version": "1.4.2", "sha256": "..." }`; every archive is checked before execution. Supply a new output directory, unused `er-` project prefix and subnet range for each run.

```sh
python3 scripts/native-acceptance.py \
  --candidate dist/exchange-rates.1.5.0.zip \
  --baselines dist/acceptance-1.5.0-integrated-20261028/baselines.json \
  --output "dist/native-acceptance-$(date -u +%Y%m%dT%H%M%SZ)" \
  --project-prefix er-new-acceptance-run --subnet-start 130
```

Preload the pinned current WordPress/CLI/MariaDB images named in the script. The minimum path requires the existing locally built `exchange-rates-release-minimum:wp5.6-php5.6-20261015` image: WordPress 5.6, PHP 5.6.40, WP-CLI and the guarded minimum entrypoint. It is not downloaded or rebuilt by this runner. A host without that image must explicitly build/review an equivalent minimum-runtime fixture before claiming that matrix; `--runtimes current` is partial coverage only. Retain image identity in the receipt. A baseline manifest containing the candidate itself exercises a separate clean installation/activation on both runtimes.

## Existing-site compositions

Phase 2 adds six permanent public compositions to `ER-real-site-compositions`. Use the [fixture guide](../acceptance/public-cases/README.md) for loopback package comparison, source provenance, report assembly and extending the collection. Current results and explicit gaps are in the [phase-two receipt](../../docs/reviews/1.5.0-phase2-public-compositions.md). The following native-browser workflow remains a separate gate.

## Real Safari and Firefox

Mount the whole test directory at `/opt/tests:ro` and copy `acceptance/native-runner.php` into the disposable site's MU directory. Open `/?er-native-acceptance=1` in the actual browser at 100% zoom and run the theme matrix. The runner loads 86 cases: six themes at 320/390/1280 CSS pixels, three widgets, failure injection, no JavaScript, multiple instances, native widgets and variants. It tests actual installed assets, not the former lab adapter. The failure injection and nonce-protected local report writer exist only in test files, guarded by local/test mode.

Reports are saved under the site's `wp-content/native-acceptance/{safari,firefox}.json`; copy them into a new evidence directory before another run. Programmatic focus checks are not VoiceOver speech evidence. Capture real screen-reader output separately with the user's action-time consent for any security-sensitive access.

See the [integrated-package receipt](../../docs/reviews/1.5.0-phase1-integrated.md) for the exact candidate, results and remaining acceptance gates.
