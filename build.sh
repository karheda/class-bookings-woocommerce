#!/bin/bash

# =============================================================================
# Class Booking Plugin - Build Script
# Creates an installable ZIP file for WordPress
#
# Requirements:
# - Docker running with zarapita_wp container
# - All tests must pass
# - Security checks must pass
#
# Usage:
#   ./build.sh           # Full build with all checks
#   ./build.sh --skip-tests   # Skip tests (not recommended)
#   ./build.sh --skip-security # Skip security checks
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_SLUG="class-booking"
PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
BUILD_DIR="${PLUGIN_DIR}/build"
DIST_DIR="${PLUGIN_DIR}/dist"
DOCKER_CONTAINER="zarapita_wp"
DOCKER_PLUGIN_PATH="/var/www/html/wp-content/plugins/class-booking"

# Parse arguments
SKIP_TESTS=false
SKIP_SECURITY=false

for arg in "$@"; do
    case $arg in
        --skip-tests)
            SKIP_TESTS=true
            shift
            ;;
        --skip-security)
            SKIP_SECURITY=true
            shift
            ;;
        --help|-h)
            echo "Usage: ./build.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --skip-tests     Skip PHPUnit tests (not recommended)"
            echo "  --skip-security  Skip security checks"
            echo "  --help, -h       Show this help message"
            exit 0
            ;;
    esac
done

# Get version from main plugin file
VERSION=$(grep -m1 "Version:" "${PLUGIN_DIR}/class-booking.php" | sed 's/.*Version: *//' | tr -d '[:space:]')

if [ -z "$VERSION" ]; then
    VERSION="1.0.0"
fi

ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

echo ""
echo -e "${BLUE}==========================================${NC}"
echo -e "${BLUE}  Building ${PLUGIN_SLUG} v${VERSION}${NC}"
echo -e "${BLUE}==========================================${NC}"
echo ""

# =============================================================================
# STEP 1: Check Docker container is running
# =============================================================================
echo -e "${YELLOW}[1/6]${NC} Checking Docker environment..."

if ! docker ps --format '{{.Names}}' | grep -q "^${DOCKER_CONTAINER}$"; then
    echo -e "${RED}✗ Docker container '${DOCKER_CONTAINER}' is not running${NC}"
    echo "  Please start the environment with: make up"
    exit 1
fi
echo -e "${GREEN}✓${NC} Docker container is running"

# =============================================================================
# STEP 2: Run PHPUnit Tests
# =============================================================================
if [ "$SKIP_TESTS" = false ]; then
    echo ""
    echo -e "${YELLOW}[2/6]${NC} Running PHPUnit tests..."

    TEST_OUTPUT=$(docker exec ${DOCKER_CONTAINER} bash -c "cd ${DOCKER_PLUGIN_PATH} && vendor/bin/phpunit --testsuite Integration 2>&1") || {
        echo -e "${RED}✗ Tests failed!${NC}"
        echo ""
        echo "$TEST_OUTPUT"
        echo ""
        echo -e "${RED}Build aborted. Fix the failing tests before releasing.${NC}"
        exit 1
    }

    # Extract test summary
    TEST_SUMMARY=$(echo "$TEST_OUTPUT" | grep -E "^OK \(" || echo "")
    if [ -n "$TEST_SUMMARY" ]; then
        echo -e "${GREEN}✓${NC} $TEST_SUMMARY"
    else
        echo -e "${RED}✗ Could not verify test results${NC}"
        echo "$TEST_OUTPUT"
        exit 1
    fi
else
    echo ""
    echo -e "${YELLOW}[2/6]${NC} Skipping tests (--skip-tests flag)"
    echo -e "${YELLOW}⚠${NC} Warning: Releasing without tests is not recommended"
fi

