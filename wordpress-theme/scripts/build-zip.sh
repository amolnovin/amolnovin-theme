#!/usr/bin/env bash
# Builds the production zip of the theme (vendor + compiled assets inside) from ./amolnovin
#   ./scripts/build-zip.sh        ->  dist/amolnovin-sage-theme.zip
# Needs: PHP >= 8.3, Composer 2, Node >= 20.19, zip
set -euo pipefail
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

cp -a "$HERE/amolnovin" "$STAGE/amolnovin"
cd "$STAGE/amolnovin"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build
rm -rf node_modules

# library tests / docs are not needed at runtime; Carbon ships 800+ locale files — keep English (fallback) and Persian
find vendor -type d \( -iname tests -o -iname test -o -iname docs -o -iname examples -o -iname .github \) -prune -exec rm -rf {} + 2>/dev/null || true
find vendor -type f \( -iname "*.md" -o -iname "phpunit*.xml*" -o -iname "CHANGELOG*" -o -iname ".gitattributes" -o -iname ".editorconfig" -o -iname "*.dist" \) -not -iname "LICENSE*" -delete 2>/dev/null || true
find vendor/nesbot/carbon/src/Carbon/Lang -type f ! \( -name 'en.php' -o -name 'en_*.php' -o -name 'fa.php' -o -name 'fa_*.php' \) -delete 2>/dev/null || true
php -r 'require "vendor/autoload.php"; echo "vendor autoload ok\n";'

mkdir -p "$HERE/dist"
rm -f "$HERE/dist/amolnovin-sage-theme.zip"
(cd "$STAGE" && zip -qr9 "$HERE/dist/amolnovin-sage-theme.zip" amolnovin)
echo "built: $HERE/dist/amolnovin-sage-theme.zip ($(du -h "$HERE/dist/amolnovin-sage-theme.zip" | cut -f1))"
