# Development contract

This contract applies to all future repository and plugin development, including work by maintainers, contributors and automated agents. Changes must follow it. Any necessary exception must be explained in the pull request and accepted by the maintainer before merge.

## Language

- Use English for repository documentation, instructions, code comments, identifiers, diagnostics, test descriptions, commit messages, pull requests, maintainer-authored issues and release notes.
- Write the plugin's source UI strings in English and make them translatable through WordPress internationalization APIs using the literal `exchange-rates` text domain. Do not hardcode another language in PHP or JavaScript UI code.
- Preserve localization catalogs and translated content in their target languages. Keep placeholders, markup, context and plural forms correct; use locale-aware number and date formatting. English development does not mean disabling translations or forcing the site's locale to English.
- Preserve upstream licenses, third-party sources, historical commits and external data in their original form. Do not rewrite them just to satisfy the language rule. Test fixtures may contain non-English text when exercising localization or input handling.

## Scope and compatibility

- Develop the installable plugin only in `plugin/trunk/`. Keep development tooling, documentation, local WordPress and tests outside that directory. Do not add SVN `tags/`, another plugin source copy, WordPress core, database dumps, generated archives or secrets to Git.
- Inspect existing behavior and the affected code before editing. Describe the problem, intended behavior, compatibility risks and rollback. Keep changes small and avoid unrelated cleanup.
- Preserve the current namespace and PSR-4 structure. Keep `exchange-rates/Install.php` with its exact case, the `exchange-rates` slug and text domain, and existing public hooks, shortcode attributes, widget identifiers, option keys and stored data contracts. Changing an exposed contract requires a documented migration and compatibility tests.
- The current declared minimums are WordPress 5.6 and PHP 5.6. Use syntax and APIs available on those versions; native types must not raise the minimum implicitly. Use PHPDoc where newer type syntax is unavailable. A minimum-version change requires an explicit compatibility decision, matching metadata, documentation and CI updates.
- Keep the historical [1.3.2 baseline manifest](docs/baseline-1.3.2.json) immutable. Future plugin changes are expected to differ from it; never rewrite the manifest to hide differences.

## WordPress conventions and maintainability

Use the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) for new first-party PHP, JavaScript, CSS and HTML. Apply them to changed code without mass-formatting untouched imported files. Preserve existing class names, paths and public APIs where a mechanical naming change would break compatibility. The imported baseline is not certified as fully WPCS-compliant.

- Prefer WordPress hooks and core APIs for settings, options, metadata, HTTP, cron, caching, assets and translations. Do not introduce a parallel framework or a custom replacement without a concrete need.
- Keep functions and classes focused, names explicit and control flow readable. Share repeated business rules, validation and formatting when they have the same meaning. DRY does not justify a generic abstraction for unrelated behavior or a single speculative use case.
- Separate data retrieval, validation, calculation and rendering. Handle errors explicitly; do not suppress warnings or replace failures with plausible-looking results.
- Enqueue scripts and styles through WordPress with explicit dependencies and versions, and only on relevant pages. Preserve CSS scoping so plugin styles do not alter the editor, themes or other plugins.
- Keep activation and migration steps repeatable. Schedule cron events without duplicates and remove the plugin's scheduled work on deactivation. Do not delete user settings or content on deactivation; any uninstall data removal must be documented and intentional.
- Keep network and database work bounded. Use deliberate cache lifetimes and invalidation, avoid unnecessary autoloaded options, and account for concurrent updates. Do not add remote requests to every page render.
- Make UI changes keyboard accessible, with labels, visible focus and meaningful status/error feedback. Check the affected frontend, settings and widget screens, including translations and narrow layouts.

## Security boundaries

