# Local development

Follow the [development contract](../CONTRIBUTING.md) for language, WordPress conventions, security and compatibility requirements.

The current candidate is **1.5.0**; use the [release status](release-status.md) for its identity and evidence. References to earlier versions below describe pinned regression baselines and the original fixes, not alternative current candidates.

## Permanent release acceptance

Follow the [three-phase release gates](acceptance/release-gates.md). The [permanent regression registry](../tests/regressions/README.md) links historical regressions to existing suites, validates coverage in CI and provides a repeatable native ZIP checker and visual comparison UI. Registered coverage and old receipts are not evidence for a new candidate.

## CSS parity checks

`tests/browser/css-parity.spec.js` compares the candidate public stylesheet against immutable Git CSS from `cb097af1d04153dd8132a6a0214642491b5c5557`, the merged 1.4.0 snapshot before CSS cleanup. Keep this reference pinned. The WordPress CI checkout includes history so the reference is available without a second source copy.

On a dedicated, seeded test environment, run:

```sh
WP_TEST_URL=http://127.0.0.1:19249 npm --prefix tests/browser test -- css-parity.spec.js
```

The suite covers tables, native widgets, converters, badges, flags/regions, trend arrows, expanded source information, input edits, hover/focus, currency swaps, LTR/RTL, 320/1064 px, and custom theme rules/properties. In default `release` mode, only a candidate script request with `ver=1.5.0` is routed to immutable published 1.4.2 JavaScript from `4555660d7eb50a5c90e76828e02950eb5f60801a`; historical installations keep their own scripts. The test reconstructs legacy table markup and removes candidate layout markers before comparison, preventing 1.5.0 table-fit observers from mutating the reference DOM. Every comparison still requires identical computed styles, values and geometry for all plugin elements. Current table-fit/layout/history-input/modal tests separately retain candidate JavaScript. Expected screenshots are generated from the pinned reference CSS in the same DOM/state, stored under ignored `test-results/`, and compared with Playwright's image comparator (`threshold: 0.05`, `maxDiffPixels: 0`). The small color/antialiasing allowance addresses observed Chromium SVG-edge rasterization differences on identical CSS; computed-style and geometry comparisons have no tolerance. A deliberate height mutation verifies that the comparison detects a visible regression.

The immutable reference receives only the documented converter box-sizing correction and the intentional visual corrections described in [the focused acceptance report](reviews/visual-regressions-1.4.1.md): table body cell centering, block-level amount inputs, wrapping captions, bounded wrapping badges and a reserved select-arrow lane. The accepted 1.5.0 visible-scrollbar behavior adds four explicit reference rules for the table container, scrollbar, thumb and track. These corrections retain complete computed-style and pixel comparisons for every other property. The dedicated visual regression tests independently fail against the unmodified plugin; do not update the reference commit or hide differences with property exclusions.

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
Page rendering uses saved rates without contacting the rate provider. Expired or missing data queues a WP-Cron refresh; a recurring 30-minute event remains scheduled. Because automatic WP-Cron is disabled in this local setup, run its cron event manually:

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

For a pinned prerelease core in its own project, use the [WordPress 7.2 Beta 1 lab](compatibility/wordpress-7.2-beta1.md).

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

The eight Chromium tests cover a single table, mixed shortcode/widget tables in both DOM orders, repeated edits, a table without an amount input, independent precision/separators/symbol position, current and previous-close values, trend images, click/blur and the existing amount-one fallback for empty, zero or negative input. Snapshots protect the other table inputs and values, badge and converter. Captions are excluded because WordPress asynchronously replaces their emoji text with images. The suite preserves existing rendering behavior, including the lack of a symbol on the initial previous-close value. Malformed input is covered separately below.

The runner accepts only loopback HTTP URLs and blocks browser requests to other origins. Use only a disposable test site. Retries are disabled; failed runs retain screenshots and traces in the ignored `tests/browser/test-results/` directory. CI uploads those diagnostics on failure.

The fix scopes settings and rate-cell updates to the input's containing table. It changes two selectors in `assets/js/public/common.js`; formulas, markup, public attributes, saved settings and version metadata are unchanged. There is no migration. Reverting that fix restores the old behavior without a database rollback. No WordPress.org release is published by this workflow.

## Amount input validation and formatting

`tests/browser/amount-input.spec.js` exercises converter and table inputs through actual WordPress shortcodes and native widgets. Run it with the same isolated environment and `WP_TEST_URL` used above:

