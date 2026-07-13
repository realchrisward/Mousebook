#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# 001_innodb_utf8mb4.sh
#
# Converts every base table to InnoDB and utf8mb4/utf8mb4_unicode_ci.
#
# CONVERGENT BY CONSTRUCTION: it inspects information_schema and only touches
# tables that are not already in the target state. Safe to run against:
#   - a pristine old install (MyISAM, latin1/utf8mb3)
#   - an install where someone hand-applied a partial charset fix
#   - an install that is already fully converted (no-op)
# This is why an unknown starting state is not a problem the operator has to
# solve. Run it and it converges.
#
# ORDERING IS MANDATORY — ENGINE FIRST, THEN CHARSET.
# `table_cages` has a varchar(255) PRIMARY KEY (`cageid`). At 4 bytes/char that
# is a 1020-byte key, over MyISAM's 1000-byte key limit — so converting charset
# while still MyISAM fails with:
#     ERROR 1071: Specified key was too long; max key length is 1000 bytes
# InnoDB's limit is 3072 bytes, so the same conversion succeeds once the engine
# has changed. Do not "optimise" this into a single combined ALTER loop.
# ---------------------------------------------------------------------------
set -uo pipefail

CLIENT="${CLIENT:-mysql}"
q() { "$CLIENT" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" -N -e "$1"; }
x() { "$CLIENT" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" -e "$1"; }

TARGET_COLLATION="utf8mb4_unicode_ci"   # NOT utf8mb4_0900_ai_ci — MySQL-only, breaks MariaDB

# --- phase 0: SAFETY GATE --------------------------------------------------
# CONVERT TO CHARACTER SET assumes the bytes in a column really are in the
# charset the column is declared as. Mousebook has never called set_charset(),
# so a latin1 column may well be holding UTF-8 bytes written through an
# unconfigured connection. Converting those "correctly" mangles them forever:
#
#     4D C3BC 6C6C6572  ("Müller")  ->  4D C383C2BC 6C6C6572  ("MÃ¼ller")
#
# utf8mb3 -> utf8mb4 is always safe (strict superset). Pure-ASCII latin1 is
# always safe (identical bytes in all three charsets). The ONLY danger is a
# latin1 column containing non-ASCII bytes — so that is exactly what we refuse
# to convert blindly.
nonascii=$(q "SELECT COUNT(*) FROM information_schema.columns
              WHERE table_schema=DATABASE()
                AND character_set_name='latin1'
                AND data_type IN ('char','varchar','text','tinytext','mediumtext','longtext');")

if [ "${nonascii:-0}" != "0" ]; then
    risky=0
    for entry in $(q "SELECT CONCAT(table_name,'|',column_name) FROM information_schema.columns
                      WHERE table_schema=DATABASE() AND character_set_name='latin1'
                        AND data_type IN ('char','varchar','text','tinytext','mediumtext','longtext');"); do
        t="${entry%%|*}"; c="${entry##*|}"
        n=$(q "SELECT COUNT(*) FROM \`${t}\` WHERE \`${c}\` REGEXP '[^\x00-\x7F]';" 2>/dev/null)
        [ -n "$n" ] && [ "$n" != "0" ] && { echo "  !! ${t}.${c}: ${n} non-ASCII row(s)"; risky=$((risky+1)); }
    done
    if [ "$risky" -ne 0 ]; then
        echo
        echo "ABORTING: ${risky} latin1 column(s) contain non-ASCII bytes."
        echo "Converting them blindly could double-encode the data irreversibly."
        echo "Run ./mb_charset_preflight.sh for a full report and decide the"
        echo "correct treatment per column before migrating."
        exit 1
    fi
    echo "phase 0 (safety):  latin1 columns are pure ASCII — conversion is byte-safe"
else
    echo "phase 0 (safety):  no latin1 character columns — nothing at risk"
fi

# --- phase 1: engine -------------------------------------------------------
engine_todo=$(q "SELECT table_name FROM information_schema.tables
                 WHERE table_schema=DATABASE() AND table_type='BASE TABLE'
                   AND engine <> 'InnoDB' AND table_name <> 'mb_schema_version';")

if [ -z "$engine_todo" ]; then
    echo "phase 1 (engine):  already InnoDB — nothing to do"
else
    echo "phase 1 (engine):  converting $(echo "$engine_todo" | wc -l) table(s) to InnoDB"
    for t in $engine_todo; do
        x "ALTER TABLE \`$t\` ENGINE=InnoDB;" || { echo "  FAILED on $t"; exit 1; }
        echo "  InnoDB   $t"
    done
fi

# --- phase 2: charset (only after every table is InnoDB) -------------------
charset_todo=$(q "SELECT table_name FROM information_schema.tables
                  WHERE table_schema=DATABASE() AND table_type='BASE TABLE'
                    AND table_collation <> '${TARGET_COLLATION}'
                    AND table_name <> 'mb_schema_version';")

if [ -z "$charset_todo" ]; then
    echo "phase 2 (charset): already ${TARGET_COLLATION} — nothing to do"
else
    echo "phase 2 (charset): converting $(echo "$charset_todo" | wc -l) table(s) to ${TARGET_COLLATION}"
    for t in $charset_todo; do
        x "ALTER TABLE \`$t\` CONVERT TO CHARACTER SET utf8mb4 COLLATE ${TARGET_COLLATION};" \
            || { echo "  FAILED on $t"; exit 1; }
        echo "  utf8mb4  $t"
    done
fi

# --- phase 3: repair truncated index prefixes ------------------------------
# An install where someone hand-applied a charset conversion while the table was
# still MyISAM will have SILENTLY TRUNCATED indexes: MyISAM cannot fit a
# varchar(255) utf8mb4 key in its 1000-byte limit, so it shortened the index to
# a prefix, e.g. KEY `..._idx` (`line`(250)). Converting the engine afterwards
# does NOT restore the full-length index — the truncation is permanent and
# invisible unless you diff the schema.
#
# This matters beyond performance: a prefix index on a UNIQUE constraint enforces
# uniqueness on a TRUNCATED value, which is a different constraint than intended.
#
# The shipped schema declares no prefix indexes anywhere, so the rule is simple:
# any index with a SUB_PART is damage, and is rebuilt at full length.
prefixed=$(q "SELECT DISTINCT CONCAT(table_name,'|',index_name)
              FROM information_schema.statistics
              WHERE table_schema=DATABASE() AND sub_part IS NOT NULL
                AND table_name <> 'mb_schema_version';")

if [ -z "$prefixed" ]; then
    echo "phase 3 (indexes): no truncated index prefixes — nothing to repair"
else
    echo "phase 3 (indexes): repairing $(echo "$prefixed" | wc -l) truncated index(es)"
    for entry in $prefixed; do
        t="${entry%%|*}"; idx="${entry##*|}"
        cols=$(q "SELECT GROUP_CONCAT(CONCAT('\`',column_name,'\`') ORDER BY seq_in_index)
                  FROM information_schema.statistics
                  WHERE table_schema=DATABASE() AND table_name='$t' AND index_name='$idx';")
        nonuniq=$(q "SELECT MAX(non_unique) FROM information_schema.statistics
                     WHERE table_schema=DATABASE() AND table_name='$t' AND index_name='$idx';")
        if [ "$idx" = "PRIMARY" ]; then
            x "ALTER TABLE \`$t\` DROP PRIMARY KEY, ADD PRIMARY KEY (${cols});" \
                || { echo "  FAILED repairing PRIMARY on $t"; exit 1; }
        elif [ "$nonuniq" = "0" ]; then
            x "ALTER TABLE \`$t\` DROP INDEX \`$idx\`, ADD UNIQUE KEY \`$idx\` (${cols});" \
                || { echo "  FAILED repairing $idx on $t"; exit 1; }
        else
            x "ALTER TABLE \`$t\` DROP INDEX \`$idx\`, ADD KEY \`$idx\` (${cols});" \
                || { echo "  FAILED repairing $idx on $t"; exit 1; }
        fi
        echo "  repaired  ${t}.${idx} -> full length (${cols})"
    done
fi

# --- verify ----------------------------------------------------------------
bad=$(q "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema=DATABASE() AND table_type='BASE TABLE'
           AND table_name <> 'mb_schema_version'
           AND (engine <> 'InnoDB' OR table_collation <> '${TARGET_COLLATION}');")
badidx=$(q "SELECT COUNT(*) FROM information_schema.statistics
            WHERE table_schema=DATABASE() AND sub_part IS NOT NULL
              AND table_name <> 'mb_schema_version';")
if [ "$bad" != "0" ]; then
    echo "VERIFY FAILED: ${bad} table(s) still not InnoDB/${TARGET_COLLATION}"
    exit 1
fi
if [ "$badidx" != "0" ]; then
    echo "VERIFY FAILED: ${badidx} index(es) still truncated"
    exit 1
fi
echo "verify: all base tables are InnoDB / ${TARGET_COLLATION}; no truncated indexes"
