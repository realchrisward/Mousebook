#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# mb_schema_check.sh — is this running database the schema we actually ship?
#
# Builds a REFERENCE database from the starter SQL plus every migration, then
# compares a live database against it, structure by structure.
#
#     ./mb_schema_check.sh --db mycolony --kind colony
#     ./mb_schema_check.sh --db userbook --kind userbook
#     ./mb_schema_check.sh --rebaseline            # refresh the CI drift baseline
#
# WHY THIS EXISTS
# There are two ways to arrive at a Mousebook database:
#   (a) fresh install  -> load the starter SQL
#   (b) existing install -> load an older starter SQL, then apply migrations
# These must produce the SAME schema. If they drift apart, new installs and
# upgraded installs quietly become different products, and a bug reproduces on
# one and not the other. That failure is slow, silent, and miserable to debug.
# This script makes it loud.
#
# It also catches the third case: a live database someone has hand-edited.
#
# Connection: DB_HOST DB_PORT DB_USER DB_PASS (as in setup.sh). The user needs
# CREATE/DROP on a scratch database (mb_ref_check_*), which is dropped afterward.
# ---------------------------------------------------------------------------
set -uo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}";      DB_PASS="${DB_PASS:-}"
TARGET=""; KIND=""; REBASELINE=0

ROOT="$(cd "$(dirname "$0")" && pwd)"
REF_DB="mb_ref_check_$$"

usage() {
    echo "usage: ./mb_schema_check.sh --db <database> --kind {colony|userbook}"
    echo "       ./mb_schema_check.sh --rebaseline"
    echo
    echo "  --db/--kind   compare a live database against starter SQL + migrations"
    echo "  --rebaseline  regenerate .github/ci/schema-baseline.tsv from the"
    echo "                starter SQL (do this ONLY when a schema change is"
    echo "                intentional, and commit it in the same PR)"
    exit "${1:-2}"
}

while [ $# -gt 0 ]; do
  case "$1" in
    --db) TARGET="${2:-}"; shift 2 ;;
    --kind) KIND="${2:-}"; shift 2 ;;
    --rebaseline) REBASELINE=1; shift ;;
    -h|--help) usage 0 ;;
    *) echo "unknown argument: $1"; echo; usage 2 ;;
  esac
done

if command -v mysql >/dev/null 2>&1;     then CLIENT=mysql; DUMPER=mysqldump
elif command -v mariadb >/dev/null 2>&1; then CLIENT=mariadb; DUMPER=mariadb-dump
else echo "ERROR: no mysql/mariadb client on PATH"; exit 1; fi

sql()  { MYSQL_PWD="$DB_PASS" "$CLIENT" --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$@"; }
# The ledger is excluded at the dump level, not filtered out afterwards: a
# half-filtered CREATE TABLE block leaks its trailing lines into the comparison
# and produces phantom differences.
dump() { MYSQL_PWD="$DB_PASS" "$DUMPER" --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
              --no-data --skip-comments --skip-dump-date --routines --skip-triggers \
              --ignore-table="${1}.mb_schema_version" "$1" 2>/dev/null; }

# ---------------------------------------------------------------------------
# Normalisation. A structural comparison must ignore everything that legitimately
# differs between two databases holding the same schema:
#   * AUTO_INCREMENT counters   - depend on rows inserted, not on structure
#   * charset spelling          - "utf8" vs "utf8mb3" across server versions
#   * the ledger table itself    - a live DB has it, a freshly loaded one may not
#   * statement/table order      - servers do not agree on collation of names
# Whatever survives all that is a REAL structural difference.
# ---------------------------------------------------------------------------
normalise() {
    sed -E \
        -e 's/ AUTO_INCREMENT=[0-9]+//g' \
        -e 's/\butf8\b/utf8mb3/g' \
        -e 's/[[:space:]]+$//' \
    | awk '
        /^CREATE TABLE/       { inblk=1 }
        /^\/\*!50001 CREATE/  { inblk=1 }
        inblk { blk = blk $0 "\n" }
        /^\) ENGINE=/ || /^\/\*!50001 CREATE VIEW.*\*\/;/ {
            if (inblk) { print blk "\036"; blk=""; inblk=0 }
        }
    ' \
    | tr '\n' '\037' | tr '\036' '\n' | LC_ALL=C sort | tr '\037' '\n'
}

