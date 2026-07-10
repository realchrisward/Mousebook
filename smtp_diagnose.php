<?php
// =====================================================================
// smtp_diagnose.php  —  standalone SMTP relay diagnostic (CLI ONLY)
//
// Place this file in the Mousebook ROOT (next to config.php) and run:
//
//     php smtp_diagnose.php you@your-domain.example
//
// It bypasses the web server, session, and page rendering entirely, so
// there is NO white screen — every error and the full SMTP conversation
// print straight to your terminal. It uses your real config.php values.
//
// It runs two isolated steps:
//   1. raw TCP connect  (is the relay reachable at all? — network/DNS/port)
//   2. full PHPMailer send with maximum debug (TLS / auth / cert / from)
//
// Nothing is written to any database. Safe to delete afterward.
// =====================================================================

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this from the command line, not the browser.\n");
    exit(1);
}
$to = $argv[1] ?? '';
if ($to === '') {
    fwrite(STDERR, "Usage: php smtp_diagnose.php recipient@example.com\n");
    exit(1);
}

// --- locate config.php ------------------------------------------------
$tried = [getcwd() . '/config.php', __DIR__ . '/config.php'];
$config = null; $root = __DIR__;
foreach ($tried as $path) {
    if (is_file($path)) {
        $config = require $path;
        $root   = dirname($path);
        echo "Loaded config: $path\n";
        break;
    }
}
if (!is_array($config)) {
    fwrite(STDERR, "Could not find config.php. Tried:\n  " . implode("\n  ", $tried) . "\n");
    fwrite(STDERR, "Run this from the Mousebook root directory.\n");
    exit(1);
}

$host    = (string)($config['smtp_host'] ?? '');
$port    = (int)($config['smtp_port'] ?? 587);
$secure  = strtolower((string)($config['smtp_secure'] ?? ''));
$auth    = !empty($config['smtp_auth']);
$timeout = (int)($config['smtp_timeout'] ?? 15);

echo "\n----- effective SMTP settings -----\n";
printf("  smtp_host              : %s\n", $host === '' ? '(EMPTY -> mail disabled)' : $host);
printf("  smtp_port              : %d\n", $port);
printf("  smtp_secure            : %s\n", $secure === '' ? '(none)' : $secure);
printf("  smtp_auth              : %s\n", $auth ? 'true' : 'false');
printf("  smtp_user              : %s\n", $auth ? (string)($config['smtp_user'] ?? '') : '(n/a)');
printf("  smtp_pass              : %s\n", $auth ? (empty($config['smtp_pass']) ? '(EMPTY!)' : '(set, hidden)') : '(n/a)');
printf("  mail_from              : %s\n", ($config['mail_from'] ?? '') === '' ? '(EMPTY!)' : (string)$config['mail_from']);
printf("  smtp_timeout           : %d\n", $timeout);
printf("  smtp_allow_selfsigned  : %s\n", !empty($config['smtp_allow_selfsigned']) ? 'true' : 'false');
echo   "  --\n";
printf("  PHP max_execution_time : %s  %s\n", ini_get('max_execution_time'),
        ((int)ini_get('max_execution_time') > 0 && (int)ini_get('max_execution_time') < $timeout)
          ? '(!! LOWER than smtp_timeout — a hang can still fatal before the socket times out)' : '');
printf("  openssl extension      : %s\n", extension_loaded('openssl')  ? 'loaded' : 'NOT LOADED');
printf("  mbstring extension     : %s\n", extension_loaded('mbstring') ? 'loaded' : 'NOT LOADED');

// common mismatch hint
$expect = [587 => 'tls', 465 => 'ssl', 25 => ''];
if (isset($expect[$port]) && $expect[$port] !== $secure) {
    printf("\n  HINT: port %d usually pairs with smtp_secure '%s', but yours is '%s'.\n",
           $port, $expect[$port] === '' ? '' : $expect[$port], $secure);
    echo   "        A port/encryption mismatch is the #1 cause of a hang.\n";
}

if ($host === '') { echo "\nsmtp_host is empty — set it before testing.\n"; exit(1); }

// --- step 1: raw TCP reachability (network vs TLS/auth) ---------------
echo "\n----- step 1: raw TCP connect to {$host}:{$port} -----\n";
$scheme = ($secure === 'ssl') ? 'ssl://' : '';
$errno = 0; $errstr = '';
$t0 = microtime(true);
$fp = @fsockopen($scheme . $host, $port, $errno, $errstr, (float)$timeout);
$dt = round(microtime(true) - $t0, 2);
if ($fp) {
    echo "  CONNECTED in {$dt}s\n";
    stream_set_timeout($fp, 5);
    $greet = fgets($fp, 512);
    echo "  server greeting        : " . (is_string($greet) ? trim($greet) : '(none within 5s)') . "\n";
    fclose($fp);
} else {
    echo "  FAILED in {$dt}s  ->  errno={$errno}  errstr={$errstr}\n";
    echo "  => The relay is not reachable with these settings. This is a\n";
    echo "     network / DNS / port / firewall (or ssl-scheme) problem that\n";
    echo "     happens BEFORE any PHPMailer logic. Fix this first.\n";
    echo "     Try from the server: nc -vz {$host} {$port}   (or telnet {$host} {$port})\n";
}

// --- step 2: full PHPMailer send with verbose debug -------------------
echo "\n----- step 2: PHPMailer send (verbose SMTP trace) -----\n";
$pm = $root . '/includes/vendor/PHPMailer/';
foreach (['PHPMailer.php', 'SMTP.php', 'Exception.php'] as $f) {
    if (!is_file($pm . $f)) { fwrite(STDERR, "Missing $pm$f\n"); exit(1); }
    require $pm . $f;
}
$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->Port       = $port;
    $mail->Timeout    = $timeout;
    $mail->SMTPDebug  = 3; // connection + client + server
    $mail->Debugoutput = 'echo';
    $mail->SMTPAuth   = $auth;
    if ($auth) { $mail->Username = (string)($config['smtp_user'] ?? ''); $mail->Password = (string)($config['smtp_pass'] ?? ''); }
    if     ($secure === 'tls') { $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; }
    elseif ($secure === 'ssl') { $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; }
    else                       { $mail->SMTPSecure = ''; $mail->SMTPAutoTLS = false; }
    if (!empty($config['smtp_allow_selfsigned'])) {
        $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
    }
    $mail->setFrom((string)($config['mail_from'] ?? ''), (string)($config['mail_from_name'] ?? 'Mousebook'));
    $mail->addAddress($to);
    $mail->Subject = 'Mousebook SMTP diagnostic';
    $mail->Body    = 'If you received this, the relay path works.';
    $mail->send();
    echo "\nRESULT: SUCCESS — test message accepted by the relay.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "\nRESULT: FAILED\n";
    echo "  Exception: " . get_class($e) . "\n";
    echo "  Message  : " . $e->getMessage() . "\n";
    echo "  ErrorInfo: " . ($mail->ErrorInfo ?? '') . "\n";
    exit(1);
}
