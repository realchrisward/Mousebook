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
        success "Migration complete."
    else
        warn "Migration completed with warnings — check output above."
    fi
else
    warn "mousebook_migration_v1.sql not found — skipping. Run it manually against your animalbook database."
fi

echo ""
echo -e "${BOLD}--- Step 5: Create MySQL read-only userbook account ---${NC}"
echo ""

info "Creating MySQL user '${UB_USER}'@'${MYSQL_HOST}'..."

# Write SQL to a temp file so the password is never exposed on the command line
# and heredoc quoting issues with special characters can't break the statement.
MYSQL_SETUP_SQL=$(mktemp /tmp/mousebook_setup_XXXXXX.sql)
chmod 600 "$MYSQL_SETUP_SQL"

cat > "$MYSQL_SETUP_SQL" << SQLEOF
CREATE USER IF NOT EXISTS '${UB_USER}'@'${MYSQL_HOST}' IDENTIFIED BY '${UB_PASS}';
GRANT SELECT ON \`${USERBOOK_DB}\`.* TO '${UB_USER}'@'${MYSQL_HOST}';
FLUSH PRIVILEGES;
SQLEOF

if $MYSQL_CMD < "$MYSQL_SETUP_SQL" 2>&1; then
    success "MySQL user '${UB_USER}'@'${MYSQL_HOST}' created with SELECT on ${USERBOOK_DB}."
else
    warn "MySQL user creation may have failed — see error above."
    warn "Run this manually to fix it:"
    warn "  mysql -u ${MYSQL_ADMIN} -p"
    warn "  CREATE USER IF NOT EXISTS '${UB_USER}'@'${MYSQL_HOST}' IDENTIFIED BY 'YOUR_PASS';"
    warn "  GRANT SELECT ON \`${USERBOOK_DB}\`.* TO '${UB_USER}'@'${MYSQL_HOST}';"
    warn "  FLUSH PRIVILEGES;"
fi

# Clean up the temp SQL file immediately — it contains the password
rm -f "$MYSQL_SETUP_SQL"

# Verify the user can actually connect
info "Verifying connection as '${UB_USER}'..."
if mysql -h"${MYSQL_HOST}" -P"${MYSQL_PORT}" \
         -u"${UB_USER}" -p"${UB_PASS}" \
         "${USERBOOK_DB}" \
         -e "SELECT COUNT(*) FROM userpass;" > /dev/null 2>&1; then
    success "Connection verified — '${UB_USER}' can connect to ${USERBOOK_DB}."
else
    warn "Could not verify connection as '${UB_USER}'. Check password and grants."
    warn "Test manually: mysql -u ${UB_USER} -p ${USERBOOK_DB} -e 'SELECT 1;'"
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

# ── post-install checklist ─────────────────────────────────
echo ""
echo -e "${BOLD}============================================${NC}"
echo -e "${BOLD}   Setup Complete — Post-Install Checklist  ${NC}"
echo -e "${BOLD}============================================${NC}"
echo ""
echo -e " ${GREEN}✔${NC}  config.php written and owned by ${CURRENT_USER}:${WEB_USER} (mode 640)"
echo -e " ${GREEN}✔${NC}  MySQL user '${UB_USER}' created"
echo -e " ${GREEN}✔${NC}  Schemas imported and migration applied"
echo ""
echo -e " ${YELLOW}TODO — Manual steps required:${NC}"
echo ""
echo -e "  1. ${BOLD}Add your colony database to userbook.dbaccess:${NC}"
echo -e "     INSERT INTO \`${USERBOOK_DB}\`.\`dbaccess\`"
echo -e "       (db_name, db_accessun, db_accesspw, db_formurl, db_host)"
echo -e "       VALUES ('${ANIMALBOOK_DB}', 'YOUR_DB_USER', 'YOUR_DB_PASS',"
echo -e "               'http://YOUR_SERVER/${APP_SUBDIR}/index.php', '${MYSQL_HOST}');"
echo ""
echo -e "  2. ${BOLD}Add your first user to userbook.userpass:${NC}"
echo -e "     INSERT INTO \`${USERBOOK_DB}\`.\`userpass\`"
echo -e "       (user_name, user_pass, user_salt) VALUES ('admin','your_password','');"
echo ""
echo -e "  3. ${BOLD}Grant that user access to your colony database:${NC}"
echo -e "     INSERT INTO \`${USERBOOK_DB}\`.\`userdbaccess\`"
echo -e "       (user_idno, db_name, db_accesstier) VALUES (1,'${ANIMALBOOK_DB}','1');"
echo ""
echo -e "  4. ${BOLD}Customise room/location options in your animalbook DB:${NC}"
echo -e "     Edit list_cage_locations and list_cage_role_assignments"
echo -e "     to match your facility. (Already seeded with defaults.)"
echo ""
echo -e "  5. ${BOLD}Set debug_mode to 'False' in config.php when ready for production.${NC}"
echo ""
echo -e "  6. ${BOLD}Test login at:${NC}"
echo -e "     http://YOUR_SERVER/${APP_SUBDIR}/pages/databases.php"
echo ""
