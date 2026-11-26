#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
SLUG="carotte-wc-price-calculator"
VERSION="$(sed -n 's/^ \* Version: //p' "$SLUG.php")"
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { printf 'Invalid version\n' >&2; exit 1; }
git rev-parse --verify HEAD >/dev/null 2>&1 || {
  printf 'Create a first commit before building a release archive.\n' >&2
  exit 1
}
if [[ -n "$(git status --porcelain)" ]]; then
  printf 'Commit all changes before building a release archive.\n' >&2
  exit 1
fi
mkdir -p dist
git archive --format=zip --prefix="$SLUG/" -o "dist/$SLUG-$VERSION.zip" HEAD
printf 'Archive: %s/dist/%s-%s.zip\n' "$ROOT" "$SLUG" "$VERSION"
