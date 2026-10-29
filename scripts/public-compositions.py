#!/usr/bin/env python3
"""Prepare sanitized component fixtures from reviewed, read-only DOM observations."""
import argparse
import json
import re
from pathlib import Path
from html.parser import HTMLParser

ROOT = Path(__file__).resolve().parents[1]
DEST = ROOT / 'tests/acceptance/public-cases'


class Elements(HTMLParser):
    def __init__(self, markup):
        super().__init__()
        self.items = []
        self.feed(markup)

    def handle_starttag(self, tag, attrs):
        self.items.append((tag, dict(attrs)))


def converter_rates(markup, source):
    data = next(a for tag, a in Elements(markup).items if 'data-currencies' in a)
    options = json.loads(data['data-currencies'])
    rates = {code: 1 / entry['rate'] if source == 'currencyrate' else entry['rate'] for code, entry in options.items()}
    first = next(iter(rates))
    rates[first] = rates.pop(first)
    return rates


def prepare(captures):
    DEST.mkdir(exist_ok=True)
    definitions = {
        'katuru': ('table', 'currencyrate', 'USD', ['AUD', 'BRL', 'EUR', 'GBP', 'JPY']),
        'todayafrica': ('table', 'currencyrate', 'USD', ['BTC', 'CNY', 'EUR', 'GBP', 'XAU', 'ZAR']),
        'editorialrn': ('converter', 'ar-bcra-retail', 'ARS', []),
        'morningtelegraph': ('converter', 'currencyrate', 'USD', []),
        'centralbank': ('badge', 'currencyrate', 'ZAR', ['USD', 'GBP', 'EUR', 'JPY', 'CAD', 'BWP']),
        'sovereign': ('badge', 'currencyrate', 'NGN', ['USD']),
    }
    for i, (key, (surface, source, base, currencies)) in enumerate(definitions.items(), 1):
        files = sorted(p for p in captures.glob(key + '-*.json') if re.search(r'-\d+\.json$', p.name))
        if not files:
            raise ValueError('Missing observation: ' + key)
        observations = {str(json.loads(p.read_text())['viewport']['width']): json.loads(p.read_text()) for p in files}
        observation = observations['1280']
        widget = observation['widgets'][0]
        if widget['version'] != '1.4.2':
            raise ValueError('Reconsider baseline version: ' + key)
        elements = Elements(widget['html']).items
        attrs = {'amount': 1, 'base_currency': base, 'currency_list': currencies}
        rates, previous = {base: 1}, {base: 1}
        if surface == 'table':
            cells = [a for tag, a in elements if tag == 'td' and 'data-rate' in a]
            if len(cells) != len(currencies):
                raise ValueError('Currency order needs review: ' + key)
            rates.update({code: float(cell['data-rate']) for code, cell in zip(currencies, cells)})
            previous.update({code: float(cell['data-previous-rate']) for code, cell in zip(currencies, cells)})
            attrs.update({'decimals': 4, 'code': False, 'region': key == 'todayafrica',
                          'flag_type': 'rectangular', 'flag_size': 16, 'full_width': True,
                          'amount_active': key == 'todayafrica', 'border': False, 'base_show': True,
                          'table_headers_previous_close_show': False, 'table_headers_changes_show': False})
        elif surface == 'converter':
            data = next(a for tag, a in elements if 'data-currencies' in a)
            # Service moves the last rate code to the first option on render.
            rates = converter_rates(widget['html'], source)
            previous = rates.copy()
            attrs = {name: data.get('data-' + name, '') for name in ['title', 'from', 'to']}
            attrs.update({'amount': 1, 'border': True, 'symbol': True, 'code': False, 'after': False})
        else:
            rows = [n['text'] for n in widget['computed'] if n['class'] == 'badge-leaders']
            if len(rows) != len(currencies):
                raise ValueError('Badge currency order needs review: ' + key)
            displayed = [float(re.search(r'([\d,.]+)\s*$', row).group(1).replace(',', '')) for row in rows]
            rates.update({code: 1 / rate for code, rate in zip(currencies, displayed)})
            previous = rates.copy()
            attrs.update({'inverse': True, 'code': key == 'sovereign', 'decimals': 2,
                          'flag_type': 'square' if key == 'sovereign' else 'none',
                          'color': '#2066a3' if key == 'sovereign' else '#fdfaf7',
                          'symbol': key == 'centralbank', 'base_show': False})
        envs = {}
        css_rules, provenance, excluded = [], [], []
        supplemental = captures / (key + '-supplement.json')
        style_snapshots = list(observations.values())
        if supplemental.exists():
            style_snapshots.append(json.loads(supplemental.read_text()))
        for width, snap in observations.items():
            root = snap['widgets'][0]
            envs[width] = {'width': root['box']['width'], 'ancestors': root['ancestors'],
                           'reference': root['computed'], 'capturedAt': snap['capturedAt'],
                           'dpr': snap['viewport']['dpr']}
            if surface == 'converter':
                envs[width]['rates'] = converter_rates(root['html'], source)
        for snap in style_snapshots:
            for sheet in snap['sheets']:
                # This observed optimizer file contains the released plugin CSS.
                # Loading it would conceal differences in the candidate stylesheet.
                if 'autoptimize_single_14fa1adb45f808a57833ec94faf7ac34' in (sheet['href'] or ''):
                    excluded.append(sheet['href'])
                    continue
                provenance.append(sheet['href'] or sheet['owner'])
                for rule in sheet['rules']:
                    if rule.startswith('@font-face'):
                        if not re.search(r'font-family: ["\']?(?:Arimo|Muli|Roboto|Open Sans)', rule):
                            continue
                        if 'unicode-range:' in rule and 'U+0-FF' not in rule:
                            continue
                        if 'font-style: italic' in rule:
                            continue
                    else:
                        # Unrelated page images are not part of the component fixture.
                        rule = re.sub(r'url\([^)]*\)', 'none', rule)
                    if rule not in css_rules:
                        css_rules.append(rule)
        css = '\n'.join(css_rules)
        extra_fonts = captures / (key + '-fonts.css')
        if extra_fonts.exists():
            for rule in re.findall(r'@font-face\s*\{[^}]+\}', extra_fonts.read_text()):
                if 'font-style: normal' in rule and 'font-weight: 400' in rule:
                    rule = re.sub(r'url\((https://[^)]+)\)', r'url("\1")', rule)
                    css += '\n' + rule
        (DEST / (key + '.css')).write_text('/* Captured theme rules; source URLs are in the adjacent JSON. */\n' + css + '\n')
        case = {'id': key, 'surface': surface, 'url': observation['url'], 'observed_version': widget['version'],
                'source': source, 'base': base, 'attributes': attrs, 'rates': rates, 'previous': previous,
                'settings': {'decimals': 4, 'currency_format': 3}, 'environments': envs,
                'css_sources': list(dict.fromkeys(provenance)), 'excluded_plugin_css': list(dict.fromkeys(excluded)),
                'unreadable_stylesheets': observation['inaccessible'],
                'notes': ['Frozen public numerical examples are test data, not current financial quotes.',
                          'Ancestor layout is normalized outside the component; unrelated page sections are not cloned.',
                          'Only public DOM-visible options are reconstructed; no owner database is accessed.']}
        (DEST / (key + '.json')).write_text(json.dumps(case, indent=2, ensure_ascii=False) + '\n')
        print(f'[{i}/{len(definitions)}] {key}: {surface}, {len(envs)} observed sizes, theme CSS {len(css)} bytes', flush=True)
    print('Prepared 6 permanent compositions in ' + str(DEST))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--captures', type=Path, required=True)
    args = parser.parse_args()
    prepare(args.captures)