# =============================================================================
# STEP 3: Security Checks
# =============================================================================
if [ "$SKIP_SECURITY" = false ]; then
    echo ""
    echo -e "${YELLOW}[3/6]${NC} Running security checks..."

    SECURITY_ISSUES=0

    # Check 1: Verify all PHP files have ABSPATH check
    echo "  → Checking ABSPATH protection..."
    MISSING_ABSPATH=$(find "${PLUGIN_DIR}/src" -name "*.php" -exec grep -L "defined('ABSPATH')" {} \; 2>/dev/null || true)
    if [ -n "$MISSING_ABSPATH" ]; then
        echo -e "${RED}  ✗ Files missing ABSPATH check:${NC}"
        echo "$MISSING_ABSPATH" | sed 's/^/    /'
        SECURITY_ISSUES=$((SECURITY_ISSUES + 1))
    else
        echo -e "${GREEN}  ✓${NC} All PHP files have ABSPATH protection"
    fi

    # Check 2: Look for direct $_GET/$_POST without sanitization
    echo "  → Checking for unsanitized input..."
    UNSAFE_INPUT=$(grep -rn "\$_\(GET\|POST\|REQUEST\)\[" "${PLUGIN_DIR}/src" --include="*.php" | grep -v "sanitize_\|absint\|wp_verify_nonce\|isset\|wp_unslash" | head -10 || true)
    if [ -n "$UNSAFE_INPUT" ]; then
        echo -e "${YELLOW}  ⚠${NC} Potential unsanitized input (review manually):"
        echo "$UNSAFE_INPUT" | sed 's/^/    /' | head -5
        # This is a warning, not a failure
    else
        echo -e "${GREEN}  ✓${NC} No obvious unsanitized input found"
    fi

    # Check 3: Verify nonce usage in form handlers (exclude shortcodes - they're not handlers)
    echo "  → Checking nonce verification..."
    HANDLERS_WITHOUT_NONCE=$(grep -rln "class_booking_action\|wp_ajax_" "${PLUGIN_DIR}/src" --include="*.php" | grep -v "Shortcode" | while read file; do
        if ! grep -q "wp_verify_nonce" "$file"; then
            echo "$file"
        fi
    done || true)
    if [ -n "$HANDLERS_WITHOUT_NONCE" ]; then
        echo -e "${RED}  ✗ Handlers missing nonce verification:${NC}"
        echo "$HANDLERS_WITHOUT_NONCE" | sed 's/^/    /'
        SECURITY_ISSUES=$((SECURITY_ISSUES + 1))
    else
        echo -e "${GREEN}  ✓${NC} All handlers verify nonces"
    fi

    # Check 4: Verify capability checks in REST endpoints
    echo "  → Checking REST API permissions..."
    REST_WITHOUT_PERMS=$(grep -rln "register_rest_route" "${PLUGIN_DIR}/src" --include="*.php" | while read file; do
        if ! grep -q "permission_callback" "$file"; then
            echo "$file"
        fi
    done || true)
    if [ -n "$REST_WITHOUT_PERMS" ]; then
        echo -e "${RED}  ✗ REST routes missing permission_callback:${NC}"
        echo "$REST_WITHOUT_PERMS" | sed 's/^/    /'
        SECURITY_ISSUES=$((SECURITY_ISSUES + 1))
    else
        echo -e "${GREEN}  ✓${NC} All REST routes have permission callbacks"
    fi

    # Check 5: Look for direct SQL queries without prepare
    echo "  → Checking SQL injection protection..."
    UNSAFE_SQL=$(grep -rn "\$wpdb->query\|\$wpdb->get_" "${PLUGIN_DIR}/src" --include="*.php" | grep -v "prepare" | head -10 || true)
    if [ -n "$UNSAFE_SQL" ]; then
        echo -e "${YELLOW}  ⚠${NC} Potential SQL without prepare (review manually):"
        echo "$UNSAFE_SQL" | sed 's/^/    /' | head -5
    else
        echo -e "${GREEN}  ✓${NC} All SQL queries use prepared statements"
    fi

    # Check 6: PHP syntax check (run inside Docker container)
    echo "  → Checking PHP syntax..."
    SYNTAX_ERRORS=$(docker exec ${DOCKER_CONTAINER} bash -c "find ${DOCKER_PLUGIN_PATH}/src -name '*.php' -exec php -l {} \; 2>&1" | grep -v "No syntax errors" || true)
    if [ -n "$SYNTAX_ERRORS" ]; then
        echo -e "${RED}  ✗ PHP syntax errors found:${NC}"
        echo "$SYNTAX_ERRORS" | sed 's/^/    /'
        SECURITY_ISSUES=$((SECURITY_ISSUES + 1))
    else
        echo -e "${GREEN}  ✓${NC} No PHP syntax errors"
    fi

    if [ $SECURITY_ISSUES -gt 0 ]; then
        echo ""
        echo -e "${RED}✗ Found ${SECURITY_ISSUES} security issue(s). Build aborted.${NC}"
        exit 1
    fi

    echo -e "${GREEN}✓${NC} All security checks passed"
else
    echo ""
    echo -e "${YELLOW}[3/6]${NC} Skipping security checks (--skip-security flag)"
    echo -e "${YELLOW}⚠${NC} Warning: Releasing without security checks is not recommended"
fi

