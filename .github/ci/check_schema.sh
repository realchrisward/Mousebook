#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# check_schema.sh — load both install schemas into a live server and verify them.
#
# Runs three checks:
#   1. LOAD        - both schemas import with zero errors, into a database whose
#                    name is NOT the default (proves the installer's "you name
#                    the database" property from M1-H still holds).
#   2. INVENTORY   - the expected number of tables / views / procedures exist.
#                    Catches a statement that silently failed to create an object.
#   3. RATCHET     - every table's ENGINE and CHARSET matches
#                    .github/ci/schema-baseline.tsv exactly.
#                    This does NOT assert the schema is *correct* — today's
#                    baseline records latin1/utf8mb3/MyISAM, which is what the
#                    schema actually ships. It asserts the schema has not
#                    *drifted*. When a migration deliberately changes engines or
#                    charsets, the baseline is regenerated in the same PR and the
#                    diff is reviewable. See docs/CI.md.
#
# In CI: called by .github/workflows/ci.yml (job: schema), once per engine.
# Locally:
#     DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=root DB_PASS=secret \
#         bash .github/ci/check_schema.sh
#
# Exit 0 = all three checks passed. Exit 1 = at least one failed; the reason is
# printed in full, and nothing is left behind (the CI databases are dropped).
# ---------------------------------------------------------------------------
set -uo pipefail

cd "$(git rev-parse --show-toplevel 2>/dev/null || echo .)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_LABEL="${DB_LABEL:-local}"

BASELINE=".github/ci/schema-baseline.tsv"

# Deliberately NOT "animalbook" / "userbook": the schema must not depend on the
# database being called anything in particular.
COLONY_DB="ci_colony_check"
USERBOOK_DB="ci_userbook_check"

# The mysql client works against MariaDB too; prefer it, fall back to mariadb.
if command -v mysql >/dev/null 2>&1;   then CLIENT=mysql
elif command -v mariadb >/dev/null 2>&1; then CLIENT=mariadb
else echo "ERROR: no mysql/mariadb client found on PATH"; exit 1
fi

