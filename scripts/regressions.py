#!/usr/bin/env python3
"""Validate the permanent regression registry and run unit or isolated WordPress suites."""

import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import time
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[1]
CATALOG = ROOT / "tests/regressions/catalog.json"


def read_catalog(path=CATALOG, root=ROOT):
    data = json.loads(path.read_text())
    if data.get("schema_version") != 1 or data.get("baseline") != "1.3.2":
        raise ValueError("Unsupported registry or changed historical baseline")
    ids, covered = set(), set()
    for entry in data["regressions"]:
        if not entry["id"].startswith("ER-") or entry["id"] in ids:
            raise ValueError("Invalid/duplicate regression ID: " + entry["id"])
        ids.add(entry["id"])
        if not entry["contract"] or not entry["recorded_scope"]:
            raise ValueError("Missing regression contract: " + entry["id"])
        if entry["status"] not in ("registered", "pending"):
            raise ValueError("A registry entry must not claim a test result")
        if entry["status"] == "registered" and not entry["tests"]:
            raise ValueError("Registered regression has no tests: " + entry["id"])
        if entry["status"] == "pending" and not entry.get("gap"):
            raise ValueError("Pending regression needs an explicit gap")
        covered.update(entry["tests"])
    for entry in data["observational_only"]:
        if not entry["reason"]:
            raise ValueError("Historical exclusions need an explanation")
        covered.add(entry["file"])
    for name in covered:
        file = root / name
        if not name.startswith("tests/") or not file.is_file() or not file.resolve().is_relative_to(root.resolve()):
            raise ValueError("Missing or unsafe test path: " + name)
    required = {p.relative_to(root).as_posix() for pattern in ("tests/browser/*.spec.js", "tests/*-wordpress.php") for p in root.glob(pattern)}
    if required - covered:
        raise ValueError("Tests missing from the permanent registry: " + ", ".join(sorted(required - covered)))
    if len(set(data["unit_tests"])) != len(data["unit_tests"]):
        raise ValueError("Duplicate unit suite")
    for name in data["unit_tests"]:
        if name not in covered or not name.endswith(".php"):
            raise ValueError("Unregistered unit suite: " + name)
    for step in data["wordpress_steps"]:
        if not isinstance(step, list) or not step or not all(isinstance(value, str) for value in step):
            raise ValueError("Invalid WordPress suite arguments")
        if step[0] not in covered or not step[0].endswith(".php"):
            raise ValueError("Unregistered WordPress suite: " + step[0])
    return data


def inventory(directory, exclude=()):
    return {p.relative_to(directory).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest()
            for p in sorted(directory.rglob("*")) if p.is_file()
            and not p.relative_to(directory).as_posix().startswith(exclude)}


def run_unit(data, output):
    # Never overwrite an earlier receipt or combine evidence from different runs.
    output.mkdir(parents=True, exist_ok=False)
    before = inventory(ROOT / "plugin/trunk")
    tests_before = {name: hashlib.sha256((ROOT / name).read_bytes()).hexdigest() for name in data["unit_tests"]}
    started = time.monotonic()
    results = []
    for index, name in enumerate(data["unit_tests"], 1):
        tick = time.monotonic()
        try:
            result = subprocess.run(["php", name], cwd=ROOT, capture_output=True, text=True, timeout=120)
            code, log = result.returncode, result.stdout + result.stderr
        except subprocess.TimeoutExpired:
            code, log = 124, "Suite exceeded the 120-second timeout.\n"
        log_name = Path(name).stem + ".log"
        (output / log_name).write_text(log)
        results.append({"test": name, "status": "PASS" if code == 0 else "FAIL", "exit_code": code,
                        "seconds": round(time.monotonic() - tick, 3), "log": log_name})
        elapsed = time.monotonic() - started
        count = len(data["unit_tests"])
        print(f"[{index}/{count}] {index/count:.0%} {results[-1]['status']} {name}; elapsed={elapsed:.1f}s ETA={elapsed/index*(count-index):.1f}s", flush=True)
    unchanged = before == inventory(ROOT / "plugin/trunk") and all(
        digest == hashlib.sha256((ROOT / name).read_bytes()).hexdigest() for name, digest in tests_before.items())
    receipt = {"created_utc": datetime.now(timezone.utc).isoformat(), "scope": "primary plugin/trunk; PHP unit suites only",
               "runtime": subprocess.check_output(["php", "-r", "echo PHP_VERSION;"], text=True),
               "catalog_sha256": hashlib.sha256(CATALOG.read_bytes()).hexdigest(), "test_hashes": tests_before,
               "plugin_files": before, "source_unchanged_during_run": unchanged, "results": results,
               "pending_regressions": [e["id"] for e in data["regressions"] if e["status"] == "pending"],
               "release_ready": False,
               "not_run": ["WordPress integration", "browser", "native ZIP upgrades/rollback", "visual acceptance", "real-site fixtures", "LocalWP"]}
    (output / "results.json").write_text(json.dumps(receipt, indent=2) + "\n")
    passed = sum(r["status"] == "PASS" for r in results)
    print(f"Summary: {passed}/{len(results)} PHP suites passed; source stable={unchanged}; release readiness NOT established; output={output}")
    return 0 if passed == len(results) and unchanged else 1


