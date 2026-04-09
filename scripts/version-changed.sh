#!/usr/bin/env bash

set -euo pipefail

NULL_SHA="0000000000000000000000000000000000000000"
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REPO_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"
PLUGIN_MAIN_FILE="wp-seo-blog-automater.php"

usage() {
    echo "Usage: $0 <old-ref> <new-ref>" >&2
    exit 2
}

version_for_ref() {
    local ref="$1"
    local temp_file
    local version
    local status=0

    temp_file="$(mktemp "${TMPDIR:-/tmp}/wp-seo-automater-version.XXXXXX")"

    if ! git show "${ref}:${PLUGIN_MAIN_FILE}" >"$temp_file" 2>/dev/null; then
        rm -f "$temp_file"
        return 3
    fi

    version="$(php "$SCRIPT_DIR/get-version.php" --file "$temp_file")" || status=$?
    rm -f "$temp_file"

    if [ "$status" -ne 0 ]; then
        return "$status"
    fi

    printf '%s\n' "$version"
}

if [ "$#" -ne 2 ]; then
    usage
fi

cd "$REPO_ROOT"

old_ref="$1"
new_ref="$2"

if [ "$new_ref" = "$NULL_SHA" ]; then
    echo "Skipping deleted ref." >&2
    exit 1
fi

if ! git cat-file -e "${new_ref}^{commit}" 2>/dev/null; then
    echo "Commit not found: $new_ref" >&2
    exit 2
fi

if [ "$old_ref" = "$NULL_SHA" ]; then
    if git rev-parse "${new_ref}^" >/dev/null 2>&1; then
        old_ref="$(git rev-parse "${new_ref}^")"
    else
        echo "No previous commit found for $new_ref. Treating as a version change." >&2
        exit 0
    fi
fi

if ! new_version="$(version_for_ref "$new_ref")"; then
    echo "Unable to read the plugin version from $new_ref." >&2
    exit 2
fi

if ! old_version="$(version_for_ref "$old_ref")"; then
    echo "No previous plugin version found at $old_ref. Treating as a version change." >&2
    exit 0
fi

if [ "$old_version" != "$new_version" ]; then
    echo "Plugin version changed: $old_version -> $new_version" >&2
    exit 0
fi

echo "Plugin version unchanged: $new_version" >&2
exit 1
