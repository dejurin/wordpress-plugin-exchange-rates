#!/usr/bin/env python3
"""Cache only font URLs already observed in the maintained public compositions."""
import argparse
import concurrent.futures
import hashlib
import json
from pathlib import Path
import re
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--output', type=Path, required=True)
args = parser.parse_args()
args.output.mkdir(parents=True, exist_ok=True)
urls = sorted({url for path in (ROOT / 'tests/acceptance/public-cases').glob('*.css')
               for url in re.findall(r'url\("(https://[^\"]+\.(?:woff2|ttf))"\)', path.read_text())})


def download(url):
    old = previous.get(url)
    if old and re.fullmatch(r'[a-f0-9]{64}\.(?:woff2|ttf)', old.get('file', '')):
        cached = args.output / old['file']
        if cached.is_file() and hashlib.sha256(cached.read_bytes()).hexdigest() == old['sha256']:
            return url, old
    with urllib.request.urlopen(url, timeout=15) as response:
        data = response.read(1048577)
    if len(data) > 1048576 or data[:4] not in [b'wOF2', b'\x00\x01\x00\x00']:
        raise ValueError('Unexpected font payload: ' + url)
    digest = hashlib.sha256(data).hexdigest()
    extension = '.woff2' if data[:4] == b'wOF2' else '.ttf'
    name = digest + extension
    (args.output / name).write_bytes(data)
    return url, {'file': name, 'sha256': digest, 'bytes': len(data)}


manifest_path = args.output / 'manifest.json'
previous = json.loads(manifest_path.read_text()) if manifest_path.exists() else {}
manifest = {}
errors = []
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
    for index, future in enumerate(concurrent.futures.as_completed([pool.submit(download, url) for url in urls]), 1):
        try:
            url, item = future.result()
            manifest[url] = item
            print(f'[{index}/{len(urls)}] Cached {item["bytes"]} bytes', flush=True)
        except Exception as error:
            errors.append(str(error))
            print(f'[{index}/{len(urls)}] ERROR: {error}', flush=True)
if not errors:
    temporary = args.output / 'manifest.json.tmp'
    temporary.write_text(json.dumps(manifest, indent=2) + '\n')
    temporary.replace(manifest_path)
print(f'Fonts: {len(manifest)}/{len(urls)}; failures: {len(errors)}; output: {args.output}')
raise SystemExit(bool(errors))
