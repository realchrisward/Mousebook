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
    define('MB_ROLE_OPTION_COL', 'Role_Option');
}

if (!defined('MB_GENDER_OPTIONS')) {
    define('MB_GENDER_OPTIONS', serialize(array('M', 'F', 'unk')));
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
        return ' AND ' . $cageCol . ' = "' . $conn->real_escape_string($selected) . '"';
    }
    /** WHERE for an animal-based page with NO cage join (subquery on currentcage). */
    function cage_attr_where_sub(mysqli $conn, $selected, $attrCol)
    {
        if ($selected === null || $selected === '' || $selected === 'all') return '';
        $safe = $conn->real_escape_string($selected);
        return ' AND currentcage IN (SELECT cageid FROM table_cages WHERE ' . $attrCol . ' = "' . $safe . '")';
    }

    /* ---- LOCATION wrappers ---------------------------------------------- */
    function location_filter_options(mysqli $c)
    {
        return cage_attr_filter_options($c, 'list_cage_locations', 'Location_Option', 'cagelocation_room');
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

    /* ---- GENDER / CAGE TYPE (static vocab) ------------------------------ */
    function gender_options()
    {
        return unserialize(MB_GENDER_OPTIONS);
    }
    function cagetype_options()
    {
        return unserialize(MB_CAGETYPE_OPTIONS);
    }
    function gender_where(mysqli $conn, $selected, $col = 'gender')
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
}
