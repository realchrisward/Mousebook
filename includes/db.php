<?php
/**
 * includes/db.php — the one place Mousebook opens a database connection.
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * Before B-2, `new mysqli(...)` was constructed in 140 places across 24 files.
 * That is not a style problem, it is a correctness problem: anything that must
 * be true of EVERY connection had nowhere to live. Three things must be:
 *
 *   1. set_charset('utf8mb4')  — there were ZERO calls to it in the entire
 *      repository. The columns are utf8mb4 (migration 001 / B-1b), but a
 *      connection that never announces its charset talks to the server in the
 *      server's default. Every byte of non-ASCII text — a strain called
 *      "Müller", a comment with a µ or an °C — is then interpreted in the
 *      wrong charset on the way in AND on the way out. That is mojibake, and
 *      it is silent, and it corrupts on write, so it is not fixable after the
 *      fact by "reading it differently". THIS is the reason the file exists.
 *
 *   2. Error reporting mode, chosen deliberately rather than inherited.
 *
 *   3. A per-request identity (actor + request id) for the audit trail — one
 *      request that touches five tables must produce audit rows that can be
 *      grouped back into one event. Track D depends on it; it is cheap to
 *      establish here and impossible to reconstruct later.
 *
 * The helper deliberately mirrors the mysqli constructor's argument order, so
 * that `new mysqli($host, $user, $pass, $db)` becomes `mb_connect(...)` with no
 * other change to the call site, and every existing `if ($conn->connect_error)`
 * check keeps working exactly as it did.
 */

// ---------------------------------------------------------------------------
// mb_connect() — open a connection, correctly configured.
//
// RETURNS A mysqli OBJECT EVEN ON FAILURE, with ->connect_errno / ->connect_error
// populated. This is not laziness; it is a hard compatibility requirement.
// Every page in the app is written as:
//
//     $conn = new mysqli(...);
//     if ($conn->connect_error) { ...show "please connect to the database"... }
//
// Returning null or throwing would turn each of those graceful branches into a
// fatal on a null-object call — a white screen instead of a message, on the one
// code path whose whole job is to handle a database being unreachable.
//
// Under PHP 8.1+ mysqli's default error mode THROWS on a failed connect, which
// would bypass those checks. includes/session.php already calls
// mysqli_report(MYSQLI_REPORT_OFF) globally, which is why they work today — but
// that is an action-at-a-distance dependency, and not every entry point loads
// session.php. So we set the mode around the connect ourselves and restore it,
// leaving each caller's query-error behaviour exactly as it was.
// ---------------------------------------------------------------------------
function mb_connect($host, $user, $pass, $db, $port = null)
{
    $prev = mysqli_report(MYSQLI_REPORT_OFF);

    $conn = mysqli_init();
    if ($conn === false) {
        mysqli_report($prev);
        // mysqli_init() only fails if the extension itself is broken. There is
        // no object to hand back, and no useful lie to tell about it.
        throw new RuntimeException('mysqli_init() failed - is the mysqli extension loaded?');
    }

    $ok = @$conn->real_connect(
        (string)$host,
        (string)$user,
        (string)$pass,
        (string)$db,
        ($port === null || $port === '') ? null : (int)$port
    );

    mysqli_report($prev);

    if (!$ok || $conn->connect_errno) {
        // Callers check ->connect_error. Leave it to them: a connection failure
        // is a normal, expected state for this app (a user with no database
        // access, a colony that has been renamed), not an exception.
        return $conn;
    }

    // ---- the entire point of this function ---------------------------------
    // set_charset() sets the client, connection AND results charsets together.
    // "SET NAMES utf8mb4" is NOT equivalent: it leaves mysqli's internal idea of
    // the charset stale, which breaks real_escape_string() for multibyte input.
    if (!@$conn->set_charset('utf8mb4')) {
        // Only reachable on a server so old it has no utf8mb4 (pre-5.5.3), which
        // Mousebook does not support. Don't kill the request over it -- but do
        // not fail silently either, because every non-ASCII value written from
        // here on is suspect.
        error_log('mousebook: set_charset(utf8mb4) failed on ' . $conn->host_info
                  . ' - text may be stored incorrectly. Server charset: '
                  . $conn->character_set_name());
    }

    return $conn;
}

// ---------------------------------------------------------------------------
// mb_debug_init() — the debug_mode block, which was copy-pasted into ~20 files.
//
// Worth stating plainly what this switch does, since it is a one-word edit in
// config.php: it turns PHP warnings and raw SQL into page output, for everyone,
// including anyone who is not logged in. It belongs on a developer's laptop and
// nowhere else. Centralising it means the day we decide it must also require a
// logged-in admin, that is one edit rather than twenty.
// ---------------------------------------------------------------------------
function mb_debug_init(array $config, $announce = false)
{
    if (($config['debug_mode'] ?? 'False') !== 'True') {
        return false;
    }
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    if ($announce) {
        echo 'DEBUGGING ENABLED';
    }
    return true;
}

// ---------------------------------------------------------------------------
// Per-request identity, for the audit trail (Track D).
//
// mb_request_id() is stable for the life of one HTTP request: every row written
// by this request carries it, so "who changed these six fields" is one query
// rather than a timestamp-window guess. 16 raw bytes, sized for the BINARY(16)
// column in the audit schema; rendered as hex when a string is wanted.
// ---------------------------------------------------------------------------
function mb_request_id($as_hex = false)
{
    static $rid = null;
    if ($rid === null) {
        $rid = random_bytes(16);
    }
    return $as_hex ? bin2hex($rid) : $rid;
}

// The acting user, as the audit trail will record them. Session first (Phase F
// auth); the explicit argument is the fallback for the handful of paths that
// know the user before the session does (login, password reset).
function mb_actor($fallback = null)
{
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['username'])) {
        return (string)$_SESSION['username'];
    }
    return $fallback !== null ? (string)$fallback : 'unknown';
}
