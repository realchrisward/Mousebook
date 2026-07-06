<?php
/**
 * Shared left-sidebar navigation (Issue #11).
 *
 * Single source of truth for the Mousebook sidebar. Replaces the per-page
 * copy-pasted <div id="left_navmenu"> blocks, which had drifted to between
 * 4 and 13 links per page (and carried stray/mis-nested </form> tags).
 *
 * Usage from a page in php/ (where $xusername / $xpassword are already set):
 *     <?php require_once __DIR__ . '/../includes/nav.php';
 *           mb_render_nav($xusername, $xpassword, $_POST['dbname'] ?? ''); ?>
 *
 * Usage from index.php at the repo root (note the './' base path):
 *     <?php require_once __DIR__ . '/includes/nav.php';
 *           mb_render_nav($xusername, $xpassword, $_POST['dbname'] ?? '', null, './'); ?>
 *
 * The active page is auto-detected from the running script name, so pages
 * don't need to pass anything extra. Each destination still opens in a new
 * tab and still POSTs credentials, preserving today's login mechanism until
 * session-based auth lands (Phase F).
 *
 * To change the menu, edit the $MB_NAV_ITEMS array below — nothing else.
 */

if (!function_exists('mb_render_nav')) {

    /**
     * Canonical menu. One entry per line: 'script.php' => 'Label'.
     * 'index' is the Home button (rendered in place, not a new tab).
     * Comment a line out to drop it from every page at once.
     */
    $GLOBALS['MB_NAV_ITEMS'] = [
        'index.php'                    => 'Home',
        'manage_alleles.php'           => 'Manage Alleles',
        'manage_strains.php'           => 'Manage Strains',
        'manage_lines.php'             => 'Manage Lines',
        'manage_roles.php'             => 'Manage Roles',
        'add_animals.php'              => 'Add animals',
        'record_dead_pups.php'         => 'Record Dead Pups',
        'litterlogger.php'             => 'Litter Logger',
        'manage_animals.php'           => 'Manage animals',
        'manage_cages.php'             => 'Manage Cages',
        'cage_location.php'            => 'Cage Location Manager',
        'cagerole.php'                 => 'Cage Role Manager',
        'cagecard_printer.php'         => 'Card Printer',
        'query_genotodo.php'           => 'Plan Genotyping',
        'query_viewer.php'             => 'View Database Queries',
        'query_animals.php'            => 'View animals',
        // Intentionally NOT in the global menu (see BACKUP/HANDOFF notes):
        //   cagecard_gen5rs.php / cagecard_gen5rs-blindgeno.php  (reached from Card Printer)
        //   autoclipsheet.php                                    (orphan; feeds Phase E clipping-logs PDF)
    ];

    /**
     * Render the sidebar.
     *
     * @param string $xusername  current username (from page scope)
     * @param string $xpassword  current password (from page scope)
     * @param string $dbname     current db name
     * @param string|null $active  script basename to mark active;
     *                              null = auto-detect from SCRIPT_NAME
     * @param string $base   URL prefix pointing at the repo root from the
     *                        calling page. Pages in php/ are one level deep,
     *                        so the default '../' is correct for them and
     *                        they need pass nothing. index.php sits AT the
     *                        repo root, so it passes './'.
     */
    function mb_render_nav($xusername, $xpassword, $dbname, $active = null, $base = '../')
    {
        if ($active === null) {
            $active = basename($_SERVER['SCRIPT_NAME'] ?? '');
        }
        // Normalise: guarantee exactly one trailing slash.
        $base = rtrim($base, '/') . '/';
        $u  = htmlspecialchars($xusername, ENT_QUOTES);
        $p  = htmlspecialchars($xpassword, ENT_QUOTES);
        $db = htmlspecialchars($dbname, ENT_QUOTES);

        echo '<div id="left_navmenu">' . "\n";
        foreach ($GLOBALS['MB_NAV_ITEMS'] as $script => $label) {
            $is_home   = ($script === 'index.php');
            $action    = $is_home ? $base . 'index.php' : $base . 'php/' . $script;
            $is_active = ($script === $active);

            // Home navigates in place; other destinations open in a new tab (today's behavior).
            $target = $is_home ? '' : ' target="_blank"';

            // Active page and Home get distinct styling; keep Home's original color.
            if ($is_home) {
                $style = ' style="background-color:#217190; color:lightgrey;"';
            } elseif ($is_active) {
                $style = ' style="background-color:#2d8fb3; color:white; font-weight:bold;"';
            } else {
                $style = '';
            }

            echo '  <form action="' . $action . '" method=post' . $target . '>' . "\n";
            echo '    <input type=hidden name="xusername" value="' . $u . '" />' . "\n";
            echo '    <input type=hidden name="xpassword" value="' . $p . '" />' . "\n";
            echo '    <input type=hidden name="dbname" value="' . $db . '" />' . "\n";
            echo '    <input type=hidden name="button_login" value="connect" />' . "\n";
            echo '    <input type=submit class="button" name=""' . $style . ' value="' . htmlspecialchars($label, ENT_QUOTES) . '" />' . "\n";
            if ($is_home) {
                echo '    <br>' . "\n";
            }
            echo '  </form>' . "\n";
        }
        echo '</div>' . "\n";
    }
}
