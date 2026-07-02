<?php
// =============================================================
// includes/auth.php
// Shared Mousebook authentication helper.
//
// Replaces the plain-text-password-in-SQL login pattern used
// in every PHP page with a two-step secure approach:
//   1. Fetch the stored bcrypt hash by username (prepared stmt)
//   2. Verify with password_verify() — never compare in SQL
//   3. Fetch db-access details if credentials are valid
//
// Include near the top of each PHP page after config.php:
//   require_once __DIR__ . '/../includes/auth.php';   // php/ pages
//   require_once __DIR__ . '/includes/auth.php';      // index.php
//   require_once __DIR__ . '/../includes/auth.php';   // pages/ pages
// =============================================================


/**
 * Verify credentials and return all db-access fields for a
 * specific colony database.
 *
 * Returns an associative array on success (keys: db_host,
 * db_accessun, db_accesspw, db_formurl, db_subject_plural,
 * db_subject_single, db_guide1_title, db_guide1_url,
 * db_name, authenticated = true)
 * or ['authenticated' => false] on failure.
 *
 * @param array  $config   The array returned by config.php
 * @param string $username Submitted username
 * @param string $password Submitted plain-text password
 * @param string $dbname   Colony database name being accessed
 */
function mb_authenticate(array $config, string $username, string $password, string $dbname): array {
    $fail = ['authenticated' => false];

    if ($username === '' || $password === '' || $dbname === '') {
        return $fail;
    }

    $host  = $config['server_host'] ?? $config['server_ip'] ?? 'localhost';
    $uname = $config['server_user'];
    $upass = $config['server_pass'];

    $conn = new mysqli($host, $uname, $upass, 'userbook');
    if ($conn->connect_error) {
        return $fail;
    }

    // ── Step 1: fetch stored hash by username ───────────────
    $stmt = $conn->prepare(
        "SELECT user_pass FROM userpass WHERE user_name = ? LIMIT 1"
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($stored_hash);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found || !password_verify($password, $stored_hash)) {
        $conn->close();
        return $fail;
    }

    // ── Opportunistic rehash (e.g. upgrading bcrypt cost) ───
    if (password_needs_rehash($stored_hash, PASSWORD_BCRYPT)) {
        $new_hash = password_hash($password, PASSWORD_BCRYPT);
        $upd = $conn->prepare(
            "UPDATE userpass SET user_pass = ? WHERE user_name = ?"
        );
        $upd->bind_param('ss', $new_hash, $username);
        $upd->execute();
        $upd->close();
    }

    // ── Step 2: fetch db-access details ────────────────────
    $stmt = $conn->prepare(
        "SELECT dbaccess.db_name, db_host, db_accessun, db_accesspw,
                db_formurl, db_subject_plural, db_subject_single,
                db_guide1_title, db_guide1_url
         FROM (userpass
               JOIN userdbaccess ON userpass.user_idno = userdbaccess.user_idno)
               JOIN dbaccess ON userdbaccess.db_name = dbaccess.db_name
         WHERE userpass.user_name = ?
           AND dbaccess.db_name   = ?
         LIMIT 1"
    );
    $stmt->bind_param('ss', $username, $dbname);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$row) {
        return $fail;
    }

    $row['authenticated'] = true;
    return $row;
}


/**
 * Convenience wrapper: returns [$host, $accessun, $accesspw]
 * or null if authentication fails.
 *
 * Usage in PHP pages (replaces the old SQL login block):
 *
 *   require_once __DIR__ . '/../includes/auth.php';
 *   $conn_details = mb_get_connection($config, $xusername, $xpassword, $dbname);
 *   if ($conn_details) {
 *       [$host, $accessun, $accesspw] = $conn_details;
 *       $xloginstatus = 'green';
 *   } else {
 *       $xloginstatus = 'red';
 *       $host = $accessun = $accesspw = null;
 *   }
 */
function mb_get_connection(array $config, string $username, string $password, string $dbname): ?array {
    $auth = mb_authenticate($config, $username, $password, $dbname);
    if (!$auth['authenticated']) {
        return null;
    }
    return [$auth['db_host'], $auth['db_accessun'], $auth['db_accesspw']];
}


/**
 * For databases.php: verify credentials and return ALL colony
 * databases the user has access to (no dbname filter).
 *
 * Returns an array of rows, each with:
 *   db_name, db_accessun, db_accesspw, db_formurl,
 *   db_subject_plural, db_subject_single,
 *   db_guide1_title, db_guide1_url
 * Returns an empty array if credentials are invalid.
 */
function mb_get_user_databases(array $config, string $username, string $password): array {
    if ($username === '' || $password === '') {
        return [];
    }

    $host  = $config['server_host'] ?? $config['server_ip'] ?? 'localhost';
    $uname = $config['server_user'];
    $upass = $config['server_pass'];

    $conn = new mysqli($host, $uname, $upass, 'userbook');
    if ($conn->connect_error) {
        return [];
    }

    // Verify password first
    $stmt = $conn->prepare(
        "SELECT user_pass FROM userpass WHERE user_name = ? LIMIT 1"
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($stored_hash);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found || !password_verify($password, $stored_hash)) {
        $conn->close();
        return [];
    }

    // Fetch all accessible databases
    $stmt = $conn->prepare(
        "SELECT dbaccess.db_name, db_accessun, db_accesspw, db_formurl,
                db_subject_plural, db_subject_single,
                db_guide1_title, db_guide1_url
         FROM (userpass
               JOIN userdbaccess ON userpass.user_idno = userdbaccess.user_idno)
               JOIN dbaccess ON userdbaccess.db_name = dbaccess.db_name
         WHERE userpass.user_name = ?
         ORDER BY dbaccess.db_name"
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    $conn->close();

    return $rows;
}
