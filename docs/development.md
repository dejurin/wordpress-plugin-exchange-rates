# Local development

Follow the [development contract](../CONTRIBUTING.md) for language, WordPress conventions, security and compatibility requirements.

## CSS parity checks

`tests/browser/css-parity.spec.js` compares the candidate public stylesheet against immutable Git CSS from `cb097af1d04153dd8132a6a0214642491b5c5557`, the merged 1.4.0 snapshot before CSS cleanup. Keep this reference pinned. The WordPress CI checkout includes history so the reference is available without a second source copy.

On a dedicated, seeded test environment, run:

```sh
WP_TEST_URL=http://127.0.0.1:19249 npm --prefix tests/browser test -- css-parity.spec.js
```

The suite covers tables, native widgets, converters, badges, flags/regions, trend arrows, expanded source information, input edits, hover/focus, currency swaps, LTR/RTL, 320/1064 px, and custom theme rules/properties. Before comparison, the test removes only the new table-layout marker classes (`exchange-rates-table-label`, `exchange-rates-table-region`, `exchange-rates-table-full`, `exchange-rates-caption-start`) to represent cached legacy markup. Every comparison then requires identical computed styles, values and geometry for all plugin elements. This protects legacy HTML, converters and badges; it does not assert pixel equality for the intentionally revised table layout, which has its own tests below. Expected screenshots are generated from the pinned reference CSS in the same DOM/state, stored under ignored `test-results/`, and compared with Playwright's image comparator (`threshold: 0.05`, `maxDiffPixels: 0`). The small color/antialiasing allowance addresses observed Chromium SVG-edge rasterization differences on identical CSS; computed-style and geometry comparisons have no tolerance. A deliberate height mutation verifies that the comparison detects a visible regression.

`CSS_REFERENCE=legacy` selects immutable 1.3.2 CSS and LTR scenarios. Run this mode only with the actual 1.3.2 plugin installed in the disposable environment: its PNG markup must remain the baseline. To check cleaned 1.4.0 CSS against legacy markup in RTL as well, run the default `release` reference mode on that same 1.3.2 installation. Direct equality to old RTL CSS is intentionally not asserted because the approved 1.4.0 bidi fixes change its rendering.

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
Page rendering uses saved rates without contacting the rate provider. Expired or missing data queues a WP-Cron refresh; the existing hourly event remains scheduled. Because automatic WP-Cron is disabled in this local setup, run its cron event manually:

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

The 28 cases dispatch the shipped `wp_ajax_exchange_rates_force_data_update` callback in separate WP-CLI processes. They use real WordPress nonce/capability checks, the registered settings sanitizer, JSON response functions and the synthetic HTTP fixture. A test-only namespaced clock freezes the administrative comparison, defaults and service writes; the local offset is derived from WordPress. The JSON termination handler exits normally rather than throwing an exception that the plugin could catch. No test clock or helper is included in the installable package.

Coverage includes UTC, UTC+3, UTC-5, UTC+5:45, Europe/Kyiv and America/New_York; the 0/59/60/61-second boundaries; stored numeric strings, future and missing timestamps; subscriber and nonce rejection before the cooldown; successful updates and provider failure. Older PHP timezone databases use the historical `Europe/Kiev` identifier; the suite prints which identifier it uses. Assertions check exact response bodies, the number of rate requests, unchanged user settings, unchanged rates on refusal/failure, persisted service metadata and receipt of the synthetic rates after success. The suite creates dedicated disposable users, restores the original plugin settings/rates and fails on PHP warnings. Named zones use their offset at execution time; this is not an exhaustive daylight-saving transition test.

This suite covers handler integration. The browser test described below also exercises the real update button over HTTP. Run it before the saved-data formatting/browser fixtures in CI. For an upgrade check, seed the table fixture before changing plugin files, run this suite and the existing browser suite after the change, and verify the saved table fixture without reseeding.

