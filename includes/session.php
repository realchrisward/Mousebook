<?php
// =============================================================
// includes/session.php
// Shared Mousebook session bootstrap (Phase F, Issue: session auth).
//
// Replaces the per-request plaintext-password pattern. Instead of
// every page re-reading $_POST['xpassword'] and re-verifying the
// bcrypt hash on every request (and re-emitting the plaintext
// password into hidden HTML fields), a page:
//
//   1. authenticates ONCE (when credentials are first posted), then
//   2. stores the resolved db-access credentials + access tier in
//      the server-side session, keyed by colony db name, and
//   3. on every later request reads those from $_SESSION.
//
// The user's own password is never stored server-side and never
// re-emitted to the client.
//
// Usage near the top of every page, AFTER config.php + auth.php:
//   require_once __DIR__ . '/../includes/auth.php';    // php/ pages
//   require_once __DIR__ . '/../includes/session.php';
//   $mb = mb_session_bootstrap($config);
//   // then use: $mb['authenticated'], $mb['host'], $mb['accessun'],
//   //           $mb['accesspw'], $mb['dbname'], $mb['tier'], ...
//
// Multi-colony: $_SESSION['mb_dbaccess'] is keyed by db name, so a
// user with several colonies open in separate tabs keeps a distinct
// credential set per colony; each request selects its colony by the
// (non-sensitive) posted 'dbname'.
// =============================================================

// -------------------------------------------------------------
// Legacy-compatible mysqli error mode.
//
// This code base was written for PHP 5.x, where a failed
// `new mysqli(...)` returned an object whose ->connect_error was
// set (rather than throwing). Every page still checks
// `if ($conn->connect_error)`. Under PHP 8's default report mode a
// failed/absent connection throws mysqli_sql_exception, which turns
// an unauthenticated page load into a fatal 500 instead of a login
// screen. Restoring OFF makes mysqli non-throwing again, matching
// how the surrounding error-handling was written and letting cold
// (no-session) loads render the login form gracefully.
// -------------------------------------------------------------
if (!defined('MB_MYSQLI_REPORT_SET')) {
    mysqli_report(MYSQLI_REPORT_OFF);
    define('MB_MYSQLI_REPORT_SET', true);
}


/**
 * Canonical access tiers, most-privileged last.
 *   read-only : may view pages; all data mutations blocked
 *   editor    : read-only + data mutations (animals, cages, litters, ...)
 *   admin     : editor + management pages (roles/lines/strains/alleles)
 *
 * Unknown / empty tier strings normalise to the most restrictive
 * tier ('read-only') so a mis-seeded row can never silently grant
 * write access. Backfill userdbaccess.db_accesstier to one of the
 * three canonical values before relying on tier gates.
 */
function mb_normalize_tier($tier): string {
    $t = strtolower(trim((string)$tier));
    switch ($t) {
        case 'admin':
        case 'administrator':
            return 'admin';
        case 'editor':
        case 'edit':
        case 'read-write':
        case 'readwrite':
        case 'rw':
            return 'editor';
        case 'read-only':
        case 'readonly':
        case 'read only':
        case 'ro':
            return 'read-only';
        default:
            return 'read-only';
    }
}


/**
 * Start the PHP session with hardened cookie parameters.
 * Idempotent: safe to call when a session is already active.
 */
function mb_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['SERVER_PORT'] ?? '') == 443)
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,   // only send the cookie over HTTPS when on HTTPS
    ]);
    session_start();
}


/**
 * Bootstrap authentication for the current request.
 *
 * Returns a normalised array:
 *   authenticated  bool
 *   username       string   ('' if none)
 *   dbname         string
 *   host           ?string  db-access host for the colony connection
 *   accessun       ?string  db-access username
 *   accesspw       ?string  db-access password
 *   tier           ?string  normalised access tier for this colony
 *   loginstatus    'green'|'red'
 *   login_attempt  bool     credentials were posted this request
 *   login_failed   bool     a posted login failed verification
 */
