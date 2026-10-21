# WordPress 7.2 Beta 1 compatibility lab

## Scope and isolation

The [official Beta 1 announcement](https://wordpress.org/news/2026/10/wordpress-7-2-beta-1-is-ready-for-download-and-testing/) was published on October 20, 2026. This lab pins `7.2-beta1`, rather than a moving nightly or beta image. The existing PHP 8.3/Apache image, MariaDB and WP-CLI remain pinned to the same digests as normal development. WordPress core is replaced at image build time from its official archive, with a mandatory SHA-256 and a version assertion.

Use a dedicated Compose project, fresh site/database volumes, a free loopback port and an unused explicit subnet. The regular development site is independent. The plugin is mounted read-only. Existing test-only MU plugins supply synthetic rates and deny other outbound WordPress HTTP API requests. Do not connect these volumes to a normal or live-provider environment.

Build-time archive downloads are separate from the WordPress HTTP fixture. The archive checksum establishes reproducibility; it is recorded locally and is not an independently published upstream signature. An existing site volume keeps its core when an image changes: always check `wp core version`, and use a new project with fresh volumes for another core release.

## Start the candidate lab

From this task's checkout, generate `wordpress/.env` only if it does not already exist:

```sh
set -euo pipefail
if [ ! -f wordpress/.env ]; then
    python3 scripts/dev-env.py
fi
export COMPOSE_PROJECT_NAME=exchange-rates-wp72-beta1
export COMPOSE_FILE=wordpress/compose.yaml:wordpress/compose.test.yaml:wordpress/compose.beta.yaml
export WP_PORT=19347
export WP_TEST_URL=http://127.0.0.1:19347
export WP_BETA_SUBNET=10.254.72.0/24
unset WP_BETA_PLUGIN_PATH

docker compose --env-file wordpress/.env config --quiet
docker compose --env-file wordpress/.env up -d --build --wait --wait-timeout 180
docker compose --env-file wordpress/.env run --rm --entrypoint sh cli /opt/dev/init.sh
docker compose --env-file wordpress/.env run --rm cli core version
```

The port and subnet above were checked for this local run. Check both before using them on another host. Keep the exported variables in the same shell for every command and the browser helper. The explicit project name takes precedence over the ordinary `.env` default. Never print expanded Compose configuration or local credentials.

To test the published plugin first, download the exact `exchange-rates.1.4.0.zip` from WordPress.org, retain its hash and extract it under ignored `dist/wp72-beta1/published/`. Before starting the fresh lab set:

```sh
export WP_BETA_PLUGIN_PATH=../dist/wp72-beta1/published/exchange-rates
```

The relative mount path is resolved from `wordpress/compose.yaml`. Verify the installed version with `wp plugin get exchange-rates --field=version`. After seeding and checking the saved-data fixtures, upgrade to this checkout by unsetting `WP_BETA_PLUGIN_PATH` and running `docker compose --env-file wordpress/.env up -d --wait`. This changes only the read-only plugin mount. Verify saved-data fixtures before reseeding, reactivation or general smoke.

## Verification

Use the existing [synthetic smoke and regression procedures](../development.md#isolated-smoke-with-synthetic-rates), including the browser suite. Run integration suites sequentially because they share the disposable database. Run general smoke, payload validation, source-directory validation, source identity, manual update, update-state, background refresh and concurrent-source tests, then the saved-formatting seed/verify and table seed. The thirty-minute interval suite applies to the 1.4.1 candidate only. Use the PHP tests from the matching release revision when testing 1.4.0: the newer background suite intentionally expects the candidate's thirty-minute schedule.

```sh
set -euo pipefail
for test in smoke payload-validation source-directory source-identity manual-update update-state background-refresh concurrent-source refresh-interval; do
    case "$test" in
        smoke) file=smoke.php ;;
        *) file="$test-wordpress.php" ;;
    esac
    docker compose --env-file wordpress/.env run --rm cli eval-file "/opt/tests/$file"
done
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/formatting-wordpress.php seed
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/formatting-wordpress.php verify
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/table-isolation-wordpress.php seed
npm ci --ignore-scripts --prefix tests/browser
npm exec --prefix tests/browser -- playwright install chromium
npm test --prefix tests/browser
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/table-isolation-wordpress.php verify
```

Also inspect the rendered frontend, settings screen, block editor and `wp-content/debug.log`. Browser tests preserve settings and exercise real DOM, assets, jQuery, AJAX, permissions, forms, widgets and shortcodes. Retain raw logs and browser diagnostics under ignored `dist/`; do not publish authenticated screenshots, nonce-bearing HTML or credentials.

## Stop and rollback

With the same exports, stop only this lab while preserving both volumes:

```sh
docker compose --env-file wordpress/.env down
```

Do not add `--volumes`. To return from the candidate to 1.4.0, let work finish, deactivate the candidate, restore the verified published plugin mount, recreate the lab service and reactivate. Confirm the hourly schedule and saved data. Replacing active files alone does not restore the old schedule. Preserve the task-owned checkout while this lab is in use: its files are bind mounts.

## Results

Tested on October 21, 2026 with WordPress `7.2-beta1`, PHP `8.3.35`, MariaDB `11.4.8`, WP-CLI `2.12.0`, Twenty Twenty-Five and Chromium. The running site confirms `WP_DEBUG=true`, `WP_DEBUG_LOG=true`, `WP_DEBUG_DISPLAY=false` and `E_ALL`.

| Target / check | Actual result |
| --- | --- |
| Published 1.4.0 ZIP, matching PHP suites | 11/11 commands passed |
| Published 1.4.0 settings, generators/widgets, AJAX and table browser tests | 25/25 passed |
| Candidate 1.4.1 PHP suites, including schedule migration | 12/12 commands passed |
| Candidate complete Chromium suite | 318/318 passed, no skipped/flaky tests |
| Candidate translations against a freshly generated POT | 7624/7624 assertions passed |
| Standalone formatting / change / payload suites on local PHP 8.5.10 | 841/841, 1157/1157, 130/130 |
| Plugin metadata, PHP syntax and ZIP parity | Passed; 56 PHP files, 929 packaged files |
| Active upgrade from published ZIP to candidate, without reactivation | Saved settings, rates, widget and three pages unchanged; schedule migrated to 30 minutes |
| Saved-data check after browser/editor/localization runs | Unchanged |
| New block editor | Three actual shortcode blocks saved through the editor store/REST path, reloaded and found in editor DOM; public output renders all three shortcodes |
| Editor/frontend JavaScript and layout | No page errors; converter input/swap passed; 1280/390px frontend without document overflow |
| Local badge SVGs | Both packaged flags loaded and decoded |
| PHP debug log after verification | No file created; zero recorded fatal, warning, notice or deprecated messages |

The published ZIP matches all 929 files of `55e4cfcb99d140d7f4f48d6b232d630fafcb661a`; its SHA-256 is `da9875d8532a6cbf9ae82dd29573e2c3497b0f6929822539d50265fe0c42dbfb`. Candidate plugin code is exactly `eb1e91fe6801144d89bc80e300e9b1cb77f9145a`; its verified ZIP SHA-256 is `30a7b354612cf0940ce7e12cce7272f1f53301c5709cb51735b0786fef86b42b`. No plugin source or compatibility metadata was changed.

Both runtimes passed payload `322`, directory `168`, source identity `73`, manual update `28`, persisted state `306`, background refresh `51`, concurrent source selection `91` and saved formatting `116` assertions. The candidate additionally passed `50` interval assertions. The initial 1.4.0 background run used the newer candidate expectation and failed its thirty-minute assertion; the matching release suite passed all 51. The initial log is retained separately and is not counted as a passing run.

Evidence is local and ignored: `dist/wp72-beta1/receipt.json`, per-version integration/browser logs, candidate browser JSON, editor report, runtime/debug summaries and screenshots. The task-owned environment file `dist/wp72-beta1/env.sh` selects the final candidate lab. The site remains running at `http://127.0.0.1:19347`; the editor smoke page is `/?page_id=19` and the general smoke is `/?pagename=exchange-rates-smoke`. Keep `wordpress/.env` local.

The image source archive SHA-256 is `be5fdce1adebb81526e7f2219c1833cb12add48202970b6d7b048a44518f98f2`. The Docker image preserves the base image's `wp-config-docker.php`, while replacing the bundled core. This host exhausted Docker's automatic address pool; the lab uses the checked unused subnet `10.254.72.0/24` without removing existing networks.

## Interpretation and remaining coverage

No WordPress 7.2 Beta 1 incompatibility was found in this tested configuration. This is a bounded compatibility result, not final-release certification. Repeat on later Beta/RC builds and the final core release. Runtime checks here do not cover other PHP versions, Safari/Firefox, physical devices, arbitrary themes/plugins, object caches or multisite. Existing converter tests emulate theme CSS; they are not a live third-party-theme installation matrix.

Live-provider availability, freshness, production CDN/page caching and real traffic are outside this synthetic lab. The default emoji badge requests `s.w.org`; these images and avatars are deliberately blocked by the browser harness. Local packaged SVG flags were checked separately. No plugin regression was inferred from the expected network block. The lab uses manual cron dispatch rather than proving traffic-driven native WP-Cron delivery. Known worker/deactivation concurrency limits remain outside this core compatibility check.

The primary dirty checkout and ordinary containers/volumes were preserved. No production changes, WordPress.org SVN writes, merge or plugin publication were performed.
