#!/usr/bin/env python3
"""Validate, lint and package the single plugin source tree using stdlib only."""

import argparse
import hashlib
import json
from pathlib import Path
import re
import subprocess
import sys
import time
import zipfile

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "plugin/trunk"


def inventory():
    files = {}
    for path in sorted(SOURCE.rglob("*")):
        relative = path.relative_to(SOURCE)
        if path.is_symlink():
            raise ValueError(f"Symlinks are not allowed: {relative}")
        if any(part in (".git", ".svn", "node_modules", "__pycache__") for part in relative.parts):
            raise ValueError(f"Unexpected development path: {relative}")
        if path.name.startswith(".env") or path.suffix in (".log", ".zip", ".pyc") or path.name == ".DS_Store":
            raise ValueError(f"Unexpected local file: {relative}")
        if path.is_file():
            files[relative.as_posix()] = path.read_bytes()
    if not files:
        raise ValueError("The plugin tree is empty")
    return files


def match(pattern, text, label):
    result = re.search(pattern, text, re.MULTILINE)
    if not result:
        raise ValueError(f"Missing {label}")
    return result.group(1).strip()


def check(files):
    required = ("Install.php", "readme.txt", "src/Plugin.php", "vendor/autoload.php")
    for name in required:
        if name not in files:
            raise ValueError(f"Missing exact-case path: {name}")
    if "install.php" in files:
        raise ValueError("Unexpected lowercase install.php")
    header = files["Install.php"].decode()
    readme = files["readme.txt"].decode()
    version = match(r"^Version:\s*(\S+)", header, "plugin version")
    if not re.fullmatch(r"\d+\.\d+\.\d+", version):
        raise ValueError(f"Invalid release version: {version}")
    versions = [version, match(r"^Stable tag:\s*(\S+)", readme, "stable tag"),
                match(r"const VERSION = '([^']+)'", files["src/Plugin.php"].decode(), "runtime version")]
    if len(set(versions)) != 1:
        raise ValueError(f"Version mismatch: {versions}")
    for field in ("Requires at least", "Requires PHP", "License"):
        if match(rf"^{field}:\s*(.+)$", header, field) != match(rf"^{field}:\s*(.+)$", readme, field):
            raise ValueError(f"Metadata mismatch: {field}")
    print(f"PASS: version={version}, files={len(files)}, required paths and metadata agree")
    return version


def baseline(files):
    expected = json.loads((ROOT / "docs/baseline-1.3.2.json").read_text())["files_sha256"]
    actual = {name: hashlib.sha256(data).hexdigest() for name, data in files.items()}
    differences = sorted(name for name in expected.keys() | actual.keys() if expected.get(name) != actual.get(name))
    if differences:
        raise ValueError("Baseline differences:\n" + "\n".join(differences))
    print(f"PASS: {len(files)}/{len(expected)} files match the published 1.3.2 baseline")


def lint(files):
    started = time.monotonic()
    paths = [SOURCE / name for name in files if name.endswith(".php")]
    for index, path in enumerate(paths, 1):
        result = subprocess.run(["php", "-l", str(path)], capture_output=True, text=True)
        if result.returncode:
            raise ValueError(result.stdout + result.stderr)
        if index % 10 == 0 or index == len(paths):
            elapsed = time.monotonic() - started
            rate = index / max(elapsed, 0.001)
            print(f"[{index}/{len(paths)}] {index / len(paths):.0%} elapsed={elapsed:.1f}s rate={rate:.1f}/s ETA={(len(paths)-index)/rate:.1f}s", flush=True)
    print(f"PASS: {len(paths)} PHP files, 0 syntax errors")


def package(files, version):
    destination = ROOT / "dist" / f"exchange-rates.{version}.zip"
    destination.parent.mkdir(exist_ok=True)
    with zipfile.ZipFile(destination, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name, data in files.items():
            info = zipfile.ZipInfo("exchange-rates/" + name, (1980, 1, 1, 0, 0, 0))
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            archive.writestr(info, data, compresslevel=9)
    with zipfile.ZipFile(destination) as archive:
        unpacked = {name.removeprefix("exchange-rates/"): archive.read(name) for name in archive.namelist()}
    if unpacked != files:
        raise ValueError("ZIP contents differ from plugin/trunk")
    digest = hashlib.sha256(destination.read_bytes()).hexdigest()
    destination.with_suffix(".zip.sha256").write_text(f"{digest}  {destination.name}\n")
    print(f"PASS: packaged and verified {len(files)} files; output={destination}; SHA256={digest}")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("command", choices=("check", "baseline", "lint", "package"))
    args = parser.parse_args()
    files = inventory()
    version = check(files)
    if args.command == "baseline":
        baseline(files)
    elif args.command == "lint":
        lint(files)
    elif args.command == "package":
        package(files, version)


if __name__ == "__main__":
    try:
        main()
    except (ValueError, OSError, KeyError, UnicodeError, zipfile.BadZipFile) as error:
        sys.exit(f"ERROR: {error}")
