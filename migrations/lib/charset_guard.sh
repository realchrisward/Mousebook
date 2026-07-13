#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# migrations/lib/charset_guard.sh
#
# ONE implementation of the utf8mb4 safety check, shared by:
#   * mb_migrate.sh preflight   (the operator-facing report)
#   * migrations/001_...        (the abort-before-touching-anything gate)
#
# It lives in one file precisely because two copies of a safety check will drift
# apart, and the copy that drifts is the one guarding your data.
#
# THE HAZARD
# `CONVERT TO CHARACTER SET utf8mb4` converts the CHARACTERS in a column,
# trusting that the bytes stored there really are in the column's declared
# charset. Mousebook has never called set_charset(), so a latin1 column may hold
# UTF-8 bytes written through an unconfigured connection. "Converting" those
# mangles them permanently:
#
#     latin1 column holds:  4D C3BC 6C6C6572        "Müller"  (UTF-8 bytes)
#     after CONVERT TO:     4D C383C2BC 6C6C6572    "MÃ¼ller"  <- irreversible
#
# The danger is narrow:
#   * utf8mb3 -> utf8mb4          : ALWAYS safe (strict superset).
#   * latin1 holding only ASCII   : ALWAYS safe (byte-identical in all three).
#   * latin1 holding NON-ASCII    : THE ONLY HAZARD.
#
# So we scan exactly that: latin1 character columns containing non-ASCII bytes.
# ---------------------------------------------------------------------------

# Requires in the environment: CLIENT, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
_cg_q() {
    MYSQL_PWD="$DB_PASS" "$CLIENT" --protocol=TCP -h "$DB_HOST" -P "$DB_PORT" \
        -u "$DB_USER" "$DB_NAME" -N -e "$1" 2>/dev/null
}

# Echoes "table|column|count" for every latin1 column holding non-ASCII bytes.
# Empty output = safe to convert.
cg_scan() {
    local cols entry t c n
    cols=$(_cg_q "SELECT CONCAT(table_name,'|',column_name)
                  FROM information_schema.columns
                  WHERE table_schema='${DB_NAME}'
                    AND character_set_name='latin1'
                    AND data_type IN ('char','varchar','text','tinytext','mediumtext','longtext');")
    [ -z "$cols" ] && return 0
    for entry in $cols; do
        t="${entry%%|*}"; c="${entry##*|}"
        n=$(_cg_q "SELECT COUNT(*) FROM \`${t}\` WHERE \`${c}\` REGEXP '[^\x00-\x7F]';")
        [ -n "$n" ] && [ "$n" != "0" ] && echo "${t}|${c}|${n}"
    done
    return 0
}

# Sample offending values as hex, so the caller can tell double-encoded UTF-8
# (C3BC) from genuine latin1 (FC).
cg_samples() {
    local t="$1" c="$2"
    _cg_q "SELECT CONCAT('       ', HEX(\`${c}\`), '  =  ', \`${c}\`)
           FROM \`${t}\` WHERE \`${c}\` REGEXP '[^\x00-\x7F]' LIMIT 3;"
}

# Human-readable report. Returns 0 if safe, 1 if not.
cg_report() {
    local findings n_latin1
    n_latin1=$(_cg_q "SELECT COUNT(*) FROM information_schema.columns
                      WHERE table_schema='${DB_NAME}' AND character_set_name='latin1'
                        AND data_type IN ('char','varchar','text','tinytext','mediumtext','longtext');")

    if [ "${n_latin1:-0}" = "0" ]; then
        echo "  no latin1 character columns — nothing can be misencoded"
        echo "  VERDICT: SAFE"
        return 0
    fi

    echo "  scanning ${n_latin1} latin1 column(s) for non-ASCII bytes..."
    findings="$(cg_scan)"

    if [ -z "$findings" ]; then
        echo "  every latin1 column is pure ASCII"
        echo "  latin1 / utf8mb3 / utf8mb4 are byte-identical for ASCII, so the"
        echo "  conversion cannot alter a single stored value."
        echo "  VERDICT: SAFE"
        return 0
    fi

    echo
    local entry t c n
    for entry in $findings; do
        t="${entry%%|*}"; n="${entry##*|}"; c="${entry#*|}"; c="${c%%|*}"
        echo "  !! ${t}.${c}: ${n} row(s) contain non-ASCII bytes"
        cg_samples "$t" "$c"
    done
    echo
    echo "  Inspect the hex above:"
    echo "    * valid UTF-8 (e.g. C3BC = 'ü') -> the app wrote UTF-8 into a latin1"
    echo "      column. A plain CONVERT TO would DOUBLE-ENCODE it. These columns"
    echo "      need a binary round-trip (VARBINARY -> VARCHAR utf8mb4) instead."
    echo "    * genuine latin1 (e.g. FC = 'ü')  -> CONVERT TO is correct."
    echo
    echo "  VERDICT: UNSAFE — do not convert until each column above is triaged."
    return 1
}
