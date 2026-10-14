# Exchange Rates

[![CI](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/workflows/ci.yml/badge.svg)](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/workflows/ci.yml)

The canonical public development repository for the **Exchange Rates** WordPress plugin.
Install the published version from the [WordPress.org Plugin Directory](https://wordpress.org/plugins/exchange-rates/).
The GitHub source archive of this entire repository is not an installable plugin ZIP.

The code in `plugin/trunk/` was imported unchanged from the official **1.3.2** release.
The plugin provides currency converter, exchange-rate table and price badge widgets and shortcodes.
See the [plugin readme](plugin/trunk/readme.txt) for features and data sources.

All repository and plugin development uses **English**. Source UI strings remain translatable through WordPress; existing translations are preserved.
Read the [development contract](CONTRIBUTING.md) before contributing. It defines WordPress conventions, DRY, security, compatibility, data integrity and review requirements. Automated agents must also read [AGENTS.md](AGENTS.md).

## Structure

```text
plugin/trunk/          The single plugin source tree, including vendor
wordpress/            Local WordPress, MariaDB and WP-CLI
scripts/              Validation, packaging and local .env generation
tests/                Isolated smoke checks with synthetic rates
docs/                 Development, release and imported source provenance
.github/workflows/    CI without WordPress SVN publishing
```

SVN `tags/`, `.svn`, WordPress core, databases and actual `.env` files are not stored in Git.
Edit code on the host in `plugin/trunk/`; containers use a read-only bind mount.

## Development quick start

Requirements: Git, Python 3.9+, Docker Engine/Desktop and Docker Compose v2 with `up --wait` support.
Run commands from the repository root:

```sh
python3 scripts/dev-env.py
docker compose --env-file wordpress/.env -f wordpress/compose.yaml up -d --wait --wait-timeout 180
docker compose --env-file wordpress/.env -f wordpress/compose.yaml run --rm --entrypoint sh cli /opt/dev/init.sh
```

Site: <http://127.0.0.1:18972>; administration: <http://127.0.0.1:18972/wp-admin/>.
The username and generated password are in the local `wordpress/.env`.
The generator does not overwrite an existing `.env`; skip that step on subsequent starts.
Normal mode fetches real rates over HTTP from the plugin's API. Synthetic test mode is enabled separately.

Stop containers while preserving the database and site files:

```sh
docker compose --env-file wordpress/.env -f wordpress/compose.yaml down
```

## Checks and installable ZIP

```sh
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
```

Lint requires PHP. The ZIP and SHA-256 are written to `dist/`; repackaging replaces only these generated files.
Output: `dist/exchange-rates.1.3.2.zip`, containing an `exchange-rates/` directory.
CI checks syntax on PHP 5.6, 8.3 and 8.4, runs smoke tests on WordPress 7.1.3 / PHP 8.3 and saves the ZIP as an artifact.
Syntax checks do not establish full runtime compatibility with all those PHP versions.

- [Development contract](CONTRIBUTING.md)
- [Local development and smoke tests](docs/development.md)
- [Packaging and release procedures](docs/release.md)
- [Source provenance and deferred CSS change](docs/normalization.md)

## License

GPL version 2 or later, as declared in the published plugin headers; see [LICENSE](LICENSE) for the GPLv2 text.
Third-party libraries retain their own licenses in `plugin/trunk/vendor/`.
