#!/usr/bin/env bash
# =============================================================
# Mousebook Setup Script
# =============================================================
# Run this once on a fresh LAMP server to:
#   1. Prompt for your configuration values
#   2. Write config.php
#   3. Import the userbook and animalbook SQL schemas
#   4. Run the v1 migration (adds missing columns/tables)
#   5. Create the recommended MySQL read-only userbook account
#   6. Patch query_viewer.php (localhost hardcode fix)
#   7. Print a post-install checklist
#
# Usage:
#   chmod +x setup.sh
#   sudo bash setup.sh
# =============================================================

# Do NOT use set -e — import steps may warn on existing data
# and should not abort the whole script. Errors are handled per-step.

# ── colours ────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

echo ""
echo -e "${BOLD}============================================${NC}"
echo -e "${BOLD}   Mousebook Setup Script                  ${NC}"
echo -e "${BOLD}============================================${NC}"
echo ""

# ── locate script directory ────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
info "Script directory: $SCRIPT_DIR"

# ── check prerequisites ────────────────────────────────────
command -v mysql  >/dev/null 2>&1 || error "mysql client not found. Install with: sudo apt install mysql-client"
command -v php    >/dev/null 2>&1 || error "php not found. Install with: sudo apt install php"
command -v apache2>/dev/null 2>&1 || warn  "apache2 not detected — if using a different web server, verify your web root manually."

echo ""
echo -e "${BOLD}--- Step 1: Collect Configuration Values ---${NC}"
echo ""

# MySQL admin credentials (used only during setup to create DBs and users)
read -p "MySQL admin username (e.g. root): " MYSQL_ADMIN
read -s -p "MySQL admin password: " MYSQL_ADMIN_PASS; echo ""
read -p "MySQL host [localhost]: " MYSQL_HOST
MYSQL_HOST="${MYSQL_HOST:-localhost}"
read -p "MySQL port [3306]: " MYSQL_PORT
MYSQL_PORT="${MYSQL_PORT:-3306}"

MYSQL_CMD="mysql -h${MYSQL_HOST} -P${MYSQL_PORT} -u${MYSQL_ADMIN} -p${MYSQL_ADMIN_PASS}"

# Test connection
info "Testing MySQL admin connection..."
$MYSQL_CMD -e "SELECT 1;" > /dev/null 2>&1 || error "Could not connect to MySQL. Check host, port, and credentials."
success "MySQL connection successful."

echo ""
read -p "Userbook read-only MySQL username to create [mousebook_ro]: " UB_USER
UB_USER="${UB_USER:-mousebook_ro}"
read -s -p "Password for $UB_USER (choose a strong password): " UB_PASS; echo ""
read -s -p "Confirm password: " UB_PASS2; echo ""
[ "$UB_PASS" = "$UB_PASS2" ] || error "Passwords do not match."

echo ""
read -p "Animalbook database name [animalbook]: " ANIMALBOOK_DB
ANIMALBOOK_DB="${ANIMALBOOK_DB:-animalbook}"
read -p "Userbook database name [userbook]: " USERBOOK_DB
USERBOOK_DB="${USERBOOK_DB:-userbook}"

echo ""
echo -e "${BOLD}--- Step 2: Locate web root and app directory ---${NC}"
echo ""

# Try to auto-detect web root
if [ -d "/var/www/html" ]; then
    DEFAULT_WEBROOT="/var/www/html"
elif [ -d "/var/www" ]; then
    DEFAULT_WEBROOT="/var/www"
else
    DEFAULT_WEBROOT=""
fi

read -p "Web root directory [$DEFAULT_WEBROOT]: " WEBROOT
WEBROOT="${WEBROOT:-$DEFAULT_WEBROOT}"
[ -d "$WEBROOT" ] || error "Web root '$WEBROOT' does not exist."

