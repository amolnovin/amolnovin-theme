#!/usr/bin/env bash
# Builds an incremental patch of the theme between two git refs and registers it in updates/manifest.json.
#
#   ./scripts/make-patch.sh <from-ref> <to-ref> <version> ["release notes"] [base-version] [build-ref]
#
# Example:
#   ./scripts/make-patch.sh v1.0.0 v1.1.0 1.1.0 "پنل تنظیمات و هماهنگی افزونه‌ها" 1.0.0
#
# `build-ref` (optional) also ships the compiled assets: the script builds the current tree and a
# worktree of <build-ref> (npm ci && npm run build), diffs the two `public/build` folders and puts the
# changed files into the patch. Use it whenever a release changes CSS/JS — the compiled bundle is not
# tracked in git, so without it the patch would only carry the sources.
#
# Example with assets:
#   ./scripts/make-patch.sh v1.1.1 HEAD 1.2.0 "هماهنگی با افزونه‌ی قطعه‌کدها" 1.1.1 v1.1.1
#
# Result:
#   wordpress-theme/updates/patches/amolnovin-<base>-to-<version>.zip   (changed files + manifest.json)
#   wordpress-theme/updates/manifest.json                              (catalogue entry with sha256)
# Needs: git, zip, python3, sha256sum (plus node/npm when build-ref is used).
set -euo pipefail

FROM_REF="${1:?from-ref is required (e.g. v1.0.0)}"
TO_REF="${2:?to-ref is required (e.g. v1.1.0 or HEAD)}"
VERSION="${3:?version is required (e.g. 1.1.0)}"
NOTES="${4:-}"
BASE_VERSION="${5:-}"
BUILD_REF="${6:-}"

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"        # wordpress-theme/
REPO="$(cd "$HERE/.." && pwd)"                                  # repository root
THEME_REL="wordpress-theme/amolnovin"
UPDATES="$HERE/updates"
PATCHES="$UPDATES/patches"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

mkdir -p "$PATCHES"

if [ -z "$BASE_VERSION" ]; then
  BASE_VERSION="$(git -C "$REPO" show "$FROM_REF:${THEME_REL}/style.css" | sed -n 's/^Version:[[:space:]]*//p' | head -1 | tr -d '[:space:]')"
fi

if [ -z "$BASE_VERSION" ]; then
  echo "could not detect the base version — pass it as the 5th argument" >&2
  exit 1
fi

PKG_NAME="amolnovin-${BASE_VERSION}-to-${VERSION}.zip"
PKG_REL="patches/${PKG_NAME}"

# files changed between the two refs (added / modified / renamed / deleted), theme-relative
git -C "$REPO" diff --name-status --find-renames "$FROM_REF" "$TO_REF" -- "$THEME_REL" > "$STAGE/status.txt"

CHANGED=()
DELETED=()
BUILT=()