The production change replaces `current_time('timestamp')` with `time()` when comparing against the already stored Unix timestamp. The 60-second interval, permissions, nonces, response messages, option keys, saved data and version metadata are preserved. Reverting that line rolls back the change without a database migration.

## Concurrent source selection

Run `docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/concurrent-source-wordpress.php` only on the disposable synthetic site. Separate PHP processes overlap an old hourly/direct request with a real settings save, in both source directions and with successful or failed responses. The suite also switches sources immediately before option filters and SQL writes, covers an absent rate cache, an obsolete alloptions cache and unrelated preference changes, then verifies recovery and restoration from fresh requests.

The updater conditionally saves rates and service metadata against the serialized settings captured before HTTP. A changed snapshot rejects the obsolete write instead of reverting user fields. Each SQL write checks its predicate atomically; clearing an option cache alone is not a concurrency guard. The request source and write values come from that database snapshot, not a potentially stale option cache. Existing hourly scheduling, five-minute retry, manual refresh authorization, option keys, sanitizer/veto hooks, successful-write notifications and widget/shortcode contracts remain. See [the scoped acceptance report](releases/1.4.0-concurrent-source.md).

## API payload validation regressions

Run `php tests/payload-validation.php` on PHP 5.6 or newer. On the isolated synthetic WordPress site, also run:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/payload-validation-wordpress.php
```

The tests reject malformed current/previous rates, non-finite values and overflowing cross-rates, unknown or mismatched sources, invalid base metadata, missing dates, relative dates, calendar normalization and invalid timezone offsets. Valid numeric strings, absolute ISO/SQL/RFC dates, and absent/null/empty previous-close records remain supported. The update service passes its requested source to the existing validator; one-argument validator calls remain supported.

The WordPress suite uses real HTTP interception, option persistence and separate requests. Rejected responses preserve the full last payload, original dates/base/success time, preferences and seven rendering paths. Cron failure schedules a retry and releases its lease. First-load failure retains the existing unavailable message; a later valid response recovers normally. Existing saved payloads are not migrated or revalidated as a whole. Invalid non-finite individual cached rates are no longer usable for arithmetic. See [the validation acceptance report](releases/1.4.0-payload-validation.md) for baseline compatibility and rollback limits.

## Failed source selection and retained-rate identity

`tests/source-identity-wordpress.php` exercises real settings sanitization and the source-change hook across separate WP-CLI processes. It checks both CurrencyRate-to-ECB and ECB-to-CurrencyRate failures, repeated failure, a later successful update, missing legacy source metadata, and restoration of the original fixture state. Seven render paths cover tables, converters, badges, inverse displays, and both native widgets.

Use only the dedicated local test environment with `wordpress/compose.test.yaml` and an explicit disposable Compose project. Then run:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/source-identity-wordpress.php
WP_TEST_URL=http://127.0.0.1:18973 npm --prefix tests/browser test -- source-identity.spec.js
```

The browser fixture `?er-source-test=1` supplies deterministic rates without saving options. Its 16 Chromium cases cover LTR/RTL at 320px and 1064px, matching-source legacy behavior, mismatched source/base metadata, table amount entry, converter amount/swap, captions, and dates. `--grep 'legacy matching-source contract'` selects the eight cases expected to pass on published 1.3.2 as well.

The fix reads source identity from the retained payload for calculation and attribution, and the converter also reads its base metadata from that payload. Existing helpers preserve the previous settings fallback when legacy metadata is missing. No settings schema, shortcode attributes, widget identifiers, retrieval scheduling, or frontend assets change. See the [source-identity acceptance report](releases/1.4.0-source-identity.md) for evidence and limitations.

## Persisted update-state regressions

