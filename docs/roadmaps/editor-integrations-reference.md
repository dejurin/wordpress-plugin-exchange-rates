# Historical editor integration design reference

Archived on 2026-10-28. **This is not the current release schedule.**
The original 2026-10-20 plan below is retained for its technical design and compatibility analysis. Its version assignments, estimates and combined Gutenberg/Elementor release gates are superseded by the [current roadmap](README.md). Gutenberg is assigned to [1.6.0](1.6.0.md); Elementor has no assigned release. Do not treat the old 1.5.0 scope below as a current instruction.

---

# Exchange Rates 1.5.0 integration roadmap

Planning date: 2026-10-20. Status: planned; implementation has not started.

This document records the agreed sequence: three native Gutenberg blocks first,
then three native Elementor widgets. Both integrations belong to the 1.5.0 scope.
It is not an implementation, compatibility acceptance or publication record.
The [development contract](../../CONTRIBUTING.md) remains authoritative.

## Purpose and baseline

Make the existing converter, currency table and badge discoverable and configurable
inside WordPress editors while preserving existing sites, data and public contracts.
Reducing placement friction is a product hypothesis; this roadmap does not promise
a particular increase in installations or introduce usage tracking.

The analysis inspected the 1.4.0 working checkout and the newer saved `origin/main`
at `eb1e91fe6801144d89bc80e300e9b1cb77f9145a` (version 1.4.1). The latter already
contains background refresh and cache/source identity changes. They must not be
reimplemented as integration features. This reference does not establish the
currently published WordPress.org version or the latest remote branch head.

Before implementation, select and record the reviewed development base, the actual
published upgrade baseline and their exact ZIP identities. Reconcile subsequent
changes before applying this plan; do not start implementation from the older
checkout merely because it was used to write this document.

At the inspected base, the plugin has three shortcodes, two classic widgets and no
native Gutenberg or Elementor registration. Converter and table rendering already
use PHP services; badge rendering is embedded in its shortcode class. The frontend
JavaScript initializes on `document.ready`, which is insufficient for editor rerenders.

## Release scope

| Required integration | Deliverable |
| --- | --- |
| Gutenberg converter | `exchange-rates/converter` with title, amount, from/to currencies, code/name, symbol placement and border controls |
| Gutenberg currency table | `exchange-rates/currency-table` with base/list, amount, supported columns, flags, formatting, precision and border controls |
| Gutenberg badge | `exchange-rates/badge` with base/list, amount, precision, flags, color and symbol controls |
| WordPress editor surfaces | The same three blocks in posts/pages, the block Widgets screen and the Site Editor/template parts where those surfaces exist |
| Elementor Free | Native converter, table and badge widgets with equivalent supported content settings and editor previews, implemented after the Gutenberg gate |
| Existing integrations | Existing shortcodes, classic widgets and their saved configurations continue to work alongside the new integrations |

These block names are proposed identifiers to freeze in stage R0. Once shipped,
their names, attribute schemas and defaults become stored-content contracts.
Elementor identifiers must also be selected and frozen before their implementation.

The source remains site-wide. Converter formatting and precision continue to follow
the existing global settings; the editor must explain inherited values. Table and
badge controls must follow their existing renderer semantics. Do not offer controls
that the renderer ignores or silently replace an unavailable currency or source.

Ready-made block patterns may be added after the required behavior passes. Automatic
widget/shortcode conversion, per-block providers, new financial calculations,
historical charts and a general-purpose visual builder are outside this scope.
Native WPBakery, Divi, Bricks and Beaver Builder adapters are deferred. Their shortcode
compatibility may be documented only for combinations actually tested. Elementor Pro
and its Theme Builder are not required dependencies or implied tested surfaces.

## Implementation boundaries

- Use dynamic PHP rendering from the existing cached data. Store display parameters,
  not a snapshot of exchange rates, in block attributes or Elementor settings.