Follow the [WordPress security guidance](https://developer.wordpress.org/apis/security/). Treat request parameters, shortcode attributes, stored values and remote responses as untrusted.

- Validate expected types, ranges and allowed values at entry points. Unslash WordPress request input before appropriate sanitization. Reject invalid data instead of silently coercing it into a valid-looking value.
- Escape output at the point of use for its HTML, attribute, URL or JavaScript context. Use a deliberate `wp_kses()` allowlist when HTML is needed; use WordPress JSON encoding for structured data.
- Check capabilities with `current_user_can()` before privileged reads or writes. Protect state-changing browser requests against CSRF using the appropriate nonce checks. [Nonces do not grant permissions](https://developer.wordpress.org/apis/security/nonces/); `is_admin()` also does not establish authorization. REST routes need an explicit permission callback, and AJAX handlers need appropriate access checks.
- Prefer core storage APIs. When SQL is necessary, use `$wpdb->prepare()` for values and allowlist any dynamic identifiers or sort clauses; never interpolate untrusted SQL fragments. Keep APIs compatible with the declared WordPress minimum.
- Use the WordPress HTTP API with TLS verification enabled and bounded timeouts, redirects and response sizes. Validate status codes and response structure, and handle `WP_Error`. Constrain destinations; use safe HTTP APIs for configurable URLs and prevent access to private/internal targets.
- Do not expose secrets, credentials, personal data or raw remote payloads in logs, HTML, fixtures, CI output or public issues. Do not add tracking, remote executable code or undocumented external requests. Document external services and data sent to them, following the [Plugin Directory guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).

## Exchange-rate data

- Preserve source identity, base/quote direction, nominal units, timestamps and freshness. Preserve buy/sell and deal distinctions whenever supplied. Document any conversion or normalization and test it with known inputs.
- Reject malformed, non-finite or invalid rates; guard division by zero and unknown currencies. Define calculation precision and round for display deliberately.
- Never silently invert a pair, substitute a source, fill a missing rate with zero or fabricate a successful update. If retaining previously valid data after a failed refresh, preserve its original timestamp and make stale/error status explicit.
- Keep synthetic rates confined to the isolated test environment. Fixture success does not prove that a live provider is reachable or correct.

## Dependencies and licensing

Keep GPLv2-or-later compatibility and all third-party notices. The shipped `plugin/trunk/vendor/` is part of the plugin; do not edit generated or vendored files as a routine fix. Dependency changes must be intentional, reproducible and reviewed for compatibility, security and licensing. Do not run `composer update` as a packaging step. See [release procedures](docs/release.md) for the imported dependency constraints.

## Validation and review

Use a focused branch and pull request. Explain the concrete behavior change, affected contracts, verification results, limitations and rollback. Update the relevant English documentation in the same change. Do not claim a check passed without running it against the reviewed revision.

For plugin or packaging changes, run:

```sh
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
```

Run the [isolated WordPress smoke](docs/development.md#isolated-smoke-with-synthetic-rates) when runtime behavior or its environment changes. Add focused regression coverage for bugs and new behavior, especially rejected input, denied access, failed providers and existing saved settings. For UI changes, also verify the affected flow in a browser. Documentation-only changes need link, command and consistency review; they do not require new runtime tests.

The existing CI checks PHP syntax, packaging and one WordPress/PHP runtime combination. It does **not** currently run PHPCS/WPCS, a full security audit or every supported runtime version. Review applicable standards manually until dedicated tooling is added. Introduce such tooling in a scoped change with explicit legacy exclusions; do not mask failures with blanket suppressions. Passing CI is required before merge but does not establish full compatibility or authorize publication.

Use [WordPress internationalization guidance](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) when changing strings. Preserve placeholder and markup parity, add translator context where needed, and avoid concatenated sentence fragments.

## Operations and release boundary

WordPress.org SVN is strictly read-only in this workflow. Reads, exports and comparisons are allowed; commits, imports, tag creation, deletion and other remote writes are not. Publication requires a separate explicit authorization and a reviewed artifact; a GitHub merge or CI success is not that authorization. Do not add SVN credentials or automatic publishing to CI.

Keep local credentials in the ignored `wordpress/.env`; never print them. Preserve ordinary development volumes and unrelated containers. Destructive cleanup or data migration requires a clearly stated target, impact and recovery path within the authorized task. CI may remove only its own disposable environment.

Follow [release and rollback procedures](docs/release.md#release-and-rollback). Revert repository changes through a reviewed PR; do not rewrite shared history. A repository rollback does not revert installed plugins, site data or a WordPress.org release.
