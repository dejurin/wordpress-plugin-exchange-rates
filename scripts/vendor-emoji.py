#!/usr/bin/env python3
"""Verify bundled Twemoji provenance offline, or explicitly download to staging."""
import argparse
from concurrent.futures import ThreadPoolExecutor, as_completed
import hashlib
import json
from pathlib import Path
import re
import socket
import sys
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
REVISION = '7a3dad4a4da30497093dab22eafba135f02308e1'
VERSION = '14.0.2'
UPSTREAM = f'https://raw.githubusercontent.com/twitter/twemoji/{REVISION}/'
CDN = 'https://s.w.org/images/core/emoji/14.0.0/svg/'
LICENSE_SHA256 = '8ae9438818c26e4873b91d8c6ad620526c011e27e125677f13031eda903f007c'
MAPPING = ROOT / 'plugin/trunk/src/Models/Emoji.php'
MAX_BYTES = 1024 * 1024
RETRY_STATUS = {408, 425, 429, 500, 502, 503, 504}


def digest(data):
    return hashlib.sha256(data).hexdigest()


def mapped_assets():
    """Read the existing literal assignments without executing PHP."""
    pattern = re.compile(r"\$emoji_flags\['([^']+)'\] = '([0-9A-F]+(?:-[0-9A-F]+)*)';")
    mapping = {}
    for line in MAPPING.read_text().splitlines():
        if '$emoji_flags[' not in line:
            continue
        match = pattern.fullmatch(line.strip())
        if not match:
            raise ValueError('Unexpected emoji mapping expression: ' + line.strip())
        mapping[match[1]] = match[2].lower() + '.svg'
    if not mapping:
        raise ValueError('The emoji mapping is empty')
    return mapping, sorted(set(mapping.values()))


def write_once(path, data):
    """Never replace earlier evidence or artwork with different bytes."""
    path.parent.mkdir(parents=True, exist_ok=True)
    if path.exists():
        if path.is_symlink() or path.read_bytes() != data:
            raise ValueError(f'Refusing to replace different existing bytes: {path}')
        return
    with path.open('xb') as stream:
        stream.write(data)


def fetch(url):
    for attempt in range(1, 5):
        try:
            request = urllib.request.Request(url, headers={
                'User-Agent': 'ExchangeRates-Twemoji-Provenance/1.0',
                'Accept-Encoding': 'identity',
            })
            with urllib.request.urlopen(request, timeout=20) as response:
                if response.status != 200 or response.geturl() != url:
                    raise ValueError(f'Unexpected response or redirect for {url}')
                data = response.read(MAX_BYTES + 1)
            if not data or len(data) > MAX_BYTES:
                raise ValueError(f'Unexpected response size for {url}')
            return data, {'url': url, 'status': 200, 'bytes': len(data),
                          'sha256': digest(data), 'attempts': attempt}
        except urllib.error.HTTPError as error:
            if error.code not in RETRY_STATUS or attempt == 4:
                return None, {'url': url, 'status': error.code, 'attempts': attempt,
                              'error': str(error)}
        except (urllib.error.URLError, TimeoutError, socket.timeout) as error:
            if attempt == 4:
                return None, {'url': url, 'status': None, 'attempts': attempt,
                              'error': str(error)}
        time.sleep(min(2 ** (attempt - 1), 4))
    raise RuntimeError('Unreachable retry state')


def attribution():
    return (
        'Twemoji graphics\n'
        'Copyright 2019 Twitter, Inc and other contributors\n'
        'Graphics licensed under Creative Commons Attribution 4.0 International.\n'
        'License: https://creativecommons.org/licenses/by/4.0/\n'
        'Full license and warranty disclaimer: LICENSE-GRAPHICS\n'
        f'Source: https://github.com/twitter/twemoji/tree/{REVISION}/assets/svg\n'
        f'Upstream version: {VERSION}\n'
        f'Upstream commit: {REVISION}\n'
        'The SVG files are an unmodified subset selected by the plugin emoji mapping.\n'
        'Their bytes match the previously used WordPress.org CDN assets.\n'
        'No Twemoji JavaScript or other MIT-licensed implementation code is included.\n'
    ).encode()


def acquire(staging, workers):
    mapping, filenames = mapped_assets()
    manifest_path = staging / 'manifest.json'
    if manifest_path.exists():
        verify(manifest_path, staging / 'upstream', staging / 'cdn')
        print(f'Existing immutable staging verified; no download: {staging}')
        return
    started = time.monotonic()

    def download_asset(filename):
        row = {'filename': filename}
        bodies = []
        for label, url in [('cdn', CDN + filename),
                           ('upstream', UPSTREAM + 'assets/svg/' + filename)]:
            data, row[label] = fetch(url)
            bodies.append(data)
            if data is not None:
                write_once(staging / label / filename, data)
        row['identical'] = bodies[0] is not None and bodies[0] == bodies[1]
        return row

    assets = []
    with ThreadPoolExecutor(max_workers=workers) as pool:
        pending = [pool.submit(download_asset, name) for name in filenames]
        for completed, future in enumerate(as_completed(pending), 1):
            row = future.result()
            assets.append(row)
            elapsed = time.monotonic() - started
            rate = completed / max(elapsed, 0.001)
            eta = (len(filenames) - completed) / rate
            state = 'MATCH' if row['identical'] else 'MISMATCH/ERROR'
            print(f'[{completed}/{len(filenames)} {completed/len(filenames):.0%}] '
                  f'{state} {row["filename"]}; {elapsed:.1f}s; '
                  f'{rate:.1f} assets/s; ETA {eta:.1f}s', flush=True)
    license_data, license_record = fetch(UPSTREAM + 'LICENSE-GRAPHICS')
    license_record['file'] = 'LICENSE-GRAPHICS'
    if license_data is None or digest(license_data) != LICENSE_SHA256:
        raise ValueError('Upstream graphics license failed its pinned SHA-256 check')
    write_once(staging / 'upstream/LICENSE-GRAPHICS', license_data)
    notice = attribution()
    manifest = {
        'schema_version': 1, 'project': 'Twemoji', 'version': VERSION,
        'revision': REVISION,
        'mapping': {'file': str(MAPPING.relative_to(ROOT)),
                    'sha256': digest(MAPPING.read_bytes()), 'entries': len(mapping)},
        'license': license_record,
        'notice': {'file': 'NOTICE.txt', 'bytes': len(notice), 'sha256': digest(notice)},
        'assets': sorted(assets, key=lambda row: row['filename']),
    }
    write_once(manifest_path, (json.dumps(manifest, indent=2) + '\n').encode())
    failed = [row['filename'] for row in assets if not row['identical']]
    if failed:
        raise ValueError(f'{len(failed)} assets did not match; retained raw evidence in {staging}')
    write_once(staging / 'upstream/NOTICE.txt', notice)
    verify(manifest_path, staging / 'upstream', staging / 'cdn')


