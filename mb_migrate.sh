#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# mb_migrate.sh — THE entry point for Mousebook schema migrations.
#
# This is the only migration script an operator runs. The files in migrations/
# are never invoked by hand: this runner executes them, in order, and records
# each in the ledger. Running one directly would leave the ledger lying about
# what happened to the database.
#
#   ./mb_migrate.sh --db mycolony preflight   # is a conversion safe on this data?
#   ./mb_migrate.sh --db mycolony status      # what is applied, what is pending
#   ./mb_migrate.sh --db mycolony apply       # apply pending migrations
#   ./mb_migrate.sh --db mycolony stamp       # record pending as applied WITHOUT
#                                             #   running them (fresh installs only)
#
# Run it against EVERY Mousebook database — the colony DB and userbook. They
# migrate independently and each carries its own ledger.
#
# Connection comes from the environment, same as setup.sh:
#     DB_HOST  DB_PORT  DB_USER  DB_PASS
# ---------------------------------------------------------------------------
set -uo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}";      DB_PASS="${DB_PASS:-}"
DB_NAME=""; CMD=""; ASSUME_YES=0

ROOT="$(cd "$(dirname "$0")" && pwd)"
MIGRATIONS_DIR="${ROOT}/migrations"

usage() {
    echo "usage: ./mb_migrate.sh --db <database> {preflight|status|apply|stamp} [--yes]"
    echo
    echo "  preflight  is a utf8mb4 conversion safe on this data? (changes nothing)"
    echo "  status     what is applied, what is pending          (changes nothing)"
    echo "  apply      apply pending migrations, in order"
    echo "  stamp      record pending as applied WITHOUT running them (fresh installs)"
    echo
    echo "connection: DB_HOST DB_PORT DB_USER DB_PASS (environment, as in setup.sh)"
    exit "${1:-2}"
}

while [ $# -gt 0 ]; do
  case "$1" in
    --db) DB_NAME="${2:-}"; shift 2 ;;
    --yes|-y) ASSUME_YES=1; shift ;;
    --user) DB_USER="${2:-}"; shift 2 ;;
    --password) DB_PASS="${2:-}"; shift 2 ;;
    --ask-pass) printf "password for %s@%s: " "${DB_USER}" "${DB_HOST}"; read -rs DB_PASS; echo; shift ;;
    -h|--help) usage 0 ;;
    status|apply|stamp|preflight) CMD="$1"; shift ;;
    *) echo "unknown argument: $1"; echo; usage 2 ;;
  esac
done
[ -n "$DB_NAME" ] || { echo "ERROR: --db <database> is required"; echo; usage 2; }
[ -n "$CMD" ]     || { echo "ERROR: one of: preflight | status | apply | stamp"; echo; usage 2; }

if command -v mysql >/dev/null 2>&1;     then CLIENT=mysql
elif command -v mariadb >/dev/null 2>&1; then CLIENT=mariadb
else echo "ERROR: no mysql/mariadb client on PATH"; exit 1; fi
export CLIENT DB_HOST DB_PORT DB_USER DB_PASS DB_NAME