```sh
npm test --prefix tests/browser -- amount-input.spec.js
```

The 76 tests cover invalid input and recovery, independent instances, swaps and blur, cached table trend images, all eight number formats and precision 0–7, negative grouping, decimal/scientific notation, overflow and underflow, LTR/RTL, and 320/1064 px viewports. The read-only bidi fixture accepts bounded `decimals` and `changes` parameters; it does not persist these overrides.

Both inputs accept complete signed decimal numbers and scientific notation with optional surrounding whitespace. They reject partial numbers (`12abc`), comma/group separators, hexadecimal/binary notation, non-finite values and nonzero amounts that underflow to zero. Malformed input or an unrepresentable calculation displays an en dash without a currency symbol and sets `aria-invalid`; valid input clears it. Invalid table input is retained on blur so the user can correct it. This deliberately replaces the old plausible-looking amount-one result for malformed table input. Empty, zero and negative table inputs still use one; the converter still supports zero and negative amounts.

The shared formatter retains existing rounding and trims trailing fractional zeros, groups only the unsigned integer digits, and expands scientific notation into decimal output. Nonzero values that round to zero display a localized bound, such as `<0.0001` or `>-0.0001` at precision four; actual zero remains `0`. PHP rate rendering uses the same display policy. The converter now transmits meaningful zero precision and an empty thousands separator instead of silently using JavaScript defaults. Old cached converter HTML must be refreshed to receive those attributes.

No options, rates, public attributes or widget identifiers are migrated. Rollback replaces the plugin files and restores the prior behavior, including the input defects. See the [focused acceptance report](reviews/amount-input-1.4.2.md) for the exact evidence and limits.

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

Run `docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/concurrent-source-wordpress.php` only on the disposable synthetic site. Separate PHP processes overlap an old recurring/direct request with a real settings save, in both source directions and with successful or failed responses. The suite also switches sources immediately before option filters and SQL writes, covers an absent rate cache, an obsolete alloptions cache and unrelated preference changes, then verifies recovery and restoration from fresh requests.

The updater conditionally saves rates and service metadata against the serialized settings captured before HTTP. A changed snapshot rejects the obsolete write instead of reverting user fields. Each SQL write checks its predicate atomically; clearing an option cache alone is not a concurrency guard. The request source and write values come from that database snapshot, not a potentially stale option cache. Existing recurring scheduling, five-minute retry, manual refresh authorization, option keys, sanitizer/veto hooks, successful-write notifications and widget/shortcode contracts remain. See [the scoped acceptance report](releases/1.4.0-concurrent-source.md).

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

The production fix overrides the first table header cell's alignment only under an RTL ancestor. The leading flag remains on the right and the label/region align next to it. LTR rules, PHP, JavaScript, HTML, attributes and stored settings are unchanged. Reverting the four added CSS lines rolls back the display change without a database migration. This fix entered 1.4.0 and is retained in 1.4.1; invalidate cached/minified CSS when updating a site through a separately authorized operation.

In Chromium, before the fix, both published 1.3.2 and the preceding 1.4.0 candidate fail the 20 RTL cases and pass the 20 LTR cases. For compatibility verification, seed on the immutable 1.3.2 baseline, switch only plugin files, run browser regressions and compare stored options, both widget types, pages and registrations without reseeding. Rollback should preserve that state too. These checks do not certify every third-party theme, physical device or nested mixed-direction container.

The earlier alignment-only patch left a separately reproduced Firefox full-width region-layout defect: region text could be positioned outside the visible table in both directions, also on published 1.3.2. The subsequent table-layout refinement replaces the nested fixed table layout for new labels; the unchanged region-alignment assertions now pass in Firefox as well. Region checks remain soft assertions so other flag/name checks still run on failure. Cross-browser results and their limits are recorded in the [layout acceptance report](releases/1.4.0-table-layout.md).

## Trend arrow regressions

`trend-arrows.spec.js` exercises real shortcode and native Table widget output in full and compact widths. The guarded `browser-trends.php` fixture supplies growth, decline, unchanged and missing previous rates without writing options. Four legacy-contract cases also pass on published 1.3.2: numeric values, trend direction (including inverse quotes), existing `up`/`down` alternative text and absence of an arrow for unchanged/missing data are preserved.