- Reuse the existing calculation, validation and formatting paths. Characterize
  behavior before narrowly extracting shared badge rendering or changing shared code.
  Keep legacy input/default semantics intact; new entry points need explicit validation.
- Introduce a container-scoped, repeatable JavaScript initializer with cleanup.
  Preserve the normal frontend entry point and prevent duplicate handlers, duplicated
  DOM IDs, unrelated-instance updates and resets of user input on unrelated rerenders.
- Keep editor controls separate from frontend behavior. Scope styles to plugin content;
  loading existing `:root` variables or admin CSS must not change the editor interface.
- Keep WordPress 5.6 and PHP 5.6 minimums. Verify registration, block API selection,
  script dependencies, translation loading and PHP syntax against those minimums.
  Modern iframe editors must also work; do not assume old editor behavior is sufficient.
- Decide the editor preview transport in stage R1. Server-side preview is an option
  for the existing PHP renderers, not a reason to duplicate rate calculations in React.
  Bound/debounce requests, reject stale responses and provide loading, empty and error
  states. Preview must use the existing cache, not directly request a provider.
- Any new REST endpoint needs typed validation, an explicit permission callback and
  capability checks appropriate to the requested data/action. Do not expose private
  options or allow preview to change global settings or force an update.
- Load Elementor classes and assets only when a supported Elementor installation is
  available. Keep widget output dynamic so rate changes are not frozen in element
  output caches. Do not copy PHP syntax from modern examples that breaks PHP 5.6.
- Keep build tooling outside the installable tree, ship the required built assets and
  dependency metadata, and document a reproducible build with pinned dependencies.
  Use WordPress-provided editor libraries; do not add a separate frontend React runtime.

## Stages and exit gates

All stages below are **not started**. Estimates are working days for one developer,
including focused verification, with no calendar commitment. R1 must validate the
estimate before implementation continues. Total initial estimate: 17–28 working days.

| Stage | Work and deliverable | Exit gate | Estimate |
| --- | --- | --- | --- |
| R0 — Freeze contracts and evidence | Select exact bases/ZIPs; inventory hooks, options, shortcode/widget defaults and saved fixtures; freeze new identifiers and the rollback requirement | A reviewed compatibility inventory, existing-behavior tests and a documented baseline defect list exist before shared code changes | 1–2 days |
| R1 — Validate the shared foundation | Prototype minimum/current editor registration, preview transport, scoped assets, JS mount/unmount and downgrade fallback; define the build | The prototype passes on WP 5.6/PHP 5.6 and the current iframe editor; repeated mounts and mixed legacy content work; preview and rollback decisions are recorded | 2–3 days |
| R2 — Implement three Gutenberg blocks | Add converter, table and badge controls, dynamic rendering, validation, translations and real-data-state previews | All three pass insert/configure/duplicate/undo/save/reopen and frontend parity checks; invalid input and missing data have explicit outcomes | 4–6 days |
| R3 — Accept WordPress surfaces | Exercise posts/pages, block Widgets and Site Editor/template parts; retain classic paths; update placement instructions | Gutenberg acceptance matrix and focused legacy regressions pass on applicable minimum/intermediate/current runtimes; editor styles and instance isolation are verified | 2–3 days |
| R4 — Add Elementor | Register three conditional widgets using the accepted shared rendering and lifecycle code; document the supported Elementor range | The preceding Gutenberg gate has passed; all three Elementor widgets pass editor rerender, save/reopen, duplication, frontend parity, absence/deactivation and cache checks | 5–9 days |
| R5 — Accept the integrated release | Run the final regression/upgrade/downgrade matrix, verify the built ZIP and record limitations and recovery steps | Exact candidate SHA/ZIP, test receipts and reviewed exceptions are recorded; no unresolved integration regression remains; publication is still a separate operation | 3–5 days |

Use focused, reviewable pull requests, with regression coverage preceding shared
behavior changes. A failed stage remains open. Removing Elementor from 1.5.0,
raising minimum requirements or weakening a compatibility gate requires an explicit
maintainer scope decision; it is not an automatic way to meet an estimate.

