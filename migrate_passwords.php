#!/usr/bin/env php
<?php
// =============================================================
// migrate_passwords.php
// One-time admin script: hashes all plain-text passwords
// in userbook.userpass using PHP's bcrypt (PASSWORD_BCRYPT).
//
// Run from your Mousebook root directory:
//   php migrate_passwords.php
//
// Safe to re-run — already-hashed passwords are detected
// via password_get_info() and skipped automatically.
//
// Prerequisites:
//   1. Run mousebook_migration_v2.sql first to widen the
//      user_pass column to varchar(255).
//   2. config.php must be present in the same directory.
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

// ── Connect to userbook ────────────────────────────────────
$conn = new mysqli(
    $config['server_host'] ?? $config['server_ip'] ?? 'localhost',
    $config['server_user'],
    $config['server_pass'],
    'userbook'
);
if ($conn->connect_error) {
    fwrite(STDERR, "ERROR: Cannot connect to userbook: " . $conn->connect_error . "\n");
    exit(1);
}
echo "Connected to userbook.\n\n";

// ── Check column width before proceeding ───────────────────
$col = $conn->query("SELECT CHARACTER_MAXIMUM_LENGTH
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'userbook'
      AND TABLE_NAME   = 'userpass'
      AND COLUMN_NAME  = 'user_pass'");
$row = $col->fetch_assoc();
$maxlen = (int)($row['CHARACTER_MAXIMUM_LENGTH'] ?? 0);
if ($maxlen < 60) {
    fwrite(STDERR,
        "ERROR: user_pass column is only {$maxlen} chars wide.\n"
      . "       Run mousebook_migration_v2.sql first, then retry.\n");
    $conn->close();
    exit(1);
}
echo "Column width OK ({$maxlen} chars).\n\n";

// ── Fetch all users ────────────────────────────────────────
$result = $conn->query("SELECT user_idno, user_name, user_pass FROM userpass ORDER BY user_idno");
if (!$result) {
    fwrite(STDERR, "ERROR: Could not read userpass table: " . $conn->error . "\n");
    $conn->close();
    exit(1);
}

$users     = $result->fetch_all(MYSQLI_ASSOC);
$total     = count($users);
$hashed    = 0;
$skipped   = 0;
$failed    = 0;

echo "Found {$total} user(s) in userpass.\n";
echo str_repeat('-', 55) . "\n";
printf("%-6s %-20s %-26s\n", 'ID', 'Username', 'Action');
echo str_repeat('-', 55) . "\n";

foreach ($users as $user) {
    $id       = $user['user_idno'];
    $name     = $user['user_name'];
    $stored   = $user['user_pass'];

    // Detect if already a valid bcrypt / Argon2 hash
    $info = password_get_info($stored);
    if ($info['algo'] !== 0 && $info['algo'] !== null) {
        printf("%-6s %-20s %-26s\n", $id, $name, 'already hashed — skipped');
        $skipped++;
        continue;
    }

    // Plain-text password detected — hash it
    $new_hash = password_hash($stored, PASSWORD_BCRYPT);
    if ($new_hash === false) {
        printf("%-6s %-20s %-26s\n", $id, $name, 'ERROR: hashing failed');
        $failed++;
        continue;
    }

    $stmt = $conn->prepare("UPDATE userpass SET user_pass = ? WHERE user_idno = ?");
    $stmt->bind_param('si', $new_hash, $id);
    if ($stmt->execute()) {
        printf("%-6s %-20s %-26s\n", $id, $name, 'hashed ✔');
        $hashed++;
    } else {
        printf("%-6s %-20s %-26s\n", $id, $name, 'ERROR: update failed');
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

echo "Done. All passwords are now hashed with bcrypt.\n";
echo "You can safely delete this script after migration.\n";
exit(0);