read -p "Mousebook subdirectory inside web root [mousebook]: " APP_SUBDIR
APP_SUBDIR="${APP_SUBDIR:-mousebook}"
APP_DIR="$WEBROOT/$APP_SUBDIR"

[ -d "$APP_DIR" ] || error "Application directory '$APP_DIR' not found. Copy the Mousebook files there first."
[ -f "$APP_DIR/index.php" ] || error "index.php not found in '$APP_DIR'. Check the directory."
success "Application directory found: $APP_DIR"

echo ""
read -p "Enable debug mode? (yes/no) [no]: " DEBUG_INPUT
DEBUG_INPUT="${DEBUG_INPUT:-no}"
if [[ "$DEBUG_INPUT" =~ ^[Yy] ]]; then
    DEBUG_MODE="True"
else
    DEBUG_MODE="False"
fi

echo ""
echo -e "${BOLD}--- Step 3: Write config.php ---${NC}"
echo ""

# ── Detect the web server's running user ───────────────────
# Apache on RHEL/CentOS uses 'apache'; on Debian/Ubuntu 'www-data'.
# This ensures config.php (chmod 640) is readable by the web server
# even when the file is owned by a different OS user.
detect_web_user() {
    # Try to find from running processes first (most reliable)
    local wu
    wu=$(ps aux | grep -E '\b(httpd|apache2)\b' | grep -v root | grep -v grep \
         | head -1 | awk '{print $1}')
    if [ -n "$wu" ]; then echo "$wu"; return; fi
    # Fall back to known defaults by distro
    if id apache  &>/dev/null; then echo "apache";   return; fi
    if id www-data &>/dev/null; then echo "www-data"; return; fi
    # Last resort: ask
    echo ""
}

WEB_USER=$(detect_web_user)
if [ -z "$WEB_USER" ]; then
    read -p "Could not auto-detect web server user. Enter it (e.g. apache or www-data): " WEB_USER
    WEB_USER="${WEB_USER:-apache}"
fi
info "Web server user detected as: $WEB_USER"

CONFIG_PATH="$APP_DIR/config.php"

cat > "$CONFIG_PATH" << CONFIGEOF
<?php
// =============================================================
// Mousebook Configuration — generated by setup.sh
// Do NOT commit this file to version control.
// =============================================================
return [
    'server_ip'   => '${MYSQL_HOST}',
    'server_host' => '${MYSQL_HOST}',
    'server_port' => '${MYSQL_PORT}',
    'server_user' => '${UB_USER}',
    'server_pass' => '${UB_PASS}',
    'debug_mode'  => '${DEBUG_MODE}',
    'site_name'   => 'Mousebook',
    'site_contact'=> '',
];
CONFIGEOF

# 640 = owner rw, group r, others none.
# Owner = current user, group = web server so Apache/httpd can read it
# but it is never world-readable.
CURRENT_USER="${SUDO_USER:-$(whoami)}"
chown "${CURRENT_USER}:${WEB_USER}" "$CONFIG_PATH"
chmod 640 "$CONFIG_PATH"
success "config.php written to $CONFIG_PATH (owner: ${CURRENT_USER}, group: ${WEB_USER}, mode: 640)"

echo ""
echo -e "${BOLD}--- Step 4: Import SQL schemas ---${NC}"
echo ""

SQL_DIR="$SCRIPT_DIR"

