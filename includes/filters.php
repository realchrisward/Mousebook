<?php

/**
 * includes/filters.php  — Mousebook shared filter/selector library
 * =========================================================================
 * One place for the dropdown/predicate logic repeated across the managing &
 * viewing pages, so a fix lands once instead of in eight drifting copies.
 *
 * Two modes per control:
 *   FILTER  — narrowing a viewed set: includes an "all" sentinel, options are
 *             the UNION of active pick-list values + values still in use, and
 *             it emits a WHERE fragment.
 *   ASSIGN  — a single pick for data entry: no "all", ACTIVE options only,
 *             emits no predicate.
 *
 * WHERE fragments are all prefixed with " AND " and assume the caller's query
 * opens with an always-true sentinel (e.g. WHERE 1=1  or  WHERE `line`=`line`).
 * That retires the old substr(...,0,-4) trailing-" and " trimming.
 *
 * Cage-attribute filters (LOCATION, ROLE) come in two WHERE shapes:
 *   _join : cage column is reachable in the query (table_cages joined)  -> col = "x"
 *   _sub  : animal-based query with NO table_cages join                 -> currentcage IN (subquery)
 * Use _sub on query_animals / manage_animals (they select FROM table_animals
 * with no cage join); use _join where table_cages is already present.
 *
 * PHP 8, mysqli. Escapes option values on output and filter values on input.
 * Identifier params (table/column) are validated against an allowlist — they
 * are only ever passed internal constants, never user input.
 */

/* ---- configurable bits (confirm these two before rollout) --------------- */

// Cage-type vocabulary. FIRST LETTERS matter (predicate is left(cageid,1)).
// TODO: confirm against  SELECT DISTINCT left(cageid,1) FROM table_cages;
if (!defined('MB_CAGETYPE_OPTIONS')) {
    define(
        'MB_CAGETYPE_OPTIONS',
        serialize(array('Holding', 'Rearrange', 'Mating', 'Experimental', 'Litter', 'Founder', 'Sac'))
    );
}
// Option column name in list_cage_role_assignments.
// TODO: confirm via  DESCRIBE list_cage_role_assignments;
if (!defined('MB_ROLE_OPTION_COL')) {
    define('MB_ROLE_OPTION_COL', 'roleassignment_option');
}

if (!defined('MB_SEX_OPTIONS')) {
    define('MB_SEX_OPTIONS', serialize(array('M', 'F', 'unk')));
}

