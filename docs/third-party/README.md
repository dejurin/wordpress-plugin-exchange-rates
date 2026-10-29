# Bundled Twemoji provenance

`twemoji.json` records the exact SVG subset used by `Models/Emoji.php`, both
source URLs and SHA-256 hashes. The graphics come from official Twemoji v14.0.2,
commit `7a3dad4a4da30497093dab22eafba135f02308e1`, and are byte-identical to the
previously used WordPress.org `14.0.0` CDN files. Graphics are CC BY 4.0;
Twemoji's MIT license applies to its implementation code, which is not bundled.

## Offline verification

From the repository root, run the same verification used by the CI package job:

```sh
python3 scripts/vendor-emoji.py
```

This checks the current mapping hash, exact SVG set, pinned source records,
local file hashes, original `LICENSE-GRAPHICS` and `NOTICE.txt`. It makes no
network requests and changes no files. The manifest has no changing timestamps.

## Explicit acquisition for a reviewed update

```sh
python3 scripts/vendor-emoji.py --download --staging dist/twemoji-review/staging
python3 scripts/vendor-emoji.py --staging dist/twemoji-review/staging
```

Downloads use bounded concurrency and retries, preserving separate `cdn/` and
`upstream/` copies plus a manifest. Staging must be outside the installable
plugin tree. Different existing bytes are never overwritten; completed staging
is verified and reused without downloading again. Use a new staging directory
for a new acquisition. Promotion into the plugin requires review; it is not an
automatic side effect of downloading.

Mapping, artwork, upstream revision or license changes are separate reviewed
updates. Recheck license/attribution and compare every CDN/upstream file before
updating the manifest; never regenerate hashes just to accept changed artwork.
Retain the original graphics license and accurate modification notices.

Hash verification proves artifact identity, not exhaustive safety or visual
compatibility. Keep SVG safety, browser, packaged-ZIP and Plugin Check coverage.
