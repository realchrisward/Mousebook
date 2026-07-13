#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# mb_charset_preflight.sh — is it safe to convert this database to utf8mb4?
#
# Read this before running migration 001 against a live colony.
#
# THE HAZARD
# `ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4` converts the *characters*
# in a column, assuming the bytes currently stored there really are in the
# column's declared charset. If an application wrote UTF-8 bytes into a latin1
# column -- which happens whenever the connection charset is not set, and
# Mousebook has never called set_charset() -- then those bytes are NOT latin1,
# and "converting" them mangles them permanently:
#
#     stored:      4D C3BC 6C6C6572     ("Müller", UTF-8 bytes in a latin1 column)
#     after CONVERT TO utf8mb4:
#                  4D C383C2BC 6C6C6572 ("MÃ¼ller")   <-- corrupted, irreversibly
#
# The correct treatment for that case is a binary round-trip (VARBINARY, then
# VARCHAR ... CHARACTER SET utf8mb4), which preserves the bytes and reinterprets
# them.
#
# WHAT THIS SCRIPT DOES
# Scans every character column in every latin1 table and reports whether it
# contains any non-ASCII bytes. If everything is pure ASCII (the common case for
# colony data: line names, ear tags, cage IDs), then latin1, utf8mb3 and utf8mb4
# are byte-identical and the conversion is completely safe.
#
# Usage:
#     DB_HOST=127.0.0.1 DB_USER=root DB_PASS=secret DB_NAME=mycolony \
#         ./mb_charset_preflight.sh
# ---------------------------------------------------------------------------
set -uo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}";      DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-}"
[ -n "$DB_NAME" ] || { echo "ERROR: set DB_NAME"; exit 2; }

if command -v mysql >/dev/null 2>&1;     then CLIENT=mysql
elif command -v mariadb >/dev/null 2>&1; then CLIENT=mariadb
else echo "ERROR: no mysql/mariadb client on PATH"; exit 1; fi

q() { MYSQL_PWD="$DB_PASS" "$CLIENT" --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" -N -e "$1"; }

echo "=============================================================="
echo " utf8mb4 conversion preflight — ${DB_NAME}"
echo "=============================================================="

cols=$(q "SELECT CONCAT(table_name,'|',column_name)
          FROM information_schema.columns
          WHERE table_schema='${DB_NAME}'
            AND character_set_name = 'latin1'
            AND data_type IN ('char','varchar','text','tinytext','mediumtext','longtext');")

if [ -z "$cols" ]; then
    echo " No latin1 character columns. Nothing can be misencoded."
    echo " VERDICT: SAFE — migration 001 may run."
    exit 0
fi

echo " Scanning $(echo "$cols" | wc -l) latin1 column(s) for non-ASCII bytes..."
echo

risky=0
for entry in $cols; do
    t="${entry%%|*}"; c="${entry##*|}"
    # Rows whose bytes are not pure 7-bit ASCII.
    n=$(q "SELECT COUNT(*) FROM \`${t}\` WHERE \`${c}\` REGEXP '[^\\x00-\\x7F]';" 2>/dev/null)
    [ -z "$n" ] && n=0
    if [ "$n" != "0" ]; then
        risky=$((risky + 1))
        echo "  !! ${t}.${c}: ${n} row(s) contain non-ASCII bytes"
        q "SELECT CONCAT('       e.g. ', HEX(\`${c}\`), '  =  ', \`${c}\`)
           FROM \`${t}\` WHERE \`${c}\` REGEXP '[^\\x00-\\x7F]' LIMIT 3;" 2>/dev/null
    fi
done

echo
echo "--------------------------------------------------------------"
if [ "$risky" -eq 0 ]; then
    echo " Every latin1 column is pure ASCII."
    echo " latin1, utf8mb3 and utf8mb4 are byte-identical for ASCII, so"
    echo " CONVERT TO CHARACTER SET cannot alter any stored value."
    echo
    echo " VERDICT: SAFE — migration 001 may run."
    exit 0
fi

echo " ${risky} latin1 column(s) contain non-ASCII bytes."
echo
echo " These bytes must be examined before converting. Look at the HEX above:"
echo "   * If they decode as valid UTF-8 (e.g. C3BC = 'ü'), the app wrote UTF-8"
echo "     into a latin1 column. A plain CONVERT TO would DOUBLE-ENCODE them."
echo "     They need a binary round-trip instead."
echo "   * If they are genuine latin1 (e.g. FC = 'ü' in latin1), CONVERT TO is"
echo "     the correct treatment."
echo
echo " VERDICT: DO NOT RUN migration 001 yet. Bring this output to the"
echo "          migration discussion so the right path is chosen per column."
exit 1
