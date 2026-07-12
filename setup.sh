#!/usr/bin/env bash
# =============================================================
# Mousebook Setup Script
# =============================================================
# Stands up a complete, working Mousebook install on a fresh host:
#
#   1. Preflight  - web server, PHP + required extensions, DB client
#   2. Prompts    - DB admin creds, colony name, app dir, admin account
#   3. Databases  - create `userbook` + your colony db (utf8mb4)
#   4. Schemas    - load the two install schemas into them
#   5. Accounts   - create the three least-privilege MySQL/MariaDB users
#   6. config.php - written with correct perms, incl. the email settings
#   7. Bootstrap  - register both databases, create the first admin,
#                   grant that admin `admin` tier on BOTH databases
#   8. Verify     - connect as each account and prove the privileges work
#
# Works on MySQL 8.0/8.4 and MariaDB 10.11+ (see DB_ENGINE_SUPPORT.md).
# Safe to re-run: it will not silently overwrite an existing database.
#
# Usage:
#   chmod +x setup.sh
#   sudo bash setup.sh
#
# Full walkthrough, including the two reference environments:  INSTALL.md
# =============================================================

# No `set -e`: each step reports and handles its own failure so a warning
# late in the run never discards the work already done.

# -- colours -------------------------------------------------
if [ -t 1 ]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
    CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; CYAN=''; BOLD=''; NC=''
fi

info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }
step()    { echo ""; echo -e "${BOLD}--- $* ---${NC}"; echo ""; }

FAILURES=0
note_failure() { FAILURES=$((FAILURES + 1)); }

echo ""
echo -e "${BOLD}============================================${NC}"
echo -e "${BOLD}   Mousebook Setup                          ${NC}"
echo -e "${BOLD}============================================${NC}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# -- temp files: one owner, cleaned on any exit --------------
TMPDIR_MB="$(mktemp -d /tmp/mousebook_setup_XXXXXX)"
chmod 700 "$TMPDIR_MB"
cleanup() { rm -rf "$TMPDIR_MB"; }
trap cleanup EXIT INT TERM

# =============================================================
step "Step 1: Preflight"
# =============================================================

info "Script directory: $SCRIPT_DIR"

# -- database client ------------------------------------------
# MariaDB ships `mysql` as a compatibility name, so either client works
# against either engine. Prefer whichever is present.
if   command -v mariadb >/dev/null 2>&1; then DB_CLIENT="mariadb"
elif command -v mysql   >/dev/null 2>&1; then DB_CLIENT="mysql"
else
    error "No database client found. Install one:
         Debian/Raspberry Pi OS:  sudo apt install mariadb-client
         RHEL/CloudLinux:         sudo dnf install mariadb   (or mysql)"
fi
success "Database client: $DB_CLIENT"

# -- PHP + the extensions the app actually needs ---------------
command -v php >/dev/null 2>&1 || error "php CLI not found. Install php-cli."
PHP_VER="$(php -r 'echo PHP_VERSION;')"
PHP_MAJOR="$(php -r 'echo PHP_MAJOR_VERSION;')"
[ "$PHP_MAJOR" -ge 8 ] 2>/dev/null \
    || warn "PHP $PHP_VER detected. Mousebook targets PHP 8.x; older builds are untested."
success "PHP $PHP_VER"

# Required per DEPENDENCIES.md: mysqli (all db access), mbstring (app + PHPMailer).
for ext in mysqli mbstring; do
    php -m | grep -qix "$ext" \
        || error "PHP extension '$ext' is missing (required).
         Debian/Raspberry Pi OS:  sudo apt install php-mysql php-mbstring
         RHEL/CloudLinux:         sudo dnf install php-mysqlnd php-mbstring"
done
success "PHP extensions present: mysqli, mbstring"

# openssl is only needed if outbound email is enabled.
if php -m | grep -qix openssl; then
    success "PHP extension present: openssl (email over TLS available)"
    HAVE_OPENSSL=1
else
    warn "PHP extension 'openssl' is missing - invitation / password-reset email over TLS will not work."
    HAVE_OPENSSL=0
fi

# -- web server ------------------------------------------------
if   command -v apache2 >/dev/null 2>&1; then success "Web server: Apache (apache2)"
elif command -v httpd   >/dev/null 2>&1; then success "Web server: Apache (httpd)"
else warn "Apache not detected. Mousebook is only tested on Apache 2.4; if you use another web server, verify your web root and that .htaccess rules are honoured."
fi

# =============================================================
step "Step 2: Database connection"
# =============================================================