if (!function_exists('mb_filters_loaded')) {
    function mb_filters_loaded()
    {
        return true;
    }

    /* ---- identifier allowlist ------------------------------------------- */
    function _mb_ident_ok($listTable, $listCol, $cageCol)
    {
        $tables = array(
            'list_cage_locations'        => array('Location_Option' => 'cagelocation_room'),
            'list_cage_role_assignments' => array(MB_ROLE_OPTION_COL => 'cagerole_assignment'),
        );
        return isset($tables[$listTable])
            && isset($tables[$listTable][$listCol])
            && ($cageCol === null || $tables[$listTable][$listCol] === $cageCol);
    }

    /* ---- generic <select> renderer -------------------------------------- */
    /**
     * @param string[] $values  option values (WITHOUT "all")
     * @param bool     $prepend_all  filter mode -> true; assign mode -> false
     */
    function filter_selectbox(
        array $values,
        $selected = 'all',
        $name = 'filter',
        $onchange = 'submitForm()',
        $prepend_all = true,
        $class = 'mediumlistbox',
        $size = 1
    ) {
        $nm = htmlspecialchars($name);
        $html = '<select id="' . $nm . '" name="' . $nm . '" size=' . (int)$size
            . ' class="' . htmlspecialchars($class) . '"'
            . ($onchange !== '' ? ' onchange="' . htmlspecialchars($onchange) . '"' : '') . '>';
        if ($prepend_all) {
            $html .= '<option value="all"'
                . (($selected === 'all' || $selected === '') ? ' selected' : '') . '>all</option>';
        }
        foreach ($values as $v) {
            $vv = htmlspecialchars($v);
            $html .= '<option value="' . $vv . '"' . ($v === $selected ? ' selected' : '')
                . '>' . $vv . '</option>';
        }
        return $html . '</select>';
    }

    /* ---- cage-attribute engine (LOCATION, ROLE) ------------------------- */

    /** FILTER options: active pick-list UNION any value still stamped on a cage. */
    function cage_attr_filter_options(mysqli $conn, $listTable, $listCol, $cageCol)
    {
        if (!_mb_ident_ok($listTable, $listCol, $cageCol)) return array();
        $sql = "SELECT loc FROM (
                    SELECT `$listCol` AS loc FROM `$listTable` WHERE active = 1
                    UNION
                    SELECT DISTINCT `$cageCol` AS loc FROM table_cages
                        WHERE `$cageCol` IS NOT NULL AND `$cageCol` <> ''
                ) t ORDER BY loc;";
        return _mb_col($conn, $sql, 'loc');
    }
    /** ASSIGN options: active pick-list only. */
    function cage_attr_assign_options(mysqli $conn, $listTable, $listCol)
    {
        if (!_mb_ident_ok($listTable, $listCol, null)) return array();
        return _mb_col($conn, "SELECT `$listCol` AS v FROM `$listTable` WHERE active = 1 ORDER BY `$listCol`;", 'v');
    }
    /** RETIRED options (for a restore picker). */
    function cage_attr_retired_options(mysqli $conn, $listTable, $listCol)
    {
        if (!_mb_ident_ok($listTable, $listCol, null)) return array();
        return _mb_col($conn, "SELECT `$listCol` AS v FROM `$listTable` WHERE active = 0 ORDER BY `$listCol`;", 'v');
    }
    /** Set active=0 (retire) or active=1 (restore). */
    function cage_attr_set_active(mysqli $conn, $listTable, $listCol, $value, $active)
    {
        if (!_mb_ident_ok($listTable, $listCol, null)) return false;
        $safe = $conn->real_escape_string($value);
        return $conn->query("UPDATE `$listTable` SET active = " . ((int)$active ? 1 : 0)
            . " WHERE `$listCol` = '$safe';") === true;
    }

    /** WHERE for a page where table_cages is joined in. */
    function cage_attr_where_join(mysqli $conn, $selected, $cageCol)
    {
        if ($selected === null || $selected === '' || $selected === 'all') return '';
        return ' AND `' . $cageCol . '` = "' . $conn->real_escape_string($selected) . '"';
    }
    /** WHERE for an animal-based page with NO cage join (subquery on currentcage). */
    function cage_attr_where_sub(mysqli $conn, $selected, $attrCol)
    {
        if ($selected === null || $selected === '' || $selected === 'all') return '';
        $safe = $conn->real_escape_string($selected);
        return ' AND currentcage IN (SELECT `cageid` FROM `table_cages` WHERE `' . $attrCol . '` = "' . $safe . '")';
    }

    /* ---- LOCATION wrappers ---------------------------------------------- */
    function location_filter_options(mysqli $c)
    {
        return cage_attr_filter_options($c, 'list_cage_locations', 'Location_Option', 'cagelocation_room');
    }
    /**
     * FILTER options for a room-of-live-cages view (issue #22): distinct rooms
     * that currently hold at least one cage containing a live animal
     * (dod IS NULL AND dob IS NOT NULL). Unlike location_filter_options(), this
     * is driven purely by cage occupancy — retired rooms still holding live
     * animals appear; active-but-empty and dead-only rooms do not. No user input,
     * so a plain query is safe.
     */
    function location_liveanimal_options(mysqli $c)
    {
        $sql = "SELECT DISTINCT c.`cagelocation_room` AS loc
                FROM `table_cages` c
                JOIN `table_animals` a ON a.`currentcage` = c.`cageid`
                WHERE a.`dod` IS NULL AND a.`dob` IS NOT NULL
                  AND c.`cagelocation_room` IS NOT NULL AND c.`cagelocation_room` <> ''
                ORDER BY loc;";
        return _mb_col($c, $sql, 'loc');
    }
    function location_assign_options(mysqli $c)
    {
        return cage_attr_assign_options($c, 'list_cage_locations', 'Location_Option');
    }
    function location_retired_options(mysqli $c)
    {
        return cage_attr_retired_options($c, 'list_cage_locations', 'Location_Option');
    }
    function location_retire(mysqli $c, $v)
    {
        return cage_attr_set_active($c, 'list_cage_locations', 'Location_Option', $v, 0);
    }
    function location_restore(mysqli $c, $v)
    {
        return cage_attr_set_active($c, 'list_cage_locations', 'Location_Option', $v, 1);
    }
    function location_where_join(mysqli $c, $sel)
    {
        return cage_attr_where_join($c, $sel, 'cagelocation_room');
    }
    function location_where_sub(mysqli $c, $sel)
    {
        return cage_attr_where_sub($c, $sel, 'cagelocation_room');
    }
    // back-compat with the earlier single-purpose include:
    function location_filter_selectbox(array $o, $sel = 'all', $n = 'location_filter', $oc = 'submitForm()')
    {
        return filter_selectbox($o, $sel, $n, $oc, true);
    }
    function location_filter_where(mysqli $c, $sel, $col = 'cagelocation_room')
    {
        return cage_attr_where_join($c, $sel, $col);
    }

    /* ---- ROLE wrappers (confirm MB_ROLE_OPTION_COL) --------------------- */
    function role_filter_options(mysqli $c)
    {
        return cage_attr_filter_options($c, 'list_cage_role_assignments', MB_ROLE_OPTION_COL, 'cagerole_assignment');
    }
    function role_assign_options(mysqli $c)
    {
        return cage_attr_assign_options($c, 'list_cage_role_assignments', MB_ROLE_OPTION_COL);
    }
    function role_retired_options(mysqli $c)
    {
        return cage_attr_retired_options($c, 'list_cage_role_assignments', MB_ROLE_OPTION_COL);
    }
    function role_retire(mysqli $c, $v)
    {
        return cage_attr_set_active($c, 'list_cage_role_assignments', MB_ROLE_OPTION_COL, $v, 0);
    }
    function role_restore(mysqli $c, $v)
    {
        return cage_attr_set_active($c, 'list_cage_role_assignments', MB_ROLE_OPTION_COL, $v, 1);
    }
    function role_where_join(mysqli $c, $sel)
    {
        return cage_attr_where_join($c, $sel, 'cagerole_assignment');
    }
    function role_where_sub(mysqli $c, $sel)
    {
        return cage_attr_where_sub($c, $sel, 'cagerole_assignment');
    }

    /* ---- LINE ----------------------------------------------------------- */
    /** Lines via the get_lines() proc; drains trailing results so the conn stays usable. */
    function line_filter_options(mysqli $conn)
    {
        $opts = array();
        $res = $conn->query("CALL get_lines();");
        while (($res) && ($row = mysqli_fetch_array($res))) {
            $opts[] = $row['line'];
        }
        if ($res instanceof mysqli_result) {
            $res->free();
        }
        while ($conn->more_results() && $conn->next_result()) {
            if ($r = $conn->store_result()) {
                $r->free();
            }
        }
        return $opts;
    }
    function line_where(mysqli $conn, $selected, $col = 'line')
    {
        if ($selected === null || $selected === '' || $selected === 'all') return '';
        return ' AND `' . $col . '` = "' . $conn->real_escape_string($selected) . '"';
    }

    /* ---- SEX / CAGE TYPE (static vocab) ------------------------------ */
    function sex_options()
    {
        return unserialize(MB_SEX_OPTIONS);
    }
    function cagetype_options()
    {
        return unserialize(MB_CAGETYPE_OPTIONS);
    }
    function sex_where(mysqli $conn, $selected, $col = 'sex')
    {
        if ($selected === null || $selected === '' || $selected === 'all') return '';
        return ' AND `' . $col . '` = "' . $conn->real_escape_string($selected) . '"';
    }
    /** First-letter category match, mirroring existing left(cageid,1) behavior. */
    function cagetype_where(mysqli $conn, $selected, $cageCol = 'currentcage')
    {
        if ($selected === null || $selected === '' || $selected === 'all') return '';
        return ' AND left(`' . $cageCol . '`,1) = left("' . $conn->real_escape_string($selected) . '",1)';
    }

    /* ---- TEXT (REGEXP word-boundary) ------------------------------------ */
    /**
     * MySQL-8/ICU word boundary. Note the doubling: PHP "\\\\b" -> emits \\b ->
     * MySQL string-unescapes to \b -> ICU reads a word boundary. (The old
     * [[:<:]]/[[:>:]] boundaries were removed in MySQL 8.)
     */
    function text_filter_where(mysqli $conn, $value, $col)
    {
        if ($value === null || $value === '') return '';
        return ' AND `' . $col . '` REGEXP "\\\\b' . $conn->real_escape_string($value) . '\\\\b"';
    }

    /* ---- CAGE list (dependent on the other filters) --------------------- */
    /**
     * Distinct current cages, optionally constrained by an already-built WHERE
     * fragment ($whereFrag must be " AND ..."-prefixed or ''). Pages whose base
     * query diverges (extra joins, dob-not-null, custom ORDER) keep their own.
     */
    function cage_filter_options(mysqli $conn, $whereFrag = '', $joinCages = false)
    {
        $join = $joinCages ? ' join table_cages on table_animals.currentcage=table_cages.cageid ' : ' ';
        $sql = "SELECT currentcage FROM table_animals" . $join
            . "WHERE dod is null" . $whereFrag . " GROUP BY currentcage ORDER BY currentcage;";
        return _mb_col($conn, $sql, 'currentcage');
    }

    /* =====================================================================
     * P2 (2b) — centralized animal-filter WHERE builder  [Option B]
     * ---------------------------------------------------------------------
     * The query/manage_animals pages used to assemble a raw SQL WHERE string
     * and round-trip it through a hidden <input> (animals_sql_where_text),
     * then re-interpolate it verbatim on the receiving request — i.e. the
     * client controlled the entire WHERE clause (SQL injection).
     *
     * Under Option B the hidden field carries the individual filter VALUES,
     * and the receiving request rebuilds the WHERE here, server-side, through
     * escaped/allowlisted predicate helpers. Because values (not SQL) travel
     * through the client, the old "single-quoted fragments only" round-trip
     * constraint no longer applies — the double-quoted helpers below are safe.
     *
     * animals_where_build() returns the inner text for `... WHERE <text> ...`,
     * opening with the always-true sentinel 1=1 so every predicate is AND-.
     * ===================================================================== */

    /** Canonical ordered list of round-tripped filter value field names. */
    function animals_filter_fields()
    {
        return array(
            'line_filter', 'sex_filter', 'source_category_selection', 'deadoralive_filter',
            'bornbefore', 'bornafter', 'deadbefore', 'deadafter',
            'linetextfilter', 'idnotextfilter', 'sourcetextfilter', 'parenttextfilter',
            'commenttextfilter', 'location_filter', 'role_filter',
        );
    }

    /** Pull the filter values out of a POST array with safe 'all' sentinels. */
    function animals_filter_values_from_post(array $post)
    {
        $v = array();
        foreach (animals_filter_fields() as $f) {
            $v[$f] = isset($post[$f]) ? (string)$post[$f] : '';
        }
        foreach (array('line_filter', 'sex_filter', 'source_category_selection', 'location_filter', 'role_filter') as $f) {
            if ($v[$f] === '') $v[$f] = 'all';
        }
        return $v;
    }

    /** Emit hidden inputs carrying the VALUES (not SQL) for the client round-trip. */
    function animals_filter_hidden_fields(array $v)
    {
        $html = '';
        foreach (animals_filter_fields() as $f) {
            $val = isset($v[$f]) ? (string)$v[$f] : '';
            $html .= '<input type="hidden" id="' . htmlspecialchars($f, ENT_QUOTES)
                . '" name="' . htmlspecialchars($f, ENT_QUOTES)
                . '" value="' . htmlspecialchars($val, ENT_QUOTES) . '">' . "\n";
        }
        return $html;
    }

    /* ---- discrete safe predicate helpers (all AND-prefixed or '') ------- */

    /** dead/alive allowlist -> `dod` IS [NOT] NULL. */
    function deadoralive_where($sel)
    {
        if ($sel === 'dead')  return ' AND `dod` is not NULL';
        if ($sel === 'alive') return ' AND `dod` is NULL';
        return '';
    }

    /** Date bound predicate, format-validated to YYYY-MM-DD then escaped. */
    function date_bound_where(mysqli $conn, $value, $col, $op)
    {
        if ($value === null || $value === '') return '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return ''; // reject non-dates outright
        if ($op !== '<=' && $op !== '>=') return '';
        return ' AND `' . $col . '` ' . $op . " '" . $conn->real_escape_string($value) . "'";
    }

    /** Line equality (value, escaped). */
    function line_eq_where(mysqli $conn, $sel)
    {
        if ($sel === null || $sel === '' || $sel === 'all') return '';
        return ' AND `line` = "' . $conn->real_escape_string($sel) . '"';
    }

    /** Source-category first-letter match, mirroring left(currentcage,1). */
    function source_category_where(mysqli $conn, $sel)
    {
        if ($sel === null || $sel === '' || $sel === 'all') return '';
        return ' AND left(`currentcage`,1) = left("' . $conn->real_escape_string($sel) . '",1)';
    }

    /** Exact current-cage equality (value, escaped). 'all'/'' -> no predicate. */
    function cage_eq_where(mysqli $conn, $sel, $col = 'currentcage')
    {
        if ($sel === null || $sel === '' || $sel === 'all') return '';
        return ' AND `' . $col . '` = "' . $conn->real_escape_string($sel) . '"';
    }

    /**
     * Build the whole WHERE inner text from a values array, server-side.
     * Semantics mirror the historical fragment order on query_animals /
     * manage_animals. Sex is allowlisted against MB_SEX_OPTIONS; location/role
     * use the subquery (no-cage-join) form; text filters use REGEXP word-
     * boundary; dates are validated. Returns e.g. `1=1 AND ...`.
     */
    function animals_where_build(mysqli $conn, array $v)
    {
        $w = '1=1';
        $w .= line_eq_where($conn, $v['line_filter'] ?? 'all');
        $sex = $v['sex_filter'] ?? 'all';
        if ($sex !== 'all' && $sex !== '' && in_array($sex, sex_options(), true)) {
            $w .= ' AND `sex` = "' . $conn->real_escape_string($sex) . '"';
        }
        $w .= source_category_where($conn, $v['source_category_selection'] ?? 'all');
        $w .= deadoralive_where($v['deadoralive_filter'] ?? '');
        $w .= date_bound_where($conn, $v['bornbefore'] ?? '', 'dob', '<=');
        $w .= date_bound_where($conn, $v['bornafter']  ?? '', 'dob', '>=');
        $w .= date_bound_where($conn, $v['deadbefore'] ?? '', 'dod', '<=');
        $w .= date_bound_where($conn, $v['deadafter']  ?? '', 'dod', '>=');
        $w .= text_filter_where($conn, $v['linetextfilter']   ?? '', 'line');
        $w .= text_filter_where($conn, $v['idnotextfilter']   ?? '', 'idno');
        $w .= text_filter_where($conn, $v['sourcetextfilter'] ?? '', 'matingcage');
        $w .= text_filter_where($conn, $v['parenttextfilter'] ?? '', 'parents');
        $w .= location_where_sub($conn, $v['location_filter'] ?? 'all');
        $w .= role_where_sub($conn, $v['role_filter'] ?? 'all');
        return $w;
    }

    /** Escaped value for the comment REGEXP subquery (feature: user regex). */
    function comment_regexp_escaped(mysqli $conn, $value)
    {
        return $conn->real_escape_string((string)$value);
    }

    /**
     * Genotype OR-predicate builder for the get_genofilt path. Values arrive as
     * geno$i (allelegroup) + genofilt$i[] (selected alleles) — already values,
     * so this just escapes and composes. Returns array(where_or_text, group_ct).
     */
    function genotype_or_where(mysqli $conn, array $agarray, array $gfarray)
    {
        $ors = array();
        $groups = 0;
        foreach ($agarray as $i => $ag) {
            $sel = isset($gfarray[$i]) ? $gfarray[$i] : array();
            if (!is_array($sel)) $sel = ($sel === '' ? array() : array($sel));
            if (count($sel) > 0) $groups++;
            foreach ($sel as $al) {
                $ors[] = '(allelegroup="' . $conn->real_escape_string($ag)
                    . '" and allele="' . $conn->real_escape_string($al) . '")';
            }
        }
        return array(implode(' or ', $ors), $groups);
    }

    /**
     * Sanitize a client-supplied "(1),(2),(3)" VALUES list down to integers only.
     * Used for the temp_cage batch-staging INSERTs, where animalautono is strictly
     * integer. Returns '' when no integer survives (caller should skip the query).
     */
    function mb_int_values_list($raw)
    {
        preg_match_all('/\d+/', (string)$raw, $m);
        if (empty($m[0])) return '';
        $ids = array_map('intval', $m[0]);
        return implode(',', array_map(function ($x) { return '(' . $x . ')'; }, $ids));
    }

    /* ---- tiny internal helper ------------------------------------------- */
    function _mb_col(mysqli $conn, $sql, $key)
    {
        $out = array();
        $res = $conn->query($sql);
        while (($res) && ($row = mysqli_fetch_array($res))) {
            $out[] = $row[$key];
        }
        return $out;
    }

    /* =====================================================================
     * P3 (#8 V1, Option A): session-keyed cage staging.
     *
     * Replaces the shared global temp_cage1..4 (MyISAM) tables — which had
     * no user/session column and so collided between concurrent users —
     * with per-session, per-colony integer lists:
     *
     *     $_SESSION['mb_stage'][$dbname][1..4]   (arrays of positive ints)
     *
     * These live entirely in the PHP session and die with it: an
     * uncommitted staging set does not survive logout, which is the
     * intended isolation win. Commit reads the slot as a `WHERE
     * animalautono IN (...)`; the available lists exclude the union with
     * `NOT IN (...)`. Because every value is an int by construction, these
     * fragments are injection-safe without escaping.
     *
     * NOTE: the legacy temp_cage1..4 tables and their helper procedures
     * (clear_cages1234, get_cage1..4) have been dropped from the install
     * schema (Track 0 T0.4). For colony DBs created before that sweep,
     * run migration_drop_temp_cages.sql to remove the orphaned objects.
     * ===================================================================== */

    /** Return the staged int list for one destination cage (1..4), or []. */
    function mb_stage_slot($dbname, $n)
    {
        $n = (int)$n;
        if ($n < 1 || $n > 4) return array();
        if (!isset($_SESSION['mb_stage'][$dbname][$n]) || !is_array($_SESSION['mb_stage'][$dbname][$n])) {
            return array();
        }
        return $_SESSION['mb_stage'][$dbname][$n];
    }

    /**
     * Coerce client input (multi-select array, scalar, or a delimited /
     * "(1),(2)" string) to de-duplicated positive ints only. Any non-digit
     * content (injection payloads included) collapses to its digits.
     */
    function mb_stage_normalize_ints($raw)
    {
        $seen = array();
        if (is_array($raw)) {
            foreach ($raw as $v) {
                $i = (int)$v;
                if ($i > 0) $seen[$i] = true;
            }
        } else {
            preg_match_all('/\d+/', (string)$raw, $m);
            foreach ($m[0] as $v) {
                $i = (int)$v;
                if ($i > 0) $seen[$i] = true;
            }
        }
        return array_keys($seen);
    }

    /** Merge input ints into destination cage $n (de-duplicated). */
    function mb_stage_add($dbname, $n, $raw)
    {
        $n = (int)$n;
        if ($n < 1 || $n > 4) return;
        $ints = mb_stage_normalize_ints($raw);
        if (empty($ints)) return;
        $merged = array();
        foreach (array_merge(mb_stage_slot($dbname, $n), $ints) as $i) {
            $merged[(int)$i] = true;
        }
        $_SESSION['mb_stage'][$dbname][$n] = array_keys($merged);
    }

    /** Drop input ints from destination cage $n. */
    function mb_stage_remove($dbname, $n, $raw)
    {
        $n = (int)$n;
        if ($n < 1 || $n > 4) return;
        $drop = array();
        foreach (mb_stage_normalize_ints($raw) as $i) $drop[(int)$i] = true;
        if (empty($drop)) return;
        $kept = array();
        foreach (mb_stage_slot($dbname, $n) as $i) {
            if (!isset($drop[(int)$i])) $kept[] = (int)$i;
        }
        $_SESSION['mb_stage'][$dbname][$n] = $kept;
    }

    /** Empty one destination cage. */
    function mb_stage_clear($dbname, $n)
    {
        $n = (int)$n;
        if ($n < 1 || $n > 4) return;
        $_SESSION['mb_stage'][$dbname][$n] = array();
    }

    /** Empty all four destination cages for this colony. */
    function mb_stage_clear_all($dbname)
    {
        $_SESSION['mb_stage'][$dbname] = array(1 => array(), 2 => array(), 3 => array(), 4 => array());
    }

    /** Union of all four staged slots (for the NOT IN exclusion). */
    function mb_stage_union($dbname)
    {
        $u = array();
        for ($n = 1; $n <= 4; $n++) {
            foreach (mb_stage_slot($dbname, $n) as $i) $u[(int)$i] = true;
        }
        return array_keys($u);
    }

    /**
     * Render an int list as a SQL IN(...) body ("1,2,3"), or '' when empty.
     * Values are re-cast to int here so the result is injection-safe even if
     * a caller passes an unvalidated array. Callers MUST treat '' as "omit
     * the predicate" — MySQL rejects an empty `IN ()`.
     */
    function mb_stage_in_csv(array $ints)
    {
        $clean = array();
        foreach ($ints as $i) {
            $ci = (int)$i;
            if ($ci > 0) $clean[] = $ci;
        }
        return implode(',', $clean);
    }
}
