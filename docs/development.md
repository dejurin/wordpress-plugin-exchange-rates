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

## CI and limitations

- PHP 5.6 / 8.3 / 8.4: syntax checks for all 56 plugin PHP files, including shipped vendor code.
- WordPress 7.1.3 / PHP 8.3: isolated runtime smoke and HTTP availability of assets.
- Packaging: consistent version metadata and exact ZIP content parity with `plugin/trunk/`.

Older WordPress versions and the full PHP range are not covered by runtime tests. Normalization does not change the imported release's minimum requirements.
CI has only `contents: read`, uses no SVN secrets and publishes no releases. PHPCS/WPCS and comprehensive security checks are not currently automated; see the [validation contract](../CONTRIBUTING.md#validation-and-review).
CI cleanup with `down --volumes` applies only to the disposable project with a unique name for that specific run.