read -p "Database admin username (e.g. root): " DB_ADMIN
read -s -p "Database admin password: " DB_ADMIN_PASS; echo ""
read -p "Database host [localhost]: " DB_HOST
DB_HOST="${DB_HOST:-localhost}"
read -p "Database port [3306]: " DB_PORT
DB_PORT="${DB_PORT:-3306}"

# Credentials go in a 0600 defaults-file, never on the command line
# (an argv password is visible to every user on the box via `ps`).
#
# Values are written QUOTED and escaped. In a MySQL/MariaDB option file '#'
# starts a comment, so an unquoted password containing '#' is silently
# truncated at that character - the connection then fails with a password
# that looks correct everywhere else.
cnf_escape() {
    local v="$1"
    v="${v//\\/\\\\}"   # backslash first
    v="${v//\"/\\\"}"     # then double quote
    printf '"%s"' "$v"
}

ADMIN_CNF="$TMPDIR_MB/admin.cnf"
umask 077
{
    echo "[client]"
    echo "user=$(cnf_escape "$DB_ADMIN")"
    echo "password=$(cnf_escape "$DB_ADMIN_PASS")"
    echo "host=$(cnf_escape "$DB_HOST")"
    echo "port=${DB_PORT}"
} > "$ADMIN_CNF"

db_admin() { "$DB_CLIENT" --defaults-extra-file="$ADMIN_CNF" "$@"; }

info "Testing the admin connection..."
db_admin -e "SELECT 1;" >/dev/null 2>&1 \
    || error "Could not connect. Check the host, port, username and password."

DB_VERSION="$(db_admin -sN -e "SELECT VERSION();" 2>/dev/null)"
case "$DB_VERSION" in
    *MariaDB*|*mariadb*) DB_ENGINE="MariaDB" ;;
    *)                   DB_ENGINE="MySQL"   ;;
esac
success "Connected. Engine: ${DB_ENGINE} (${DB_VERSION})"
info "Both engines are supported - see DB_ENGINE_SUPPORT.md."

# =============================================================
step "Step 3: What to install"
# =============================================================

# The auth database is called `userbook` by default, but the name is
# configurable (config.php -> 'userbook_db'), because cPanel-style hosts force
# an account prefix onto every database they create and will not hand you a
# database called exactly `userbook`.
echo "Mousebook keeps accounts and passwords in an 'auth' database, separate from"
echo "your colony. It is normally called 'userbook'. If your host prefixes database"
echo "names (cPanel does: you get something like 'myaccount_userbook'), enter the"
echo "name it actually gave you."
echo ""
read -p "Auth database name [userbook]: " USERBOOK_DB
USERBOOK_DB="${USERBOOK_DB:-userbook}"
case "$USERBOOK_DB" in
    *[!A-Za-z0-9_]*) error "The auth database name may contain only letters, digits and underscores." ;;
esac
[ "${#USERBOOK_DB}" -le 64 ] || error "The auth database name must be 64 characters or fewer."
success "Auth database: '${USERBOOK_DB}'"
echo ""

read -p "Colony database name [animalbook]: " COLONY_DB
COLONY_DB="${COLONY_DB:-animalbook}"
case "$COLONY_DB" in
    *[!A-Za-z0-9_]*) error "Colony database name may contain only letters, digits and underscores." ;;
esac
[ "$COLONY_DB" = "$USERBOOK_DB" ] && error "The colony database cannot have the same name as the auth database."
[ "${#COLONY_DB}" -le 64 ] || error "The colony database name must be 64 characters or fewer."
success "Colony database: '${COLONY_DB}'"

echo ""
read -p "What do you call your animals, plural (e.g. mice, rats) [mice]: " SUBJECT_PLURAL
SUBJECT_PLURAL="${SUBJECT_PLURAL:-mice}"
read -p "...and singular (e.g. mouse, rat) [mouse]: " SUBJECT_SINGLE
SUBJECT_SINGLE="${SUBJECT_SINGLE:-mouse}"

# =============================================================
step "Step 4: Where the files live"
# =============================================================

if   [ -d "/var/www/html" ]; then DEFAULT_WEBROOT="/var/www/html"
elif [ -d "/var/www" ];      then DEFAULT_WEBROOT="/var/www"
else DEFAULT_WEBROOT=""
fi

read -p "Web root directory [$DEFAULT_WEBROOT]: " WEBROOT
WEBROOT="${WEBROOT:-$DEFAULT_WEBROOT}"
[ -d "$WEBROOT" ] || error "Web root '$WEBROOT' does not exist."

