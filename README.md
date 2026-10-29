# Exchange Rates

[![CI on main](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/workflows/ci.yml/badge.svg?branch=main&event=push)](https://github.com/dejurin/wordpress-plugin-exchange-rates/actions/workflows/ci.yml?query=branch%3Amain)
[![Published plugin version](https://img.shields.io/wordpress/plugin/v/exchange-rates?label=WordPress.org&logo=wordpress)](https://wordpress.org/plugins/exchange-rates/)
[![Requires WordPress 5.6 or later](https://img.shields.io/badge/WordPress-5.6%2B-21759B?logo=wordpress&logoColor=white)](plugin/trunk/readme.txt)
[![Requires PHP 5.6 or later](https://img.shields.io/badge/PHP-5.6%2B-777BB4?logo=php&logoColor=white)](plugin/trunk/Install.php)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)](LICENSE)

**Currency conversion, exchange-rate tables and price badges for WordPress.**

Add currency tools to posts, pages and widget areas with configurable widgets and shortcodes. Choose a data source, customize the appearance and display exchange rates without an API key.

[WordPress.org](https://wordpress.org/plugins/exchange-rates/) · [Live demo](https://yuri.ws/exchange-rates/) · [Installation](#installation) · [Development](#development-quick-start) · [Support](#support-and-useful-links)

## Current development version: 1.5.0

Version 1.5.0 adds compact widget footers, a shared data-details dialog, visitor-local footer dates, keyboard and screen-reader improvements, narrow historical tables and missing-history input recovery. Existing settings, widgets and shortcodes retain their contracts. This is an unpublished development candidate; its final commit, PR review, CI and remaining visual acceptance are not yet recorded.

The current ZIP has SHA-256 `5a812edadb81e9947e6eeefd0efcdb07496f3369d5a7e56fccf5f46d66e365c7` (937 files). It restores the published 1.4.2 modal labels and order: **Check:** (`put_time`), then **Latest change:** (`local_time`), with timestamp values and explanatory titles unchanged. Focused native Safari/Firefox checks passed, as did four LocalWP browser runs on WordPress 7.1.3 and 7.2 Beta 2. VoiceOver was excluded from this rerun at the maintainer's request. See [release status](docs/release-status.md) and the [current verification receipt](docs/reviews/1.5.0-final-gates.md).

Official Plugin Check 2.1.0 reported **9 errors and 22 warnings**, compared with 4 errors and 22 warnings in 1.4.2. Manual review records analyzer limitations and inherited warnings; **two external emoji SVG findings remain open**. This is not a clean Plugin Check pass. See the [checker receipt](docs/reviews/1.5.0-plugin-check.md).

The full browser/VoiceOver/LocalWP matrix belongs to `f511fa42…48f4`; the intermediate `bfc05332…ba91` package changed only source gettext metadata, as recorded in the [catalog refresh receipt](docs/reviews/1.5.0-release-docs.md). Neither earlier receipt represents a new full-matrix run of the current ZIP. The [1.4.2 candidate receipt](docs/releases/1.4.2-final-candidate.md) remains historical evidence, not the current 1.5.0 CI result.

The [roadmap](docs/roadmaps/README.md) assigns additional accessibility work to 1.5.6 and Gutenberg blocks to 1.6.0. These plans do not make a WCAG/EAA certification or publication claim.

## Features

- **Currency converter:** let visitors convert amounts between currencies.
- **Exchange-rate tables:** display selected currencies, with optional previous-close and change columns when the source supplies that data.
- **Price badges:** show an amount in multiple currencies inside a post or page.
- **Widgets and shortcodes:** use widget areas or embed tools directly in content.
- **Configurable display:** adjust currencies, decimal places, flags, colors and borders.
- **Multiple data sources:** retrieve rates through the [data service](https://api-bank.fex.to/), with thirty-minute updates scheduled through WordPress Cron.

Available currencies and update freshness depend on the selected source and successful refreshes. See the [plugin readme](plugin/trunk/readme.txt) for source details and the changelog.

## Installation

### From the WordPress dashboard

1. Open **Plugins → Add New Plugin** and search for **Exchange Rates**. Use the [official listing](https://wordpress.org/plugins/exchange-rates/) to identify the plugin.
2. Click **Install Now**, then **Activate**.
3. Open **Settings → Exchange Rates** to select the data source and configure the plugin.
4. Add a shortcode to a post or page, or add a converter/table widget in a theme that supports widget areas.

### From a ZIP file

Download the published ZIP from [WordPress.org](https://wordpress.org/plugins/exchange-rates/), then use **Plugins → Add New Plugin → Upload Plugin → Install Now → Activate**.

For a development build, follow [Checks and installable ZIP](#checks-and-installable-zip). Upload the generated `exchange-rates.<version>.zip`, not GitHub's **Code → Download ZIP** archive. The repository archive includes development files and is not an installable plugin package.

## Shortcode examples

Paste an example into a WordPress **Shortcode** block:

**Currency converter**

```text
[exchange-rates_currency-converter title="Currency converter" amount="100" from="USD" to="EUR"]
```

**Exchange-rate table**

```text
[exchange-rates_currency-table base_currency="USD" currency_list="EUR,GBP,JPY"]
```

**Multi-currency price badge**

```text
[exchange-rates_badge amount="100" base_currency="USD" currency_list="EUR,GBP"]
```

The selected data source must provide the currencies used in the examples. Configure the source in the plugin settings before adding the shortcodes.

## Requirements and verification

| Scope | Version |
| --- | --- |
| Declared minimum WordPress | 5.6 |
| Declared minimum PHP | 5.6 |
| CI syntax checks | PHP 5.6, 8.3 and 8.4 |
| CI runtime smoke | WordPress 7.1.3 / PHP 8.3 |
| Local compatibility of the reviewed `f511fa42…48f4` ZIP | WordPress 7.1.3 and **7.2 Beta 2** / PHP 8.2.29 ([receipt](docs/reviews/1.5.0-final-recheck.md)) |

The version badge tracks the published WordPress.org release; the CI badge tracks this repository's `main` branch. WordPress/PHP requirement badges reflect the plugin metadata, not a full runtime test matrix. Syntax checks do not establish runtime compatibility with every supported version.

The beta check covers the pinned 7.2-beta2 build, not the future final WordPress 7.2 release. The plugin's `Tested up to` remains 7.1: [WordPress.org permits this field up to the current release candidate, not a beta](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/#what-version-of-wordpress-should-the-tested-up-to-value-be).

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
Output: `dist/exchange-rates.1.5.0.zip`, containing an `exchange-rates/` directory. Preserve the reviewed ZIP before rebuilding; a versioned filename alone does not identify its contents. The [release status](docs/release-status.md) distinguishes the verified package from subsequent source revisions and retains their checksums.

The current 1.5.0 work includes the accepted footer/dialog composition, local footer timestamps, accessible controls and announcements, explicit zero handling, adaptive historical tables, missing-history JPY recovery and removal of the unused jQuery UI Autocomplete dependency. See the [table integration](docs/reviews/1.5.0-table-integration.md) and [exact-ZIP recheck](docs/reviews/1.5.0-final-recheck.md) for scoped evidence.

CI saves a ZIP artifact when its workflow runs. An old 1.4.2 CI run is not evidence for the current working tree. After the final 1.5.0 commit passes CI, compare its installable tree and package checksum with the reviewed artifact. Extract the outer Actions artifact archive and install only the plugin ZIP inside it. Artifacts have limited retention; see [packaging and release procedures](docs/release.md).

## Repository and documentation

This is the canonical public development repository. The single source tree in `plugin/trunk/` was imported unchanged from the official **1.3.2** release; provenance is recorded in [the normalization report](docs/normalization.md).

```text
plugin/trunk/          The single plugin source tree, including vendor
wordpress/            Local WordPress, MariaDB and WP-CLI
scripts/              Validation, packaging and local .env generation
tests/                Isolated smoke checks with synthetic rates
examples/banner/      Editable HTML/CSS directory banner and local assets
docs/                 Development, release and imported source provenance
.github/workflows/    CI without WordPress SVN publishing
```

SVN `tags/`, `.svn`, WordPress core, databases and actual `.env` files are not stored in Git. Edit plugin code on the host in `plugin/trunk/`; containers use a read-only bind mount. **WordPress.org SVN is strictly read-only in this workflow.**

- [Local development and smoke tests](docs/development.md)
- [Mandatory three-phase release acceptance](docs/acceptance/release-gates.md)
- [Permanent regression registry and runner](tests/regressions/README.md)
- [Editable banner and local PNG rendering](examples/banner/README.md)
- [Current release status and candidate identity](docs/release-status.md)
- [Packaging and release procedures](docs/release.md)
- [1.4.1 compatibility acceptance and limitations](docs/releases/1.4.1.md)
- [Current roadmap: 1.5.0 / 1.5.6 / 1.6.0](docs/roadmaps/README.md)
- [1.5.0 completed-work checkpoint](docs/reviews/1.5.0-checkpoint.md)
- [Historical 1.4.0 compatibility acceptance](docs/releases/1.4.0.md)
- [Source provenance and deferred CSS change](docs/normalization.md)

## Contributing

All repository and plugin development uses **English**. Source UI strings remain translatable through WordPress; existing translations are preserved.

Read [CONTRIBUTING.md](CONTRIBUTING.md) for WordPress conventions, DRY, security, compatibility and review requirements. Automated agents must also follow [AGENTS.md](AGENTS.md). Submit focused pull requests with verification results and relevant documentation updates.

## Support and useful links

| Resource | Purpose |
| --- | --- |
| [WordPress.org plugin page](https://wordpress.org/plugins/exchange-rates/) | Published release, installation details and reviews |
| [Live demo](https://yuri.ws/exchange-rates/) | See the plugin in use |
| [WordPress support forum](https://wordpress.org/support/plugin/exchange-rates/) | Installation and usage questions |
| [GitHub issues](https://github.com/dejurin/wordpress-plugin-exchange-rates/issues) | Reproducible bugs and development requests |
| [Translate the plugin](https://translate.wordpress.org/projects/wp-plugins/exchange-rates/) | Contribute WordPress translations |
| [CurrencyRate.Today](https://currencyrate.today/) | The project's currency data website |

For bug reports, include WordPress/PHP/plugin versions, reproduction steps and sanitized error details. Never include credentials or your local `.env`.

## License

GPL version 2 or later, as declared in the published plugin headers; see [LICENSE](LICENSE) for the GPLv2 text.
Third-party libraries retain their own licenses in `plugin/trunk/vendor/`.