## Acceptance matrix

| Area | Required checks and passing condition |
| --- | --- |
| Editing lifecycle | Insert each type, change every supported setting, duplicate, reorder, undo/redo, delete, save and reopen. Values persist and no invalid-block warning or unexpected dirty state appears. |
| Preview/frontend parity | For fixed rates and equivalent settings, source identity, pair direction, amounts, precision, formatting and enabled columns agree. Empty/error states never fabricate a successful result. |
| Multiple instances | Render identical and different instances, repeated template content and mixed block/shortcode/classic-widget content. IDs are unique; changing one instance affects only that instance; handlers do not accumulate. |
| Core editor surfaces | Test posts/pages, Widgets and Site Editor/template parts on versions where they exist. Cover iframe and non-iframe editors, a classic theme and a block theme. No editor UI styling leak occurs. |
| Elementor | Record exact tested versions and supported range. Verify initial mount, control changes, rerender, duplication, save/reopen and frontend output for all three widgets. Absence, unsupported versions and deactivation do not break other integrations. |
| Accessibility/layout | Test keyboard operation, associated labels, visible focus, meaningful loading/error feedback, 200% browser zoom, 320px reflow and RTL. Check Chromium, Firefox and WebKit; identify any native-browser or assistive-technology gaps explicitly. |
| Internationalization | Test English and an installed translated locale, JS translations, placeholder/markup parity and locale-sensitive numbers. Saved user-authored labels remain unchanged. |
| Invalid input/data | Exercise malformed attributes, invalid currencies/types, empty lists, absent or incomplete rates, failed refreshes and retained cached data. New entry points reject invalid values without silent substitutions. |
| Permissions | Verify the intended editing roles can preview and save within their permissions. Denied requests, missing/invalid nonces where applicable and tampered parameters cannot change settings or expose private data. |
| Network/performance | Repeated renders and control changes do not directly fetch providers. Bound preview requests; load no new integration assets on irrelevant pages; avoid duplicate library downloads and unintended render-time writes. |
| Caches | Verify fresh/warmed browser caches, page HTML caches and the selected optimizer/object-cache profiles. Elementor output must reflect refreshed cached rates after applicable HTML-cache invalidation. Stale HTML is not proof of current renderer behavior. |
| Runtime/package | Run the real minimum WP 5.6/PHP 5.6 runtime, transition versions exercising block Widgets/Site Editor/API differences, and the current supported runtime. Verify the installable ZIP contains every required asset and no development dependencies. |

Do not test impossible WordPress/PHP combinations or mark surfaces absent in older
WordPress versions as failed. Record exact version pairs and applicable surfaces in
the acceptance receipt. Confirm the minimum supported Elementor version in R1/R4;
the plugin must still work on its own minimum runtime when Elementor cannot run there.

## Backward compatibility and rollback proof

The compatibility target is preservation of all existing supported contracts and
saved-site behavior. A finite test matrix does not prove every theme/plugin/host
combination; report gaps instead of claiming universal compatibility.

Protect at least:

- `exchange-rates/Install.php`, namespace/autoloading, slug and `exchange-rates` text domain;
- `exchange-rates_currency-converter`, `exchange-rates_currency-table` and
  `exchange-rates_badge`, including existing attributes and boolean/default semantics;
- both classic widget identifiers, saved instances and sidebar assignments;
- existing option keys/data shapes, public hooks, source/rate provenance and schedules;
- existing HTML classes/IDs used by consumers, formatting, flags, captions and translations.

Required upgrade procedure, only in a dedicated disposable environment:

1. Install the exact published baseline ZIP and create representative saved content,
   both widget types, settings and deterministic cached rates. Include all eight
   formats, precision boundaries, custom labels, flags and shortcode boolean variants.
   Capture raw protected state and baseline render/interaction results.
