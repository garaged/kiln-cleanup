#!/bin/sh
set -eu
root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$root/dist"
shasum -a 256 -c kiln-cleanup-0.1.0-dev.zip.sha256
unzip -t kiln-cleanup-0.1.0-dev.zip >/dev/null
echo "Release archive verification passed."