function mb_session_bootstrap(array $config): array {
    mb_session_start();

    $out = [
        'authenticated' => false,
        'username'      => $_SESSION['mb_user'] ?? '',
        'dbname'        => '',
        'host'          => null,
        'accessun'      => null,
        'accesspw'      => null,
        'tier'          => null,
        'loginstatus'   => 'red',
        'login_attempt' => false,
        'login_failed'  => false,
        'subject_plural'=> '',
        'subject_single'=> '',
        'guide1_title'  => '',
        'guide1_url'    => '',
    ];

    // ── Logout: clear the whole session (all colonies) ──────────
    if (isset($_POST['button_disco'])) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $out['username'] = '';
        return $out; // unauthenticated, red
    }

    // Non-sensitive colony selector: forms/links still carry dbname.
    $dbname = (string)($_POST['dbname'] ?? $_GET['dbname'] ?? $_SESSION['mb_current_db'] ?? '');
    $out['dbname'] = $dbname;

    // ── Login path: credentials posted → verify ONCE ────────────
    $u = (string)($_POST['xusername'] ?? '');
    $p = (string)($_POST['xpassword'] ?? '');
    if ($u !== '' && $p !== '') {
        $out['login_attempt'] = true;
        $auth = mb_authenticate($config, $u, $p, $dbname);
        if (!empty($auth['authenticated'])) {
            // New privilege level → rotate the session id (anti-fixation).
            session_regenerate_id(true);
            $_SESSION['mb_user'] = $u;
            $_SESSION['mb_dbaccess'][$dbname] = [
                'host'           => $auth['db_host'],
                'accessun'       => $auth['db_accessun'],
                'accesspw'       => $auth['db_accesspw'],
                'tier'           => mb_normalize_tier($auth['db_accesstier'] ?? ''),
                'subject_plural' => $auth['db_subject_plural'] ?? '',
                'subject_single' => $auth['db_subject_single'] ?? '',
                'guide1_title'   => $auth['db_guide1_title'] ?? '',
                'guide1_url'     => $auth['db_guide1_url'] ?? '',
            ];
            $_SESSION['mb_current_db'] = $dbname;
            $out['username'] = $u;
        } else {
            $out['login_failed'] = true;
        }
    }

    // ── Resolve from session (login just now, or a prior login) ─
    if ($dbname !== '' && isset($_SESSION['mb_dbaccess'][$dbname])) {
        $rec = $_SESSION['mb_dbaccess'][$dbname];
        $_SESSION['mb_current_db'] = $dbname;
        $out['authenticated'] = true;
        $out['username']      = $_SESSION['mb_user'] ?? $u;
        $out['host']          = $rec['host'];
        $out['accessun']      = $rec['accessun'];
        $out['accesspw']      = $rec['accesspw'];
        $out['tier']          = $rec['tier'];
        $out['loginstatus']   = 'green';
        $out['subject_plural']= $rec['subject_plural'] ?? '';
        $out['subject_single']= $rec['subject_single'] ?? '';
        $out['guide1_title']  = $rec['guide1_title'] ?? '';
        $out['guide1_url']    = $rec['guide1_url'] ?? '';
    }

    return $out;
}


// -------------------------------------------------------------
// Tier accessors + gates.
//
// These read the tier that mb_session_bootstrap() stored for the
// current colony (or a named colony). They deliberately fail closed:
// no session / unknown tier ⇒ read-only.
// -------------------------------------------------------------

/** Tier string for the current (or named) colony, or null if none. */
function mb_tier(?string $db = null): ?string {
    $db = $db ?? ($_SESSION['mb_current_db'] ?? '');
    if ($db === '' || !isset($_SESSION['mb_dbaccess'][$db]['tier'])) {
        return null;
    }
    return $_SESSION['mb_dbaccess'][$db]['tier'];
}

/** True if a colony session exists for the current (or named) db. */
function mb_logged_in(?string $db = null): bool {
    $db = $db ?? ($_SESSION['mb_current_db'] ?? '');
    return $db !== '' && isset($_SESSION['mb_dbaccess'][$db]);
}

/** editor or admin. */
function mb_can_write(?string $db = null): bool {
    $t = mb_tier($db);
    return $t === 'editor' || $t === 'admin';
}

/** admin only. */
function mb_can_admin(?string $db = null): bool {
    return mb_tier($db) === 'admin';
}

/** True when the session is read-only (or absent). */
function mb_is_readonly(?string $db = null): bool {
    return !mb_can_write($db);
}

/**
 * Guard a page whose actions require write access. Call at the top of
 * a page (right after bootstrap) BEFORE any mutating action handler.
 * If the current tier can't write, mutating buttons are neutralised so
 * downstream `isset($_POST['button_*'])` handlers skip, and a notice is
 * exposed as $GLOBALS['mb_denied_notice'] for the page to display.
 * Returns true if the request may proceed to mutate.
 */
function mb_guard_write(): bool {
    if (mb_can_write()) {
        return true;
    }
    // Only complain if this request actually tried to mutate.
    $tried = false;
    foreach (array_keys($_POST) as $k) {
        if (strpos($k, 'button_') === 0
            && $k !== 'button_login' && $k !== 'button_disco') {
            unset($_POST[$k]);        // neutralise the action
            $tried = true;
        }
    }
    if ($tried) {
        $GLOBALS['mb_denied_notice'] =
            'Your access level for this database is read-only; that change was not applied.';
    }
    return false;
}

/**
 * Guard a whole page behind admin tier (e.g. management pages).
 * Same neutralise-and-notice contract as mb_guard_write().
 */
function mb_guard_admin(): bool {
    if (mb_can_admin()) {
        return true;
    }
    $tried = false;
    foreach (array_keys($_POST) as $k) {
        if (strpos($k, 'button_') === 0
            && $k !== 'button_login' && $k !== 'button_disco') {
            unset($_POST[$k]);
            $tried = true;
        }
    }
    if ($tried) {
        $GLOBALS['mb_denied_notice'] =
            'This page requires admin access for that action; the change was not applied.';
    }
    return false;
}
