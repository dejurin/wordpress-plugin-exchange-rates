# Release acceptance contract

Maintainer decision: 2026-10-28. Applies to 1.5.0 and subsequent releases. These gates supplement [CONTRIBUTING.md](../../CONTRIBUTING.md); passing tests is not permission to publish.

## Phase 1 — accumulated regressions, updates and visual comparison

Use the existing PHP, WordPress and browser suites. Maintain one [regression registry](../../tests/regressions/catalog.json): stable IDs, protected behavior, executable tests, evidence and unresolved gaps. Add a regression when fixing a defect; do not replace the tester for every release. A registered test is coverage, not a passing result. Historical evidence is not a current run.

- Preserve the immutable 1.3.2 manifest and released packages. The primary visual baseline for 1.5.0 is the verified **released 1.4.2**, not the lab's patched baseline mode.
- Run direct native ZIP upgrades from 1.3.2, 1.4.0, 1.4.1 and 1.4.2, and rollback to each starting package on fresh, disposable installations. Cover WP 5.6/PHP 5.6 and the pinned current supported runtime. Do not require manual reactivation, resaving widgets or recreating content.
- Seed synthetic settings, rates, timestamps, both saved widget types, sidebar assignments and shortcode content **before** upgrading. Compare raw state afterwards without reseeding. Separately inspect intentional operational changes such as cron migration, activation and asset versions.
- Compare the actual rendered releases side by side: previous version on the left, candidate on the right; identical viewport, 100% browser zoom, theme, input, locale, source and timestamps. Switch between the three widgets and configurations. Include shortcode/native-widget, one/many instances, editable/static/inverse tables, history, long labels, LTR/RTL, flags, borders, custom badge colors and narrow containers.
- Exercise 320, 390 and desktop widths, Chrome/Chromium, native Safari and Firefox. Keep screenshots, measured layout/value differences, logs and exact artifact hashes. Do not infer native Safari coverage from WebKit emulation.
- Treat the approved footer/modal/local-time changes as explicit expected differences. Everything else requires investigation. No broad screenshot rebaselining, hiding differences with CSS, omitting failing cases or accepting an overflow because the footer changed.
- Verify stale HTML/new assets and old assets/new markup, unavailable data and modal failure. Neither synthetic data nor cache models prove external provider/CDN behavior.
- Build and test the actual installable candidate. A lab MU adapter is useful for design review but cannot pass the packaged-plugin gate.

Automation produces measurements and evidence. The maintainer approves the visual comparison; automated success must not be recorded as human acceptance.

## Phase 2 — existing-site composition compatibility

Use the previously audited public installations as representative inputs. A [six-site starting roster](public-site-cases.json) preserves the historical table/converter/badge examples without claiming fresh verification. Recheck the exact page and plugin version first: a search result, cached homepage or historical version marker is insufficient. Select at least two distinct compositions for each surface, including a narrow sidebar, custom styling, long labels and multiple currencies/instances where observed.

Read public pages only. Reproduce their relevant wrapper widths, typography, theme CSS, plugin attributes and options locally with sanitized/synthetic fixtures. Run the unchanged released version and candidate under **the same** reproduced conditions. Keep a public screenshot next to the local baseline to show fidelity. A local model is not an upgraded live site or a complete owner database clone.

**Strict visual gate:** existing widget bodies retain configured dimensions, alignment, typography, currency order, flags, colors, borders, input/result behavior and surrounding-page layout. Approved footer changes may change the footer's own height, but must not clip data, overlap neighbors, move controls outside their wrapper or override unrelated theme elements. Any unavoidable additional change needs an explicit documented maintainer decision before release. Snapshot approval cannot silently enlarge this allowance.

Retain each reproduced scenario in the permanent regression collection. Never update public sites, access their administration, send forms or import private data for this phase.

## Phase 3 — LocalWP

Use a dedicated disposable site created in the actual LocalWP application. Docker results do not satisfy this gate. Record the LocalWP, WordPress, PHP and web-server versions actually tested.

Install the exact released ZIP, configure both native widgets and all three shortcodes, preserve a state snapshot, update with the reviewed candidate ZIP, then check rendering, interaction, errors, persistence, activation, cron and rollback. Include desktop/mobile views and the modal. Do not alter an existing LocalWP site, its database or application-wide settings as a shortcut.

For 1.5.0, also include a separate WordPress 7.2 beta site. Pin the official beta archive/version, preserve its SHA256 and verify the installed core; keep the stable-core site and evidence intact. Beta results do not justify changing `Tested up to` to an unreleased final version. The initial additional row is [7.2 Beta 2](../reviews/1.5.0-phase3-wp72-beta2.md).

If LocalWP is unavailable, record this gate as not run; do not substitute a differently named Docker result.

## Readiness and rollback

Include [WordPress Plugin Check](../release.md#wordpress-plugin-check) on the
exact installed candidate in the final acceptance record. Retain checker/runtime
versions, ZIP identity, raw static/runtime results and finding dispositions;
the repository metadata validator is not a substitute for the official checker.

All required checks must refer to the same candidate package/tree. Record PASS, FAIL, NOT RUN or explicitly accepted pre-existing limitation; never collapse them into green. New regressions, unexplained visual differences, changed saved data or missing required evidence block release readiness. Keep original packages, snapshots and logs. Revert only this task's files; no ordinary databases, volumes or production sites are changed.

Known additional accessibility work stays in [1.5.6](../roadmaps/1.5.6.md), Gutenberg in [1.6.0](../roadmaps/1.6.0.md). This contract neither removes existing accessibility protections nor asserts certification.