def verify(manifest_path, assets_path, cdn_path=None):
    manifest = json.loads(manifest_path.read_text())
    mapping, filenames = mapped_assets()
    if (manifest.get('schema_version'), manifest.get('project'),
            manifest.get('version'), manifest.get('revision')) != (1, 'Twemoji', VERSION, REVISION):
        raise ValueError('Unexpected provenance schema or upstream revision')
    expected_mapping = {'file': str(MAPPING.relative_to(ROOT)),
                        'sha256': digest(MAPPING.read_bytes()), 'entries': len(mapping)}
    if manifest['mapping'] != expected_mapping:
        raise ValueError('Current emoji mapping differs from the provenance record')
    if [row['filename'] for row in manifest['assets']] != filenames:
        raise ValueError('Manifest does not cover exactly the current mapped assets')
    if sorted(path.name for path in assets_path.glob('*.svg')) != filenames:
        raise ValueError('Local SVG set contains missing or additional files')
    for row in manifest['assets']:
        name = row['filename']
        if not row['identical'] or row['cdn']['sha256'] != row['upstream']['sha256']:
            raise ValueError(f'CDN/upstream provenance mismatch: {name}')
        for key, url in [('cdn', CDN + name), ('upstream', UPSTREAM + 'assets/svg/' + name)]:
            if row[key]['url'] != url or row[key]['status'] != 200:
                raise ValueError(f'Invalid source record: {name}/{key}')
        data = (assets_path / name).read_bytes()
        if digest(data) != row['upstream']['sha256'] or len(data) != row['upstream']['bytes']:
            raise ValueError(f'Local artwork changed: {name}')
        if row['cdn']['bytes'] != row['upstream']['bytes']:
            raise ValueError(f'CDN/upstream byte counts differ: {name}')
        if cdn_path is not None and (cdn_path / name).read_bytes() != data:
            raise ValueError(f'Staged CDN artwork changed: {name}')
    license_record = manifest['license']
    if (license_record['file'] != 'LICENSE-GRAPHICS'
            or license_record['url'] != UPSTREAM + 'LICENSE-GRAPHICS'
            or license_record['sha256'] != LICENSE_SHA256 or license_record['status'] != 200):
        raise ValueError('Invalid graphics-license provenance')
    for key in ['license', 'notice']:
        record = manifest[key]
        expected_name = 'LICENSE-GRAPHICS' if key == 'license' else 'NOTICE.txt'
        if record['file'] != expected_name:
            raise ValueError('Unexpected notice filename')
        data = (assets_path / expected_name).read_bytes()
        if digest(data) != record['sha256'] or len(data) != record['bytes']:
            raise ValueError(f'License or attribution bytes changed: {expected_name}')
    if (assets_path / 'NOTICE.txt').read_bytes() != attribution():
        raise ValueError('Attribution does not match the reviewed notice')
    print(f'PASS: {len(filenames)}/{len(filenames)} exact CDN/upstream/local matches; '
          f'license and attribution verified; manifest: {manifest_path}; assets: {assets_path}')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--download', action='store_true', help='Explicitly fetch pinned artwork into staging only')
    parser.add_argument('--staging', type=Path, help='Download/evidence directory; required with --download')
    parser.add_argument('--manifest', type=Path, default=ROOT / 'docs/third-party/twemoji.json')
    parser.add_argument('--assets', type=Path, default=ROOT / 'plugin/trunk/assets/flags/emoji')
    parser.add_argument('--workers', type=int, default=6)
    args = parser.parse_args()
    if args.download:
        if args.staging is None or not 1 <= args.workers <= 8:
            parser.error('--download requires --staging and 1–8 workers')
        staging = args.staging.resolve()
        if staging == (ROOT / 'plugin').resolve() or (ROOT / 'plugin').resolve() in staging.parents:
            parser.error('Download staging must be outside the installable plugin tree')
        acquire(staging, args.workers)
    else:
        if args.staging is not None:
            verify(args.staging / 'manifest.json', args.staging / 'upstream', args.staging / 'cdn')
        else:
            verify(args.manifest, args.assets)


if __name__ == '__main__':
    try:
        main()
    except (OSError, ValueError, KeyError, TypeError, json.JSONDecodeError) as error:
        print(f'ERROR: {error}', file=sys.stderr)
        raise SystemExit(1)
