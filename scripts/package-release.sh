#!/bin/sh
set -eu

root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
stage="$root/dist/kiln-cleanup"
archive="$root/dist/kiln-cleanup-0.1.0-dev.zip"

rm -rf "$stage" "$archive" "$archive.sha256"
mkdir -p "$stage"
cp -R "$root/src" "$root/docs" "$root/openspec" "$stage/"
cp "$root/kiln-cleanup.php" "$root/LICENSE" "$root/composer.json" "$stage/"
(
  cd "$stage"
  if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
  else
    docker run --rm -v "$stage:/app" -w /app composer:2.8 install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
  fi
)
(
  cd "$root/dist"
  zip -qr "$(basename "$archive")" kiln-cleanup
  shasum -a 256 "$(basename "$archive")" > "$(basename "$archive").sha256"
)
echo "Created $archive"