def wordpress_context(args):
    """Reject ordinary databases before any fixture writes."""
    url = urlparse(args.site)
    if url.scheme != "http" or url.hostname not in ("127.0.0.1", "localhost") or url.username or url.password:
        raise ValueError("Only loopback HTTP test sites are allowed")
    compose = ["docker", "compose", "--project-name", args.project, "--env-file", str(args.env_file.resolve()), "-f", str(args.compose_file.resolve())]
    guard = "if ('1' !== getenv('EXCHANGE_RATES_TEST_MODE') || 'local' !== wp_get_environment_type() || getenv('DEV_SITE_URL') !== " + json.dumps(args.site) + ") { WP_CLI::error('Wrong isolated environment'); } echo PHP_VERSION, ' / WordPress ', get_bloginfo('version');"
    preflight = subprocess.run(compose + ["run", "--rm", "cli", "eval", guard], capture_output=True, text=True, timeout=60)
    if preflight.returncode:
        raise ValueError("Disposable WordPress guard failed; no fixtures were run")
    return compose, preflight.stdout.strip()


def run_wordpress(data, args):
    """Run the accumulated integration suites only on a guarded disposable site."""
    compose, runtime = wordpress_context(args)
    output = args.output.resolve()
    output.mkdir(parents=True, exist_ok=False)
    before = inventory(ROOT / "plugin/trunk")
    tests = {p.relative_to(ROOT).as_posix(): hashlib.sha256(p.read_bytes()).hexdigest() for p in (ROOT / "tests").glob("*.php")}
    started = time.monotonic()
    results = []
    for index, step in enumerate(data["wordpress_steps"], 1):
        file, *arguments = step
        if not (ROOT / file).is_file() or not file.startswith("tests/"):
            raise ValueError("Invalid WordPress suite")
        command = compose + ["run", "--rm", "cli", "eval-file", "/opt/" + file, *arguments]
        try:
            result = subprocess.run(command, cwd=ROOT, capture_output=True, text=True, timeout=180)
            code, log = result.returncode, result.stdout + result.stderr
        except subprocess.TimeoutExpired:
            code, log = 124, "WordPress suite exceeded 180 seconds.\n"
        name = f"{index:02d}-{Path(file).stem}.log"
        (output / name).write_text(log)
        results.append({"test": step, "status": "PASS" if code == 0 else "FAIL", "exit_code": code, "log": name})
        elapsed = time.monotonic() - started
        total = len(data["wordpress_steps"])
        print(f"[{index}/{total}] {index/total:.0%} {results[-1]['status']} {' '.join(step)}; elapsed={elapsed:.1f}s ETA={elapsed/index*(total-index):.1f}s", flush=True)
    stable = before == inventory(ROOT / "plugin/trunk") and all(
        digest == hashlib.sha256((ROOT / name).read_bytes()).hexdigest() for name, digest in tests.items())
    receipt = {"created_utc": datetime.now(timezone.utc).isoformat(), "scope": "isolated WordPress integration suites; synthetic fixture writes authorized",
               "site": args.site, "project": args.project, "runtime": runtime, "plugin_files": before,
               "test_hashes": tests, "source_unchanged_during_run": stable, "results": results, "release_ready": False}
    (output / "results.json").write_text(json.dumps(receipt, indent=2) + "\n")
    passed = sum(r["status"] == "PASS" for r in results)
    print(f"Summary: {passed}/{len(results)} WordPress suites passed; source stable={stable}; output={output}")
    return 0 if stable and passed == len(results) else 1