The 24 presentation cases cover LTR/RTL, 1064/320/360 px viewports, normal/inverse quotes and symbols before/after values. They verify loaded same-origin SVG assets, 14 px dimensions, a 4 px gap, vertical alignment, unchanged 16 px flags, row height, contained overflow, repeated amount changes and isolation from other tables. Run them in the disposable environment with `WP_TEST_URL="http://127.0.0.1:${WP_PORT}" npm test --prefix tests/browser -- trend-arrows.spec.js`.

The production change replaces only the trend image template and adds scoped CSS plus two local SVG assets and their license. The existing PNG paths remain available. Public JavaScript still clones the same `img` element when recalculating a table; rate calculations, options, shortcodes, widgets and the 1.4.0 version are unchanged. The icons use Lucide paths with customized colours and stroke weight; provenance and license notices are shipped beside the assets in `assets/img/LICENSE-lucide.txt`.

Rollback reverts the trend image template and CSS; no database migration is needed. Purge stale HTML/CSS and regenerate optimizer bundles when installing a rebuilt 1.4.0 candidate. Passing this fixture does not establish compatibility with every theme, physical device or cache configuration.

## CI and limitations

- PHP 5.6 / 8.3 / 8.4: syntax checks for all 56 plugin PHP files, including shipped vendor code, formatting, payload, percentage and version metadata regression tests, and syntax checks for WordPress test helpers.
- WordPress 7.1.3 / PHP 8.3: isolated runtime smoke, manual-update cooldown and persisted-state regressions, saved-data formatting regressions, Chromium table-isolation, settings-label, administrative JavaScript and real manual-update regressions, saved-data preservation and HTTP availability of assets.
- Packaging: consistent version metadata and exact ZIP content parity with `plugin/trunk/`.

The WordPress job has a 30-minute limit covering dependency/browser installation, integration checks and the serial Chromium suite. The previous 20-minute budget could cancel passing tests after slow runner setup; a timeout is not a successful regression result and requires a completed rerun.

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

The original 1.4.0 acceptance required rebuilding the ZIP after this fix and purging cached/minified CSS in the release smoke. Earlier candidate ZIPs did not contain it. Use the current 1.4.1 identity above for new verification; this workflow does not publish to WordPress.org.


## Widget version diagnostics (1.4.1)

The existing Info caption displays the escaped `Plugin::VERSION` as plain text after the Exchange Rates link and before the rocket. Table, converter and badge markup carries `data-exchange-rates-version` on its component root. Error output carries the same attribute on the existing plugin-name element. All three shortcodes and both native widgets use the server constant; no shortcode or saved widget setting controls this value. Existing credits, links and disclaimers are unchanged.

Run the focused checks in the isolated synthetic-rate environment:

```sh
php tests/version.php
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/version-wordpress.php
WP_TEST_URL="http://127.0.0.1:${WP_PORT}" npm test --prefix tests/browser -- version.spec.js
```

The standalone test substitutes an HTML-sensitive version to prove dynamic output and context escaping. The WordPress test covers real shortcodes, native-widget sanitization, multiple instances, unavailable rates, unsupported pairs and unchanged saved settings/rates/widgets. Four Chromium cases cover LTR/RTL at 320/1064 px, opening Info, amount changes, swapping converter currencies and preservation of the original link. CI includes these checks in its existing unit, WordPress and browser jobs.

Version metadata identifies the code that rendered the HTML. A page cache can retain an earlier version until that page is regenerated; inspecting this attribute does not bypass caching. No additional requests, options or migrations are introduced. Rollback reverts the caption and attribute additions; stored data needs no changes.

## Percentage and visible-change regressions

Run `php tests/change-percentage.php` with PHP 5.6 or newer. The 1,157 assertions use literal expected results for growth/decline, previous-close denominators, inverse pairs, invalid/missing previous rates, small percentages, all eight format IDs, precisions 0/2/7 and amounts 1/100. It does not bootstrap WordPress or write data.

`php tests/percentage.php` adds 110 assertions through the public `Currency` constructor and the actual `RatesData` reader. These cover numeric strings, localized unsigned percentages, sub-precision direction, both changing legs of a cross-currency pair, both bank-source orientations, amount independence, and invalid current/previous values on either leg (including non-finite values). It uses only in-memory option fixtures and complements the existing reflection-based calculation suite. CI runs both suites on PHP 5.6, 8.3 and 8.4.

