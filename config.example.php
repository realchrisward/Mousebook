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

    // Name of the auth database. Leave as 'userbook' unless your host will
    // not give you that exact name -- cPanel and similar panels force an
    // account prefix onto every database, so you may have been handed
    // something like 'myaccount_userbook'. Put whatever it actually is here.
    // Letters, digits and underscores only.
    'userbook_db' => 'userbook',

    // Set to 'True' to show PHP errors on-screen — NEVER in production
    'debug_mode'  => 'False',

    // Optional display settings
    'site_name'   => 'Mousebook',
    'site_contact'=> '',

    // -------------------------------------------------------------
    // Phase G (issue #19) — userbook management: links & email.
    // -------------------------------------------------------------
    // Public base URL of this install, NO trailing slash. Used to build
    // absolute invite / password-reset links in outgoing email.
    'base_url'       => 'https://mousebook.example.com',

    // Outgoing mail via an SMTP relay (invitations, password resets).
    'smtp_host'      => '',            // relay hostname/IP (empty = mail disabled)
    'smtp_port'      => '587',         // 587 STARTTLS | 465 SMTPS | 25 relay
    'smtp_secure'    => 'tls',         // 'tls' | 'ssl' | '' (no encryption)
    'smtp_auth'      => false,         // true if the relay requires a login
    'smtp_user'      => '',            // relay username (only if smtp_auth)
    'smtp_pass'      => '',            // relay password (only if smtp_auth)
    'mail_from'      => '',            // From: address (e.g. no-reply@example.com)
    'mail_from_name' => 'Mousebook',   // From: display name

    // Fail fast instead of hanging (seconds). A bad host/port/secure
    // pairing errors within this window rather than white-screening.
    'smtp_timeout'   => 15,
    // Set true to log the full SMTP conversation to the PHP error log
    // (diagnosing relay problems). Leave false in normal operation.
    'smtp_debug'     => false,
    // Set true ONLY for a trusted internal relay with a self-signed or
    // hostname-mismatched TLS certificate.
    'smtp_allow_selfsigned' => false,
];
