#!/bin/bash
#
# WP SEO Blog Automater - Build Script
# Creates a production-ready ZIP file for distribution
#
# Usage: ./build.sh
#
# @package    WP_SEO_Blog_Automater
# @author     Codezela Technologies
# @version    plugin-version

set -euo pipefail

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_SLUG="wp-seo-blog-automater"
BUILD_DIR="build"
DIST_DIR="dist"

# Get the script directory (plugin root)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

if ! command -v php >/dev/null 2>&1; then
    echo "Error: php is required to detect the plugin version." >&2
    exit 1
fi

VERSION="$(php "$SCRIPT_DIR/scripts/get-version.php")"

echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   WP SEO Blog Automater - Build Script v${VERSION}   ║${NC}"
echo -e "${BLUE}║          Codezela Technologies                         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

cd "$SCRIPT_DIR"

echo -e "${YELLOW}→${NC} Preparing build environment..."

# Clean previous builds
if [ -d "$BUILD_DIR" ]; then
    rm -rf "$BUILD_DIR"
fi

# Create directories
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"
mkdir -p "$DIST_DIR"

# Clean only ZIP files from dist (preserve other files)
rm -f "$DIST_DIR"/*.zip 2>/dev/null || true

echo -e "${GREEN}✓${NC} Build directories created"

# Copy plugin files
echo -e "${YELLOW}→${NC} Copying plugin files..."

# Main files
cp wp-seo-blog-automater.php "$BUILD_DIR/$PLUGIN_SLUG/"
cp uninstall.php "$BUILD_DIR/$PLUGIN_SLUG/"
cp README.md "$BUILD_DIR/$PLUGIN_SLUG/"
cp LICENSE "$BUILD_DIR/$PLUGIN_SLUG/"
cp CHANGELOG.md "$BUILD_DIR/$PLUGIN_SLUG/" 2>/dev/null || true
cp CONTRIBUTING.md "$BUILD_DIR/$PLUGIN_SLUG/" 2>/dev/null || true

echo -e "${GREEN}✓${NC} Main files copied"

# Copy directories
echo -e "${YELLOW}→${NC} Copying directories..."

cp -r admin "$BUILD_DIR/$PLUGIN_SLUG/"
cp -r includes "$BUILD_DIR/$PLUGIN_SLUG/"
cp -r images "$BUILD_DIR/$PLUGIN_SLUG/"
cp -r languages "$BUILD_DIR/$PLUGIN_SLUG/"

echo -e "${GREEN}✓${NC} Directories copied"

# Remove excluded files and directories
echo -e "${YELLOW}→${NC} Cleaning build..."

# Remove tests directory if exists
if [ -d "$BUILD_DIR/$PLUGIN_SLUG/tests" ]; then
    rm -rf "$BUILD_DIR/$PLUGIN_SLUG/tests"
fi

# Remove dot files and directories
find "$BUILD_DIR/$PLUGIN_SLUG" -name ".*" -type f -delete
find "$BUILD_DIR/$PLUGIN_SLUG" -name ".*" -type d -exec rm -rf {} + 2>/dev/null || true

# Remove common development files
find "$BUILD_DIR/$PLUGIN_SLUG" -name "*.map" -delete
find "$BUILD_DIR/$PLUGIN_SLUG" -name "*.log" -delete
find "$BUILD_DIR/$PLUGIN_SLUG" -name "node_modules" -type d -exec rm -rf {} + 2>/dev/null || true
find "$BUILD_DIR/$PLUGIN_SLUG" -name "vendor" -type d -exec rm -rf {} + 2>/dev/null || true
find "$BUILD_DIR/$PLUGIN_SLUG" -name ".DS_Store" -delete 2>/dev/null || true
find "$BUILD_DIR/$PLUGIN_SLUG" -name "Thumbs.db" -delete 2>/dev/null || true

# Remove build-related files from the package
rm -f "$BUILD_DIR/$PLUGIN_SLUG/build.sh" 2>/dev/null || true
rm -f "$BUILD_DIR/$PLUGIN_SLUG/build.bat" 2>/dev/null || true
rm -f "$BUILD_DIR/$PLUGIN_SLUG/BUILD.md" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_SLUG/dist" 2>/dev/null || true

echo -e "${GREEN}✓${NC} Build cleaned"

# Create ZIP file
echo -e "${YELLOW}→${NC} Creating ZIP archive..."

cd "$BUILD_DIR"
ZIP_NAME="${PLUGIN_SLUG}-v${VERSION}.zip"

# Create ZIP with optimal compression
if command -v zip &> /dev/null; then
    zip -r -9 "../$DIST_DIR/$ZIP_NAME" "$PLUGIN_SLUG" > /dev/null
else
    # Fallback to tar if zip is not available
    tar -czf "../$DIST_DIR/${PLUGIN_SLUG}-v${VERSION}.tar.gz" "$PLUGIN_SLUG"
    ZIP_NAME="${PLUGIN_SLUG}-v${VERSION}.tar.gz"
fi

cd ..

echo -e "${GREEN}✓${NC} Archive created"

# Calculate file size
FILE_SIZE=$(du -h "$DIST_DIR/$ZIP_NAME" | cut -f1)

# Cleanup build directory
rm -rf "$BUILD_DIR"

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              Build Completed Successfully!             ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}Package:${NC}    $ZIP_NAME"
echo -e "${BLUE}Size:${NC}       $FILE_SIZE"
echo -e "${BLUE}Location:${NC}   $(pwd)/$DIST_DIR/$ZIP_NAME"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "  1. Test the plugin by uploading to WordPress"
echo "  2. Verify all functionality works correctly"
echo "  3. Create GitHub release (see instructions below)"
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Create GitHub Release for Automatic Updates${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Option 1: Using GitHub CLI (gh)${NC}"
echo ""
echo "  gh release create v$VERSION \\"
echo "    --title \"Version $VERSION\" \\"
echo "    --notes \"See CHANGELOG.md for details\" \\"
echo "    $DIST_DIR/$ZIP_NAME"
echo ""
echo -e "${YELLOW}Option 2: Manual via GitHub Web${NC}"
echo ""
echo "  1. Go to: https://github.com/sayuru-akash/wp-seo-blog-automater-plugin/releases/new"
echo "  2. Tag: v$VERSION"
echo "  3. Title: Version $VERSION"
echo "  4. Upload: $DIST_DIR/$ZIP_NAME"
echo "  5. Publish release"
echo ""
echo -e "${GREEN}Once released, WordPress sites will see update notifications! 🚀${NC}"
echo ""