Run the rendering companion only in the separate synthetic WordPress environment:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/percentage-wordpress.php
```

Its 30 assertions verify unsigned Changes values, translated direction tooltips, accessible labels, client percentage/direction metadata, current/previous prices, mixed arrows and unchanged stored settings/rates. Temporary option filters supply the fixture without writing either option. CI runs this alongside the existing smoke and browser regressions. These additional tests, CI steps and documentation introduce no plugin-code, version, public-contract or database changes; reverting them only removes this extra coverage.

`change-percentage.spec.js` adds 14 browser scenarios using the guarded `?er-trend-test=1&changes=1` fixture. They cover shortcode/native-widget tables, LTR/RTL, 1280px/320px, decimal comma/point, precision 0/2/4/7, repeated amount changes and neighbouring table isolation. Initial PHP output and subsequent JavaScript updates must agree on percentage visibility, class and accessible label.

Percentage magnitude is `abs((current - previous) / previous * 100)` for the displayed currency direction. It has no plus/minus sign; existing Price arrows and Changes colors convey direction. A positive percentage that would round to zero is displayed as `<0.01%` (localized separator). Missing previous data and unchanged rates retain a dash.

The renderer compares the two formatted prices, not the rounded absolute difference. Equal displayed prices suppress both percentage and arrow, with an accessible below-precision explanation; crossing a rounding boundary still shows the direction. Only the edited table is updated. The percentage and source direction are independent of amount, so PHP supplies escaped metadata and translated labels for JavaScript to show/hide. Old cached markup without this metadata retains the previous input behavior and requires normal cache invalidation to receive the fix.

This intentionally corrects values previously shown in Changes and replaces signed percentages with magnitude plus direction. It preserves rate/amount formatting, saved options/rates/widgets/pages, shortcode attributes, public method signatures and the existing 14px SVG assets. There is no migration. Revert the fix to roll back; the incorrect percentage formula and rounded-direction bug then return. The correction entered 1.4.0 and is retained in 1.4.1. The later constructor/rendering regressions add coverage without changing that calculation.


## Readable table layout regressions

`tests/browser/table-layout.spec.js` adds 20 scenarios using the guarded, request-local `tests/browser-layout.php` fixture. It covers shortcode and native-widget renderers, LTR/RTL, 320/900 px, compact/full widths, caption placement and source-popup interaction, flag centering on the currency name, unchanged flag sizes, secondary region typography, readable wrapping, table-only horizontal scrolling, amount recalculation and complete base-row backgrounds. The grid matrix checks 256 combinations of direction, renderer, history availability, Code/Previous Close/Changes columns, title mode and header visibility; every colspan must be positive and match the visible grid.

`tests/browser/table-columns.spec.js` adds 16 scenarios for full-width tables at 320/480/900 px. It measures actual glyph bounds and column widths: only Currency receives spare width, Code and Price stay content-sized, and the Code header shares the values' leading edge. It covers both renderers, LTR/RTL, hidden Code/headers/history, unchanged displayed values across resizing, large amounts and numeric containment.

Run on a separate synthetic environment with the test Compose override:

```sh
WP_TEST_URL=http://127.0.0.1:19260 npm test --prefix tests/browser -- table-columns.spec.js table-layout.spec.js rtl-labels.spec.js trend-arrows.spec.js
```

The fixture filters options for one HTTP request only. It does not save settings, widgets, rates or pages. The full browser suite still needs its normal seeded table/admin fixtures. Tests intentionally fail on unmodified 1.3.2 and the preceding 1.4.0 snapshot; keep the immutable baseline unchanged.

Compact table captions now follow the logical start edge; full-width tables, converters and badges retain their caption behavior. Full-width tables give spare width to Currency and keep other columns content-sized; the Code header follows the same logical edge as its values. New table label classes provide a 12ch minimum, normal wrapping and non-shrinking flags; region text is smaller and regular weight. Base-row filler cells use the count of visible history columns, independently of title mode or whether headers are displayed. No public attributes, option keys, data migrations, calculations, JavaScript, translations or version metadata change. See the [acceptance report](releases/1.4.0-table-layout.md) for compatibility evidence, intentional visual differences and rollback.

## Background rate refresh

Rendering a page never calls the rate provider. Existing cached rates, source information and their original date remain visible when a refresh fails. With no current rates, all three shortcodes and both native widgets show the existing translated unavailable-data message. No frontend AJAX or polling is added, and an already open page does not replace its rates after a background update. Reload to read the newly saved data.

The existing `exchange-rates_data_update` hook runs every 30 minutes and is repaired if absent; legacy hourly events migrate on bootstrap. Unknown/expired successful-update time or missing current data queues one `exchange-rates_data_retry` event unless a recurring event is already due. A failed background request schedules another attempt after five minutes and preserves the last successful timestamp. A successful manual update makes a pending retry unnecessary. WordPress removes each due retry before invoking its callback; a queued retry then checks freshness and exits without another provider request. Refresh callbacks must not clear other retry events, because the native cron runner may already hold them in its ready-event snapshot. Activation, explicit manual updates and source changes retain their existing synchronous administrator/service behavior.

Background scheduling and workers share a per-site, five-minute lease in the non-autoloaded `exchange-rates_refresh_lock` option. A unique database insert and compare-and-swap on expiry avoid relying on persistent object-cache behavior or the `add_option()` upsert. Release checks token ownership. A killed worker therefore cannot block refresh permanently. The transient `exchange-rates_refresh_after` records retry timing separately from success metadata. Deactivation removes both cron hooks and these internal scheduling values while preserving user settings and rates. No tables or data migrations are introduced.

WP-Cron normally starts on site requests, not on a wall-clock timer. Updating a quiet site independently of visitors requires the site's system scheduler to run WordPress cron. If cron is disabled or loopback requests fail, old rates remain visible but do not become fresh automatically. Full-page/CDN caches also need their own expiry or purge policy; this change does not invalidate externally cached HTML.

Run on the disposable environment before seeding browser fixtures:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/background-refresh-wordpress.php
```

