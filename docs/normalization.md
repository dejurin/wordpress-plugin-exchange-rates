# Repository normalization: 2026-10-14

Canonical public repository: <https://github.com/dejurin/wordpress-plugin-exchange-rates>.
The original 72-commit history was preserved; previous main: `a6f8d56f2649215e232d7a108f81c20f74c3df48`.
Published code import into `plugin/trunk/`: `ae7048c5a20099a3eb592a0a7f51c27e0869f9cf`.

## Verified source

- Version: **1.3.2**.
- SVN: <https://plugins.svn.wordpress.org/exchange-rates/tags/1.3.2>, last-changed revision **3656329**, 2026-08-20.
- Inspected snapshot: global SVN revision **3746744**. This identifies the entire SVN snapshot, not a plugin change revision.
- Official ZIP: <https://downloads.wordpress.org/plugin/exchange-rates.1.3.2.zip>.
- Official ZIP SHA-256: `1db5b5f74d5d42f43f495a3d85ce23fa8d140c0e87313321fe7a15c1e450a97f`.
- SVN trunk, the tag and the ZIP contained the same **926 files**; see [individual SHA-256 hashes](baseline-1.3.2.json).

The import changes no runtime code, shipped libraries, translations or version metadata. The main file remains `Install.php` with an uppercase I.
Development files live outside `plugin/trunk/` and are excluded from the installable ZIP.
The root LICENSE matches the declared GPLv2 or later; library licenses remain in place.

## Preserved deferred work

The previous private 1.3.0 working copy had one difference from the official 1.3.0 release:
in `assets/css/public/style.css`, the `.cr-exchange-rates.shortcode-exchange-rates-badge .badge-leaders` rule changed `align-items: center` to `align-items: stretch`.
Official 1.3.2 still uses `center`. The change was not imported into the clean baseline; its purpose and visual effect need a separate PR.

The private copy contains SVN tag snapshots and a local Docker environment. Its `.env`, history containing local files and root MIT license were not imported into the public project.
The private repository was archived after the public result was verified and a backup preserved. It remains private; its README points to this canonical repository.

## Verification boundaries

Byte-for-byte equality establishes import fidelity, not the absence of defects in the published plugin.
The new infrastructure was checked separately: packaging, PHP syntax, startup from clean volumes, activation, shortcodes, widgets and settings.
WordPress SVN remained strictly read-only throughout normalization.