# ---------------------------------------------------------------------------
# Connection. Environment (as in setup.sh) or flags; flags win.
#   DB_HOST DB_PORT DB_USER DB_PASS
#   --user <u>  --password <p>  --ask-pass
# ---------------------------------------------------------------------------
check_connection() {
    local err
    if err=$(MYSQL_PWD="$DB_PASS" "$CLIENT" --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" \
                 -u "$DB_USER" -e "SELECT 1;" 2>&1 >/dev/null); then
        return 0
    fi
    echo "=============================================================="
    echo " CANNOT CONNECT"
    echo "=============================================================="
    echo "   host     : ${DB_HOST}:${DB_PORT}"
    echo "   user     : ${DB_USER}"
    echo "   password : $([ -n "$DB_PASS" ] && echo 'set' || echo 'NOT SET')"
    echo
    echo "   server said: ${err}"
    echo
    if [ "$DB_USER" = "root" ] && [ -z "$DB_PASS" ]; then
        echo " These are the built-in defaults, which means DB_USER / DB_PASS did not"
        echo " reach this script. Common causes:"
        echo "   * the 'export' happened in a different shell/session"
        echo "   * you used sudo, which strips the environment -> use 'sudo -E', or no sudo"
        echo
    fi
    echo " Set the connection either way:"
    echo
    echo "   export DB_HOST=localhost DB_USER=youruser DB_PASS='yourpassword'"
    echo "   $(basename "$0") ..."
    echo
    echo " or pass it directly (and be prompted for the password):"
    echo
    echo "   $(basename "$0") --user youruser --ask-pass ..."
    echo "=============================================================="
    exit 1
}

check_connection

db()  { MYSQL_PWD="$DB_PASS" "$CLIENT" --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" "$@"; }
dbq() { db -N -e "$1" 2>/dev/null; }

# The charset safety check lives in ONE file, shared with migration 001's own
# gate. Two copies of a safety check drift apart, and the copy that drifts is
# the one guarding your data.
# shellcheck source=migrations/lib/charset_guard.sh
. "${MIGRATIONS_DIR}/lib/charset_guard.sh"

# ---------------------------------------------------------------------------
# preflight
# ---------------------------------------------------------------------------
if [ "$CMD" = "preflight" ]; then
    echo "=============================================================="
    echo " utf8mb4 conversion preflight — ${DB_NAME}"
    echo "=============================================================="
    if cg_report; then
        echo
        echo " Migration may run:  ./mb_migrate.sh --db ${DB_NAME} apply"
        exit 0
    fi
    echo
    echo " Migration 001 will REFUSE to run against this database until the"
    echo " columns above are triaged. Nothing has been changed."
    exit 1
fi

# ---------------------------------------------------------------------------
# ledger
# An existing install has no ledger, and that is fine: an empty ledger means
# "nothing recorded", and every migration is convergent (safe against a database
# that already satisfies it), so an unknown starting state is not a problem the
# operator has to solve.
# ---------------------------------------------------------------------------
db -e "CREATE TABLE IF NOT EXISTS \`mb_schema_version\` (
         \`migration\`  varchar(128) NOT NULL,
         \`checksum\`   char(64)     NOT NULL,
         \`applied_at\` datetime     NOT NULL,
         \`applied_by\` varchar(64)  NOT NULL,
         \`outcome\`    enum('applied','stamped') NOT NULL DEFAULT 'applied',
         PRIMARY KEY (\`migration\`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" 2>/dev/null

sum_of()     { sha256sum "$1" | cut -d' ' -f1; }
is_applied() { [ -n "$(dbq "SELECT migration FROM mb_schema_version WHERE migration='$1';")" ]; }
record()     { db -e "INSERT INTO mb_schema_version (migration, checksum, applied_at, applied_by, outcome)
                      VALUES ('$1','$2',NOW(),'${USER:-unknown}','$3')
                      ON DUPLICATE KEY UPDATE checksum=VALUES(checksum), applied_at=NOW();" 2>/dev/null; }

# -maxdepth 1 keeps migrations/lib/ out: it is a library directory, not a step.
migrations() { find "$MIGRATIONS_DIR" -maxdepth 1 \( -name '*.sql' -o -name '*.sh' \) 2>/dev/null | sort; }

echo "=============================================================="
echo " Mousebook migrations — database: ${DB_NAME}"
echo "=============================================================="
applied_n=$(dbq "SELECT COUNT(*) FROM mb_schema_version;")
echo " applied: ${applied_n:-0}"
dbq "SELECT CONCAT('   [x] ', migration, '  (', outcome, ' ', applied_at, ')') FROM mb_schema_version ORDER BY migration;"

# A migration whose file changed after being applied means the repo and the
# database now disagree about what actually ran.
for f in $(migrations); do
    n="$(basename "$f")"
    recorded="$(dbq "SELECT checksum FROM mb_schema_version WHERE migration='$n';")"
    [ -z "$recorded" ] && continue
    actual="$(sum_of "$f")"
    if [ "$recorded" != "$actual" ]; then
        echo "  !! ${n}: file has CHANGED since it was applied here"
        echo "     recorded ${recorded:0:12}…  actual ${actual:0:12}…"
        echo "     A released migration must never be edited. Add a new one instead."
    fi
done

PENDING=""
for f in $(migrations); do
    is_applied "$(basename "$f")" || PENDING="${PENDING}${f}"$'\n'
done
PENDING="$(echo "$PENDING" | sed '/^$/d')"

if [ -z "$PENDING" ]; then
    echo " pending: 0 — this database is up to date."
else
    echo " pending: $(echo "$PENDING" | wc -l)"
    for f in $PENDING; do echo "   [ ] $(basename "$f")"; done
fi

[ "$CMD" = "status" ] && { echo "=============================================================="; exit 0; }
[ -z "$PENDING" ]     && { echo "=============================================================="; exit 0; }

# ---------------------------------------------------------------------------
# stamp
# ---------------------------------------------------------------------------
if [ "$CMD" = "stamp" ]; then
    echo
    echo " STAMP: recording pending migrations as applied WITHOUT running them."
    echo " Do this only on a FRESH install, where the install schema already"
    echo " contains everything these migrations would do."
    if [ "$ASSUME_YES" -ne 1 ]; then
        printf " Type 'stamp' to confirm: "; read -r c
        [ "$c" = "stamp" ] || { echo " aborted."; exit 1; }
    fi
    for f in $PENDING; do
        record "$(basename "$f")" "$(sum_of "$f")" stamped && echo "   stamped: $(basename "$f")"
    done
    echo " done."
    exit 0
fi

# ---------------------------------------------------------------------------
# apply
# ---------------------------------------------------------------------------
echo
echo " BACKUP GATE"
echo " Schema migrations are DDL. In MySQL and MariaDB, DDL cannot be rolled"
echo " back — there is no transaction to abort. If a migration fails partway,"
echo " your only recovery is the backup."
echo
echo "   mysqldump -h ${DB_HOST} -u ${DB_USER} -p ${DB_NAME} > ${DB_NAME}-\$(date +%F).sql"
echo
if [ "$ASSUME_YES" -ne 1 ]; then
    printf " Type 'yes' to confirm you have a verified backup: "; read -r c
    [ "$c" = "yes" ] || { echo " aborted — no changes made."; exit 1; }
fi

for f in $PENDING; do
    n="$(basename "$f")"
    echo
    echo "--- applying ${n} ---"
    case "$f" in
        *.sql) out=$(db < "$f" 2>&1); rc=$? ;;
        *.sh)  out=$(MIGRATIONS_DIR="$MIGRATIONS_DIR" bash "$f" 2>&1); rc=$? ;;
    esac
    echo "$out" | sed 's/^/    /'
    if [ "$rc" -ne 0 ]; then
        echo
        echo "!! FAILED: ${n} (exit ${rc})"
        echo "!! Not recorded as applied. Fix the cause and re-run — migrations are"
        echo "!! convergent, so re-running against a partially-migrated database is"
        echo "!! safe. If the database is damaged, restore from your backup."
        exit 1
    fi
    record "$n" "$(sum_of "$f")" applied
    echo "    recorded in mb_schema_version"
done

echo
echo "=============================================================="
echo " All migrations applied. ${DB_NAME} is up to date."
echo
echo " Verify it matches the reference schema:"
echo "   ./mb_schema_check.sh --db ${DB_NAME} --kind colony"
echo "=============================================================="