# ---------------------------------------------------------------------------
# Build the reference: starter SQL + every migration, in a scratch database.
# ---------------------------------------------------------------------------
build_reference() {
    local schema_file="$1"
    sql -e "DROP DATABASE IF EXISTS \`${REF_DB}\`; CREATE DATABASE \`${REF_DB}\`;"
    sql "${REF_DB}" < "${ROOT}/${schema_file}" || { echo "ERROR: starter SQL failed to load"; return 1; }
    DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" DB_PASS="$DB_PASS" \
        bash "${ROOT}/mb_migrate.sh" --db "${REF_DB}" apply --yes > /tmp/ref_migrate.log 2>&1 \
        || { echo "ERROR: migrations failed against the reference database:"; sed 's/^/    /' /tmp/ref_migrate.log; return 1; }
    return 0
}

cleanup() { sql -e "DROP DATABASE IF EXISTS \`${REF_DB}\`;" 2>/dev/null; }
trap cleanup EXIT

# ---------------------------------------------------------------------------
# --rebaseline
# ---------------------------------------------------------------------------
if [ "$REBASELINE" -eq 1 ]; then
    echo "Regenerating the CI drift baseline from the starter SQL..."
    rm -f "${ROOT}/.github/ci/schema-baseline.tsv"
    DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" DB_PASS="$DB_PASS" \
        DB_LABEL="rebaseline" bash "${ROOT}/.github/ci/check_schema.sh" > /dev/null 2>&1
    if [ -f "${ROOT}/.github/ci/schema-baseline.tsv" ]; then
        echo "Wrote .github/ci/schema-baseline.tsv ($(wc -l < "${ROOT}/.github/ci/schema-baseline.tsv") tables)"
        echo
        echo "Review it before committing:   git diff .github/ci/schema-baseline.tsv"
        echo "Commit it in the SAME pull request as the schema change that caused it."
        echo "Never rebaseline just to make CI green — that turns a real signal into noise."
        exit 0
    fi
    echo "ERROR: baseline was not written"; exit 1
fi

# ---------------------------------------------------------------------------
# compare
# ---------------------------------------------------------------------------
[ -n "$TARGET" ] || { echo "ERROR: --db is required"; echo; usage 2; }
case "$KIND" in
    colony)   SCHEMA_FILE="mousebook_install_schema.sql" ;;
    userbook) SCHEMA_FILE="mousebook_userbook_install_schema.sql" ;;
    *) echo "ERROR: --kind must be 'colony' or 'userbook'"; echo; usage 2 ;;
esac

echo "=============================================================="
echo " Schema compliance check"
echo "   live database : ${TARGET}"
echo "   reference     : ${SCHEMA_FILE} + all migrations"
echo "=============================================================="

if ! sql -N -e "SELECT 1 FROM information_schema.schemata WHERE schema_name='${TARGET}';" | grep -q 1; then
    echo "ERROR: database '${TARGET}' does not exist"; exit 1
fi

echo " building reference..."
build_reference "$SCHEMA_FILE" || exit 1

dump "$TARGET"  | normalise > /tmp/mb_live.sql
dump "$REF_DB"  | normalise > /tmp/mb_ref.sql

echo " comparing structures..."
echo

if diff -u /tmp/mb_ref.sql /tmp/mb_live.sql > /tmp/mb_schema_diff.txt; then
    echo "--------------------------------------------------------------"
    echo " COMPLIANT — ${TARGET} is structurally identical to the schema"
    echo " a fresh install would produce (starter SQL + migrations)."
    echo "--------------------------------------------------------------"
    exit 0
fi

echo "--------------------------------------------------------------"
echo " DIVERGENT — ${TARGET} does not match the reference schema."
echo
echo " '-' is the reference (what a fresh install would have)"
echo " '+' is the live database (what ${TARGET} actually has)"
echo "--------------------------------------------------------------"
sed 's/^/  /' /tmp/mb_schema_diff.txt
echo "--------------------------------------------------------------"
echo
echo " Likely causes, in order of probability:"
echo "   1. Migrations have not been applied to this database:"
echo "        ./mb_migrate.sh --db ${TARGET} status"
echo "   2. The starter SQL has drifted from the migration chain — a schema"
echo "      change was made to one and not the other. Fresh installs and"
echo "      upgraded installs are now DIFFERENT PRODUCTS. Fix this before"
echo "      shipping anything else."
echo "   3. Someone hand-edited this database."
echo
exit 1