db() { "$CLIENT" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$@" 2>&1; }

fail() { echo; echo "::error::$*"; echo "FAILED: $*"; FAILURES=$((FAILURES + 1)); }

FAILURES=0

echo "=================================================="
echo " Mousebook schema check — ${DB_LABEL}"
echo "=================================================="
db -N -e "SELECT VERSION();" | sed 's/^/ server: /'
echo

# ---------------------------------------------------------------------------
# 1. LOAD
# ---------------------------------------------------------------------------
echo "--- [1/3] Loading schemas into non-default database names ---"
db -e "DROP DATABASE IF EXISTS \`${COLONY_DB}\`;   CREATE DATABASE \`${COLONY_DB}\`;"   >/dev/null
db -e "DROP DATABASE IF EXISTS \`${USERBOOK_DB}\`; CREATE DATABASE \`${USERBOOK_DB}\`;" >/dev/null

if out=$(db "${COLONY_DB}" < mousebook_install_schema.sql); then
    echo "  OK   colony schema   -> ${COLONY_DB}"
else
    fail "colony schema failed to load into ${COLONY_DB}"
    echo "$out" | head -20
fi

if out=$(db "${USERBOOK_DB}" < mousebook_userbook_install_schema.sql); then
    echo "  OK   userbook schema -> ${USERBOOK_DB}"
else
    fail "userbook schema failed to load into ${USERBOOK_DB}"
    echo "$out" | head -20
fi

# ---------------------------------------------------------------------------
# 2. INVENTORY
# ---------------------------------------------------------------------------
echo
echo "--- [2/3] Object inventory ---"

count() { db -N -e "$1" | tr -d '[:space:]'; }

EXPECT_COLONY_TABLES=37
EXPECT_COLONY_VIEWS=13
EXPECT_COLONY_PROCS=10
EXPECT_USERBOOK_TABLES=5

check_count() {  # name expected actual
    if [ "$2" = "$3" ]; then
        printf "  OK   %-24s %s\n" "$1" "$3"
    else
        printf "  FAIL %-24s expected %s, found %s\n" "$1" "$2" "$3"
        fail "$1: expected $2, found $3"
    fi
}

check_count "colony tables"   "$EXPECT_COLONY_TABLES" \
  "$(count "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${COLONY_DB}' AND table_type='BASE TABLE';")"
check_count "colony views"    "$EXPECT_COLONY_VIEWS" \
  "$(count "SELECT COUNT(*) FROM information_schema.views WHERE table_schema='${COLONY_DB}';")"
check_count "colony procedures" "$EXPECT_COLONY_PROCS" \
  "$(count "SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema='${COLONY_DB}' AND routine_type='PROCEDURE';")"
check_count "userbook tables" "$EXPECT_USERBOOK_TABLES" \
  "$(count "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${USERBOOK_DB}' AND table_type='BASE TABLE';")"

# ---------------------------------------------------------------------------
# 3. RATCHET — engine + charset per table, against the checked-in baseline
# ---------------------------------------------------------------------------
echo
echo "--- [3/3] Engine + charset ratchet (vs ${BASELINE}) ---"

# MariaDB and MySQL have historically spelled the 3-byte UTF-8 charset both
# "utf8" and "utf8mb3". Normalise so the baseline is engine-independent.
snapshot() {
    {
        db -N -e "SELECT 'colony', t.table_name, t.engine,
                         SUBSTRING_INDEX(t.table_collation, '_', 1)
                  FROM information_schema.tables t
                  WHERE t.table_schema='${COLONY_DB}' AND t.table_type='BASE TABLE'
                  ORDER BY t.table_name;"
        db -N -e "SELECT 'userbook', t.table_name, t.engine,
                         SUBSTRING_INDEX(t.table_collation, '_', 1)
                  FROM information_schema.tables t
                  WHERE t.table_schema='${USERBOOK_DB}' AND t.table_type='BASE TABLE'
                  ORDER BY t.table_name;"
    } | sed 's/\butf8\b/utf8mb3/g'
}

snapshot > /tmp/mb_schema_actual.tsv

if [ ! -f "$BASELINE" ]; then
    echo "  no baseline found — writing one from this run:"
    cp /tmp/mb_schema_actual.tsv "$BASELINE"
    cat "$BASELINE"
    echo "  (commit ${BASELINE} to enable drift detection)"
else
    if diff -u "$BASELINE" /tmp/mb_schema_actual.tsv > /tmp/mb_schema_diff.txt; then
        echo "  OK   $(wc -l < "$BASELINE") table(s) match the baseline exactly"
    else
        fail "schema drifted from ${BASELINE}"
        echo
        echo "  Lines starting '-' are the baseline; '+' is what this run produced."
        sed 's/^/  /' /tmp/mb_schema_diff.txt
        echo
        echo "  If this drift is INTENTIONAL (e.g. the InnoDB migration), regenerate"
        echo "  the baseline and commit it in the SAME pull request, so the engine/"
        echo "  charset change is visible in review:"
        echo
        echo "      bash .github/ci/regen_baseline.sh"
        echo
        echo "  If it is NOT intentional, a schema edit changed an engine or charset"
        echo "  by accident. That is what this check exists to catch."
    fi
fi

# ---------------------------------------------------------------------------
db -e "DROP DATABASE IF EXISTS \`${COLONY_DB}\`; DROP DATABASE IF EXISTS \`${USERBOOK_DB}\`;" >/dev/null

echo
echo "=================================================="
if [ "$FAILURES" -eq 0 ]; then
    echo " PASS — ${DB_LABEL}: schemas load, inventory correct, no drift"
    echo "=================================================="
    exit 0
fi
echo " FAIL — ${DB_LABEL}: ${FAILURES} check(s) failed (see above)"
echo "=================================================="
exit 1
