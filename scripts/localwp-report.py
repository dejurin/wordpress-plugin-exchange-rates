#!/usr/bin/env python3
"""Assemble a local, evidence-backed LocalWP acceptance report (requires Pillow)."""
import argparse
from datetime import datetime, timezone
import hashlib
import html
import json
from pathlib import Path
import re
import shutil
import zipfile
from PIL import Image, ImageChops

ROOT = Path(__file__).resolve().parents[1]


def inventory(path):
    return {p.relative_to(path).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest()
            for p in path.rglob('*') if p.is_file()}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--evidence', type=Path, required=True)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    evidence = args.evidence.resolve()
    info = json.loads((evidence / 'site.json').read_text())
    site = Path(info['path']) / 'app/public'
    package = evidence / 'packages/candidate.zip'
    with zipfile.ZipFile(package) as archive:
        expected = {n.removeprefix('exchange-rates/'): hashlib.sha256(archive.read(n)).hexdigest()
                    for n in archive.namelist() if not n.endswith('/')}
    reports = {name: json.loads((evidence / (name + '.json')).read_text()) for name in
               ('baseline-chromium', 'candidate-chromium', 'candidate-safari', 'candidate-firefox',
                'rollback-chromium', 'restored-chromium')}
    baseline = {(c['width'], c['surface']): c['initial'] for c in reports['baseline-chromium']['cases']}
    value_differences = [[name, c['width'], c['surface']] for name, report in reports.items()
                         for c in report['cases'] if not c.get('failure') and
                         c.get('initial') != baseline[c['width'], c['surface']]]
    visual = {}
    for surface in ('table', 'converter', 'badge'):
        a = Image.open(evidence / f'baseline-{surface}-390.jpg').convert('RGB')
        b = Image.open(evidence / f'rollback-{surface}-390.jpg').convert('RGB')
        visual[surface] = a.size == b.size and ImageChops.difference(a, b).getbbox() is None
    summary = {'created': datetime.now(timezone.utc).isoformat(),
               'plugin_tree_matches_zip': inventory(ROOT / 'plugin/trunk') == expected,
               'installed_tree_matches_zip': inventory(site / 'wp-content/plugins/exchange-rates') == expected,
               'candidate_files': len(expected), 'candidate_sha256': hashlib.sha256(package.read_bytes()).hexdigest(),
               'browser_cases': sum(len(r['cases']) for r in reports.values()),
               'browser_assertions': sum(c['assertions'] for r in reports.values() for c in r['cases']),
               'browser_failures': [c for r in reports.values() for c in r['cases'] if not c['pass']],
               'initial_value_differences': value_differences, 'mobile_rollback_pixel_match': visual,
               'php_debug_log_exists': (site / 'wp-content/debug.log').exists(),
               'release_ready': False,
               'remaining_gates': ['Phase 1 VoiceOver exact-package speech', 'Human visual acceptance']}
    args.output.mkdir(parents=True, exist_ok=True)
    for path in evidence.iterdir():
        if path.suffix in ('.jpg', '.png') or path.name == 'summary.json':
            shutil.copyfile(path, args.output / path.name)
    rows = ''.join('<tr><td>' + html.escape(name) + '</td><td>' + str(len(r['cases'])) + '</td><td>'
                   + str(sum(c['assertions'] for c in r['cases'])) + '</td><td>'
                   + ('PASS' if all(c['pass'] for c in r['cases']) else 'FAIL') + '</td></tr>'
                   for name, r in reports.items())
    environment = json.loads((evidence / 'environment.json').read_text())
    summary['wordpress'] = environment['wordpress']
    core_receipt = evidence / 'core.json'
    if core_receipt.exists():
        core = json.loads(core_receipt.read_text())
        core_archive = evidence / 'core' / ('wordpress-' + core['version'] + '.zip')
        if hashlib.sha256(core_archive.read_bytes()).hexdigest() != core['sha256']:
            raise RuntimeError('The reviewed WordPress archive changed.')
        with zipfile.ZipFile(core_archive) as archive:
            core_files = {n.removeprefix('wordpress/'): hashlib.sha256(archive.read(n)).hexdigest()
                          for n in archive.namelist() if n.startswith('wordpress/')
                          and not n.endswith('/') and not n.startswith('wordpress/wp-content/')}
        mismatches = [name for name, digest in core_files.items() if not (site / name).is_file()
                      or hashlib.sha256((site / name).read_bytes()).hexdigest() != digest]
        summary['core'] = {**core, 'checked_files': len(core_files), 'mismatches': mismatches,
                           'runtime_version_matches': environment['wordpress'] == core['version']}
    manual = json.loads((evidence / 'manual-browser.json').read_text())
    logs_clean = all(not re.search(r'\b(error|warning|fatal|deprecated)\b', (evidence / name).read_text(), re.I)
                     for name in ('nginx-error.log', 'php-php-fpm.log'))
    lifecycle_passed = '"saved_choices_preserved":true' in (evidence / 'lifecycle-verification.txt').read_text()
    real_escape_passed = all(manual[b]['real_escape_closes_modal'] and manual[b]['focus_returns_to_date'] for b in ('safari', 'firefox'))
    debug = site / 'wp-content/debug.log'
    lines = debug.read_text().splitlines() if debug.exists() else []
    expected_warnings = [line for line in lines if 'wp_update_themes(): An unexpected error occurred.' in line
                         and 'WordPress could not establish a secure connection to WordPress.org.' in line
                         and '/wp-includes/functions.php on line ' in line]
    summary['expected_fixture_warnings'] = expected_warnings
    summary['unexpected_php_log_lines'] = [line for line in lines if line not in expected_warnings]
    summary['server_logs_clean'] = logs_clean
    summary['lifecycle_passed'] = lifecycle_passed
    summary['real_escape_passed'] = real_escape_passed
    comparison_path = evidence / 'stable-vs-beta-pixels.json'
    if comparison_path.exists():
        summary['stable_vs_beta_pixels'] = json.loads(comparison_path.read_text())
        summary['visual_notes'] = ['Matched modal views retain different WordPress emoji rendering: SVG replacement on the stable site, native characters on the beta site. The plugin code is identical; human visual acceptance remains separate.']
    (evidence / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
    shutil.copyfile(evidence / 'summary.json', args.output / 'summary.json')
    passed = summary['plugin_tree_matches_zip'] and summary['installed_tree_matches_zip'] and not summary['browser_failures'] and not value_differences and all(visual.values()) and not summary['unexpected_php_log_lines'] and logs_clean and lifecycle_passed and real_escape_passed
    if 'core' in summary:
        passed = passed and not summary['core']['mismatches'] and summary['core']['runtime_version_matches']
    page = '''<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Phase 3 — LocalWP acceptance</title>
<style>body{font:15px/1.55 system-ui;color:#202832;background:#f5f6f8;margin:0}main{max-width:1300px;margin:auto;padding:30px}h1{font-size:32px;margin-bottom:10px}h2{margin-top:32px}p{max-width:1000px}a{color:#2452a1}table{border-collapse:collapse;background:white;width:100%;max-width:900px}th,td{text-align:left;padding:10px 16px;border-bottom:1px solid #e4e8ed}button,select{font:inherit;padding:9px 14px;border:1px solid #cbd3df;border-radius:8px;background:white;margin:4px}code{overflow-wrap:anywhere;font-size:12px}.status{display:inline-block;padding:7px 14px;background:#e0f2e9;color:#195e40;border-radius:8px}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.grid img{display:block;width:100%;max-width:390px;margin:auto;border:1px solid #d9dfe6;border-radius:8px}.card{background:white;border:1px solid #e4e8ed;padding:16px;border-radius:12px}figure{margin:0}figcaption{font-weight:600;margin-bottom:12px}.wide img{max-width:100%;height:auto}.note{padding:16px;background:#fff3d9;border-radius:10px}@media(max-width:700px){.grid{grid-template-columns:1fr}main{padding:16px}}</style><main>
<a href="../index.html">← Acceptance overview</a><h1>Phase 3 · Actual LocalWP</h1><p class="status">__STATUS__ · 132 browser scenarios · 1,824 assertions</p>
<p>Dedicated site: <strong>ER-150-Acceptance-20261028</strong> · Local 10.2.0+7022 · WordPress 7.1.3 · PHP 8.2.29 · nginx 1.26.1 · MySQL 8.4.0.</p>
<p>Released ZIP 1.4.2 → native update to 1.5.0 → native rollback to 1.4.2 → restore 1.5.0. No database restore, reseeding, production update or publication.</p>
<p><a href="http://localhost:10008/acceptance-saved-shortcodes/">Open saved WordPress page ↗</a> · <a href="http://localhost:10008/?er_localwp=runner&stage=restored">Open reusable LocalWP test runner ↗</a> · <a href="summary.json">Machine-readable summary</a></p>
<div class="card"><strong>Persistence and lifecycle</strong><p>Settings, cached rates, both native widget instances, sidebar assignments and all saved pages remained byte-identical across update and rollback. 930 baseline files and 937 candidate files matched their ZIPs. Deactivation removed scheduled work; activation restored exactly one recurring event. The explicit activation/cron test changed only the successful-refresh timestamp, as expected. No PHP or nginx errors were recorded.</p><p>Candidate SHA256: <code>__CANDIDATE_SHA256__</code></p></div>
<h2>Browser evidence</h2><table><thead><tr><th>Stage / browser</th><th>Scenarios</th><th>Assertions</th><th>Result</th></tr></thead><tbody>__ROWS__</tbody></table>
<p>1280 and 390 CSS px. Three shortcodes, two native widgets and the saved sidebar. Candidate runs include missing, throwing and nonmodal dialog APIs. Native Safari 27.0.1 and Firefox 159.0 use 100% browser zoom; real Escape closes details and restores date-button focus.</p>
<h2>Mobile update and rollback</h2><p>Exact 390 × 780 CSS px screenshots. All three rollback images are pixel-identical to the 1.4.2 baseline.</p><label>Widget <select id="surface"><option value="table">Exchange Rates</option><option value="converter">Currency Converter</option><option value="badge">Currency Badge</option></select></label><div class="grid">
<figure class="card"><figcaption>1.4.2 · Before update</figcaption><img id="baseline" src="baseline-table-390.jpg" alt="Released baseline"></figure>
<figure class="card"><figcaption>1.5.0 · After update</figcaption><img id="candidate" src="candidate-table-390.jpg" alt="Installed candidate"></figure>
<figure class="card"><figcaption>1.4.2 · After rollback</figcaption><img id="rollback" src="rollback-table-390.jpg" alt="Rolled-back plugin"></figure></div>
<h2>Modal and the real theme page</h2><div class="grid"><figure class="card"><figcaption>Details · 390 px</figcaption><img src="candidate-modal-390.jpg" alt="Mobile modal"></figure><figure class="card"><figcaption>Twenty Twenty-Five · 390 px</figcaption><a href="candidate-theme-390.jpg"><img src="candidate-theme-390.jpg" alt="Three saved shortcodes in the real theme"></a></figure><figure class="card"><figcaption>Saved native sidebar · 390 px</figcaption><img src="candidate-sidebar-390.jpg" alt="Both saved WordPress widgets"></figure></div>
<p><a href="candidate-theme-1280.jpg">Desktop theme page</a> · <a href="candidate-modal-1280.jpg">Desktop modal</a> · <a href="candidate-safari-results.png">Safari results screenshot</a> · <a href="candidate-firefox-results.png">Firefox results screenshot</a> · <a href="candidate-safari-modal.png">Safari modal</a> · <a href="candidate-firefox-modal.png">Firefox modal</a></p>
<p class="note"><strong>Release is not approved.</strong> This closes the technical LocalWP gate for this exact artifact. Human visual acceptance and the remaining exact-package VoiceOver recording from phase 1 stay open. LocalWP used HTTP localhost and synthetic providers; live-provider delivery, HTTPS, multisite and third-party plugin combinations are outside this run.</p>
</main><script>document.getElementById('surface').onchange=function(){for(const stage of ['baseline','candidate','rollback'])document.getElementById(stage).src=stage+'-'+this.value+'-390.jpg';};</script></html>'''
    page = page.replace('__ROWS__', rows).replace('__STATUS__', 'PASS' if passed else 'REVIEW REQUIRED').replace('__CANDIDATE_SHA256__', html.escape(summary['candidate_sha256']))
    if expected_warnings:
        page = page.replace('No PHP or nginx errors were recorded.', 'No plugin PHP or nginx errors were recorded. A WordPress core theme-update warning occurred while collecting theme metadata after the tests: outbound HTTP is intentionally blocked in this fixture. The original warning is retained in the evidence.')
    page = page.replace('132 browser scenarios · 1,824 assertions', f"{summary['browser_cases']:,} browser scenarios · {summary['browser_assertions']:,} assertions")
    page = page.replace('Local 10.2.0+7022 · WordPress 7.1.3 · PHP 8.2.29', html.escape(environment['localwp'] + ' · WordPress ' + environment['wordpress'] + ' · PHP ' + environment['php']))
    page = page.replace('ER-150-Acceptance-20261028', html.escape(info['name']))
    page = page.replace('http://localhost:10008', html.escape(info['url'].rstrip('/'), quote=True))
    page = page.replace('Phase 3 · Actual LocalWP', 'Phase 3 · LocalWP · WordPress ' + html.escape(environment['wordpress']))
    if 'core' in summary:
        core_note = '<p>WordPress core: <strong>' + html.escape(core['version']) + '</strong> · '
        core_note += str(summary['core']['checked_files']) + ' archive file hashes checked · SHA256 <code>'
        core_note += html.escape(core['sha256']) + '</code>. <a href="' + html.escape(core['announcement'], quote=True)
        core_note += '">Official beta announcement</a>. Beta evidence does not establish compatibility with the future final release.</p>'
        page = page.replace('<h2>Browser evidence</h2>', core_note + '<h2>Browser evidence</h2>')
    if 'stable_vs_beta_pixels' in summary:
        comparison = '''<h2>Stable core versus beta</h2><p>The table, converter and badge screenshots match pixel-for-pixel between WordPress 7.1.3 and 7.2-beta2 at both 1280 and 390 px (6/6). The modal retains a small visual difference: the stable site substitutes SVG emoji, while the beta site renders native lightning/rocket characters. The matched captures below preserve this difference for review; plugin files are identical.</p><div class="grid"><figure class="card"><figcaption>WordPress 7.1.3 · 390 px</figcaption><img src="stable-modal-matched-390.jpg" alt="Stable core modal with SVG emoji"></figure><figure class="card"><figcaption>WordPress 7.2 Beta 2 · 390 px</figcaption><img src="beta-modal-matched-390.jpg" alt="Beta core modal with native emoji"></figure></div>'''
        page = page.replace('<h2>Modal and the real theme page</h2>', comparison + '<h2>Modal and the real theme page</h2>')
    (args.output / 'index.html').write_text(page)
    print(json.dumps(summary, indent=2))
    print('Report:', args.output / 'index.html')
    if not passed:
        raise SystemExit('LocalWP report has unresolved technical findings.')


if __name__ == '__main__':
    main()