read -p "Mousebook subdirectory inside the web root [mousebook]: " APP_SUBDIR
APP_SUBDIR="${APP_SUBDIR:-mousebook}"
APP_DIR="$WEBROOT/$APP_SUBDIR"

[ -d "$APP_DIR" ]            || error "'$APP_DIR' not found. Copy the Mousebook files there first (see INSTALL.md)."
[ -f "$APP_DIR/index.php" ]  || error "index.php is not in '$APP_DIR'. Check the directory."
success "Application directory: $APP_DIR"

# The web server user must be able to read config.php (mode 640, group-owned).
detect_web_user() {
    local wu
    wu=$(ps aux 2>/dev/null | grep -E '\b(httpd|apache2)\b' | grep -v root | grep -v grep \
         | head -1 | awk '{print $1}')
    if [ -n "$wu" ]; then echo "$wu"; return; fi
    if id apache   >/dev/null 2>&1; then echo "apache";   return; fi
    if id www-data >/dev/null 2>&1; then echo "www-data"; return; fi
    echo ""
}
WEB_USER="$(detect_web_user)"
if [ -z "$WEB_USER" ]; then
    read -p "Could not detect the web server user. Enter it (apache or www-data): " WEB_USER
    WEB_USER="${WEB_USER:-apache}"
fi
success "Web server runs as: $WEB_USER"

echo ""
read -p "Enable debug mode (shows PHP errors on screen - never in production)? (yes/no) [no]: " DEBUG_INPUT
if [[ "${DEBUG_INPUT:-no}" =~ ^[Yy] ]]; then DEBUG_MODE="True"; else DEBUG_MODE="False"; fi

# =============================================================
step "Step 5: Email (optional)"
# =============================================================

echo "Mousebook emails invitation and password-reset links through an SMTP relay."
echo "Leave the relay host blank to disable email: you can still create users, but"
echo "you will have to hand them their set-password links yourself."
echo ""
read -p "SMTP relay host (blank = email disabled): " SMTP_HOST
SMTP_PORT=""; SMTP_SECURE=""; SMTP_AUTH="false"; SMTP_USER=""; SMTP_PASS=""
MAIL_FROM=""; MAIL_FROM_NAME="Mousebook"

if [ -n "$SMTP_HOST" ]; then
    [ "$HAVE_OPENSSL" -eq 1 ] || warn "PHP openssl is missing - only an unencrypted relay (port 25) will work."
    read -p "SMTP port (587=STARTTLS, 465=SSL, 25=plain) [587]: " SMTP_PORT
    SMTP_PORT="${SMTP_PORT:-587}"
    case "$SMTP_PORT" in
        465) SMTP_SECURE="ssl" ;;
        25)  SMTP_SECURE=""    ;;
        *)   SMTP_SECURE="tls" ;;
    esac
    info "Encryption for port ${SMTP_PORT}: '${SMTP_SECURE:-none}'  (port and mode must match, or sending hangs)"
    read -p "Does the relay require a login? (yes/no) [no]: " SMTP_AUTH_IN
    if [[ "${SMTP_AUTH_IN:-no}" =~ ^[Yy] ]]; then
        SMTP_AUTH="true"
        read -p "  SMTP username: " SMTP_USER
        read -s -p "  SMTP password: " SMTP_PASS; echo ""
    fi
    read -p "From address for outgoing mail (e.g. no-reply@example.org): " MAIL_FROM
    read -p "From display name [Mousebook]: " MAIL_FROM_NAME_IN
    MAIL_FROM_NAME="${MAIL_FROM_NAME_IN:-Mousebook}"
fi

echo ""
echo "The base URL is what Mousebook puts in emailed links. It must be the address"
echo "your users type in their browser, with no trailing slash."
read -p "Base URL [http://localhost/${APP_SUBDIR}]: " BASE_URL
BASE_URL="${BASE_URL:-http://localhost/${APP_SUBDIR}}"
BASE_URL="${BASE_URL%/}"

# =============================================================
step "Step 6: The first Mousebook administrator"
# =============================================================

read -p "Admin username [admin]: " ADMIN_USER
ADMIN_USER="${ADMIN_USER:-admin}"
read -p "Admin email address: " ADMIN_EMAIL

while true; do
    read -s -p "Admin password (stored as a bcrypt hash, never in plain text): " ADMIN_PASS; echo ""
    read -s -p "Confirm password: " ADMIN_PASS2; echo ""
    if [ -z "$ADMIN_PASS" ]; then warn "Password cannot be empty."; continue; fi
    if [ "$ADMIN_PASS" = "$ADMIN_PASS2" ]; then break; fi
    warn "Passwords do not match - try again."
done