# =============================================================================
# STEP 4: Clean and prepare build directory
# =============================================================================
echo ""
echo -e "${YELLOW}[4/6]${NC} Preparing build directory..."
rm -rf "${BUILD_DIR}"
rm -rf "${DIST_DIR}"
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}"
mkdir -p "${DIST_DIR}"
echo -e "${GREEN}✓${NC} Build directory ready"

# =============================================================================
# STEP 5: Copy and prepare files
# =============================================================================
echo ""
echo -e "${YELLOW}[5/6]${NC} Copying plugin files..."

# Copy main plugin files
cp "${PLUGIN_DIR}/class-booking.php" "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp "${PLUGIN_DIR}/composer.json" "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp -r "${PLUGIN_DIR}/src" "${BUILD_DIR}/${PLUGIN_SLUG}/"

# Copy README if exists
if [ -f "${PLUGIN_DIR}/README.md" ]; then
    cp "${PLUGIN_DIR}/README.md" "${BUILD_DIR}/${PLUGIN_SLUG}/"
fi

# Copy templates if exists
if [ -d "${PLUGIN_DIR}/templates" ]; then
    cp -r "${PLUGIN_DIR}/templates" "${BUILD_DIR}/${PLUGIN_SLUG}/"
fi

echo -e "${GREEN}✓${NC} Plugin files copied"

# Install composer dependencies (production only)
echo "  → Installing production dependencies..."
cd "${BUILD_DIR}/${PLUGIN_SLUG}"
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null
    echo -e "${GREEN}  ✓${NC} Composer dependencies installed"
else
    echo -e "${YELLOW}  ⚠${NC} Composer not found, copying vendor from source"
    cp -r "${PLUGIN_DIR}/vendor" "${BUILD_DIR}/${PLUGIN_SLUG}/"
fi

# Clean up unnecessary files
echo "  → Cleaning up build files..."
cd "${BUILD_DIR}/${PLUGIN_SLUG}"
rm -rf .git .gitignore .gitattributes
rm -rf tests phpunit.xml* .phpunit*
rm -rf node_modules package*.json
rm -rf .editorconfig .prettierrc* .eslintrc*
rm -rf composer.lock
rm -f build.sh
rm -f DEVELOPERS.md CLAUDE.md

# Remove dev files from vendor
if [ -d "vendor" ]; then
    find vendor -name "*.md" -delete 2>/dev/null || true
    find vendor -name "phpunit*" -type d -exec rm -rf {} + 2>/dev/null || true
    find vendor -name "tests" -type d -exec rm -rf {} + 2>/dev/null || true
    find vendor -name ".git*" -delete 2>/dev/null || true
    find vendor -name "docs" -type d -exec rm -rf {} + 2>/dev/null || true
fi

echo -e "${GREEN}✓${NC} Build files cleaned"

# =============================================================================
# STEP 6: Create ZIP archive
# =============================================================================
echo ""
echo -e "${YELLOW}[6/6]${NC} Creating ZIP archive..."
cd "${BUILD_DIR}"
zip -r "${DIST_DIR}/${ZIP_NAME}" "${PLUGIN_SLUG}" -x "*.DS_Store" -x "*__MACOSX*" -q

# Cleanup build directory
rm -rf "${BUILD_DIR}"

# Get ZIP info
ZIP_SIZE=$(du -h "${DIST_DIR}/${ZIP_NAME}" | cut -f1)
FILE_COUNT=$(unzip -l "${DIST_DIR}/${ZIP_NAME}" | tail -1 | awk '{print $2}')

# =============================================================================
# Build Summary
# =============================================================================
echo ""
echo -e "${GREEN}==========================================${NC}"
echo -e "${GREEN}  ✅ Build Complete!${NC}"
echo -e "${GREEN}==========================================${NC}"
echo ""
echo -e "  ${BLUE}Version:${NC}  ${VERSION}"
echo -e "  ${BLUE}File:${NC}     ${DIST_DIR}/${ZIP_NAME}"
echo -e "  ${BLUE}Size:${NC}     ${ZIP_SIZE}"
echo -e "  ${BLUE}Files:${NC}    ${FILE_COUNT}"
echo ""

if [ "$SKIP_TESTS" = true ] || [ "$SKIP_SECURITY" = true ]; then
    echo -e "${YELLOW}⚠ Warning: Some checks were skipped${NC}"
    [ "$SKIP_TESTS" = true ] && echo "  - Tests were skipped"
    [ "$SKIP_SECURITY" = true ] && echo "  - Security checks were skipped"
    echo ""
fi

echo "Install via: WordPress Admin → Plugins → Add New → Upload Plugin"
echo ""

