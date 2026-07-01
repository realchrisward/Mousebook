<?php
// =============================================================
// Mousebook Configuration Template
// =============================================================
// 1. Copy this file to config.php
// 2. Fill in your values
// 3. Ensure config.php is NOT web-accessible (see README)
// 4. NEVER commit config.php to version control
// =============================================================

return [
    // MySQL server hostname or IP (use 'localhost' if on the same machine)
    'server_ip'   => 'localhost',
    'server_host' => 'localhost',

    // MySQL port (default: 3306)
    'server_port' => '3306',

    // Read-only MySQL user for the userbook database
    // Only needs SELECT on the userbook database
    'server_user' => '',
    'server_pass' => '',

    // Set to 'True' to show PHP errors on-screen — NEVER in production
    'debug_mode'  => 'False',

    // Optional display settings
    'site_name'   => 'Mousebook',
    'site_contact'=> '',
];