# Hash through the environment, never through argv or a shell-interpolated
# -r string: a quote in the password would otherwise break (or inject into)
# the PHP code, and argv is world-readable through `ps`.
ADMIN_HASH="$(MB_SETUP_PW="$ADMIN_PASS" php -r 'echo password_hash(getenv("MB_SETUP_PW"), PASSWORD_BCRYPT);')"
unset ADMIN_PASS ADMIN_PASS2
case "$ADMIN_HASH" in
    \$2y\$*) success "Admin password hashed (bcrypt)." ;;
    *)       error "password_hash() failed - check the PHP install." ;;
esac

# =============================================================
step "Step 7: Create the databases"
# =============================================================

db_has_tables() {
    local n
    n="$(db_admin -sN -e \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$1';" 2>/dev/null)"
    [ "${n:-0}" -gt 0 ] 2>/dev/null
}

# utf8mb4_unicode_ci: the one collation both engines have (M1-G / #37).
# MySQL's default utf8mb4_0900_ai_ci does not exist in MariaDB.
create_db() {
    db_admin -e "CREATE DATABASE IF NOT EXISTS \`$1\`
                 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null \
        || error "Could not create database '$1' - does the admin account have CREATE?"
}

# The schemas carry no CREATE DATABASE / USE header, so they load into
# whichever database we name here. They open with DROP TABLE IF EXISTS,
# so loading over live data destroys it: never do that without consent.
load_schema() {
    local dbname="$1" sqlfile="$2" label="$3"

    [ -f "$sqlfile" ] || { warn "$(basename "$sqlfile") is missing from $SCRIPT_DIR - skipping the $label schema."; note_failure; return; }

    create_db "$dbname"

    if db_has_tables "$dbname"; then
        warn "Database '${dbname}' already contains tables."
        echo -e "       ${RED}Loading the $label schema would DROP those tables and destroy all data in them.${NC}"
        read -p "       Keep the existing database and skip the load? (yes/no) [yes]: " SKIP_IN
        if [[ "${SKIP_IN:-yes}" =~ ^[Yy] ]]; then
            info "Kept '${dbname}' as it is - $label schema not loaded."
            return
        fi
        warn "Overwriting '${dbname}' at your request..."
    fi

    info "Loading the $label schema into '${dbname}'..."
    if db_admin "$dbname" < "$sqlfile" 2>"$TMPDIR_MB/load.err"; then
        success "$label schema loaded into '${dbname}'."
    else
        warn "$label schema failed to load:"
        sed 's/^/         /' "$TMPDIR_MB/load.err"
        note_failure
    fi
}

load_schema "$USERBOOK_DB" "$SCRIPT_DIR/mousebook_userbook_install_schema.sql" "auth (userbook)"
load_schema "$COLONY_DB"   "$SCRIPT_DIR/mousebook_install_schema.sql"          "colony"

# =============================================================
step "Step 8: Create the database accounts"
# =============================================================

# Three accounts, because Mousebook uses three different levels of access:
#
#   1. LOGIN account    - lives in config.php. Reads userbook to check a
#                         password and look up which colonies a user may
#                         open. Needs UPDATE on userpass only so an
#                         out-of-date bcrypt hash can be re-hashed at login
#                         (includes/auth.php, password_needs_rehash).
#   2. USERBOOK account - write access to userbook. Used by the admin pages
#                         and the invite/reset tokens. Its credentials live
#                         in userbook.dbaccess, NOT in config.php
#                         (includes/usertoken.php, mb_userbook_conn).
#   3. COLONY account   - read/write on the colony. LOCK TABLES is required
#                         by the reservation code, EXECUTE by the stored
#                         procedures the line filters call.
#
# Passwords are stored in the auth database's dbaccess table, whose
# db_accesspw column is varchar(255) - long passwords are fine.
gen_pass() {
    # 24 chars, with the upper/lower/digit/symbol mix that MySQL's MEDIUM
    # validate_password policy insists on. The symbol is deliberately '!'
    # and not '#': '#' opens a comment in a MySQL option file.
    local base
    base="$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 20)"
    echo "Mb!${base}9"
}

read -p "Login (read) account name [mousebook_login]: " RO_USER
RO_USER="${RO_USER:-mousebook_login}"
read -p "Userbook (write) account name [mousebook_ub]: " UB_USER
UB_USER="${UB_USER:-mousebook_ub}"
read -p "Colony account name [mousebook_app]: " APP_USER
APP_USER="${APP_USER:-mousebook_app}"

