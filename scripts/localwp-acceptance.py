#!/usr/bin/env python3
"""Run guarded native ZIP/state checks on a dedicated, existing LocalWP site."""
import argparse
import hashlib
import json
import subprocess
import time
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[1]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('action', choices=['prepare', 'baseline', 'checkpoint', 'candidate', 'rollback', 'restored', 'verify', 'collect', 'lifecycle'])
    parser.add_argument('--site', required=True, help='Dedicated ER-150-Acceptance-* LocalWP site name')
    parser.add_argument('--evidence', required=True, type=Path)
    args = parser.parse_args()
    if not args.site.startswith('ER-150-Acceptance-'):
        parser.error('Only explicitly named disposable acceptance sites are supported.')
    evidence = args.evidence.resolve()
    evidence.mkdir(parents=True, exist_ok=True)
    info = json.loads(subprocess.check_output(['localwp', 'info', args.site, '--json'], text=True))
    if info['name'] != args.site or info['status'] != 'running' or urlparse(info['url']).hostname not in ('localhost', '127.0.0.1'):
        raise RuntimeError('Expected the running, loopback-only dedicated site.')
    site = Path(info['path']).resolve() / 'app/public'
    manifest_path = evidence / 'site.json'
    if manifest_path.exists() and json.loads(manifest_path.read_text()) != info:
        raise RuntimeError('Site identity changed; use a new evidence directory.')
    manifest_path.write_text(json.dumps(info, indent=2) + '\n')
    started = time.monotonic()

    def wp(*command, quiet=False):
        result = subprocess.run(['localwp', 'cli', info['id'], '--', *command], text=True, capture_output=True)
        # Commands contain no credentials. Keep evidence for failures as well.
        with (evidence / 'commands.log').open('a') as log:
            log.write('\n$ wp ' + ' '.join(command) + '\n' + result.stdout + result.stderr)
        if not quiet or result.returncode:
            print(result.stdout + result.stderr, end='', flush=True)
        if result.returncode:
            raise RuntimeError('WP-CLI failed; see commands.log')
        return result.stdout

    packages = evidence / 'packages'
    for label in ('baseline', 'candidate'):
        if not (packages / (label + '.zip')).is_file():
            raise RuntimeError('Missing reviewed package: ' + label)
    hashes = {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(packages.glob('*.zip'))}
    identity = evidence / 'packages.json'
    if identity.exists() and json.loads(identity.read_text()) != hashes:
        raise RuntimeError('Reviewed packages changed.')
    identity.write_text(json.dumps(hashes, indent=2) + '\n')
    owner = 'localwp-' + info['id']
    marker = site / 'wp-content/mu-plugins/er-localwp-acceptance.php'
    if args.action == 'prepare':
        if marker.exists():
            raise RuntimeError('Already prepared; refuse to reconfigure/reseed.')
        configs = {'WP_ENVIRONMENT_TYPE': 'local', 'ER_LOCALWP_OWNER': owner,
                   'ER_LOCALWP_TEST_ROOT': str(ROOT), 'ER_LOCALWP_TEST_PACKAGES': str(packages),
                   'FS_METHOD': 'direct'}
        for key, value in configs.items():
            wp('config', 'set', key, value)
        for key in ('DISABLE_WP_CRON', 'AUTOMATIC_UPDATER_DISABLED', 'WP_HTTP_BLOCK_EXTERNAL', 'WP_DEBUG', 'WP_DEBUG_LOG'):
            wp('config', 'set', key, 'true', '--raw')
        wp('config', 'set', 'WP_DEBUG_DISPLAY', 'false', '--raw')
        wp('option', 'update', 'blog_public', '0')
        wp('option', 'update', 'timezone_string', 'Europe/Kyiv')
        marker.parent.mkdir(exist_ok=True)
        marker.write_text('<?php\n// Disposable LocalWP acceptance only.\nrequire ' + repr(str(ROOT / 'tests/acceptance/localwp.php')) + ';\n')
    else:
        if not marker.is_file() or wp('config', 'get', 'ER_LOCALWP_OWNER').strip() != owner:
            raise RuntimeError('Owned disposable fixture not found.')
        harness = str(ROOT / 'tests/regressions/native-upgrade.php')
        label = 'baseline' if args.action in ('baseline', 'checkpoint', 'rollback') else 'candidate'
        if args.action == 'checkpoint':
            wp('eval-file', harness, 'snapshot', label)
        elif args.action == 'baseline':
            wp('eval-file', harness, 'install', label)
            wp('plugin', 'activate', 'exchange-rates')
            wp('eval-file', harness, 'seed', label)
        elif args.action in ('candidate', 'rollback', 'restored'):
            wp('eval-file', harness, 'upgrade', label)
        if args.action == 'lifecycle':
            lifecycle = str(ROOT / 'tests/regressions/localwp-lifecycle.php')
            wp('eval-file', harness, 'verify', 'candidate')
            wp('eval-file', lifecycle, 'snapshot')
            wp('plugin', 'deactivate', 'exchange-rates')
            wp('eval-file', lifecycle, 'inactive')
            wp('plugin', 'activate', 'exchange-rates')
            wp('eval-file', lifecycle, 'active')
            wp('cron', 'event', 'run', 'exchange-rates_data_update')
            (evidence / 'lifecycle-verification.txt').write_text(wp('eval-file', lifecycle, 'cron'))
        if args.action not in ('collect', 'lifecycle'):
            wp('eval-file', harness, 'verify', label)
            state_option = wp('eval', "echo getenv('ER_ACCEPTANCE_STATE_OPTION') ?: 'er_acceptance_saved_state';", quiet=True).strip()
            state = wp('option', 'get', state_option, '--format=json', quiet=True)
            (evidence / 'saved-state.json').write_text(state)
            cron = wp('cron', 'event', 'list', '--format=json', quiet=True)
            (evidence / (args.action + '-cron.json')).write_text(cron)
            events = [e for e in json.loads(cron) if e['hook'] == 'exchange-rates_data_update']
            if len(events) != 1:
                raise RuntimeError('Expected exactly one recurring refresh event.')
            (evidence / (args.action + '-verification.txt')).write_text(wp('eval-file', harness, 'verify', label))
        reports = site / 'wp-content/localwp-acceptance'
        if reports.is_dir():
            for report in reports.glob('*.json'):
                (evidence / report.name).write_bytes(report.read_bytes())
        debug = site / 'wp-content/debug.log'
        if debug.exists():
            (evidence / 'wordpress-debug.log').write_bytes(debug.read_bytes())
    print(f'Completed {args.action}: {time.monotonic()-started:.1f}s; evidence={evidence}', flush=True)


if __name__ == '__main__':
    main()