The 51 checks cover real shortcode/widget HTML, fresh/stale/empty caches, missing/zero legacy timestamps, failure and recovery, five-minute backoff, 30-minute scheduling, manual update coexistence, three concurrent worker processes, expired/successor lease ownership and deactivation. Child requests run in separate WordPress processes; the suite restores saved settings, rates and scheduling state. The provider is synthetic and outbound network access is blocked by the test fixture. This verifies behavior, not live-source availability.

`tests/native-cron-retry-wordpress.php` runs the real `wp-cron.php` in child CLI processes, checking both orders of overlapping recurring/retry events, multiple overdue retries with fresh data, a future retry, and a failed provider. Its 38 assertions cover core diagnostics, event ownership, request counts, persistence, backoff and lease release. It snapshots and restores the disposable database options. Run it with the same `wp eval-file` command as the background suite.

On PHP 5.6, run the interval suite with `wp --skip-plugins=exchange-rates eval-file /opt/tests/refresh-interval-wordpress.php`. The suite loads the plugin after its frozen clock; loading the plugin earlier can cache the native `time()` function on that PHP version and make the 1799-second assertion depend on elapsed test time.

For rollback to 1.3.2, deactivate the candidate, replace only the plugin files and reactivate 1.3.2. Deactivation clears the new internal scheduling state but keeps rates, widgets, settings and posts. The old synchronous loading behavior returns with the old code. See the [background-refresh acceptance report](releases/1.4.0-background-refresh.md) for the exact versions and results tested.


## Converter control box sizing

`tests/browser/converter-box-sizing.spec.js` checks shortcode and native-widget converters in LTR/RTL at 320, 390 and 1064 CSS pixels. Input, swap button and selects retain the existing 40px height with a consistent border box; the site's 44px minimum remains effective. The suite also checks control containment, icon alignment, unrelated inputs, amount changes, swaps, currency selection, zero, neighbouring converters and the original caption/link contract.

Run after seeding the disposable synthetic WordPress environment:

```sh
WP_TEST_URL=http://127.0.0.1:18973 npm --prefix tests/browser test -- converter-box-sizing.spec.js
```

CI uses deterministic bare, Astra-reset and site-minimum CSS patterns. To test captured full theme styles without external network dependencies, set `CONVERTER_THEME_CSS_DIR` to a local directory containing `astra-main.css`, `astra-settings.css`, `wp-global.css`, `fonts-local.css`, `yuri-page.css` and the referenced `font-N.ttf` files. These optional local captures are not shipped or committed. This does not replace a check with the actual theme active in WordPress.

For the functional 1.3.2 baseline, install its immutable plugin export on a separate disposable site, then run with `CONVERTER_REFERENCE=legacy` and `--grep 'legacy contract'`. The sizing regression is intentionally expected to fail on unmodified 1.3.2 and the preceding 1.4.0 candidate. CSS parity normalizes only this known reference defect; all remaining computed styles and geometry are compared exactly, with the existing screenshot antialiasing tolerance.

