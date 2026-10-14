# Local development

Follow the [development contract](../CONTRIBUTING.md) for language, WordPress conventions, security and compatibility requirements.

## Purpose and boundaries

`plugin/trunk/` is the single source of plugin code. Installed WordPress files, volume contents, local secrets and build outputs are excluded from Git.
`wordpress/compose.yaml` runs separate WordPress, MariaDB and WP-CLI containers. Images are pinned by version and manifest digest; update both deliberately and repeat the smoke test.

Defaults: Compose project `exchange-rates-dev`, address `127.0.0.1:18972`, WordPress 7.1.3, PHP 8.3, MariaDB 11.4.8 and WP-CLI 2.12.0.
The database has no published host port. Images support amd64 and arm64.

## Startup

From the repository root:

```sh
python3 scripts/dev-env.py
docker compose --env-file wordpress/.env -f wordpress/compose.yaml config --quiet
docker compose --env-file wordpress/.env -f wordpress/compose.yaml up -d --wait --wait-timeout 180
docker compose --env-file wordpress/.env -f wordpress/compose.yaml run --rm --entrypoint sh cli /opt/dev/init.sh
```

The generator creates `.env` with mode `0600` and random passwords. It does not replace an existing file; skip this step when the file already exists.
For manual configuration, copy `wordpress/.env.example` to `wordpress/.env` and fill in the three empty password values; Compose refuses to start with empty values.
Do not publish `.env`, container environments or the output of `docker compose config` without `--quiet`.

Initialization installs WordPress only if it is not already installed, preserves existing user passwords and activates the plugin.
Credentials are `WP_ADMIN_USER` and `WP_ADMIN_PASSWORD` from `.env`.
Activation in the normal environment requests real rates from `https://api-bank.fex.to/`.
Network failures or missing data are not masked by substituting test rates.

Edits to `plugin/trunk/` on the host are immediately visible to containers; file updates and modifications through the administration UI are disabled.
Automatic WordPress updates and automatic WP-Cron are disabled for reproducibility.
The plugin's own hourly HTTP fallback remains unchanged. To run its cron event manually:

```sh
docker compose --env-file wordpress/.env -f wordpress/compose.yaml run --rm cli cron event run exchange-rates_data_update
```

## Logs and shutdown

```sh
docker compose --env-file wordpress/.env -f wordpress/compose.yaml ps
docker compose --env-file wordpress/.env -f wordpress/compose.yaml logs --tail=100 wordpress
docker compose --env-file wordpress/.env -f wordpress/compose.yaml exec wordpress sh -c 'test ! -f wp-content/debug.log || tail -n 100 wp-content/debug.log'
docker compose --env-file wordpress/.env -f wordpress/compose.yaml down
```

`down` preserves both named volumes. **Do not add `--volumes` for an ordinary shutdown: it deletes the local database and site.**
Changing passwords in `.env` does not change an existing database user: coordinate the corresponding change inside the database first. This is why the generator does not replace `.env`.

Choose the port before the first installation. If changing `WP_PORT` for an existing site, recreate the containers and update the stored URL before running initialization again. For example, when moving to port 18973:

```sh
docker compose --env-file wordpress/.env -f wordpress/compose.yaml up -d --wait
docker compose --env-file wordpress/.env -f wordpress/compose.yaml run --rm cli search-replace http://127.0.0.1:18972 http://127.0.0.1:18973 --all-tables-with-prefix --skip-columns=guid
```

This command changes URLs in the **local database of the selected Compose project**. Do not use it for remote sites.

## Isolated smoke with synthetic rates

Use a separate project name; tests create a page and write synthetic rates to its database.
If the normal environment is already running, stop it or select an available QA port.
These commands use separate volumes and port 18973:

```sh
export COMPOSE_PROJECT_NAME=exchange-rates-qa
export COMPOSE_FILE=wordpress/compose.yaml:wordpress/compose.test.yaml
export WP_PORT=18973
docker compose --env-file wordpress/.env up -d --wait --wait-timeout 180
docker compose --env-file wordpress/.env run --rm --entrypoint sh cli /opt/dev/init.sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/smoke.php
docker compose --env-file wordpress/.env down
unset COMPOSE_PROJECT_NAME COMPOSE_FILE WP_PORT
```

The override loads an MU plugin only in the test environment. It returns synthetic JSON for the exact `rates/currencyrate.json` and `sources.json` endpoints on `api-bank.fex.to` and blocks all other outbound WordPress HTTP API requests.
WordPress core, theme and plugin update probes on `admin_init` are disabled in test mode to avoid warnings caused by the blocked network.
Synthetic values are not intended for financial use and are excluded from the plugin ZIP.
The normal Compose configuration does not load the fixture. Do not reuse volumes between test and normal modes: stored test rates would remain in the database.

The smoke test checks activation of `exchange-rates/Install.php`, autoloading, cron registration, data updates through the WordPress HTTP API, three shortcodes, two widgets, settings rendering and admin CSS loading scope.
It prints the URL of the `Exchange Rates Smoke - synthetic data` page when finished.
Open that page and the administration UI: check the converter, table and badge, settings persistence, CSS/JS loading and absence of PHP/browser errors.
The synthetic smoke test does not establish the availability or correctness of live external sources.