while IFS=$'\t' read -r status path _rest; do
  [ -n "${path:-}" ] || continue
  rel="${path#"$THEME_REL"/}"

  case "$rel" in
    updates/*|public/build/*|vendor/*|node_modules/*|dist/*|.gitignore|*.log) continue ;;
  esac

  case "$status" in
    D) DELETED+=("$rel") ;;
    R*|C*)
      # rename/copy: the new path is a changed file; a rename also removes the old path
      new_rel="${_rest#"$THEME_REL"/}"
      [ -n "${new_rel:-}" ] && CHANGED+=("$new_rel")
      [ "${status#R}" != "$status" ] && DELETED+=("$rel")
      ;;
    *) CHANGED+=("$rel") ;;
  esac
done < "$STAGE/status.txt"

if [ ${#CHANGED[@]} -eq 0 ] && [ ${#DELETED[@]} -eq 0 ]; then
  echo "no theme changes between $FROM_REF and $TO_REF" >&2
  exit 1
fi

# stage the changed files at their theme-relative paths
for rel in "${CHANGED[@]}"; do
  mkdir -p "$STAGE/root/$(dirname "$rel")"
  git -C "$REPO" show "$TO_REF:${THEME_REL}/${rel}" > "$STAGE/root/$rel"
done

# -------------------------------------------------------------------------- compiled assets (optional)
# public/build/ is git-ignored: a patch that changes CSS/JS only carries the sources unless the built
# bundle is added here. We build the current tree and a worktree of $BUILD_REF and ship the difference.
if [ -n "$BUILD_REF" ]; then
  command -v npm >/dev/null 2>&1 || { echo "npm is required for the build-ref mode" >&2; exit 1; }

  echo "building the assets (current tree + $BUILD_REF) …"

  ( cd "$HERE/amolnovin" && { [ -d node_modules ] || npm ci --no-audit --no-fund >/dev/null; } && npm run build >/dev/null )

  OLD_TREE="$(mktemp -d)"
  rmdir "$OLD_TREE"
  git -C "$REPO" worktree add --detach "$OLD_TREE" "$BUILD_REF" >/dev/null 2>&1
  trap 'git -C "$REPO" worktree remove --force "$OLD_TREE" >/dev/null 2>&1 || true; rm -rf "$STAGE" "$OLD_TREE"' EXIT
  ( cd "$OLD_TREE/$THEME_REL" && npm ci --no-audit --no-fund >/dev/null && npm run build >/dev/null )

  NEW_BUILD="$HERE/amolnovin/public/build"
  OLD_BUILD="$OLD_TREE/$THEME_REL/public/build"

  if [ -d "$NEW_BUILD" ] && [ -d "$OLD_BUILD" ]; then
    while IFS= read -r rel; do
      [ -n "$rel" ] || continue
      mkdir -p "$STAGE/root/public/build/$(dirname "$rel")"
      cp "$NEW_BUILD/$rel" "$STAGE/root/public/build/$rel"
      BUILT+=("public/build/$rel")
    done < <(cd "$NEW_BUILD" && find . -type f | sed 's|^\./||' | while IFS= read -r rel; do
      cmp -s "$NEW_BUILD/$rel" "$OLD_BUILD/$rel" || echo "$rel"
    done)

    while IFS= read -r rel; do
      [ -n "$rel" ] || continue
      DELETED+=("public/build/$rel")
    done < <(cd "$OLD_BUILD" && find . -type f | sed 's|^\./||' | while IFS= read -r rel; do
      [ -f "$NEW_BUILD/$rel" ] || echo "$rel"
    done)
  else
    echo "warning: no public/build folder found — the patch carries the sources only" >&2
  fi

  echo "built assets: ${#BUILT[@]} changed, ${#DELETED[@]} removed"
fi

# hash every file and build the two manifests (inside the zip for offline installs, catalogue outside)
python3 - "$STAGE" "$VERSION" "${BASE_VERSION}" "$NOTES" "$PKG_REL" <<'PY'
import hashlib, json, os, sys
from datetime import date

stage, version, base, notes, package = sys.argv[1:6]
root = os.path.join(stage, 'root')

def sha(path):
    h = hashlib.sha256()
    with open(path, 'rb') as fh:
        for chunk in iter(lambda: fh.read(65536), b''):
            h.update(chunk)
    return h.hexdigest()

files = []
for dirpath, _dirnames, filenames in os.walk(root):
    for name in sorted(filenames):
        full = os.path.join(dirpath, name)
        rel = os.path.relpath(full, root).replace(os.sep, '/')
        files.append({'path': rel, 'sha256': sha(full)})

files.sort(key=lambda r: r['path'])

release = {
    'version': version,
    'date': date.today().isoformat(),
    'channel': 'stable',
    'requires': {'php': '8.4', 'wp': '6.6', 'from': [base]},
    'notes': notes,
    'files': files,
    'deleted': [],
}

with open(os.path.join(root, 'manifest.json'), 'w', encoding='utf-8') as fh:
    json.dump(release, fh, ensure_ascii=False, indent=2)

with open(os.path.join(stage, 'release.json'), 'w', encoding='utf-8') as fh:
    json.dump(release, fh, ensure_ascii=False, indent=2)
PY

# deletions are recorded after the fact (they carry no content in the zip)
if [ ${#DELETED[@]} -gt 0 ]; then
  python3 - "$STAGE/release.json" "${DELETED[@]}" <<'PY'
import json, sys
path, deleted = sys.argv[1], sys.argv[2:]
data = json.load(open(path, encoding='utf-8'))
data['deleted'] = deleted
json.dump(data, open(path, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
json.dump(data, open(path.replace('release.json', 'root/manifest.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
PY
fi

# build the zip (theme-relative paths at its root)
( cd "$STAGE/root" && zip -qr9 "$PATCHES/$PKG_NAME" . -x '.*' )

ZIP_SHA="$(sha256sum "$PATCHES/$PKG_NAME" | cut -d' ' -f1)"

# merge the release into the catalogue
python3 - "$UPDATES/manifest.json" "$STAGE/release.json" "$PKG_REL" "$ZIP_SHA" <<'PY'
import json, os, sys
from datetime import datetime, timezone

manifest_path, release_path, package, zip_sha = sys.argv[1:5]
release = json.load(open(release_path, encoding='utf-8'))
release['package'] = package
release['sha256'] = zip_sha

if os.path.exists(manifest_path):
    manifest = json.load(open(manifest_path, encoding='utf-8'))
else:
    manifest = {'schema': 1, 'theme': 'amolnovin', 'releases': []}

manifest.setdefault('schema', 1)
manifest.setdefault('theme', 'amolnovin')
manifest.setdefault('name', 'قالب وردپرس آمل نوین')
manifest['generated'] = datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')
releases = [r for r in manifest.get('releases', []) if r.get('version') != release['version']]
releases.append(release)
releases.sort(key=lambda r: [int(p) if p.isdigit() else p for p in r['version'].replace('-', '.').split('.')])
manifest['releases'] = releases

json.dump(manifest, open(manifest_path, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
open(manifest_path, 'a', encoding='utf-8').write('\n')
print(f"release {release['version']} → {package} ({len(release['files'])} files)")
PY

echo "patch:   $PATCHES/$PKG_NAME"
echo "sha256:  $ZIP_SHA"
echo "catalog: $UPDATES/manifest.json"
