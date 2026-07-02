#!/usr/bin/env php
<?php
// =============================================================
// patch_auth.php
// Updates all Mousebook PHP pages to use includes/auth.php
// instead of the plain-text-password-in-SQL login block.
//
// Run from your Mousebook root directory:
//   php patch_auth.php [--dry-run]
//
// Flags:
//   --dry-run   Show what would change without writing files
//
// Each file is backed up as filename.php.bak before patching.
// Safe to re-run — already-patched files are detected and
// skipped automatically.
// =============================================================

$dry_run = in_array('--dry-run', $argv, true);

if ($dry_run) {
    echo "[DRY RUN — no files will be modified]\n\n";
}

// ── Locate Mousebook root ──────────────────────────────────
$root = __DIR__;
if (!file_exists($root . '/index.php')) {
    fwrite(STDERR, "ERROR: Run from the Mousebook root directory (where index.php lives).\n");
    exit(1);
}

// ── Files to patch and their include paths ─────────────────
// path => require_once path for auth.php (relative from file location)
function discover_files(string $root): array {
    $map = [];

    // php/ directory
    foreach (glob($root . '/php/*.php') as $f) {
        $map[$f] = "__DIR__ . '/../includes/auth.php'";
    }
    // pages/ directory
    foreach (glob($root . '/pages/*.php') as $f) {
        $map[$f] = "__DIR__ . '/../includes/auth.php'";
    }
    // root index.php
    if (file_exists($root . '/index.php')) {
        $map[$root . '/index.php'] = "__DIR__ . '/includes/auth.php'";
    }

    return $map;
}

// ── The old login SQL block pattern ───────────────────────
// We match the core SQL + connection block that appears in
// every page. Two variants exist (different SELECT columns).
// We replace both with a call to mb_get_connection().

// Marker we inject so we can detect already-patched files
define('PATCH_MARKER', '// [mb_auth_patched]');

function build_replacement(string $include_path): string {
    return PATCH_MARKER . "\n"
        . "\t\trequire_once {$include_path};\n"
        . "\t\t\$_mb_conn = mb_get_connection(\$config, \$xusername, \$xpassword, \$dbname);\n"
        . "\t\tif (\$_mb_conn) {\n"
        . "\t\t\t[\$host, \$accessun, \$accesspw] = \$_mb_conn;\n"
        . "\t\t}\n";
}

// Regex patterns that match the old auth SQL block.
// Using PREG_OFFSET_CAPTURE so we can replace precisely.
$patterns = [
    // Standard pattern (php/ pages)
    '/\$sql\s*=\s*"select\s+dbaccess\.db_name.*?\.close\(\);'
        . '\s*while\s*\(\$row\s*=\s*mysqli_fetch_array\(\$results\)\)\s*\{'
        . '.*?\$host\s*=\s*\$row\[.db_host.\];'
        . '\s*\}'
        . '/s',

    // Variant with trailing whitespace / slightly different layout
    '/\$sql="select dbaccess\.db_name.*?while\(\$row=mysqli_fetch_array\(\$results\)\)\{'
        . '.*?\$host=\$row\[.db_host.\];'
        . '\s*\}/s',
];

// ── Process each file ──────────────────────────────────────
$files   = discover_files($root);
$patched = 0;
$skipped = 0;
$failed  = 0;

echo str_repeat('=', 60) . "\n";
echo "Mousebook auth patcher\n";
echo str_repeat('=', 60) . "\n\n";

foreach ($files as $filepath => $include_path) {
    $short = str_replace($root . '/', '', $filepath);

    if (!file_exists($filepath)) {
        continue;
    }

    $source = file_get_contents($filepath);

    // Skip already patched
    if (strpos($source, PATCH_MARKER) !== false) {
        echo "  [skip]    $short — already patched\n";
        $skipped++;
        continue;
    }

    // Try each pattern
    $matched  = false;
    $new_source = $source;

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $source)) {
            $replacement = build_replacement($include_path);
            $new_source  = preg_replace($pattern, $replacement, $source, 1);
            if ($new_source !== null && $new_source !== $source) {
                $matched = true;
                break;
            }
        }
    }

    if (!$matched) {
        echo "  [no match] $short — login block not found (may need manual update)\n";
        $failed++;
        continue;
    }

    if ($dry_run) {
        echo "  [would patch] $short\n";
        $patched++;
        continue;
    }

    // Back up original
    $backup = $filepath . '.bak';
    if (!file_exists($backup)) {
        file_put_contents($backup, $source);
    }

    if (file_put_contents($filepath, $new_source) === false) {
        echo "  [ERROR]   $short — could not write file\n";
        $failed++;
        continue;
    }

    echo "  [patched]  $short\n";
    $patched++;
}

echo "\n" . str_repeat('-', 60) . "\n";
echo "Summary:\n";
echo "  Patched:      $patched\n";
echo "  Skipped:      $skipped (already patched)\n";
echo "  No match:     $failed (check manually)\n";

if ($failed > 0) {
    echo "\nFiles marked 'no match' still use plain-text password SQL.\n";
    echo "Replace their login block manually — see includes/auth.php\n";
    echo "for the mb_get_connection() usage example.\n";
}

if (!$dry_run && $patched > 0) {
    echo "\nBackups written as *.bak alongside each patched file.\n";
    echo "Test login on all pages, then remove .bak files when satisfied.\n";
}

echo "\n";
exit($failed > 0 ? 1 : 0);