## Number-formatting regression tests

Run the dependency-free formatter suite with PHP 5.6 or newer:

```sh
php tests/formatting.php
```

Its 841 assertions cover all eight format IDs, precision 0 through 7, integer zeros, negative values, rounding boundaries, numeric strings, null, precision clamping and the existing unknown-format fallback. Explicit expected strings protect the fractional output as well as the fixed integer output. PHP's historical negative-zero behavior is preserved (see the [PHP changelog](https://www.php.net/manual/en/function.number-format.php#refsect1-function.number-format-changelog)).

For WordPress integration, use only the separate synthetic environment described above. After initialization, run:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/formatting-wordpress.php seed
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/formatting-wordpress.php verify
```

`seed` writes synthetic saved settings, cached rates, a widget instance and a shortcode page to the disposable database. `verify` runs 116 assertions across all format IDs, checks the existing zero placeholders, and confirms that settings, widget options, source data and stored shortcodes are unchanged. It does not resave them. For an upgrade regression, seed while running the released source, replace only the plugin files with the candidate, and run `verify` without seeding again. Do not run the general smoke between these two steps: it deliberately replaces rates through the update path.

Open the printed fixture URL to check browser parity: the saved table amount/current/previous values should be `1,000`, the badge should be `100`, and entering `1000` in the table should keep its values at `1,000`. This covers the repaired integer path; the change does not claim complete PHP/JavaScript rounding parity for all floating-point inputs.

The fix changes only zero-precision formatting: integer zeros are retained and zero is returned as `"0"`. Existing callers can therefore apply their normal dash/null zero placeholders instead of receiving an empty string. Fractional trimming, format IDs, separators, rounding, option keys and stored values are preserved. There is no data migration. Reverting the fix restores the old formatter and its bug without a database rollback. The development ZIP still carries the current version metadata; it is not a new published release.

## Table amount isolation regressions

The browser suite uses the real WordPress-rendered shortcodes and saved widget with the plugin's public JavaScript and WordPress jQuery. It requires Node.js 20 or newer, npm and Chromium; CI uses Node.js 24. Dependencies are pinned under `tests/browser/` and are excluded from the plugin ZIP.

Initialize the **separate synthetic environment** above, keeping its `COMPOSE_PROJECT_NAME`, `COMPOSE_FILE` and `WP_PORT` exports active. Then run from the repository root:

```sh
npm ci --ignore-scripts --prefix tests/browser
npm exec --prefix tests/browser -- playwright install chromium
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/table-isolation-wordpress.php seed
WP_TEST_URL="http://127.0.0.1:${WP_PORT}" npm test --prefix tests/browser
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/table-isolation-wordpress.php verify
```

`seed` creates three pages, a saved widget and synthetic settings/rates in the disposable database. The fixture temporarily filters formatting settings per rendered table without persisting those overrides. `verify` confirms that settings, rates, widget options and saved page content are unchanged. For an upgrade check, seed before changing plugin files and run the browser suite and `verify` afterwards without seeding again. Run the general smoke and formatting fixture before this seed/verify sequence: they deliberately write their own test data.

The eight Chromium tests cover a single table, mixed shortcode/widget tables in both DOM orders, repeated edits, a table without an amount input, independent precision/separators/symbol position, current and previous-close values, trend images, click/blur and the existing amount-one fallback for empty, zero, negative or invalid input. Snapshots protect the other table inputs and values, badge and converter. Captions are excluded because WordPress asynchronously replaces their emoji text with images. The suite preserves existing rendering behavior, including the lack of a symbol on the initial previous-close value. It does not attempt unrelated formatting fixes.

The runner accepts only loopback HTTP URLs and blocks browser requests to other origins. Use only a disposable test site. Retries are disabled; failed runs retain screenshots and traces in the ignored `tests/browser/test-results/` directory. CI uploads those diagnostics on failure.

The fix scopes settings and rate-cell updates to the input's containing table. It changes two selectors in `assets/js/public/common.js`; formulas, markup, public attributes, saved settings and version metadata are unchanged. There is no migration. Reverting that fix restores the old behavior without a database rollback. No WordPress.org release is published by this workflow.

## Settings label regressions

The same `npm test --prefix tests/browser` command also runs eight settings-page tests. Keep the disposable `COMPOSE_PROJECT_NAME`, `COMPOSE_FILE` and `WP_PORT` exports active: the runner uses WP-CLI to create a dedicated test administrator and signs in through the normal WordPress login endpoint. The helper refuses non-test environments and mismatched site URLs. Its generated password and session cookies stay in memory; no ordinary administrator credentials are read. Authenticated traces are disabled. CI excludes settings-page diagnostics from uploaded artifacts because page snapshots can contain nonce-bearing admin links.

The tests check the native `label.control` association for Source, Formatting and Decimals; unchanged field IDs and submitted names; click-to-focus without changing values; and the Sources link's existing `#source-table` navigation by mouse and Enter. Clicking this link must not focus the source selector or change settings. A save/restore round trip checks that saved values survive reopening the page and remain editable through the existing form. The table fixture's subsequent `verify` checks all saved plugin options, rates, widget data and page content after both browser suites.

