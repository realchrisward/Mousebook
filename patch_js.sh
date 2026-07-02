#!/usr/bin/env bash
# =====================================================
# patch_js.sh
# Adds <script src="mousebook.js"> before </body> in
# every Mousebook PHP page that doesn't already have it.
#
# Usage (run from Mousebook root):
#   bash patch_js.sh [--dry-run]
# =====================================================

DRY_RUN=false
[[ "${1}" == "--dry-run" ]] && DRY_RUN=true
$DRY_RUN && echo "[DRY RUN — no files modified]" && echo ""

ADDED=0; SKIPPED=0; NO_BODY=0

patch_file() {
    local file="$1"
    local script_tag="$2"
    local short="${file#$APP_DIR/}"

    # Skip library files that have no <body>
    if ! grep -qi '</body>' "$file"; then
        echo "  [no body]  $short"
        ((NO_BODY++))
        return
    fi

    # Skip if already has mousebook.js
    if grep -q 'mousebook\.js' "$file"; then
        echo "  [skip]     $short — already has mousebook.js"
        ((SKIPPED++))
        return
    fi

    if $DRY_RUN; then
        echo "  [would add] $short"
        ((ADDED++))
        return
    fi

    # Insert script tag before </body> (case-insensitive match)
    sed -i "s|</body>|<script src=\"${script_tag}\"></script>\n</body>|i" "$file"
    echo "  [added]    $short"
    ((ADDED++))
}

# ── Locate Mousebook root ──────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[[ ! -f "$APP_DIR/index.php" ]] && \
    echo "ERROR: Run from the Mousebook root directory." && exit 1

echo "Patching mousebook.js into PHP pages..."
echo "Root: $APP_DIR"
echo ""

# php/ pages — script is one level up
for f in "$APP_DIR"/php/*.php; do
    [[ -f "$f" ]] && patch_file "$f" "../mousebook.js"
done

# pages/ pages — script is one level up
for f in "$APP_DIR"/pages/*.php; do
    [[ -f "$f" ]] && patch_file "$f" "../mousebook.js"
done

# Root index.php — script is in same directory
patch_file "$APP_DIR/index.php" "./mousebook.js"

echo ""
echo "Summary:"
echo "  Added:    $ADDED"
echo "  Skipped:  $SKIPPED (already patched)"
echo "  No body:  $NO_BODY (library files, skipped safely)"
