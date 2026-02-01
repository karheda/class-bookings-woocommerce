#!/bin/bash

# =============================================================================
# Class Booking Plugin - Build Script
# Creates an installable ZIP file for WordPress
# =============================================================================

set -e

# Configuration
PLUGIN_SLUG="class-booking"
PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
BUILD_DIR="${PLUGIN_DIR}/build"
DIST_DIR="${PLUGIN_DIR}/dist"

# Get version from main plugin file
VERSION=$(grep -m1 "Version:" "${PLUGIN_DIR}/class-booking.php" | sed 's/.*Version: *//' | tr -d '[:space:]')

if [ -z "$VERSION" ]; then
    VERSION="1.0.0"
fi

ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

echo "=========================================="
echo "Building ${PLUGIN_SLUG} v${VERSION}"
echo "=========================================="

# Clean previous builds
echo "→ Cleaning previous builds..."
rm -rf "${BUILD_DIR}"
rm -rf "${DIST_DIR}"
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}"
mkdir -p "${DIST_DIR}"

# Copy plugin files
echo "→ Copying plugin files..."
cp "${PLUGIN_DIR}/class-booking.php" "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp "${PLUGIN_DIR}/composer.json" "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp -r "${PLUGIN_DIR}/src" "${BUILD_DIR}/${PLUGIN_SLUG}/"

# Copy templates if exists
if [ -d "${PLUGIN_DIR}/templates" ]; then
    cp -r "${PLUGIN_DIR}/templates" "${BUILD_DIR}/${PLUGIN_SLUG}/"
fi

# Install composer dependencies (production only)
echo "→ Installing production dependencies..."
cd "${BUILD_DIR}/${PLUGIN_SLUG}"
composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null || {
    echo "  (No composer in PATH, copying vendor from source)"
    cp -r "${PLUGIN_DIR}/vendor" "${BUILD_DIR}/${PLUGIN_SLUG}/"
}

# Remove unnecessary files
echo "→ Cleaning up..."
cd "${BUILD_DIR}/${PLUGIN_SLUG}"
rm -rf .git .gitignore .gitattributes
rm -rf tests phpunit.xml* .phpunit*
rm -rf node_modules package*.json
rm -rf .editorconfig .prettierrc* .eslintrc*
rm -rf *.md !README.md
rm -rf composer.lock
rm -f build.sh

# Remove dev files from vendor
if [ -d "vendor" ]; then
    find vendor -name "*.md" -delete 2>/dev/null || true
    find vendor -name "phpunit*" -type d -exec rm -rf {} + 2>/dev/null || true
    find vendor -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
    find vendor -name ".git*" -delete 2>/dev/null || true
fi

# Create ZIP
echo "→ Creating ZIP archive..."
cd "${BUILD_DIR}"
zip -r "${DIST_DIR}/${ZIP_NAME}" "${PLUGIN_SLUG}" -x "*.DS_Store" -x "*__MACOSX*" -q

# Cleanup build directory
rm -rf "${BUILD_DIR}"

# Output result
ZIP_SIZE=$(du -h "${DIST_DIR}/${ZIP_NAME}" | cut -f1)
echo ""
echo "=========================================="
echo "✅ Build complete!"
echo "=========================================="
echo "File: ${DIST_DIR}/${ZIP_NAME}"
echo "Size: ${ZIP_SIZE}"
echo ""
echo "Install via WordPress Admin → Plugins → Add New → Upload Plugin"