echo ""
echo "Passwords for these three accounts can be generated for you. You never have"
echo "to type or remember them: they go into config.php and userbook directly."
read -p "Generate them automatically? (yes/no) [yes]: " GEN_IN
if [[ "${GEN_IN:-yes}" =~ ^[Yy] ]]; then
    RO_PASS="$(gen_pass)"; UB_PASS="$(gen_pass)"; APP_PASS="$(gen_pass)"
    success "Three passwords generated."
else
    read -s -p "Password for ${RO_USER}: "  RO_PASS;  echo ""
    read -s -p "Password for ${UB_USER}: "  UB_PASS;  echo ""
    read -s -p "Password for ${APP_USER}: " APP_PASS; echo ""
    for p in "$RO_PASS" "$UB_PASS" "$APP_PASS"; do
        [ -n "$p" ] || error "Account passwords cannot be empty."
        [ "${#p}" -le 255 ] || error "Account passwords must be 255 characters or fewer."
        case "$p" in
            *\'*|*\\*) error "Account passwords may not contain a single quote or a backslash." ;;
        esac
    done
fi

# The application accounts connect from the web server. If the database is on
# this same machine the connection arrives as 'localhost'; if Mousebook and the
# database are on different machines, the accounts must exist for the address
# the database sees the web server as.
if [ "$DB_HOST" = "localhost" ] || [ "$DB_HOST" = "127.0.0.1" ]; then
    APP_FROM="localhost"
else
    echo ""
    info "The database is on another host (${DB_HOST})."
    read -p "Address the database will see this web server as (or % for any) [%]: " APP_FROM
    APP_FROM="${APP_FROM:-%}"
fi
info "Accounts will be created as '<user>'@'${APP_FROM}'."

ACCT_SQL="$TMPDIR_MB/accounts.sql"
cat > "$ACCT_SQL" <<SQLEOF
-- CREATE USER IF NOT EXISTS does NOT change the password of an account that
-- already exists - it silently does nothing. On a re-run that would leave the
-- account holding its old password while config.php and dbaccess get the new
-- one, and every connection would fail. ALTER USER makes the account match
-- what we are about to write, whether it existed before or not.
CREATE USER IF NOT EXISTS '${RO_USER}'@'${APP_FROM}'  IDENTIFIED BY '${RO_PASS}';
CREATE USER IF NOT EXISTS '${UB_USER}'@'${APP_FROM}'  IDENTIFIED BY '${UB_PASS}';
CREATE USER IF NOT EXISTS '${APP_USER}'@'${APP_FROM}' IDENTIFIED BY '${APP_PASS}';

ALTER USER '${RO_USER}'@'${APP_FROM}'  IDENTIFIED BY '${RO_PASS}';
ALTER USER '${UB_USER}'@'${APP_FROM}'  IDENTIFIED BY '${UB_PASS}';
ALTER USER '${APP_USER}'@'${APP_FROM}' IDENTIFIED BY '${APP_PASS}';