# ── Helper: check if a database already has tables ─────────
db_has_tables() {
    local dbname="$1"
    local count
    count=$($MYSQL_CMD -sN -e \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${dbname}';" \
        2>/dev/null || echo "0")
    [ "${count:-0}" -gt 0 ]
}

# ── Helper: safe import with pre-existing DB detection ─────
import_schema() {
    local dbname="$1"
    local sqlfile="$2"
    local label="$3"

    if [ ! -f "$sqlfile" ]; then
        warn "$sqlfile not found in $SQL_DIR — skipping $label import. Run it manually."
        return
    fi

    # Create database if it doesn't exist
    $MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS \`${dbname}\` CHARACTER SET utf8 COLLATE utf8_general_ci;" 2>/dev/null

    if db_has_tables "$dbname"; then
        echo ""
        warn "${label} database '${dbname}' already exists and contains tables."
        echo -e "       ${YELLOW}Importing now would overwrite existing schema and may cause errors.${NC}"
        read -p "       Skip import and keep existing database? (yes/no) [yes]: " SKIP_INPUT
        SKIP_INPUT="${SKIP_INPUT:-yes}"
        if [[ "$SKIP_INPUT" =~ ^[Nn] ]]; then
            info "Force-importing $label schema (existing data may conflict)..."
            if $MYSQL_CMD "${dbname}" < "$sqlfile" 2>&1 | grep -v "Warning"; then
                success "${label} schema imported."
            else
                warn "${label} import completed with warnings — this is often harmless on an existing DB."
            fi
        else
            info "Skipped $label import — existing database kept intact."
            info "To apply schema changes only, the migration script will still run."
        fi
    else
        info "Importing fresh ${label} schema into '${dbname}'..."
        if $MYSQL_CMD "${dbname}" < "$sqlfile" 2>&1 | grep -v "Warning"; then
            success "${label} schema imported."
        else
            warn "${label} import completed with warnings."
        fi
    fi
    echo ""
}

import_schema "${USERBOOK_DB}"  "$SQL_DIR/default_userbook.sql"  "Userbook"
import_schema "${ANIMALBOOK_DB}" "$SQL_DIR/default_animalbook.sql" "Animalbook"

# v1 migration — always safe to run (uses IF NOT EXISTS throughout)
if [ -f "$SQL_DIR/mousebook_migration_v1.sql" ]; then
    info "Running v1 migration (adds missing columns and tables — safe to re-run)..."
    if $MYSQL_CMD "${ANIMALBOOK_DB}" < "$SQL_DIR/mousebook_migration_v1.sql" 2>&1 | grep -v "Warning"; then
        success "Migration v1 complete."
    else
        warn "Migration v1 completed with warnings — check output above."
    fi
else
    warn "mousebook_migration_v1.sql not found — skipping."
fi

# v2 migration — widens user_pass to varchar(255) for bcrypt hashes
if [ -f "$SQL_DIR/mousebook_migration_v2.sql" ]; then
    info "Running v2 migration (widens user_pass column for bcrypt — safe to re-run)..."
    if $MYSQL_CMD "${USERBOOK_DB}" < "$SQL_DIR/mousebook_migration_v2.sql" 2>&1 | grep -v "Warning"; then
        success "Migration v2 complete."
    else
        warn "Migration v2 completed with warnings — check output above."
    fi
else
    warn "mousebook_migration_v2.sql not found — skipping. Run it manually against your userbook database."
fi

echo ""
echo -e "${BOLD}--- Step 5: Create MySQL read-only userbook account ---${NC}"
echo ""

info "Creating MySQL user '${UB_USER}'@'${MYSQL_HOST}'..."

# ── Show password policy so the user knows what's required ─
POLICY=$($MYSQL_CMD -sN \
    -e "SELECT VALUE FROM performance_schema.global_variables \
        WHERE VARIABLE_NAME='validate_password.policy';" 2>/dev/null \
    || $MYSQL_CMD -sN \
    -e "SELECT @@validate_password_policy;" 2>/dev/null \
    || echo "unknown")
MIN_LEN=$($MYSQL_CMD -sN \
    -e "SELECT VALUE FROM performance_schema.global_variables \
        WHERE VARIABLE_NAME='validate_password.length';" 2>/dev/null \
    || $MYSQL_CMD -sN \
    -e "SELECT @@validate_password_length;" 2>/dev/null \
    || echo "8")

if [ "$POLICY" != "unknown" ]; then
    info "MySQL password policy: level=${POLICY}, min_length=${MIN_LEN}"
    if [ "$POLICY" = "MEDIUM" ] || [ "$POLICY" = "STRONG" ]; then
        info "Your password must contain: uppercase, lowercase, number, and special character."
    fi
fi

# ── Write SQL to a temp file ────────────────────────────────
# Avoids heredoc shell-expansion issues with special characters in passwords.
MYSQL_SETUP_SQL=$(mktemp /tmp/mousebook_setup_XXXXXX.sql)
chmod 600 "$MYSQL_SETUP_SQL"

cat > "$MYSQL_SETUP_SQL" << SQLEOF
CREATE USER IF NOT EXISTS '${UB_USER}'@'${MYSQL_HOST}' IDENTIFIED BY '${UB_PASS}';
GRANT SELECT ON \`${USERBOOK_DB}\`.* TO '${UB_USER}'@'${MYSQL_HOST}';
FLUSH PRIVILEGES;
SQLEOF

CREATE_OUTPUT=$($MYSQL_CMD < "$MYSQL_SETUP_SQL" 2>&1)
CREATE_EXIT=$?

# Clean up the temp SQL file immediately — it contains the password
rm -f "$MYSQL_SETUP_SQL"

if [ $CREATE_EXIT -eq 0 ]; then
    success "MySQL user '${UB_USER}'@'${MYSQL_HOST}' created with SELECT on ${USERBOOK_DB}."
else
    echo "$CREATE_OUTPUT"
    warn "MySQL user creation failed — see error above."

    # Detect password policy rejection specifically
    if echo "$CREATE_OUTPUT" | grep -q "1819\|password.*policy\|validate_password"; then
        echo ""
        echo -e "${YELLOW}  PASSWORD POLICY ERROR detected.${NC}"
        echo -e "  Your MySQL server requires a stronger password."
        echo -e "  Current policy: level=${POLICY}, min_length=${MIN_LEN}"
        echo ""
        echo -e "  To check full requirements:"
        echo -e "    mysql -u root -p -e \"SHOW VARIABLES LIKE 'validate_password%';\""
        echo ""
        echo -e "  A MEDIUM-policy compliant password looks like: ${BOLD}MyColony#2024!${NC}"
        echo -e "  (uppercase + lowercase + number + special character, 8+ chars)"
        echo ""
        echo -e "  Re-run setup.sh with a stronger password, or create the user manually:"
        echo -e "    mysql -u ${MYSQL_ADMIN} -p"
        echo -e "    CREATE USER '${UB_USER}'@'${MYSQL_HOST}' IDENTIFIED BY 'StrongPass#1';"
        echo -e "    GRANT SELECT ON \`${USERBOOK_DB}\`.* TO '${UB_USER}'@'${MYSQL_HOST}';"
        echo -e "    FLUSH PRIVILEGES;"
        echo ""
        echo -e "  Then update config.php:"
        echo -e "    'server_pass' => 'StrongPass#1',"
    else
        warn "To fix manually:"
        warn "  mysql -u ${MYSQL_ADMIN} -p"
        warn "  CREATE USER '${UB_USER}'@'${MYSQL_HOST}' IDENTIFIED BY 'YOUR_PASS';"
        warn "  GRANT SELECT ON \`${USERBOOK_DB}\`.* TO '${UB_USER}'@'${MYSQL_HOST}';"
        warn "  FLUSH PRIVILEGES;"
    fi
fi

# ── Verify the user can actually connect ───────────────────
info "Verifying connection as '${UB_USER}'..."
if mysql -h"${MYSQL_HOST}" -P"${MYSQL_PORT}" \
         -u"${UB_USER}" -p"${UB_PASS}" \
         "${USERBOOK_DB}" \
         -e "SELECT COUNT(*) FROM userpass;" > /dev/null 2>&1; then
    success "Connection verified — '${UB_USER}' can connect to ${USERBOOK_DB}."
else
    warn "Could not verify connection as '${UB_USER}'."
    warn "If user creation failed above, fix that first then test with:"
    warn "  mysql -u ${UB_USER} -p ${USERBOOK_DB} -e 'SELECT 1;'"
fi

echo ""
echo -e "${BOLD}--- Step 6: Patch query_viewer.php (localhost hardcode) ---${NC}"
echo ""

QV="$APP_DIR/php/query_viewer.php"
if [ -f "$QV" ]; then
    if grep -q 'new mysqli("localhost"' "$QV"; then
        # Replace hardcoded "localhost" with $config['server_host'] pattern
        sed -i 's/new mysqli("localhost",\$ubname,\$ubpass,"userbook")/new mysqli($config['"'"'server_host'"'"'],$ubname,$ubpass,"userbook")/' "$QV"
        success "query_viewer.php patched (localhost → config server_host)."
    else
        info "query_viewer.php — no hardcoded localhost found, skipping."
    fi
else
    warn "query_viewer.php not found at $QV"
fi

echo ""
echo -e "${BOLD}--- Step 7: Secure config.php from web access ---${NC}"
echo ""

HTACCESS="$APP_DIR/.htaccess"
if [ -f "$HTACCESS" ]; then
    if grep -q "config.php" "$HTACCESS"; then
        info ".htaccess already references config.php — skipping."
    else
        cat >> "$HTACCESS" << 'HTEOF'

# Block direct web access to config.php
<Files "config.php">
    Require all denied
</Files>
HTEOF
        success ".htaccess updated to block direct access to config.php."
    fi
else
    cat > "$HTACCESS" << 'HTEOF'
# Block direct web access to config.php
<Files "config.php">
    Require all denied
</Files>
HTEOF
    success ".htaccess created to block direct access to config.php."
fi

echo ""
echo -e "${BOLD}--- Step 8: Create admin user and colony database entry ---${NC}"
echo ""

# ── Collect admin user details ─────────────────────────────
read -p "Admin username for Mousebook [admin]: " ADMIN_USER
ADMIN_USER="${ADMIN_USER:-admin}"

while true; do
    read -s -p "Admin password (will be hashed with bcrypt): " ADMIN_PASS; echo ""
    read -s -p "Confirm password: " ADMIN_PASS2; echo ""
    [ "$ADMIN_PASS" = "$ADMIN_PASS2" ] && break
    warn "Passwords do not match — try again."
done

# ── Hash the password with PHP (never stored plain text) ───
command -v php >/dev/null 2>&1 || error "php CLI not found — needed to hash the password."
ADMIN_HASH=$(php -r "echo password_hash('${ADMIN_PASS}', PASSWORD_BCRYPT);")
[ -n "$ADMIN_HASH" ] || error "password_hash() failed — check PHP installation."
unset ADMIN_PASS ADMIN_PASS2
success "Password hashed with bcrypt."

# ── Collect colony database details ────────────────────────
echo ""
read -p "Colony database name [${ANIMALBOOK_DB}]: " COLONY_DB
COLONY_DB="${COLONY_DB:-$ANIMALBOOK_DB}"
read -p "MySQL user for colony DB (the app read/write account): " COLONY_USER
read -s -p "MySQL password for ${COLONY_USER}: " COLONY_PASS; echo ""
read -p "Full URL to Mousebook index.php [http://localhost/${APP_SUBDIR}/index.php]: " COLONY_URL
COLONY_URL="${COLONY_URL:-http://localhost/${APP_SUBDIR}/index.php}"
read -p "Subject plural label (e.g. mice, rats) [mice]: " SUBJECT_PLURAL
SUBJECT_PLURAL="${SUBJECT_PLURAL:-mice}"
read -p "Subject singular label (e.g. mouse, rat) [mouse]: " SUBJECT_SINGLE
SUBJECT_SINGLE="${SUBJECT_SINGLE:-mouse}"

# ── Write and execute setup SQL ────────────────────────────
ADMIN_SQL=$(mktemp /tmp/mousebook_admin_XXXXXX.sql)
chmod 600 "$ADMIN_SQL"

cat > "$ADMIN_SQL" << SQLEOF
-- Insert admin user with bcrypt-hashed password
INSERT IGNORE INTO \`${USERBOOK_DB}\`.\`userpass\`
    (\`user_name\`, \`user_pass\`, \`user_salt\`)
VALUES
    ('${ADMIN_USER}', '${ADMIN_HASH}', '');

-- Register colony database
INSERT IGNORE INTO \`${USERBOOK_DB}\`.\`dbaccess\`
    (\`db_name\`, \`db_accessun\`, \`db_accesspw\`,
     \`db_formurl\`, \`db_host\`,
     \`db_subject_plural\`, \`db_subject_single\`)
VALUES
    ('${COLONY_DB}', '${COLONY_USER}', '${COLONY_PASS}',
     '${COLONY_URL}', '${MYSQL_HOST}',
     '${SUBJECT_PLURAL}', '${SUBJECT_SINGLE}');

-- Grant admin access to colony database
INSERT IGNORE INTO \`${USERBOOK_DB}\`.\`userdbaccess\`
    (\`user_idno\`, \`db_name\`, \`db_accesstier\`)
SELECT user_idno, '${COLONY_DB}', '1'
FROM \`${USERBOOK_DB}\`.\`userpass\`
WHERE user_name = '${ADMIN_USER}'
LIMIT 1;
SQLEOF

if $MYSQL_CMD < "$ADMIN_SQL" 2>&1 | grep -v "Warning"; then
    success "Admin user '${ADMIN_USER}' created with bcrypt password."
    success "Colony database '${COLONY_DB}' registered in userbook."
    success "Access granted: ${ADMIN_USER} → ${COLONY_DB}."
else
    warn "Admin setup may have failed — check output above."
    warn "You can re-run this step manually using the SQL in ${ADMIN_SQL}"
fi

rm -f "$ADMIN_SQL"
unset COLONY_PASS

# ── post-install checklist ─────────────────────────────────
echo ""
echo -e "${BOLD}============================================${NC}"
echo -e "${BOLD}   Setup Complete — Post-Install Checklist  ${NC}"
echo -e "${BOLD}============================================${NC}"
echo ""
echo -e " ${GREEN}✔${NC}  config.php written and owned by ${CURRENT_USER}:${WEB_USER} (mode 640)"
echo -e " ${GREEN}✔${NC}  MySQL read-only user '${UB_USER}' created"
echo -e " ${GREEN}✔${NC}  Schemas imported and migrations applied"
echo -e " ${GREEN}✔${NC}  Admin user '${ADMIN_USER}' created with bcrypt-hashed password"
echo -e " ${GREEN}✔${NC}  Colony database '${COLONY_DB}' registered"
echo ""
echo -e " ${YELLOW}TODO — Remaining manual steps:${NC}"
echo ""
echo -e "  1. ${BOLD}Customise room/location options in your animalbook DB:${NC}"
echo -e "     Edit list_cage_locations and list_cage_role_assignments"
echo -e "     to match your facility's rooms and workflows."
echo ""
echo -e "  2. ${BOLD}Apply the auth patcher to update PHP login checks:${NC}"
echo -e "     php ${APP_DIR}/patch_auth.php --dry-run   # preview"
echo -e "     php ${APP_DIR}/patch_auth.php             # apply"
echo ""
echo -e "  3. ${BOLD}Set debug_mode to 'False' in config.php when ready for production.${NC}"
echo ""
echo -e "  4. ${BOLD}Test login at:${NC}"
echo -e "     http://YOUR_SERVER/${APP_SUBDIR}/pages/databases.php"
echo ""
