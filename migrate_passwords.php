#!/usr/bin/env php
<?php
// =============================================================
// migrate_passwords.php
// One-time admin script: hashes all plain-text passwords
// in userbook.userpass using PHP bcrypt (PASSWORD_BCRYPT).
//
// Run from your Mousebook root directory:
//   php migrate_passwords.php
//
// Uses config.php for host/port only.
// Prompts separately for MySQL admin credentials (needs
// UPDATE on userbook — the read-only mousebook_ro account
// is not sufficient).
//
// Safe to re-run — already-hashed passwords are detected
// via password_get_info() and skipped automatically.
//
// Prerequisites:
//   Run mousebook_migration_v2.sql first to widen user_pass.
// =============================================================

// ── Locate config.php ──────────────────────────────────────
$config_candidates = [
    __DIR__ . '/config.php',
    __DIR__ . '/../config.php',
];
$config = null;
foreach ($config_candidates as $path) {
    if (file_exists($path)) {
        $config = require $path;
        break;
    }
}
if (!$config) {
    fwrite(STDERR, "ERROR: config.php not found. Run from the Mousebook root directory.\n");
    exit(1);
}

$host = $config['server_host'] ?? $config['server_ip'] ?? 'localhost';
$port = $config['server_port'] ?? '3306';

echo "Mousebook password migration\n";
echo str_repeat('=', 55) . "\n";
echo "Server: {$host}:{$port}\n\n";
echo "The read-only mousebook_ro account cannot run UPDATE.\n";
echo "Enter a MySQL account with UPDATE on the userbook database\n";
echo "(e.g. root, or any account granted WRITE on userbook).\n\n";

// ── Prompt for admin credentials ───────────────────────────
echo "MySQL admin username: ";
$admin_user = trim(fgets(STDIN));

// Hide password input if possible
if (PHP_OS_FAMILY !== 'Windows') {
    system('stty -echo');
    echo "MySQL admin password: ";
    $admin_pass = trim(fgets(STDIN));
    system('stty echo');
    echo "\n\n";
} else {
    echo "MySQL admin password: ";
    $admin_pass = trim(fgets(STDIN));
    echo "\n";
}

// ── Connect with admin credentials ─────────────────────────
$conn = new mysqli($host, $admin_user, $admin_pass, 'userbook', (int)$port);
if ($conn->connect_error) {
    fwrite(STDERR, "\nERROR: Cannot connect to userbook as '{$admin_user}': "
        . $conn->connect_error . "\n");
    exit(1);
}
echo "Connected to userbook as '{$admin_user}'.\n\n";

// ── Check column width ─────────────────────────────────────
$col = $conn->query(
    "SELECT CHARACTER_MAXIMUM_LENGTH
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = 'userbook'
       AND TABLE_NAME   = 'userpass'
       AND COLUMN_NAME  = 'user_pass'"
);
$row    = $col->fetch_assoc();
$maxlen = (int)($row['CHARACTER_MAXIMUM_LENGTH'] ?? 0);

if ($maxlen < 60) {
    fwrite(
        STDERR,
        "ERROR: user_pass column is only {$maxlen} chars wide.\n"
            . "       Run mousebook_migration_v2.sql first, then retry.\n"
    );
    $conn->close();
    exit(1);
}
echo "Column width OK ({$maxlen} chars).\n\n";

// ── Fetch all users ────────────────────────────────────────
$result = $conn->query(
    "SELECT user_idno, user_name, user_pass FROM userpass ORDER BY user_idno"
);
if (!$result) {
    fwrite(STDERR, "ERROR: Could not read userpass: " . $conn->error . "\n");
    $conn->close();
    exit(1);
}

$users   = $result->fetch_all(MYSQLI_ASSOC);
$total   = count($users);
$hashed  = 0;
$skipped = 0;
$failed  = 0;

echo "Found {$total} user(s) in userpass.\n";
echo str_repeat('-', 55) . "\n";
printf("%-6s %-20s %-26s\n", 'ID', 'Username', 'Action');
echo str_repeat('-', 55) . "\n";

foreach ($users as $user) {
    $id     = $user['user_idno'];
    $name   = $user['user_name'];
    $stored = $user['user_pass'];

    // Detect if already a valid bcrypt / Argon2 hash
    $info = password_get_info($stored);
    if (!empty($info['algo'])) {
        printf("%-6s %-20s %-26s\n", $id, $name, 'already hashed — skipped');
        $skipped++;
        continue;
    }

    // Plain-text detected — hash with bcrypt
    $new_hash = password_hash($stored, PASSWORD_BCRYPT);
    if ($new_hash === false) {
        printf("%-6s %-20s %-26s\n", $id, $name, 'ERROR: hashing failed');
        $failed++;
        continue;
    }

    $stmt = $conn->prepare(
        "UPDATE userpass SET user_pass = ? WHERE user_idno = ?"
    );
    $stmt->bind_param('si', $new_hash, $id);

    if ($stmt->execute()) {
        printf("%-6s %-20s %-26s\n", $id, $name, 'hashed ✔');
        $hashed++;
    } else {
        printf(
            "%-6s %-20s %-26s\n",
            $id,
            $name,
            'ERROR: ' . $stmt->error
        );
        $failed++;
    }
    $stmt->close();
}

echo str_repeat('-', 55) . "\n";
echo "Summary:\n";
echo "  Hashed:   {$hashed}\n";
echo "  Skipped:  {$skipped} (already hashed)\n";
echo "  Failed:   {$failed}\n\n";

$conn->close();

if ($failed > 0) {
    fwrite(STDERR, "WARNING: {$failed} password(s) failed — check output above.\n");
    exit(1);
}

if ($hashed > 0) {
    echo "Done. All passwords are now hashed with bcrypt.\n";
    echo "You can safely delete this script after confirming login works.\n";
} else {
    echo "Nothing to do — all passwords were already hashed.\n";
}
exit(0);
