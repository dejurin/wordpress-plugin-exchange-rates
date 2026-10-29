#!/usr/bin/env python3
"""Assemble an existing local acceptance run without exposing environment files."""
import argparse
import json
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--evidence', type=Path, required=True)
parser.add_argument('--run', type=Path, required=True)
parser.add_argument('--output', type=Path, required=True)
args = parser.parse_args()
report = json.loads((args.run / 'results.json').read_text())
if not report.get('finishedAt'):
    raise SystemExit('Refusing an unfinished test run')
report['artifacts'] = json.loads((args.evidence / 'artifacts.json').read_text())
report['publicImages'] = json.loads((args.evidence / 'screenshot-review.json').read_text())
report['sources'] = {p.stem: json.loads(p.read_text())['url']
                     for p in (ROOT / 'tests/acceptance/public-cases').glob('*.json')}
args.output.mkdir(parents=True, exist_ok=True)
report['native'] = {}
for browser in ['safari', 'firefox']:
    result_path = args.evidence / (browser + '.json')
    if not result_path.exists():
        continue
    native = json.loads(result_path.read_text())
    checks = [check for case in native['cases'] for side in ['baseline', 'candidate']
              for check in case.get(side, {}).get('checks', [])]
    report['native'][browser] = {'total': len(native['cases']), 'counts': native['counts'],
                                  'checks': sum(c['pass'] for c in checks),
                                  'failureModes': sum(sum(c.get('failureModes', {}).values()) for c in native['cases']),
                                  'finishedAt': native['finishedAt'], 'userAgent': native['userAgent']}
    (args.output / 'native').mkdir(exist_ok=True)
    shutil.copy2(result_path, args.output / 'native' / result_path.name)
    for image in args.evidence.glob(browser + '-*.png'):
        shutil.copy2(image, args.output / 'native' / image.name)
if report['native']:
    shutil.copy2(ROOT / 'tests/acceptance/public-native-report.html', args.output / 'native.html')
for folder in ['public', 'screenshots']:
    (args.output / folder).mkdir(exist_ok=True)
for path in (args.evidence / 'public').glob('*.jpg'):
    shutil.copy2(path, args.output / 'public' / path.name)
for path in args.run.glob('*.png'):
    shutil.copy2(path, args.output / 'screenshots' / path.name)
shutil.copy2(ROOT / 'tests/acceptance/public-compare.html', args.output / 'index.html')
shutil.copy2(ROOT / 'tests/acceptance/public-compare.js', args.output / 'public-compare.js')
(args.output / 'report-data.json').write_text(json.dumps(report, indent=2) + '\n')
print(json.dumps({'cases': len(report['cases']), 'counts': report['counts'], 'output': str(args.output)}))