def run_browser(data, args):
    """Seed the browser contract, run all specs and verify persisted state afterward."""
    compose, runtime = wordpress_context(args)
    output = args.output.resolve()
    output.mkdir(parents=True, exist_ok=False)
    excluded = ("browser/node_modules/", "browser/test-results/", "browser/playwright-report/", "regressions/__pycache__/")
    before, tests_before = inventory(ROOT / "plugin/trunk"), inventory(ROOT / "tests", excluded)
    results = []
    for mode in ("seed", "browser", "verify"):
        log_path = output / (mode + ".log")
        env = os.environ.copy()
        if mode == "browser":
            command = ["npm", "--prefix", "tests/browser", "test", "--", "--max-failures=8"]
            env.update(WP_TEST_URL=args.site, WP_TEST_ENV_FILE=str(args.env_file.resolve()),
                       COMPOSE_FILE=str(args.compose_file.resolve()), COMPOSE_PROJECT_NAME=args.project)
        else:
            command = compose + ["run", "--rm", "cli", "eval-file", "/opt/tests/table-isolation-wordpress.php", mode]
        started = time.monotonic()
        print(f"Starting {mode}; output={log_path}", flush=True)
        with log_path.open("w") as log:
            process = subprocess.Popen(command, cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, env=env)
            count, total, last_notice = 0, 0, 0
            while process.poll() is None:
                time.sleep(1)
                elapsed = time.monotonic() - started
                if elapsed > 1200:
                    process.kill()
                    process.wait()
                    break
                if mode == "browser" and elapsed - last_notice >= 15:
                    text = log_path.read_text()
                    match = re.search(r"Running (\d+) tests", text)
                    total = int(match[1]) if match else 0
                    counts = re.findall(r"[✓✘]\s+(\d+)\s", text)
                    count = int(counts[-1]) if counts else 0
                    eta = elapsed / count * (total - count) if count else 0
                    print(f"[{count}/{total}] {count/max(total,1):.0%} browser; elapsed={elapsed:.0f}s ETA={eta:.0f}s", flush=True)
                    last_notice = elapsed
        results.append({"step": mode, "exit_code": process.returncode, "status": "PASS" if process.returncode == 0 else "FAIL", "log": log_path.name})
        if mode == "seed" and process.returncode:
            break
    # Browser output/artifacts are generated under tests; hash maintained inputs only.
    tests_after = inventory(ROOT / "tests", excluded)
    stable = before == inventory(ROOT / "plugin/trunk") and tests_after == tests_before
    receipt = {"created_utc": datetime.now(timezone.utc).isoformat(), "site": args.site, "project": args.project,
               "runtime": runtime, "plugin_files": before, "test_hashes": tests_before, "source_unchanged_during_run": stable,
               "results": results, "release_ready": False}
    (output / "results.json").write_text(json.dumps(receipt, indent=2) + "\n")
    passed = sum(item["status"] == "PASS" for item in results)
    print(f"Summary: {passed}/3 browser stages passed; source stable={stable}; output={output}")
    return 0 if len(results) == 3 and passed == 3 and stable else 1


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("command", choices=("check", "run-unit", "run-wordpress", "run-browser"))
    parser.add_argument("--output", type=Path, help="New directory for immutable run evidence")
    parser.add_argument("--compose-file", type=Path)
    parser.add_argument("--env-file", type=Path)
    parser.add_argument("--project")
    parser.add_argument("--site")
    args = parser.parse_args()
    data = read_catalog()
    pending = sum(e["status"] == "pending" for e in data["regressions"])
    print(f"Registry valid: {len(data['regressions'])} groups, {pending} pending; registration is not a PASS.")
    if args.command == "run-unit":
        if not args.output:
            parser.error("run-unit requires --output pointing to a new evidence directory")
        return run_unit(data, args.output.resolve())
    if args.command in ("run-wordpress", "run-browser"):
        if not all((args.output, args.compose_file, args.env_file, args.project, args.site)):
            parser.error(args.command + " requires --output, --compose-file, --env-file, --project and --site")
        return run_wordpress(data, args) if args.command == "run-wordpress" else run_browser(data, args)
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except (ValueError, OSError, KeyError, TypeError) as error:
        sys.exit("ERROR: " + str(error))
