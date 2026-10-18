# Selected 1.4.0 release-review follow-ups

## Scope

This change removes three confirmed dead fragments and corrects release documentation. It does not change CSS, JavaScript, rate calculation, source selection, public hooks, shortcode/widget contracts, minimum versions, the Install.php description, or inherited SVG flag licensing material. Source-directory validation is reviewed separately in the preceding PR.

## Dead code

- `Service/UpdateDataSources.php`: remove an unused `DataSources::getInstance()` assignment, its commented-out fetch and its now-unused import. The constructor is empty; the call did not fetch the directory.
- `Models/Dev.php`: remove `0 === $dev` inside the `2 === $dev` branch. Keep the reachable mode-zero branch. All 24 caption combinations (modes 0/1/2, two sources, compact/full width, base only/pair) retain identical HTML hashes compared with the parent revision.
- `Plugin.php`: remove the commented-out `hide_example_widget()` method. It had no executable declaration or registered caller.

## Documentation

The external-service disclosure now distinguishes frontend rendering from a separately executed WP-Cron request. The 1.4.0 changelog includes the already implemented RTL ordering/flag fixes, 14 px SVG trend icons, table composition and converter box-sizing normalization. Full source/currency lists remain in the FAQ.

## In-flight worker after deactivation

Run the observational probe only in a disposable local WordPress environment with `EXCHANGE_RATES_TEST_MODE=1`:

```sh
wp eval-file /opt/tests/deactivation-inflight-wordpress.php
```

The probe performs actual deactivation in a separate PHP process while the worker is inside a controlled HTTP response. It inspects persisted state from a fresh request, runs the orphan cron event through WP-CLI, and restores the original local plugin state, rates, settings, cron and transients. It must not be used on ordinary local or production sites.

Observed on WordPress 7.1.3 / PHP 8.3 and WordPress 5.6 / PHP 5.6:

| Boundary | Result |
| --- | --- |
| Immediately after deactivation | Both plugin cron hooks are cleared. |
| In-flight HTTP fails afterwards | One non-recurring `exchange-rates_data_retry` event and the five-minute backoff can reappear; the old rates remain intact and availability becomes false. |
| Fresh request while inactive | Neither plugin cron callback is registered. |
| Dispatch orphan retry while inactive | Zero provider requests; the event is consumed without scheduling another event. |
| In-flight HTTP succeeds afterwards | The already-running worker can save a validated result; no retry is recreated. |

This confirms a lifecycle cleanup race, not a demonstrated data-corruption or endless-retry defect. The probe covers a controlled in-flight request on two runtimes, not every possible worker/deactivation interleaving. No worker cancellation fix is included: the request was to investigate this finding. Strict cancellation would require fencing both persistence and retry scheduling against deactivation, with separate race regressions; checking only whether the plugin is active in a request-local cache would be insufficient.

## Compatibility and rollback

The source changes only remove inactive/redundant statements. Existing integration suites and exact caption comparisons protect the executed behavior. No settings migration is needed, and reverting the cleanup restores only those dead fragments and old prose. Neither this review nor passing CI authorizes WordPress.org publication; SVN remains read-only.