The production change only adds `box-sizing: border-box` to converter inputs, selects and swap buttons. No font, padding, height, SVG, PHP, JavaScript, saved data or public interface changes. Removing the rule restores the old box model without a migration. See [the acceptance report](releases/1.4.0-converter-box-sizing.md) for tested versions and limitations.

## Source-directory validation

Run `tests/source-directory-wordpress.php` with `wp eval-file` only in the isolated test environment. It covers invalid/missing timestamps, malformed rows and responses, HTTP failures, poisoned legacy caches, recovery, unchanged valid HTML, and retention across PHP requests. Rate payloads and user settings must remain byte-for-byte unchanged.

`exchange-rates_sources` keeps the existing response envelope and one-hour success TTL. A valid legacy cache is copied unchanged into the non-autoloaded `exchange-rates_sources_last_good` option before expiry. Failed refreshes retain that directory, report an explicit error, and cache the failure for five minutes. There are no additional frontend requests or cron events. A fresh install with no valid directory displays the error without inventing rows or dates. Data already evicted before this version first reads it cannot be recovered.

The backup is retained on deactivation and deleted on uninstall. Reverting the code requires no settings/widget migration; 1.3.2 ignores the additional backup option. The rate updater, shortcode attributes, widget IDs, rate cache, and source selection are unchanged. Fault-injection tests use synthetic responses and do not prove provider availability.

## Thirty-minute refresh (1.4.1)

`Cron\UpdateRates::REFRESH_INTERVAL` defines both the 1800-second recurrence and
cache expiry (`age >= 1800`). The public `exchange-rates_data_update` hook and
`run_hourly()` callback remain compatible, including explicit source arguments.
The source-directory cache remains hourly; it is not the rate-refresh interval.
Schedule metadata is available during bootstrap; its display label is translated
from `init` onwards to avoid premature WordPress translation loading.

On ordinary plugin bootstrap, the registered `exchange-rates_30_minutes` schedule
replaces legacy default events under the existing per-site lease. The migration
writes the replacement before removing obsolete occurrences, preserves events
with explicit arguments and never postpones an earlier due time. It does not
align sites to half-hour wall-clock boundaries. A rejected schedule preserves
legacy work; a rejected removal returns failure and a later bootstrap retries.
Cron storage is not transactional across other plugins/core workers: partial
cleanup may temporarily leave more than one event until a successful retry.

