# Existing-site composition fixtures

These six sanitized compositions come from read-only public DOM/CSS observations on 2026-10-28. They are component reproductions, not complete website clones or owner database backups. See the [phase-two receipt](../../../docs/reviews/1.5.0-phase2-public-compositions.md).

The adjacent JSON files retain the public URL, observed plugin version, configured attributes, frozen numerical examples, measured wrapper typography/widths, and stylesheet provenance. CSS files contain relevant observed theme rules, including generic rules for elements introduced by the new footer/modal and hover/focus states. Direct plugin CSS and the identified Central Bank optimizer bundle containing plugin CSS are excluded: both local versions load their own installed assets.

Cross-origin stylesheets inaccessible through CSSOM are listed explicitly. Fonts were cached from observed URLs for offline local tests; fonts and public screenshots remain in ignored evidence storage, not the installable plugin. Public values are historical test inputs, not current financial quotes. No site administration, private data or submissions are involved.

## Reproduce the recorded run

Use two explicitly disposable WordPress sites with the actual baseline and candidate ZIPs installed. Do not mount the source tree over the installed plugin. Both require `EXCHANGE_RATES_TEST_MODE=1`, `WP_ENVIRONMENT_TYPE=local`, the existing synthetic HTTP fixture, `/opt/tests:ro`, this MU adapter at `wp-content/mu-plugins/public-fixture.php`, and the frozen font directory at `/er-public-fonts:ro`. The adapter serves only allowlisted `er_public_case` requests and supplies options through request-local filters; it does not save options or widgets.

```sh
set -euo pipefail
python3 scripts/cache-public-fonts.py \
  --output dist/acceptance-1.5.0-phase2-20261028/fonts
node tests/acceptance/public-runner.cjs \
  --baseline=http://127.0.0.1:19334 --baseline-version=1.4.2 \
  --candidate=http://127.0.0.1:19335 --candidate-version=1.5.0 \
  --output="dist/public-compositions-$(date -u +%Y%m%dT%H%M%SZ)"
```

The font command reuses hash-verified cached files and preserves a complete previous manifest on download failure. The test runner refuses an existing output directory, allows loopback endpoints only, blocks external requests, and writes per-composition JSON plus baseline/candidate/modal screenshots. The current collection has 12 observed configurations: desktop 1280 and mobile 390 for all six sites. The runner also checks conversion/recovery, link states, modal opening/closing and three modal failure modes per composition.

`PASS` covers measured body properties and the listed assertions; it is not screenshot pixel equality or human acceptance. `DESIGN REVIEW` retains the known converter-border difference. `FIDELITY REVIEW`, `FAIL` and `ERROR` must be investigated. The report has a separate browser measurement button; those DOM measurements do not establish VoiceOver acceptance. The separate native runner is described below.

## Extend without replacing the tester

Keep original captures/screenshots in a new ignored evidence directory. Collect reviewed public DOM/CSS through the browser using `public-observe.js`; do not turn this into an unbounded crawler. Add the reviewed composition definition in `scripts/public-compositions.py`, then run:

```sh
python3 scripts/public-compositions.py --captures dist/acceptance-1.5.0-phase2-20261028/public
```

Only numeric `site-width.json` captures establish observed viewport coverage. Optional `site-supplement.json` contributes additional theme rules, not new coverage. Confirm source identity, attributes, currency order and baseline fidelity before accepting a new case. Never change captures or tolerances to hide candidate differences. Add its allowlisted ID in the MU fixture and label in the comparison UI; preserve the stable `ER-real-site-compositions` registry entry.

To assemble only the local review page, use `scripts/public-report.py`. It copies an explicit capture/run selection and package identity file, not private environment files. This is a local filesystem operation, not website publication.

## Native Safari and Firefox

The guarded MU adapter loads `public-native.php` from the read-only tests mount. Open `http://127.0.0.1:19335/?er_public_native=1` in each real browser at 100% zoom, then run all 12 compositions. It compares installed 1.4.2 on port 19334 with installed 1.5.0 on port 19335. Measurements cross origins only through validated frame/origin messages. The native page checks amount input/recovery/swap, focus, IDs, source identity, footer/modal links, layout and missing/throwing/no-JavaScript modal cases. It writes a nonce-protected report only to `wp-content/public-composition-checks/` in the disposable candidate site. Copy the exact saved filename into the evidence directory as `safari.json` or `firefox.json`; preserve failed attempts separately.

A manual 390 × 780 preview is available with `&preview=editorialrn`, `&preview=todayafrica` or `&preview=centralbank`. Verify real Escape and save screenshots through the native app. No VoiceOver, AppleScript or browser security settings are needed. A 390 px desktop iframe is responsive-layout evidence, not an iOS/Android device test.

Public screenshots now retain the whole visible viewport and a separate crop rectangle in `screenshot-review.json`. Do not rely on the browser tool's clipped screenshot coordinates: they produced shifted crops in the initial run. Render the retained viewport with a CSS crop; keep originals intact. Converter rates are frozen per observed viewport because public quotes can change between captures. Both packages always receive the same snapshot for a given configuration.
