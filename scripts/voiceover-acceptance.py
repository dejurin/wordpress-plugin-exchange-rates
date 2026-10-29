#!/usr/bin/env python3
"""Record real VoiceOver speech on the explicitly authorized localhost Safari stand.

Requires prior user permission for VoiceOver AppleScript control. This utility
does not enable VoiceOver or change its permissions. Navigation stays in CUA.
"""
import argparse
from datetime import datetime, timezone
import json
from pathlib import Path
import subprocess
import time


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('output', type=Path)
    parser.add_argument('--seconds', type=float, default=2)
    parser.add_argument('--move', choices=['left', 'right', 'into item', 'out of item'])
    parser.add_argument('--count', type=int, default=1)
    parser.add_argument('--action', action='store_true')
    parser.add_argument('--input', choices=['0', '2', 'bad'], help='Bounded amount-field test input')
    parser.add_argument('--escape', action='store_true', help='Close the test dialog with Escape')
    args = parser.parse_args()
    if not 0 < args.seconds <= 30 or not 1 <= args.count <= 40:
        parser.error('Capture must be bounded to 30 seconds or 40 moves.')
    if args.output.exists():
        parser.error('Refuse to overwrite an existing speech recording.')
    guard = '''if not (frontmost of application "Safari") then return "__OUTSIDE_TEST__"
tell application "Safari"
if not (exists front window) then return "__OUTSIDE_TEST__"
if URL of current tab of front window does not start with "http://127.0.0.1:19335/" then return "__OUTSIDE_TEST__"
end tell
'''
    read = 'tell application "VoiceOver" to get content of last phrase'
    records = []
    stopped = None
    started = time.monotonic()

    def capture(command=''):
        nonlocal stopped
        # Guard both before actions and immediately before reading speech.
        result = subprocess.run(['osascript', '-e', guard + command + guard + read],
                                text=True, capture_output=True, timeout=4)
        if result.returncode:
            stopped = result.stderr.strip()
            return False
        phrase = result.stdout.strip()
        if phrase == '__OUTSIDE_TEST__':
            stopped = 'Safari lost the authorized foreground page; capture stopped.'
            return False
        if phrase and (command or not records or phrase != records[-1]['phrase']):
            record = {'elapsed': round(time.monotonic() - started, 3), 'phrase': phrase}
            records.append(record)
            print(json.dumps(record, ensure_ascii=False), flush=True)
        return True

    try:
        # Restore the already validated test window before starting the recording.
        activation = '''tell application "Safari"
if not (exists front window) then error "Missing Safari test window"
if URL of current tab of front window does not start with "http://127.0.0.1:19335/" then error "Not the authorized test page"
activate
end tell
delay 0.5
'''
        subprocess.run(['osascript', '-e', activation], check=True,
                       text=True, capture_output=True, timeout=4)
        if args.escape:
            subprocess.run(['osascript', '-e', guard + 'tell application "System Events" to key code 53'],
                           check=True, text=True, capture_output=True, timeout=4)
        if args.input is not None:
            entry = '''tell application "System Events" to tell process "Safari"
set focusedItem to value of attribute "AXFocusedUIElement"
if role of focusedItem is not "AXTextField" then error "Expected amount text field"
if description of focusedItem is not in {"Amount in USD", "Amount to convert"} then error "Not an allowed amount field"
keystroke "a" using command down
delay 0.3
if value of attribute "AXSelectedText" of focusedItem is not value of focusedItem then error "Amount selection did not settle"
keystroke "''' + args.input + '''"
end tell
'''
            subprocess.run(['osascript', '-e', guard + entry], check=True,
                           text=True, capture_output=True, timeout=4)
        if args.action:
            capture('tell application "VoiceOver" to tell vo cursor to perform action\ndelay 0.4\n')
        if not stopped and args.move:
            for _ in range(args.count):
                if not capture('tell application "VoiceOver" to tell vo cursor to move ' + args.move + '\ndelay 0.4\n'):
                    break
        elif not stopped:
            while time.monotonic() - started < args.seconds:
                if not capture():
                    break
                time.sleep(0.1)
    except subprocess.TimeoutExpired:
        stopped = 'AppleScript timed out; no speech inferred.'
    except subprocess.CalledProcessError as error:
        stopped = error.stderr.strip()
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps({
        'captured_at': datetime.now(timezone.utc).isoformat(),
        'method': 'Native VoiceOver last phrase; foreground Safari and localhost:19335 guards',
        'records': records, 'stopped': stopped,
    }, ensure_ascii=False, indent=2) + '\n')
    print(f'Saved {len(records)} phrases: {args.output}; stopped={stopped}', flush=True)
    if stopped or not records:
        raise SystemExit(1)


if __name__ == '__main__':
    main()