-- 1. login account: read userbook, plus re-hash an old password at login
GRANT SELECT ON \`${USERBOOK_DB}\`.* TO '${RO_USER}'@'${APP_FROM}';
GRANT UPDATE ON \`${USERBOOK_DB}\`.\`userpass\` TO '${RO_USER}'@'${APP_FROM}';

-- 2. userbook account: manage accounts, colonies, grants and tokens
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${USERBOOK_DB}\`.* TO '${UB_USER}'@'${APP_FROM}';

-- 3. colony account: the application proper
--    LOCK TABLES -> reservation code (add_animals.php, manage_cages.php)
--    EXECUTE     -> stored procedures (includes/filters.php, CALL get_lines())
GRANT SELECT, INSERT, UPDATE, DELETE, LOCK TABLES, EXECUTE
      ON \`${COLONY_DB}\`.* TO '${APP_USER}'@'${APP_FROM}';

FLUSH PRIVILEGES;
SQLEOF

if ACCT_OUT="$(db_admin < "$ACCT_SQL" 2>&1)"; then
    success "Created ${RO_USER}, ${UB_USER}, ${APP_USER}."
else
    echo "$ACCT_OUT" | sed 's/^/         /'
    note_failure
    if echo "$ACCT_OUT" | grep -qi "1819\|password.*policy\|validate_password"; then
        warn "The database rejected a password as too weak (validate_password policy)."
        warn "Re-run setup.sh and let it generate the passwords, or relax the policy."
    else
        warn "Account creation failed. The admin account may lack CREATE USER / GRANT."
    fi
fi

# =============================================================
step "Step 9: Write config.php"
# =============================================================

CONFIG_PATH="$APP_DIR/config.php"
if [ -f "$CONFIG_PATH" ]; then
    BACKUP_CFG="${CONFIG_PATH}.bak.$(date +%Y%m%d%H%M%S)"
    cp -p "$CONFIG_PATH" "$BACKUP_CFG"
    warn "An existing config.php was backed up to $(basename "$BACKUP_CFG")"
fi

cat > "$CONFIG_PATH" <<CONFIGEOF
<?php
// =============================================================
// Mousebook configuration - generated by setup.sh on $(date '+%Y-%m-%d %H:%M:%S')
// Contains database passwords. Never commit this file.
// =============================================================
return [
    // Database server. 'server_ip' is the legacy name for the same value.
    'server_ip'   => '${DB_HOST}',
    'server_host' => '${DB_HOST}',
    'server_port' => '${DB_PORT}',

    // The LOGIN account: reads userbook to verify passwords and to find out
    // which colonies a user may open. It deliberately cannot write to any
    // colony - those credentials live in userbook.dbaccess instead.
    'server_user' => '${RO_USER}',
    'server_pass' => '${RO_PASS}',

    // The auth database. Not necessarily called 'userbook': cPanel-style
    // hosts prefix database names, and this must be whatever yours is.
    'userbook_db' => '${USERBOOK_DB}',

    // 'True' shows PHP errors in the browser. Never in production.
    'debug_mode'  => '${DEBUG_MODE}',

    'site_name'   => 'Mousebook',
    'site_contact'=> '${ADMIN_EMAIL}',

    // Public address of this install, no trailing slash. Used to build the
    // invitation and password-reset links that go out by email.
    'base_url'       => '${BASE_URL}',

    // Outgoing mail. An empty smtp_host disables email entirely.
    // smtp_port and smtp_secure MUST agree: 587/tls, 465/ssl, 25/(none).
    'smtp_host'      => '${SMTP_HOST}',
    'smtp_port'      => '${SMTP_PORT:-587}',
    'smtp_secure'    => '${SMTP_SECURE}',
    'smtp_auth'      => ${SMTP_AUTH},
    'smtp_user'      => '${SMTP_USER}',
    'smtp_pass'      => '${SMTP_PASS}',
    'mail_from'      => '${MAIL_FROM}',
    'mail_from_name' => '${MAIL_FROM_NAME}',

    // Fail fast rather than hang if the relay is wrong or unreachable.
    'smtp_timeout'   => 15,
    // Log the whole SMTP conversation to the PHP error log (diagnosis only).
    'smtp_debug'     => false,
    // Only for a trusted internal relay with a self-signed certificate.
    'smtp_allow_selfsigned' => false,
];
CONFIGEOF

# 640 with the web server as group: Apache can read it, nobody else can.
CURRENT_USER="${SUDO_USER:-$(whoami)}"
chown "${CURRENT_USER}:${WEB_USER}" "$CONFIG_PATH" 2>/dev/null \
    || warn "Could not chown config.php to ${CURRENT_USER}:${WEB_USER} - check it by hand."
chmod 640 "$CONFIG_PATH"
success "config.php written (${CURRENT_USER}:${WEB_USER}, mode 640)."

if php -l "$CONFIG_PATH" >/dev/null 2>&1; then
    success "config.php is valid PHP."
else
    warn "config.php is NOT valid PHP - a quote in one of your answers may have broken it."
    note_failure
fi

# -- keep config.php off the web ------------------------------
HTACCESS="$APP_DIR/.htaccess"
if [ -f "$HTACCESS" ] && grep -q "config.php" "$HTACCESS"; then
    info ".htaccess already blocks config.php."
else
    cat >> "$HTACCESS" <<'HTEOF'

# Block direct web access to config.php
<Files "config.php">
    Require all denied
</Files>
HTEOF
    success ".htaccess now blocks direct access to config.php."
fi
warn "That .htaccess rule only takes effect if Apache is set to AllowOverride All for this directory (see INSTALL.md)."

# =============================================================
step "Step 10: Register the databases and create the admin"
# =============================================================

# This is what makes the install usable, and it is the easiest part to get
# wrong by hand:
#
#   * BOTH databases are registered in userbook.dbaccess - the colony AND
#     userbook itself. The admin pages reach the auth database through its
#     own dbaccess row (includes/usertoken.php, mb_userbook_conn), so without
#     that row "Manage Users" cannot work at all.
#   * db_formurl is stored relative to pages/databases.php. The column is
#     varchar(255) now, so an absolute URL would also fit - but a relative
#     path keeps working if the site later moves or changes hostname.
#   * The admin needs tier 'admin' on BOTH databases. Any value that is not
#     exactly admin/editor/read-only is normalised down to 'read-only'
#     (includes/session.php, mb_normalize_tier) - which is why writing a
#     tier of '1' produces an "admin" who can do nothing.

BOOT_SQL="$TMPDIR_MB/bootstrap.sql"
cat > "$BOOT_SQL" <<SQLEOF
USE \`${USERBOOK_DB}\`;

-- The colony, as users will see it on the login page
-- ON DUPLICATE KEY UPDATE, not INSERT IGNORE: on a re-run the account
-- passwords have just been rotated, so the stored credentials must be
-- rewritten to match or the colony becomes unreachable. Only the credentials
-- are overwritten - db_formurl and the subject labels, which an admin may have
-- customised in Manage Databases, are left alone once the row exists.
INSERT INTO \`dbaccess\`
    (\`db_name\`, \`db_accessun\`, \`db_accesspw\`, \`db_formurl\`, \`db_host\`,
     \`db_subject_plural\`, \`db_subject_single\`)
VALUES
    ('${COLONY_DB}', '${APP_USER}', '${APP_PASS}', '../index.php', '${DB_HOST}',
     '${SUBJECT_PLURAL}', '${SUBJECT_SINGLE}')
ON DUPLICATE KEY UPDATE
    \`db_accessun\` = VALUES(\`db_accessun\`),
    \`db_accesspw\` = VALUES(\`db_accesspw\`),
    \`db_host\`     = VALUES(\`db_host\`);

-- userbook registered as a database in its own right, with WRITE credentials.
-- This is what the admin pages and the invite/reset tokens connect through.
INSERT INTO \`dbaccess\`
    (\`db_name\`, \`db_accessun\`, \`db_accesspw\`, \`db_formurl\`, \`db_host\`,
     \`db_subject_plural\`, \`db_subject_single\`)
VALUES
    ('${USERBOOK_DB}', '${UB_USER}', '${UB_PASS}', '../index.php?ctx=userbook', '${DB_HOST}',
     'users', 'user')
ON DUPLICATE KEY UPDATE
    \`db_accessun\` = VALUES(\`db_accessun\`),
    \`db_accesspw\` = VALUES(\`db_accesspw\`),
    \`db_host\`     = VALUES(\`db_host\`);

-- The first administrator
INSERT IGNORE INTO \`userpass\` (\`user_name\`, \`user_pass\`, \`user_salt\`)
VALUES ('${ADMIN_USER}', '${ADMIN_HASH}', '');

INSERT IGNORE INTO \`userdetail\` (\`user_idno\`, \`user_email\`)
SELECT \`user_idno\`, '${ADMIN_EMAIL}' FROM \`userpass\`
 WHERE \`user_name\` = '${ADMIN_USER}';

-- Admin tier on the colony AND on userbook (the latter is what puts
-- Manage Users / Manage Databases in the sidebar).
INSERT INTO \`userdbaccess\` (\`user_idno\`, \`db_name\`, \`db_accesstier\`)
SELECT u.\`user_idno\`, '${COLONY_DB}', 'admin' FROM \`userpass\` u
 WHERE u.\`user_name\` = '${ADMIN_USER}'
   AND NOT EXISTS (SELECT 1 FROM \`userdbaccess\` x
                    WHERE x.\`user_idno\` = u.\`user_idno\` AND x.\`db_name\` = '${COLONY_DB}');

INSERT INTO \`userdbaccess\` (\`user_idno\`, \`db_name\`, \`db_accesstier\`)
SELECT u.\`user_idno\`, '${USERBOOK_DB}', 'admin' FROM \`userpass\` u
 WHERE u.\`user_name\` = '${ADMIN_USER}'
   AND NOT EXISTS (SELECT 1 FROM \`userdbaccess\` x
                    WHERE x.\`user_idno\` = u.\`user_idno\` AND x.\`db_name\` = '${USERBOOK_DB}');
SQLEOF

if BOOT_OUT="$(db_admin < "$BOOT_SQL" 2>&1)"; then
    success "Colony '${COLONY_DB}' registered."
    success "Auth database '${USERBOOK_DB}' registered with write credentials."
    success "Admin '${ADMIN_USER}' created, with admin tier on both."
    info    "(If '${ADMIN_USER}' already existed, its password was left as it was -"
    info    " setup.sh never resets an existing user's password. Use Manage Users"
    info    " or the Forgot-password link for that.)"
else
    echo "$BOOT_OUT" | sed 's/^/         /'
    warn "Bootstrap failed - the install will not be usable until this is fixed."
    note_failure
fi

# =============================================================
step "Step 11: Verify"
# =============================================================

probe() {  # probe <user> <pass> <db> <sql> <description>
    local cnf="$TMPDIR_MB/probe.cnf"
    umask 077
    {
        echo "[client]"
        echo "user=$(cnf_escape "$1")"
        echo "password=$(cnf_escape "$2")"
        echo "host=$(cnf_escape "${DB_HOST}")"
        echo "port=${DB_PORT}"
    } > "$cnf"
    if "$DB_CLIENT" --defaults-extra-file="$cnf" -sN "$3" -e "$4" >/dev/null 2>&1; then
        success "$5"
    else
        warn "FAILED: $5"
        note_failure
    fi
    rm -f "$cnf"
}

probe "$RO_USER"  "$RO_PASS"  "$USERBOOK_DB" "SELECT COUNT(*) FROM userpass;" \
      "login account can read userbook"
probe "$UB_USER"  "$UB_PASS"  "$USERBOOK_DB" "SELECT COUNT(*) FROM dbaccess;" \
      "userbook account can read userbook"
probe "$APP_USER" "$APP_PASS" "$COLONY_DB"   "SELECT COUNT(*) FROM table_animals;" \
      "colony account can read the colony"
probe "$APP_USER" "$APP_PASS" "$COLONY_DB"   "LOCK TABLES reservations_animals WRITE; UNLOCK TABLES;" \
      "colony account can LOCK TABLES (reservations)"
probe "$APP_USER" "$APP_PASS" "$COLONY_DB"   "CALL get_lines();" \
      "colony account can run stored procedures (line filters)"

TIER="$(db_admin -sN "$USERBOOK_DB" -e \
    "SELECT db_accesstier FROM userdbaccess d JOIN userpass u ON u.user_idno=d.user_idno
      WHERE u.user_name='${ADMIN_USER}' AND d.db_name='${USERBOOK_DB}';" 2>/dev/null)"
if [ "$TIER" = "admin" ]; then
    success "'${ADMIN_USER}' holds tier 'admin' on ${USERBOOK_DB} (Manage Users will appear)"
else
    warn "FAILED: the admin tier on ${USERBOOK_DB} is '${TIER:-missing}', not 'admin'."
    note_failure
fi

# =============================================================
echo ""
echo -e "${BOLD}============================================${NC}"
if [ "$FAILURES" -eq 0 ]; then
    echo -e "${BOLD}   Setup complete                           ${NC}"
else
    echo -e "${BOLD}   Setup finished with ${FAILURES} problem(s)        ${NC}"
fi
echo -e "${BOLD}============================================${NC}"
echo ""
echo -e "  Log in at:  ${BOLD}${BASE_URL}/pages/databases.php${NC}"
echo -e "  Username:   ${BOLD}${ADMIN_USER}${NC}"
echo ""
echo -e "  After logging in you will see two buttons:"
echo -e "    ${BOLD}${COLONY_DB}${NC}  - your colony"
echo -e "    ${BOLD}${USERBOOK_DB}${NC}   - user administration (Manage Users / Manage Databases)"
echo ""
if [ "$FAILURES" -gt 0 ]; then
    echo -e " ${YELLOW}Some steps reported problems - scroll up. INSTALL.md has a"
    echo -e " troubleshooting section covering each of them.${NC}"
    echo ""
fi
echo -e " ${YELLOW}Next steps:${NC}"
echo ""
echo -e "  1. ${BOLD}Set up your rooms and cage roles.${NC} A new colony starts with just one"
echo -e "     of each - the location ${BOLD}Limbo${NC} and the cage role ${BOLD}Community${NC} - so that it"
echo -e "     works out of the box. Add your own real rooms and roles in"
echo -e "     ${BOLD}Cage Location Manager${NC} and ${BOLD}Manage Roles${NC}. See ADMIN_GUIDE.md section 4."
echo ""
if [ -z "$SMTP_HOST" ]; then
echo -e "  2. ${BOLD}Email is off.${NC} New users cannot be sent an invitation link, so you"
echo -e "     must copy each set-password link out of Manage Users by hand. To turn"
echo -e "     email on, fill in the smtp_* values in config.php."
echo ""
fi
echo -e "  3. ${BOLD}Serve this over HTTPS before real use.${NC} Passwords are posted to the"
echo -e "     login page; over plain HTTP they cross the network in the clear."
echo ""
echo -e "  4. ${BOLD}Set up backups${NC} - see BACKUP.md."
echo ""
[ "$FAILURES" -eq 0 ] || exit 1
exit 0
