#!/usr/bin/env python3
"""Install, upgrade and roll back reviewed ZIPs in fresh disposable Docker projects."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import secrets
import shutil
import subprocess
import time
import zipfile

ROOT = Path(__file__).resolve().parents[1]
CURRENT = "wordpress:7.1.3-php8.3-apache@sha256:81a34254acaa6ff3b9a67268c2745e9a041c4094cbe5ccd35b75ed3bbdc033b5"
CLI = "wordpress:cli-2.12.0-php8.3@sha256:8c63570c29d2357eef1215547619b968284ef897953a109b0e00cdd21f417343"
DB = "mariadb:11.4.8@sha256:bc474f00629f0123c10f9e1bca193a45d18af15a274cf0656acda64f1086c3b6"


def identity(path):
    with zipfile.ZipFile(path) as archive:
        header = archive.read("exchange-rates/Install.php").decode()
        version = re.search(r"^Version:\s*(\d+\.\d+\.\d+)\s*$", header, re.M)
        if not version:
            raise ValueError("Missing package version")
    return {"version": version[1], "sha256": hashlib.sha256(path.read_bytes()).hexdigest()}


def run(args):
    manifest = json.loads(args.baselines.read_text())
    candidate = identity(args.candidate)
    baselines = []
    for item in manifest:
        path = (ROOT / item["path"]).resolve()
        if identity(path) != {"version": item["version"], "sha256": item["sha256"]}:
            raise ValueError("Baseline identity mismatch: " + item["version"])
        baselines.append((item, path))
    if not re.fullmatch(r"er-[a-z0-9-]+", args.project_prefix):
        raise ValueError("Use a dedicated er- project prefix")
    if not 101 <= args.subnet_start <= 230:
        raise ValueError("Use an explicit unused subnet range between 101 and 230")
    args.output.mkdir(parents=True, exist_ok=False)
    (args.output / "identity.json").write_text(json.dumps({"candidate": candidate, "baselines": manifest}, indent=2) + "\n")
    cases = [(runtime, item, path) for runtime in args.runtimes for item, path in baselines]
    started, results = time.monotonic(), []
    for index, (runtime, item, baseline) in enumerate(cases):
        name = runtime + "-" + item["version"].replace(".", "")
        project = args.project_prefix + "-" + name
        for resource, command in (("containers", ["docker", "ps", "-aq"]), ("volumes", ["docker", "volume", "ls", "-q"])):
            existing = subprocess.check_output(command + ["--filter", "label=com.docker.compose.project=" + project], text=True)
            if existing.strip():
                raise ValueError("Refuse to reuse existing " + resource + " for " + project)
        directory = args.output / name
        packages = directory / "packages"
        packages.mkdir(parents=True)
        shutil.copyfile(args.candidate, packages / "candidate.zip")
        shutil.copyfile(baseline, packages / "baseline.zip")
        environment = directory / "private.env"
        environment.write_text("DB_PASSWORD=" + secrets.token_hex(24) + "\nDB_ROOT_PASSWORD=" + secrets.token_hex(24) + "\nWP_ADMIN_PASSWORD=" + secrets.token_hex(24) + "\n")
        environment.chmod(0o600)
        # No ports are published; wp-cli uses loopback URLs only as site identity.
        site = "http://127.0.0.1:19336"
        env = {"WORDPRESS_DB_HOST": "db:3306", "WORDPRESS_DB_NAME": "wordpress", "WORDPRESS_DB_USER": "wordpress", "WORDPRESS_DB_PASSWORD": "${DB_PASSWORD}", "EXCHANGE_RATES_TEST_MODE": "1", "ER_ACCEPTANCE_ID": project, "DEV_SITE_URL": site, "WP_ADMIN_PASSWORD": "${WP_ADMIN_PASSWORD}", "WORDPRESS_CONFIG_EXTRA": "define('WP_ENVIRONMENT_TYPE','local'); define('WP_HOME',getenv('DEV_SITE_URL')); define('WP_SITEURL',getenv('DEV_SITE_URL')); define('DISABLE_WP_CRON',true); define('AUTOMATIC_UPDATER_DISABLED',true); define('WP_HTTP_BLOCK_EXTERNAL',true); define('FS_METHOD','direct');"}
        mounts = ["site:/var/www/html", "plugins:/var/www/html/wp-content/plugins", str(directory.resolve()) + ":/acceptance:ro", str(ROOT / "tests") + ":/opt/tests:ro", str(ROOT / "tests/http-fixture.php") + ":/var/www/html/wp-content/mu-plugins/exchange-rates-test-data.php:ro"]
        config = {"services": {
            "db": {"image": DB, "pull_policy": "never", "environment": {"MARIADB_DATABASE": "wordpress", "MARIADB_USER": "wordpress", "MARIADB_PASSWORD": "${DB_PASSWORD}", "MARIADB_ROOT_PASSWORD": "${DB_ROOT_PASSWORD}"}, "volumes": ["database:/var/lib/mysql"], "healthcheck": {"test": ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"], "interval": "2s", "timeout": "3s", "retries": 40}},
            "wordpress": {"image": CURRENT if runtime == "current" else args.minimum_image, "pull_policy": "never", "environment": env.copy(), "volumes": mounts.copy(), "depends_on": {"db": {"condition": "service_healthy"}}, "healthcheck": {"test": ["CMD", "php", "-r", "exit(is_file('/var/www/html/wp-config.php') && @fsockopen('127.0.0.1',80) ? 0 : 1);"], "interval": "2s", "timeout": "3s", "retries": 40}},
            "cli": {"image": CLI, "pull_policy": "never", "user": "33:33", "environment": env, "volumes": mounts, "profiles": ["tools"]}},
            "volumes": {"site": {}, "plugins": {}, "database": {}},
            "networks": {"default": {"internal": True, "ipam": {"config": [{"subnet": f"10.254.{args.subnet_start + index}.0/28"}]}}}}
        if runtime == "minimum":
            config["services"]["wordpress"]["environment"]["EXCHANGE_RATES_DISPOSABLE_PROJECT"] = "exchange-rates-release-minimum-20261015"
            # The minimum image installs its own fixture before starting Apache.
            config["services"]["wordpress"]["volumes"] = mounts[:-1]
        compose_file = directory / "compose.json"
        compose_file.write_text(json.dumps(config, indent=2) + "\n")
        compose = ["docker", "compose", "-p", project, "--env-file", str(environment.resolve()), "-f", str(compose_file.resolve())]
        wp = compose + (["exec", "-T", "--user", "www-data", "wordpress", "wp"] if runtime == "minimum" else ["run", "--rm", "cli"])
        log_path = directory / "native.log"
        def execute(command, log):
            result = subprocess.run(command, stdout=log, stderr=subprocess.STDOUT, timeout=180)
            if result.returncode:
                raise RuntimeError("Command failed; inspect native.log")
        status, error = "PASS", ""
        try:
            with log_path.open("w") as log:
                execute(compose + ["up", "-d", "--wait", "--wait-timeout", "120"], log)
                execute(compose + ["exec", "-T", "wordpress", "sh", "-c", "mkdir -p /var/www/html/wp-content/upgrade && chown www-data:www-data /var/www/html/wp-content/plugins /var/www/html/wp-content/upgrade"], log)
                # Read the generated credential only inside the guarded local container.
                install = "wp core install --url=\"$DEV_SITE_URL\" --title='Native acceptance' --admin_user=acceptance --admin_password=\"$WP_ADMIN_PASSWORD\" --admin_email=acceptance@example.test --skip-email"
                setup = compose + (["exec", "-T", "--user", "www-data", "wordpress", "sh", "-c", install] if runtime == "minimum" else ["run", "--rm", "--entrypoint", "sh", "cli", "-c", install])
                execute(setup, log)
                execute(wp + ["eval", "echo 'Runtime: PHP ', PHP_VERSION, ' / WordPress ', get_bloginfo('version'), PHP_EOL;"], log)
                execute(wp + ["eval-file", "/opt/tests/regressions/native-upgrade.php", "install", "baseline"], log)
                execute(wp + ["plugin", "activate", "exchange-rates"], log)
                for mode, label in [("seed", "baseline"), ("verify", "baseline"), ("upgrade", "candidate"), ("verify", "candidate"), ("upgrade", "baseline"), ("verify", "baseline"), ("upgrade", "candidate"), ("verify", "candidate")]:
                    execute(wp + ["eval-file", "/opt/tests/regressions/native-upgrade.php", mode, label], log)
                execute(wp + ["eval-file", "/opt/tests/version-wordpress.php"], log)
                execute(wp + ["eval-file", "/opt/tests/converter-controls-wordpress.php"], log)
        except (RuntimeError, subprocess.TimeoutExpired) as exception:
            status, error = "FAIL", str(exception)
        finally:
            # Keep owned volumes and logs for investigation; never delete data.
            subprocess.run(compose + ["stop"], capture_output=True, timeout=60)
        result = {"runtime": runtime, "baseline": item["version"], "candidate": candidate, "project": project, "status": status, "error": error, "log": str(log_path)}
        results.append(result)
        (args.output / "results.json").write_text(json.dumps(results, indent=2) + "\n")
        elapsed = time.monotonic() - started
        print(f"[{index+1}/{len(cases)}] {(index+1)/len(cases):.0%} {status} {name}; elapsed={elapsed:.1f}s ETA={elapsed/(index+1)*(len(cases)-index-1):.1f}s", flush=True)
    passed = sum(row["status"] == "PASS" for row in results)
    print(f"Summary: {passed}/{len(cases)} native upgrade/rollback cases passed; volumes preserved; output={args.output}")
    return 0 if passed == len(cases) else 1


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--candidate", type=Path, required=True)
    parser.add_argument("--baselines", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--project-prefix", required=True)
    parser.add_argument("--subnet-start", type=int, required=True)
    parser.add_argument("--runtimes", nargs="+", choices=["current", "minimum"], default=["current", "minimum"])
    parser.add_argument("--minimum-image", default="exchange-rates-release-minimum:wp5.6-php5.6-20261015")
    raise SystemExit(run(parser.parse_args()))
