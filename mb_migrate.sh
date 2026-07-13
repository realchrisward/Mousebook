#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# mb_migrate.sh — apply pending schema migrations to a Mousebook database.
#
# The user does NOT decide which migration applies. The database records what
# has already been applied, in a ledger table (mb_schema_version), and this
# script computes the rest. Run it against any install, in any state, and it
# converges that install to the current schema.
#
#   ./mb_migrate.sh --db mycolony status     # what's applied, what's pending
#   ./mb_migrate.sh --db mycolony apply      # apply pending migrations
#   ./mb_migrate.sh --db mycolony stamp      # mark all pending as applied
#                                            #   (fresh installs only — the
#                                            #    install schema already has them)
#
# Connection comes from the same environment as setup.sh:
#   DB_HOST DB_PORT DB_USER DB_PASS
# ---------------------------------------------------------------------------
set -uo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}";      DB_PASS="${DB_PASS:-}"
DB_NAME=""; CMD=""; ASSUME_YES=0

MIGRATIONS_DIR="$(cd "$(dirname "$0")" && pwd)/migrations"

while [ $# -gt 0 ]; do
  case "$1" in
    --db) DB_NAME="$2"; shift 2 ;;
    --yes|-y) ASSUME_YES=1; shift ;;
    status|apply|stamp) CMD="$1"; shift ;;
    *) echo "unknown argument: $1"; exit 2 ;;
  esac
done
[ -n "$DB_NAME" ] || { echo "ERROR: --db <database> is required"; exit 2; }
[ -n "$CMD" ]     || { echo "ERROR: one of: status | apply | stamp"; exit 2; }

if command -v mysql >/dev/null 2>&1;     then CLIENT=mysql
elif command -v mariadb >/dev/null 2>&1; then CLIENT=mariadb
else echo "ERROR: no mysql/mariadb client on PATH"; exit 1; fi

db()  { "$CLIENT" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" "$@"; }
dbq() { db -N -e "$1" 2>/dev/null; }

# --- ledger -----------------------------------------------------------------
# Created on first contact. Existing installs have no ledger and that is fine:
# an empty ledger means "nothing recorded as applied", and every migration is
# written to be convergent (safe to run against a database that already
# satisfies it), so an unknown starting state is not a problem to solve — it is
# a state the migrations handle by construction.
ensure_ledger() {
  db -e "CREATE TABLE IF NOT EXISTS \`mb_schema_version\` (
           \`migration\`  varchar(128) NOT NULL,
           \`checksum\`   char(64)     NOT NULL,
           \`applied_at\` datetime     NOT NULL,
           \`applied_by\` varchar(64)  NOT NULL,
           \`outcome\`    enum('applied','stamped') NOT NULL DEFAULT 'applied',
           PRIMARY KEY (\`migration\`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" 2>/dev/null
}

sum_of()     { sha256sum "$1" | cut -d' ' -f1; }
is_applied() { [ -n "$(dbq "SELECT migration FROM mb_schema_version WHERE migration='$1';")" ]; }
record()     { db -e "INSERT INTO mb_schema_version (migration, checksum, applied_at, applied_by, outcome)
                      VALUES ('$1','$2',NOW(),'${USER:-unknown}','$3')
                      ON DUPLICATE KEY UPDATE checksum=VALUES(checksum), applied_at=NOW();" ; }

migrations() { find "$MIGRATIONS_DIR" -maxdepth 1 \( -name '*.sql' -o -name '*.sh' \) 2>/dev/null | sort; }

ensure_ledger

# --- checksum drift ---------------------------------------------------------
# An applied migration whose file has since changed means someone edited
# history. Loud warning: the database and the repo disagree about what ran.
check_drift() {
  local drift=0
  for f in $(migrations); do
    local n; n="$(basename "$f")"
    local recorded; recorded="$(dbq "SELECT checksum FROM mb_schema_version WHERE migration='$n';")"
    [ -z "$recorded" ] && continue
    local actual; actual="$(sum_of "$f")"
    if [ "$recorded" != "$actual" ]; then
      echo "  !! ${n}: file has CHANGED since it was applied here"
      echo "     recorded ${recorded:0:12}…  actual ${actual:0:12}…"
      drift=1
    fi
  done
  [ "$drift" -eq 1 ] && echo "     A migration must never be edited after release. Add a new one instead."
  return 0
}

# --- status -----------------------------------------------------------------
pending_list() {
  for f in $(migrations); do
    is_applied "$(basename "$f")" || echo "$f"
  done
}

echo "=============================================================="
echo " Mousebook migrations — database: ${DB_NAME}"
echo "=============================================================="
applied_n=$(dbq "SELECT COUNT(*) FROM mb_schema_version;")
echo " applied: ${applied_n:-0}"
dbq "SELECT CONCAT('   [x] ', migration, '  (', outcome, ' ', applied_at, ')') FROM mb_schema_version ORDER BY migration;"
check_drift

PENDING="$(pending_list)"
if [ -z "$PENDING" ]; then
  echo " pending: 0 — this database is up to date."
else
  echo " pending: $(echo "$PENDING" | wc -l)"
  for f in $PENDING; do echo "   [ ] $(basename "$f")"; done
fi

[ "$CMD" = "status" ] && { echo "=============================================================="; exit 0; }
[ -z "$PENDING" ]     && { echo "=============================================================="; exit 0; }

# --- stamp ------------------------------------------------------------------
if [ "$CMD" = "stamp" ]; then
  echo
  echo " STAMP: recording pending migrations as applied WITHOUT running them."
  echo " Do this only on a FRESH install, where the install schema already"
  echo " includes everything these migrations would do."
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

# --- apply ------------------------------------------------------------------
echo
echo " BACKUP GATE"
echo " Schema migrations are DDL. In MySQL and MariaDB, DDL cannot be rolled"
echo " back — there is no transaction to abort. If a migration fails halfway,"
echo " your only recovery is the backup. Take one now if you have not."
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
    *.sh)  out=$(DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" \
                 DB_PASS="$DB_PASS" DB_NAME="$DB_NAME" CLIENT="$CLIENT" bash "$f" 2>&1); rc=$? ;;
  esac
  echo "$out" | sed 's/^/    /'
  if [ "$rc" -ne 0 ]; then
    echo
    echo "!! FAILED: ${n} (exit ${rc})"
    echo "!! Not recorded as applied. The database may be PARTIALLY migrated."
    echo "!! Fix the cause and re-run — migrations are convergent, so re-running"
    echo "!! is safe. If the database is damaged, restore from your backup."
    exit 1
  fi
  record "$n" "$(sum_of "$f")" applied
  echo "    recorded in mb_schema_version"
done

echo
echo "=============================================================="
echo " All migrations applied. ${DB_NAME} is up to date."
echo "=============================================================="
