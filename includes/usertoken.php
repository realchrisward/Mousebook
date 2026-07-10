<?php
// =============================================================
// includes/usertoken.php
// Shared Mousebook userbook-management helpers (Phase G, issue #19).
//
// Provides:
//   * mb_userbook_conn()      — a WRITE connection to the userbook db,
//                               bootstrapped from the `dbaccess` row for
//                               db_name='userbook' (its db_accessun/pw
//                               are the write-capable credentials, per the
//                               "credentials live in the dbaccess row"
//                               decision). No new config credential.
//   * mb_token_generate()     — mint a single-use, time-limited token
//                               (raw token returned once; only its
//                               sha256 hash is stored).
//   * mb_token_peek()         — validate a raw token WITHOUT consuming it.
//   * mb_token_consume()      — validate + mark used (single-use).
//   * mb_password_policy_error() — shared password-strength check.
//
// All SQL here is parameterised (prepared statements).
// =============================================================

if (!defined('MB_USERBOOK_DB')) {
    // The auth database name. Edit here (and in config/schema) if your
    // install renamed it.
    define('MB_USERBOOK_DB', 'userbook');
}

if (!function_exists('mb_userbook_conn')) {

    /**
     * Open a connection to the userbook database using the write-capable
     * credentials stored in dbaccess for the userbook row itself.
     *
     * Bootstrap: connect with the config read account to userbook, read
     * the userbook dbaccess row, then reconnect with those credentials.
     * Returns a live mysqli or null on any failure (caller shows a
     * generic "cannot reach the user database" notice).
     *
     * @param array  $config
     * @param string &$error out: diagnostic (for logs, not end users)
     */
    function mb_userbook_conn(array $config, string &$error = ''): ?mysqli {
        $error = '';
        $host  = $config['server_host'] ?? $config['server_ip'] ?? 'localhost';
        $port  = (int)($config['server_port'] ?? 3306);

        // Step 1: read connection (config account) to fetch write creds.
        $ro = @new mysqli($host, (string)$config['server_user'],
                          (string)$config['server_pass'], MB_USERBOOK_DB, $port);
        if ($ro->connect_error) {
            $error = 'userbook read connect failed: ' . $ro->connect_error;
            return null;
        }
        $wr_host = $host; $wr_un = null; $wr_pw = null; $wr_port = $port;
        $stmt = $ro->prepare(
            "SELECT db_host, db_accessun, db_accesspw
               FROM dbaccess WHERE db_name = ? LIMIT 1"
        );
        if ($stmt) {
            $ubname = MB_USERBOOK_DB;
            $stmt->bind_param('s', $ubname);
            $stmt->execute();
            $stmt->bind_result($h, $un, $pw);
            if ($stmt->fetch()) {
                if (!empty($h))  { $wr_host = $h; }
                $wr_un = $un; $wr_pw = $pw;
            }
            $stmt->close();
        }
        $ro->close();

        if ($wr_un === null) {
            $error = 'no dbaccess row for ' . MB_USERBOOK_DB
                   . ' (register it with write-capable credentials).';
            return null;
        }

        // Step 2: write connection with the userbook dbaccess credentials.
        $wr = @new mysqli($wr_host, (string)$wr_un, (string)$wr_pw,
                          MB_USERBOOK_DB, $wr_port);
        if ($wr->connect_error) {
            $error = 'userbook write connect failed: ' . $wr->connect_error;
            return null;
        }
        return $wr;
    }


    /**
     * Mint a single-use token for a user and store only its hash.
     *
     * @return string|null the RAW token to embed in a link (shown once),
     *                     or null on failure.
     */
    function mb_token_generate(mysqli $conn, int $user_idno, string $purpose,
                               int $ttl_seconds, string $created_by = ''): ?string {
        $purpose = ($purpose === 'invite') ? 'invite' : 'reset';
        $raw  = bin2hex(random_bytes(32));           // 64 hex chars
        $hash = hash('sha256', $raw);                // stored form
        $now  = gmdate('Y-m-d H:i:s');
        $exp  = gmdate('Y-m-d H:i:s', time() + max(60, $ttl_seconds));

        $stmt = $conn->prepare(
            "INSERT INTO usertoken
                (user_idno, token_hash, purpose, created_by, created_at, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) { return null; }
        $stmt->bind_param('isssss', $user_idno, $hash, $purpose, $created_by, $now, $exp);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? $raw : null;
    }


    /**
     * Look up a raw token without consuming it. Returns the user_idno if
     * the token is valid (right purpose, unused, unexpired), else null.
     */
    function mb_token_peek(mysqli $conn, string $raw, string $purpose): ?int {
        $purpose = ($purpose === 'invite') ? 'invite' : 'reset';
        if (!preg_match('/^[0-9a-f]{64}$/', $raw)) { return null; }
        $hash = hash('sha256', $raw);
        $now  = gmdate('Y-m-d H:i:s');

        $stmt = $conn->prepare(
            "SELECT user_idno FROM usertoken
              WHERE token_hash = ? AND purpose = ?
                AND used_at IS NULL AND expires_at >= ?
              LIMIT 1"
        );
        if (!$stmt) { return null; }
        $stmt->bind_param('sss', $hash, $purpose, $now);
        $stmt->execute();
        $stmt->bind_result($uid);
        $uid_out = $stmt->fetch() ? (int)$uid : null;
        $stmt->close();
        return $uid_out;
    }


    /**
     * Validate and atomically consume a token (single-use). Returns the
     * user_idno on success, else null. Uses a conditional UPDATE so two
     * concurrent submits can't both win.
     */
    function mb_token_consume(mysqli $conn, string $raw, string $purpose): ?int {
        $purpose = ($purpose === 'invite') ? 'invite' : 'reset';
        if (!preg_match('/^[0-9a-f]{64}$/', $raw)) { return null; }
        $hash = hash('sha256', $raw);
        $now  = gmdate('Y-m-d H:i:s');

        $uid = mb_token_peek($conn, $raw, $purpose);
        if ($uid === null) { return null; }

        $stmt = $conn->prepare(
            "UPDATE usertoken SET used_at = ?
              WHERE token_hash = ? AND used_at IS NULL"
        );
        if (!$stmt) { return null; }
        $stmt->bind_param('ss', $now, $hash);
        $stmt->execute();
        $won = ($stmt->affected_rows === 1);
        $stmt->close();
        return $won ? $uid : null;
    }


    /**
     * Shared password policy. Returns an error string, or '' if OK.
     * Deliberately simple (length + not-all-same); tighten as needed.
     */
    function mb_password_policy_error(string $pw): string {
        if (strlen($pw) < 10) {
            return 'Password must be at least 10 characters.';
        }
        if (preg_match('/^(.)\1*$/', $pw)) {
            return 'Password must not be a single repeated character.';
        }
        return '';
    }
}