Run only in the disposable synthetic environment:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/refresh-interval-wordpress.php
```

The suite covers expiry at 1799/1800/1801 seconds (and no expiry at 899/900/901), upgrade without reactivation,
legacy duplicates, idempotence, explicit-source events, schedule/removal failures,
pending retries, busy/expired leases, concurrent bootstraps and deactivation.
Existing background, manual-update, payload and source-race suites remain required.
WP-Cron timing depends on site visits or an external cron runner. Page HTML may
also remain cached after a successful rate refresh; this change does not purge
third-party page caches or update already-open browser pages.

## Hybrid table flags (1.4.1)

The shared table label renderer handles regular and base rows. With a country
and a flag smaller than 40 CSS pixels, the flag and currency name form the first
row; the country spans the group below, aligned to its logical start edge. At
40 pixels or above, the flag centers beside the combined name/country block.
Without a country, the flag always centers on the name, with no empty second row.
Without a flag, no slot or gap is reserved. Direction follows LTR/RTL; no client
measurements or new settings are required. Rectangular flags retain a 4:3 ratio.
New CSS is scoped to new markup; cached legacy markup keeps its existing rules.

`tests/browser/flag-hybrid.spec.js` covers 16/24/32/39/40/41/64 px, all five flag
modes, country on/off, 16/22 px text, LTR/RTL, 320/1064 px, compact/full tables,
shortcodes/native widgets, base rows and long wrapped names. Geometry assertions
check the exact vertical center, country edge, absence of empty rows/flag slots,
unchanged image size and document overflow. Existing CSS parity reconstructs
legacy labels and still compares immutable CSS, computed styles and pixels.

## Focused visual regressions (1.4.1 follow-up)

`tests/browser-visual.php` is mounted only by the disposable test override. It
renders real WordPress shortcodes and native widgets with request-local synthetic
settings and rates. Its generic theme baseline reproduces cross-cell baseline
alignment without copying plugin HTML or saving settings. It also covers large
badge values and long names, inherited 16/22px text, and default/custom/empty
converter titles. The Ukrainian title check loads the shipped MO catalog.

After starting the isolated environment above, run:

```sh
WP_TEST_URL=http://127.0.0.1:18973 npm --prefix tests/browser test -- visual-regressions.spec.js
```

The 27 tests cover LTR/RTL, 320/900px viewports, 16/32/64px flags, regions on/off,
static/editable base amounts, all row cells, badge content and flag dimensions,
caption text bounds, select arrow spacing, unchanged control heights, currency
swaps and explicit saved titles. Keep before/after captures on the same runtime,
viewport, font, fixture and data. For concurrent runtime runs, pass distinct
Playwright `--output` directories so one runner cannot remove another's traces.

Two title-filter regressions exercise `shortcode_atts_exchange-rates_currency-converter`
with an omitted `title` in English and Ukrainian. A custom or empty title returned
by the filter must survive sanitization and rendering; explicit saved titles and
the native widget remain unchanged. The translated default is supplied to
`shortcode_atts()` before the filter runs, rather than replacing its result.

The change deliberately allows badge values and source captions to occupy another
line when space is insufficient. It changes no rates, stored options, shortcode
attributes, widget identifiers or version metadata. Table border behavior remains
unchanged and is deferred separately. See the [acceptance report](reviews/visual-regressions-1.4.1.md)
for the exact payload identity, regression results, upgrade and code rollback.

## Small values and cancellation during deactivation

Run the focused checks against the disposable test Compose project:

```sh
set -euo pipefail
php tests/small-values.php
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/deactivation-cancellation-wordpress.php
WP_TEST_URL=http://127.0.0.1:18973 npm --prefix tests/browser test -- small-values.spec.js amount-input.spec.js
```

The cancellation suite launches independent WordPress processes and restores its
original synthetic state. Do not run it concurrently with browser or other
state-mutating tests on the same database. Different disposable runtime projects
can run independently.

For a manual visual check, open `/?er-small-test=1&precision=4` on the test site.
The fixture supplies 1 USD = 0.00001234 BTC without saving rates. Both tables
must show `<0.0001` initially and after typing `2`, then `1`. Precision seven
must show `0.0000123` initially and after restoring amount `1`. At precision four,
in either converter, `-1` must show `>-0.0001`,
zero must show `0`, and invalid input must show an en dash and recover after
valid input. The fixture is mounted only by the test Compose override.

See [the focused review](reviews/remaining-fixes-1.4.2.md) for intentional display
changes, the cancellation generation, compatibility boundaries and rollback.

## Explicit table zero and converter control names

An explicit numeric zero entered in an editable table now produces zero current
and previous values. Signed zero and zero scientific notation follow the same
rule; blur and click-without-edit preserve the accepted zero. Empty/whitespace
and negative table input retain the existing amount-one fallback. Invalid input,
rounding, tiny-value bounds and positive amounts keep their existing behavior.
This intentionally supersedes the zero-input policy described in older reviews.

Converter amount, source-currency and target-currency fields and the swap button
receive accessible names from escaped, translated metadata rendered by PHP.
English, Ukrainian and Bulgarian catalogs include the new currency/swap labels;
the existing Amount translation is reused. Both shortcodes and native widgets
use the same renderer. There are no visible labels or CSS changes. Old cached
HTML without the new metadata still calculates and swaps; accessible names
require refreshed HTML. The public script depends on jQuery without the unused
jQuery UI Autocomplete dependency. Conditional asset loading remains deferred.

Captions, their link destinations/attributes, dates, disclosure behavior and the
disclaimer are unchanged. No settings, widget, rate-cache or content migration
is required. Reverting the patch restores the previous zero-input behavior and
dependencies without changing stored data.

Run only against the isolated synthetic WordPress environment described above:

```sh
docker compose --env-file wordpress/.env run --rm cli eval-file /opt/tests/converter-controls-wordpress.php
WP_TEST_URL=http://127.0.0.1:18973 npm --prefix tests/browser test -- amount-input.spec.js converter-controls.spec.js table-isolation.spec.js converter-box-sizing.spec.js small-values.spec.js
```

The new WordPress suite verifies translated and escaped label metadata after
native-widget sanitization, dependency registration and unchanged stored state.
Browser coverage checks accessible names and keyboard swapping in three locales,
LTR/RTL at 320/1064 px, zero variants, blur/recovery, independent instances,
unchanged captions and the absence of jQuery UI requests. Caption comparisons
normalize only WordPress's asynchronous emoji-image replacement back to its
equivalent character. Existing localization, version and CSS-parity suites
remain applicable; these checks do not establish full screen-reader conformance
or authorize a release.

See the [phase-one acceptance](reviews/technical-controls-phase1.md) for the exact
tested package, runtime results, preserved-state comparison and limitations.

## Accumulated release acceptance

Use the [regression collection](../tests/regressions/README.md) for the maintained unit/WordPress runner, native historical upgrade and rollback matrix, and installed-package Safari/Firefox harness. Keep new cases in the permanent registry and preserve failed receipts. The [release gates](acceptance/release-gates.md) distinguish automated checks, human visual approval, public-site reproductions and actual LocalWP acceptance.

### Actual LocalWP acceptance

Create a new disposable `ER-150-Acceptance-*` site through the installed LocalWP application or its official `localwp new` command. Keep existing sites and application-wide preferences unchanged. Use localhost routing; this harness rejects non-loopback sites. Place the reviewed ZIPs as `baseline.zip` and `candidate.zip` inside a new ignored evidence directory's `packages/` folder before preparing the site.

```sh
python3 scripts/localwp-acceptance.py prepare --site ER-150-Acceptance-20261028 --evidence dist/acceptance-1.5.0-localwp-20261028
python3 scripts/localwp-acceptance.py baseline --site ER-150-Acceptance-20261028 --evidence dist/acceptance-1.5.0-localwp-20261028
# Run /?er_localwp=runner&stage=baseline in the browser and wait for saved results.
python3 scripts/localwp-acceptance.py candidate --site ER-150-Acceptance-20261028 --evidence dist/acceptance-1.5.0-localwp-20261028
# Run the candidate browser stage before replacing the package again.
python3 scripts/localwp-acceptance.py rollback --site ER-150-Acceptance-20261028 --evidence dist/acceptance-1.5.0-localwp-20261028
# Run the rollback browser stage, then restore the candidate for review.
python3 scripts/localwp-acceptance.py restored --site ER-150-Acceptance-20261028 --evidence dist/acceptance-1.5.0-localwp-20261028
python3 scripts/localwp-acceptance.py lifecycle --site ER-150-Acceptance-20261028 --evidence dist/acceptance-1.5.0-localwp-20261028
# Run the restored browser stage, then collect its reports.
python3 scripts/localwp-acceptance.py collect --site ER-150-Acceptance-20261028 --evidence dist/acceptance-1.5.0-localwp-20261028
```

The commands above describe the recorded run; use a **new site and evidence path** for another run. Preparation and seeding deliberately refuse to overwrite prior fixtures. The lifecycle test runs only after the byte-identical update/rollback check: activation and cron normally advance the successful-update timestamp. Do not rerun the strict pre-refresh raw-state comparison after that intentional refresh and mistake the expected timestamp change for data loss.

The browser runner uses saved options and content, with synthetic outbound HTTP only. It stores reports inside the dedicated site's `wp-content/localwp-acceptance`; `collect` copies them into ignored evidence. Capture screenshots through the browser, then use `scripts/localwp-report.py --evidence PATH --output LOCAL_REPORT_DIRECTORY` with a Python runtime containing Pillow to assemble the comparison. Keep environment versions and real-keyboard observations with that evidence. See the [phase 3 receipt](reviews/1.5.0-phase3-localwp.md) for the exact tested artifact and remaining release gates.

For the [WordPress 7.2 Beta 2 matrix](reviews/1.5.0-phase3-wp72-beta2.md), create a separate LocalWP site and update its core from the official pinned ZIP **before** running `prepare` (which blocks outbound HTTP). Use `localwp cli SITE_ID -- core update /absolute/path/wordpress-7.2-beta2.zip --version=7.2-beta2 --force`, then `core update-db` and `core version`. Only target the newly created disposable site. Store the original archive under `core/` and a `core.json` object with `version`, `sha256`, `source` and `announcement`. The report generator verifies all archive core files outside `wp-content` against the installed site. Run the same plugin update/rollback/browser sequence in a new evidence directory; do not replace the stable-site evidence or change release metadata based on a beta result.