Run the persistence suite in the same disposable environment, before the formatting and table fixtures:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/update-state-wordpress.php
```

The 306 assertions cover sequential requests in separate WordPress processes without reseeding between requests: a successful update at `t`, refusals at `t+1` and `t+59`, another success at `t+60`, and a refusal at `t+61` measured against that new success. The matrix includes four UTC offsets, integer/numeric-string timestamps, missing timestamps and zero. Further cases cover failed/malformed providers, rejected option writes, administrative and CLI service calls, cron, explicit source arguments, a missing settings option, source changes, unknown-time rendering and the absence of synchronous HTTP during expired-cache page bootstrap. Form input cannot supply service metadata, including after a service update in the same request. Unknown existing settings are preserved. The suite restores its original options and fails on PHP warnings.

The internal writer saves only `rates_available`, `base_currency` and `update_timestamp` through the existing WordPress option API. A temporary sanitizer filter overlays trusted service fields and is removed in `finally`; the form sanitizer remains active. A successful response requires readback of both rates and metadata. The last successful UTC timestamp advances only after a validated, stored rates response. Provider failure retains the cached rates, base and previous successful timestamp while clearing availability. Source-change refreshes run on `updated_option`, after the selected source has been stored, to avoid an outer form write overwriting the new metadata.

Missing or zero time means no known successful update. It allows the first authorized manual attempt and does not display a fabricated date. Page rendering queues background recovery for an unknown or expired timestamp; it never fetches rates synchronously. The state writer needs no migration. Nonces, capabilities, response messages and the 60-second interval remain unchanged.

`manual-update-state.spec.js` adds a 25th Chromium test: sign in, click the actual update button, verify persisted metadata after the page reload, click again immediately and verify refusal without changing the successful state. It forwards the real AJAX response unchanged; only provider HTTP is synthetic. Its dedicated fixture restores the original settings and rates. Authenticated traces are disabled and CI excludes its screenshots from uploaded diagnostics.

For an upgrade/rollback check, seed saved options, pages and both widget types on the published 1.3.2 baseline, replace only plugin files, and compare raw saved values and rendered output without reseeding or reactivation. Run the state suites and browser suite, verify restoration, then restore the old files and verify the saved content again. Rollback needs no data migration but reintroduces the old persistence bug.

Rates and settings remain two separate option writes, not an atomic transaction. A rejected metadata write is reported as failure, but rates may already have been stored. Background refreshes are serialized and use retry/backoff as described below. Explicit manual/source-change requests retain their existing service path; concurrency with those requests and cache/source consistency after a failed source change remain separate concerns. Synthetic fixtures do not establish live-provider availability.

## RTL flag and label alignment regressions

`rtl-labels.spec.js` adds 40 cases using the guarded, read-only RTL fixture. The matrix covers LTR and RTL at 1064px and 320px, shortcode and native tables, full and compact widths, all five flag modes, 16px/64px flags, codes/names, regions on/off, the base row, Arabic/Hebrew text and long labels. Emoji images are supplied locally by the test runner; this does not test the availability of WordPress.org's emoji CDN.

The tests measure the rendered text with DOM `Range` rectangles, not just the full-width label container. They require a small non-overlapping flag/text gap, aligned region labels, unchanged Latin code order, readable wrapping with no ellipsis and no document-level horizontal overflow. The layout refinement described below deliberately replaces the old truncation assertions; flag adjacency, size, direction and region alignment remain checked. Run them in the disposable environment after seeding the table fixture:

```sh
WP_TEST_URL="http://127.0.0.1:${WP_PORT}" npm test --prefix tests/browser -- rtl-labels.spec.js
```

The production fix overrides the first table header cell's alignment only under an RTL ancestor. The leading flag remains on the right and the label/region align next to it. LTR rules, PHP, JavaScript, HTML, attributes and stored settings are unchanged. Reverting the four added CSS lines rolls back the display change without a database migration. This fix is part of unreleased 1.4.0; rebuild the final ZIP and invalidate cached/minified CSS after the reviewed changes are merged.

In Chromium, before the fix, both published 1.3.2 and the preceding 1.4.0 candidate fail the 20 RTL cases and pass the 20 LTR cases. For compatibility verification, seed on the immutable 1.3.2 baseline, switch only plugin files, run browser regressions and compare stored options, both widget types, pages and registrations without reseeding. Rollback should preserve that state too. These checks do not certify every third-party theme, physical device or nested mixed-direction container.

The earlier alignment-only patch left a separately reproduced Firefox full-width region-layout defect: region text could be positioned outside the visible table in both directions, also on published 1.3.2. The subsequent table-layout refinement replaces the nested fixed table layout for new labels; the unchanged region-alignment assertions now pass in Firefox as well. Region checks remain soft assertions so other flag/name checks still run on failure. Cross-browser results and their limits are recorded in the [layout acceptance report](releases/1.4.0-table-layout.md).

## Trend arrow regressions

`trend-arrows.spec.js` exercises real shortcode and native Table widget output in full and compact widths. The guarded `browser-trends.php` fixture supplies growth, decline, unchanged and missing previous rates without writing options. Four legacy-contract cases also pass on published 1.3.2: numeric values, trend direction (including inverse quotes), existing `up`/`down` alternative text and absence of an arrow for unchanged/missing data are preserved.

The 24 presentation cases cover LTR/RTL, 1064/320/360 px viewports, normal/inverse quotes and symbols before/after values. They verify loaded same-origin SVG assets, 14 px dimensions, a 4 px gap, vertical alignment, unchanged 16 px flags, row height, contained overflow, repeated amount changes and isolation from other tables. Run them in the disposable environment with `WP_TEST_URL="http://127.0.0.1:${WP_PORT}" npm test --prefix tests/browser -- trend-arrows.spec.js`.

The production change replaces only the trend image template and adds scoped CSS plus two local SVG assets and their license. The existing PNG paths remain available. Public JavaScript still clones the same `img` element when recalculating a table; rate calculations, options, shortcodes, widgets and the 1.4.0 version are unchanged. The icons use Lucide paths with customized colours and stroke weight; provenance and license notices are shipped beside the assets in `assets/img/LICENSE-lucide.txt`.

Rollback reverts the trend image template and CSS; no database migration is needed. Purge stale HTML/CSS and regenerate optimizer bundles when installing a rebuilt 1.4.0 candidate. Passing this fixture does not establish compatibility with every theme, physical device or cache configuration.

## CI and limitations

- PHP 5.6 / 8.3 / 8.4: syntax checks for all 56 plugin PHP files, including shipped vendor code, formatter regression tests and syntax checks for the manual-update test helpers.
- WordPress 7.1.3 / PHP 8.3: isolated runtime smoke, manual-update cooldown and persisted-state regressions, saved-data formatting regressions, Chromium table-isolation, settings-label, administrative JavaScript and real manual-update regressions, saved-data preservation and HTTP availability of assets.
- Packaging: consistent version metadata and exact ZIP content parity with `plugin/trunk/`.

Older WordPress versions and the full PHP range are not covered by CI runtime tests. Browser automation currently covers Chromium only. Normalization does not change the imported release's minimum requirements.
CI has only `contents: read`, uses no SVN secrets and publishes no releases. PHPCS/WPCS and comprehensive security checks are not currently automated; see the [validation contract](../CONTRIBUTING.md#validation-and-review).
CI cleanup with `down --volumes` applies only to the disposable project with a unique name for that specific run.

## RTL numeric direction regressions

`tests/browser/rtl-numbers.spec.js` runs 66 cases against the real plugin renderers in the guarded, read-only `tests/browser-rtl.php` fixture. It covers all eight number formats, RTL/LTR, 320px/1064px widths, both symbol positions, shortcode and native widget tables/converters, badges, previous-close cells, static base amounts, input click/blur, recalculation and negative converter results. Character `Range` coordinates detect visual reordering that correct DOM strings alone cannot detect. Native input values and computed direction are checked separately. No synthetic fixture is shipped in the ZIP.

After seeding rates with the isolated smoke, run:

```sh
WP_TEST_URL="http://127.0.0.1:${WP_PORT}" npm test --prefix tests/browser -- rtl-numbers
```

The fixture is loaded only by the test Compose override. It filters formatting options for the current request without saving them and retains the page's RTL direction for labels, tables and currency selectors. It does not change the site's language. Existing table-shortcode behavior (no symbol attribute), initial previous-close symbol omission and the converter's empty-group-separator fallback are intentionally preserved.

The fix adds scoped RTL CSS for numeric cells, amount inputs, converter results and badge values. It keeps numeric runs left-to-right and isolated while retaining the previous input/badge alignment. PHP, JavaScript, calculations, format IDs, output strings, data attributes, option keys, shortcode/widget registrations and version 1.4.0 remain unchanged. No migration is required. Reverting this CSS change restores the previous display without changing saved data. Existing mobile table overflow, label alignment and mixed-direction captions are separate issues.

Before publishing the still-unreleased 1.4.0, build a new ZIP from the merged revision and purge cached/minified CSS in the release smoke; older 1.4.0 candidate ZIPs do not contain this fix. This workflow does not publish to WordPress.org.


## Percentage and visible-change regressions

Run `php tests/change-percentage.php` with PHP 5.6 or newer. The 1,157 assertions use literal expected results for growth/decline, previous-close denominators, inverse pairs, invalid/missing previous rates, small percentages, all eight format IDs, precisions 0/2/7 and amounts 1/100. It does not bootstrap WordPress or write data.

`change-percentage.spec.js` adds 14 browser scenarios using the guarded `?er-trend-test=1&changes=1` fixture. They cover shortcode/native-widget tables, LTR/RTL, 1280px/320px, decimal comma/point, precision 0/2/4/7, repeated amount changes and neighbouring table isolation. Initial PHP output and subsequent JavaScript updates must agree on percentage visibility, class and accessible label.

Percentage magnitude is `abs((current - previous) / previous * 100)` for the displayed currency direction. It has no plus/minus sign; existing Price arrows and Changes colors convey direction. A positive percentage that would round to zero is displayed as `<0.01%` (localized separator). Missing previous data and unchanged rates retain a dash.

The renderer compares the two formatted prices, not the rounded absolute difference. Equal displayed prices suppress both percentage and arrow, with an accessible below-precision explanation; crossing a rounding boundary still shows the direction. Only the edited table is updated. The percentage and source direction are independent of amount, so PHP supplies escaped metadata and translated labels for JavaScript to show/hide. Old cached markup without this metadata retains the previous input behavior and requires normal cache invalidation to receive the fix.

This intentionally corrects values previously shown in Changes and replaces signed percentages with magnitude plus direction. It preserves rate/amount formatting, saved options/rates/widgets/pages, shortcode attributes, public method signatures and the existing 14px SVG assets. There is no migration. Revert the fix to roll back; the incorrect percentage formula and rounded-direction bug then return. The version remains unreleased 1.4.0; an older release ZIP does not contain this fix and must not be mistaken for the new candidate after merge.


## Readable table layout regressions

`tests/browser/table-layout.spec.js` adds 20 scenarios using the guarded, request-local `tests/browser-layout.php` fixture. It covers shortcode and native-widget renderers, LTR/RTL, 320/900 px, compact/full widths, caption placement and source-popup interaction, first-line flag alignment, unchanged flag sizes, secondary region typography, readable wrapping, table-only horizontal scrolling, amount recalculation and complete base-row backgrounds. The grid matrix checks 256 combinations of direction, renderer, history availability, Code/Previous Close/Changes columns, title mode and header visibility; every colspan must be positive and match the visible grid.

`tests/browser/table-columns.spec.js` adds 16 scenarios for full-width tables at 320/480/900 px. It measures actual glyph bounds and column widths: only Currency receives spare width, Code and Price stay content-sized, and the Code header shares the values' leading edge. It covers both renderers, LTR/RTL, hidden Code/headers/history, unchanged displayed values across resizing, large amounts and numeric containment.

Run on a separate synthetic environment with the test Compose override:

```sh
WP_TEST_URL=http://127.0.0.1:19260 npm test --prefix tests/browser -- table-columns.spec.js table-layout.spec.js rtl-labels.spec.js trend-arrows.spec.js
```

The fixture filters options for one HTTP request only. It does not save settings, widgets, rates or pages. The full browser suite still needs its normal seeded table/admin fixtures. Tests intentionally fail on unmodified 1.3.2 and the preceding 1.4.0 snapshot; keep the immutable baseline unchanged.

Compact table captions now follow the logical start edge; full-width tables, converters and badges retain their caption behavior. Full-width tables give spare width to Currency and keep other columns content-sized; the Code header follows the same logical edge as its values. New table label classes provide a 12ch minimum, normal wrapping and non-shrinking flags; region text is smaller and regular weight. Base-row filler cells use the count of visible history columns, independently of title mode or whether headers are displayed. No public attributes, option keys, data migrations, calculations, JavaScript, translations or version metadata change. See the [acceptance report](releases/1.4.0-table-layout.md) for compatibility evidence, intentional visual differences and rollback.

## Background rate refresh

Rendering a page never calls the rate provider. Existing cached rates, source information and their original date remain visible when a refresh fails. With no current rates, all three shortcodes and both native widgets show the existing translated unavailable-data message. No frontend AJAX or polling is added, and an already open page does not replace its rates after a background update. Reload to read the newly saved data.

The existing `exchange-rates_data_update` hourly event is retained and repaired if absent. Unknown/expired successful-update time or missing current data queues one `exchange-rates_data_retry` event unless an hourly event is already due. A failed background request schedules another attempt after five minutes and preserves the last successful timestamp. A successful manual update makes a pending retry unnecessary. Activation, explicit manual updates and source changes retain their existing synchronous administrator/service behavior.

Background scheduling and workers share a per-site, five-minute lease in the non-autoloaded `exchange-rates_refresh_lock` option. A unique database insert and compare-and-swap on expiry avoid relying on persistent object-cache behavior or the `add_option()` upsert. Release checks token ownership. A killed worker therefore cannot block refresh permanently. The transient `exchange-rates_refresh_after` records retry timing separately from success metadata. Deactivation removes both cron hooks and these internal scheduling values while preserving user settings and rates. No tables or data migrations are introduced.

WP-Cron normally starts on site requests, not on a wall-clock timer. Updating a quiet site independently of visitors requires the site's system scheduler to run WordPress cron. If cron is disabled or loopback requests fail, old rates remain visible but do not become fresh automatically. Full-page/CDN caches also need their own expiry or purge policy; this change does not invalidate externally cached HTML.

Run on the disposable environment before seeding browser fixtures:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/background-refresh-wordpress.php
```

The 51 checks cover real shortcode/widget HTML, fresh/stale/empty caches, missing/zero legacy timestamps, failure and recovery, five-minute backoff, hourly scheduling, manual update coexistence, three concurrent worker processes, expired/successor lease ownership and deactivation. Child requests run in separate WordPress processes; the suite restores saved settings, rates and scheduling state. The provider is synthetic and outbound network access is blocked by the test fixture. This verifies behavior, not live-source availability.

For rollback to 1.3.2, deactivate the candidate, replace only the plugin files and reactivate 1.3.2. Deactivation clears the new internal scheduling state but keeps rates, widgets, settings and posts. The old synchronous loading behavior returns with the old code. See the [background-refresh acceptance report](releases/1.4.0-background-refresh.md) for the exact versions and results tested.
