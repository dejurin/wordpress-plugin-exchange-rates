#!/usr/bin/env python3
"""Create local-only credentials without replacing an existing environment."""

import os
from pathlib import Path
import secrets
import sys

root = Path(__file__).resolve().parents[1]
destination = root / "wordpress/.env"
content = (root / "wordpress/.env.example").read_text()
for key in ("DB_PASSWORD", "DB_ROOT_PASSWORD", "WP_ADMIN_PASSWORD"):
    content = content.replace(f"{key}=\n", f"{key}={secrets.token_urlsafe(32)}\n")
try:
    descriptor = os.open(destination, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
except FileExistsError:
    sys.exit(f"Environment already exists; left unchanged: {destination}")
with os.fdopen(descriptor, "w") as output:
    output.write(content)
print(f"Created {destination} (mode 0600). Credentials were not printed.")
