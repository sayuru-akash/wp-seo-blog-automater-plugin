#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
REPO_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"
PLUGIN_SLUG="wp-seo-blog-automater"

require_command() {
    local command_name="$1"

    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Missing required command: $command_name" >&2
        exit 1
    fi
}

assert_zip_contains() {
    local zip_index_file="$1"
    local expected_path="$2"

    if ! grep -Fqx "$expected_path" "$zip_index_file"; then
        echo "ZIP validation failed: missing $expected_path" >&2
        exit 1
    fi
}

assert_zip_excludes() {
    local zip_index_file="$1"
    local forbidden_pattern="$2"

    if grep -Eq "$forbidden_pattern" "$zip_index_file"; then
        echo "ZIP validation failed: found forbidden entry matching $forbidden_pattern" >&2
        exit 1
    fi
}

cd "$REPO_ROOT"

require_command php
require_command bash
require_command zip
require_command unzip

version="$(php "$SCRIPT_DIR/get-version.php")"
zip_path="dist/${PLUGIN_SLUG}-v${version}.zip"
zip_index="$(mktemp "${TMPDIR:-/tmp}/wp-seo-automater-zip.XXXXXX")"
packaged_main_file="$(mktemp "${TMPDIR:-/tmp}/wp-seo-automater-main.XXXXXX")"

cleanup() {
    rm -f "$zip_index" "$packaged_main_file"
}

trap cleanup EXIT

echo "Validating PHP syntax..."
while IFS= read -r -d '' php_file; do
    php -l "$php_file" >/dev/null
done < <(find . -type f -name '*.php' -not -path './build/*' -not -path './dist/*' -print0)

echo "Building plugin package for version $version..."
bash ./build.sh >/dev/null

if [ ! -f "$zip_path" ]; then
    echo "Build validation failed: expected package $zip_path was not created." >&2
    exit 1
fi

unzip -Z -1 "$zip_path" >"$zip_index"
unzip -p "$zip_path" "${PLUGIN_SLUG}/wp-seo-blog-automater.php" >"$packaged_main_file"

packaged_version="$(php "$SCRIPT_DIR/get-version.php" --file "$packaged_main_file")"
if [ "$packaged_version" != "$version" ]; then
    echo "Packaged plugin version mismatch: expected $version but found $packaged_version" >&2
    exit 1
fi

assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/wp-seo-blog-automater.php"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/uninstall.php"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/README.md"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/LICENSE"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/admin/js/admin.js"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/admin/css/style.css"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/includes/class-wp-seo-automater-admin.php"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/includes/class-gemini-api-handler.php"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/includes/class-github-updater.php"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/images/logo.png"
assert_zip_contains "$zip_index" "${PLUGIN_SLUG}/languages/wp-seo-blog-automater.pot"

assert_zip_excludes "$zip_index" "^${PLUGIN_SLUG}/tests(/|$)"
assert_zip_excludes "$zip_index" "^${PLUGIN_SLUG}/\\.git"
assert_zip_excludes "$zip_index" "^${PLUGIN_SLUG}/\\.github"
assert_zip_excludes "$zip_index" "^${PLUGIN_SLUG}/build(/|$)"
assert_zip_excludes "$zip_index" "^${PLUGIN_SLUG}/dist(/|$)"
assert_zip_excludes "$zip_index" "^${PLUGIN_SLUG}/build\\.sh$"
assert_zip_excludes "$zip_index" "^${PLUGIN_SLUG}/build\\.bat$"
assert_zip_excludes "$zip_index" "^${PLUGIN_SLUG}/BUILD\\.md$"

echo "Release build validation passed for version $version."