2. Replace only plugin files with the reviewed 1.5.0 ZIP. Do not reseed, recreate
   widgets or reactivate between phases. Account explicitly for already documented
   baseline migrations and controlled scheduled/manual updates.
3. Verify protected state and legacy behavior before editing new integrations. Then
   add all new block/widget types and repeat mixed-instance and editor checks.
4. Test downgrade to the recorded prior supported ZIP and upgrade again. Verify
   legacy state/output and the selected recovery path for newly created content.
5. Retain exact ZIP hashes, before/after comparisons, browser receipts and complete
   diagnostic logs, with sanitized summaries linked from the release acceptance.

New block names are unknown to 1.4.x. R1 must select and prove a recoverable path:
a safely serialized existing-shortcode fallback or an explicit export/conversion
performed before downgrade. Test all three types, custom text, boolean values and
currency lists. Never persist stale rate HTML as the fallback. Returning `null` from
`save` alone does not preserve new output on an older plugin. An older editor may
identify the new block as unavailable; do not claim native editing there. Restoring
the previous ZIP alone is insufficient unless the chosen fallback was verified.

Elementor content needs its own downgrade path because old versions do not register
the new widget identifiers. Preserve/export equivalent shortcodes or restore the
matching content backup according to a tested procedure. Do not imply that a
Gutenberg fallback also protects Elementor metadata.

Before any site update, retain database/files backups and the previous reviewed ZIP;
follow the existing [release and rollback procedure](../release.md#rollback). This
roadmap introduces no automatic content rewrite, database cleanup or data migration.

## Verification and completion record

Reuse the existing formatter, saved-state, update, source-identity, payload-validation,
background-refresh, concurrency, localization and browser suites from the selected
base. Some of these exist only in the newer reference and are absent in the older
working checkout. Add meaningful registration, serialization, lifecycle, permissions,
editor and downgrade coverage; do not treat the existing CI as block/Elementor proof.

Run the contract's checks when plugin implementation begins:

```sh
set -euo pipefail
python3 scripts/plugin.py check
python3 scripts/plugin.py lint
python3 scripts/plugin.py package
```

Runtime fixtures write synthetic rates and content. Run them only under the
[isolated development procedure](../development.md#isolated-smoke-with-synthetic-rates),
with an explicit dedicated Compose project and verified separate volumes as required
by the [acceptance environment instructions](../release.md#isolated-acceptance-environments).
Keep the immutable 1.3.2 provenance manifest unchanged.

The historical [1.4.0 acceptance report](../releases/1.4.0.md#known-issues-deferred-after-140)
records a block-widget shortcode-generator issue and localized amount-input limits.
Reproduce them against the selected base before assigning current status. Keep
existing defects separate from new regressions; address any release-blocking defect
in a focused, tested change rather than silently altering legacy behavior.

The 1.5.0 completion record must include exact reviewed SHA/ZIP identities, runtime
and builder versions, all stage results, protected-state comparisons, cache and
rollback evidence, documentation/translation checks, open limitations and explicit
maintainer acceptance. Pending checks must remain pending; older receipts are not
new test results. SVN publication and production changes require separate authorization.

This roadmap-only change requires link, command and consistency review. It does not
require running runtime suites, installing builders or changing plugin version metadata.

## Technical references

- [Dynamic rendering](https://developer.wordpress.org/block-editor/getting-started/fundamentals/static-dynamic-rendering/)
- [Block metadata](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/)
- [Block API versions](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-api-versions/)
- [Metadata registration](https://developer.wordpress.org/reference/functions/register_block_type_from_metadata/)
- [Editor assets and iframe boundaries](https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/)
- [Server-side preview guidance](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-server-side-render/)
- [Elementor widget registration](https://developers.elementor.com/docs/widgets/add-new-widget)
- [Elementor JavaScript lifecycle](https://developers.elementor.com/add-javascript-to-elementor-widgets/)
- [Elementor output caching](https://developers.elementor.com/docs/widgets/widget-output-caching/)