Seed the table fixture before replacing plugin files for an upgrade check, then run the full browser suite and `verify` without reseeding. The settings save test restores its original format and precision after checking different values. Use only the disposable site; it intentionally saves settings during that test.

The label fix changes only three `label_for` values to match existing control IDs. It preserves input names, IDs, translations, the Sources link, validation, saved data and release metadata. Revert these three changes to roll back; no data migration is needed. The synthetic HTTP fixture and browser authentication helper are not shipped in the plugin ZIP.

## Administrative JavaScript regressions

Eight additional tests in `admin-globals.spec.js` bring the browser suite to 24 tests. Four cases verify that `i` and `value` neither appear on `window` nor overwrite another script's values, both during initialization and after repeated input and expand/collapse events. Compatibility cases preserve exact badge, table and converter shortcode text, nested widget field names, multiple currencies, checkbox/range handling, independent forms and the five-row table limit. Unexpected browser page errors fail these tests.

The tests use the real settings page and a test-only Tools page that renders the actual Table and Converter widget forms with the shipped admin JavaScript. The page requires `manage_options` and exists only with the guarded synthetic HTTP fixture loaded. It reads the saved table fixture without saving widget changes. This covers form rendering and generator interactions, not the complete WordPress block-widget editor. Authentication is shared with the settings-label tests; authenticated traces are disabled and CI excludes these page diagnostics from uploaded artifacts.

The production fix adds only two local `var` declarations in `assets/js/admin/common.js`. It preserves the existing JavaScript syntax level, algorithms, generated text, field names, options and release metadata. Seed saved fixtures before replacing plugin files, then run the browser suite and saved-data verification without reseeding. Rollback is reverting the two declarations; no database migration is needed.

## Manual update cooldown regressions

Run the AJAX-handler suite only in the separate synthetic environment, with its Compose exports still active:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/manual-update-wordpress.php
```

The 28 cases dispatch the shipped `wp_ajax_exchange_rates_force_data_update` callback in separate WP-CLI processes. They use real WordPress nonce/capability checks, the registered settings sanitizer, JSON response functions and the synthetic HTTP fixture. A test-only namespaced clock freezes the administrative comparison and settings defaults; the local offset is derived from WordPress. The JSON termination handler exits normally rather than throwing an exception that the plugin could catch. No test clock or helper is included in the installable package.

Coverage includes UTC, UTC+3, UTC-5, UTC+5:45, Europe/Kyiv and America/New_York; the 0/59/60/61-second boundaries; stored numeric strings, future and missing timestamps; subscriber and nonce rejection before the cooldown; successful updates and provider failure. Assertions check exact response bodies, the number of rate requests, unchanged user settings, unchanged rates on refusal/failure and receipt of the synthetic rates after success. The suite creates dedicated disposable users, restores the original plugin settings/rates and fails on PHP warnings. Named zones use their offset at execution time; this is not an exhaustive daylight-saving transition test.

This is handler integration coverage, not an HTTP login or browser-click test of the update button. Run it before the saved-data formatting/browser fixtures in CI. For an upgrade check, seed the table fixture before changing plugin files, run this suite and the existing browser suite after the change, and verify the saved table fixture without reseeding.

The production change replaces `current_time('timestamp')` with `time()` when comparing against the already stored Unix timestamp. The 60-second interval, permissions, nonces, response messages, option keys, saved data and version metadata are preserved. Reverting that line rolls back the change without a database migration.

The separate sanitizer defect that discards updated service fields, including `update_timestamp`, remains unresolved. These tests seed the stored timestamp to isolate clock comparison; they do not establish that a successful AJAX update persists a fresh timestamp or that consecutive updates are reliably throttled. Retry/backoff, parallel requests and source/cache consistency are outside this fix.

## CI and limitations

- PHP 5.6 / 8.3 / 8.4: syntax checks for all 56 plugin PHP files, including shipped vendor code, formatter regression tests and syntax checks for the manual-update test helpers.
- WordPress 7.1.3 / PHP 8.3: isolated runtime smoke, manual-update cooldown regressions, saved-data formatting regressions, Chromium table-isolation, settings-label and administrative JavaScript regressions, saved-data preservation and HTTP availability of assets.
- Packaging: consistent version metadata and exact ZIP content parity with `plugin/trunk/`.

Older WordPress versions and the full PHP range are not covered by CI runtime tests. Browser automation currently covers Chromium only. Normalization does not change the imported release's minimum requirements.
CI has only `contents: read`, uses no SVN secrets and publishes no releases. PHPCS/WPCS and comprehensive security checks are not currently automated; see the [validation contract](../CONTRIBUTING.md#validation-and-review).
CI cleanup with `down --volumes` applies only to the disposable project with a unique name for that specific run.
