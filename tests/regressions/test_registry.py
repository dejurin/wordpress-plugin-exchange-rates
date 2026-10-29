"""The registry must fail closed on missing coverage or invented green states."""
import copy
import importlib.util
import json
from pathlib import Path
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location("regressions", ROOT / "scripts/regressions.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class RegistryTests(unittest.TestCase):
    def setUp(self):
        self.data = module.read_catalog()

    def validate(self, data):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "catalog.json"
            path.write_text(json.dumps(data))
            return module.read_catalog(path)

    def test_all_existing_suites_are_registered(self):
        self.validate(self.data)

    def test_removing_coverage_is_an_error(self):
        for entry in self.data["regressions"]:
            entry["tests"] = [name for name in entry["tests"] if name != "tests/browser/rtl-numbers.spec.js"]
        with self.assertRaisesRegex(ValueError, "missing from"):
            self.validate(self.data)

    def test_duplicate_id_is_an_error(self):
        self.data["regressions"].append(copy.deepcopy(self.data["regressions"][0]))
        with self.assertRaisesRegex(ValueError, "duplicate"):
            self.validate(self.data)

    def test_file_removal_is_an_error(self):
        self.data["regressions"][0]["tests"].append("tests/not-a-real-regression.php")
        with self.assertRaisesRegex(ValueError, "Missing or unsafe"):
            self.validate(self.data)

    def test_declaring_pass_without_execution_is_an_error(self):
        self.data["regressions"][0]["status"] = "PASS"
        with self.assertRaisesRegex(ValueError, "must not claim"):
            self.validate(self.data)

    def test_cannot_erase_historical_baseline(self):
        self.data["baseline"] = "1.4.2"
        with self.assertRaisesRegex(ValueError, "historical baseline"):
            self.validate(self.data)

    def test_wordpress_runner_rejects_unregistered_files(self):
        self.data["wordpress_steps"].append(["tests/../../unsafe.php"])
        with self.assertRaisesRegex(ValueError, "Unregistered WordPress suite"):
            self.validate(self.data)

    def test_wordpress_runner_rejects_invalid_arguments(self):
        self.data["wordpress_steps"].append([])
        with self.assertRaisesRegex(ValueError, "Invalid WordPress suite arguments"):
            self.validate(self.data)


if __name__ == "__main__":
    unittest.main()
