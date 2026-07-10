<?php
// =============================================================
// includes/mail.php
// Shared Mousebook mail helper (Phase G, issue #19).
//
// Sends transactional mail (user invitations, password-reset
// links) through an SMTP relay using the vendored PHPMailer
// (includes/vendor/PHPMailer, no Composer required).
//
// All relay settings come from config.php:
//   base_url        e.g. 'https://mousebook.example.com'  (no trailing slash)
//   smtp_host       relay hostname/IP
//   smtp_port       587 (submission/STARTTLS) | 465 (SMTPS) | 25 (relay)
//   smtp_secure     'tls' | 'ssl' | ''   ('' = no transport encryption)
//   smtp_auth       true|false           (false for an unauthenticated relay)
//   smtp_user       relay username       (only if smtp_auth)
//   smtp_pass       relay password       (only if smtp_auth)
//   mail_from       envelope/From address
//   mail_from_name  From display name
//
// Usage:
//   require_once __DIR__ . '/mail.php';
//   $ok = mb_send_mail($config, $to, $subject, $html_body, $err);
//   if (!$ok) { error_log("mail failed: $err"); }
//
// Returns true on success, false on failure. On failure the human
// error string is written to the $error out-parameter (never shown
// verbatim to end users, to avoid leaking relay detail).
// =============================================================

require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/vendor/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

if (!function_exists('mb_send_mail')) {

    /**
     * Send one message through the configured SMTP relay.
     *
     * @param array  $config  the config.php array
     * @param string $to      recipient address
     * @param string $subject subject line
     * @param string $html    HTML body (a plain-text alternative is derived)
     * @param string &$error  out: human-readable failure reason on false
     * @return bool           true on success
     */
    function mb_send_mail(array $config, string $to, string $subject, string $html, string &$error = ''): bool {
        $error = '';

        if (empty($config['smtp_host'])) {
            $error = 'SMTP relay is not configured (smtp_host empty).';
            return false;
        }
        if (empty($config['mail_from'])) {
            $error = 'Sender address is not configured (mail_from empty).';
            return false;
        }
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $error = 'Invalid recipient address.';
            return false;
        }

        $mail = new PHPMailer(true); // exceptions on
        try {
            $mail->isSMTP();
            $mail->Host       = (string)$config['smtp_host'];
            $mail->Port       = (int)($config['smtp_port'] ?? 587);

            // Fail fast: a bad host/port/encryption pairing must error in
            // seconds (catchable below) rather than hang until PHP's
            // max_execution_time kills the request (which white-screens).
            $mail->Timeout    = (int)($config['smtp_timeout'] ?? 15);

            // Optional: log the full SMTP conversation to the PHP error log
            // for diagnosis. Set 'smtp_debug' => true in config.php.
            if (!empty($config['smtp_debug'])) {
                $mail->SMTPDebug   = 2; // client+server
                $mail->Debugoutput = function ($str, $level) {
                    error_log('PHPMailer[' . $level . ']: ' . rtrim($str));
                };
            }

            $mail->SMTPAuth   = !empty($config['smtp_auth']);
            if ($mail->SMTPAuth) {
                $mail->Username = (string)($config['smtp_user'] ?? '');
                $mail->Password = (string)($config['smtp_pass'] ?? '');
            }

            $secure = strtolower((string)($config['smtp_secure'] ?? ''));
            if ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                // No transport encryption (e.g. trusted internal relay on :25).
                $mail->SMTPSecure  = '';
                $mail->SMTPAutoTLS = false;
            }

            // Escape hatch for internal relays presenting a self-signed or
            // hostname-mismatched certificate. Set 'smtp_allow_selfsigned'
            // => true in config.php ONLY for a trusted internal relay.
            if (!empty($config['smtp_allow_selfsigned'])) {
                $mail->SMTPOptions = ['ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ]];
            }

            $mail->setFrom((string)$config['mail_from'],
                           (string)($config['mail_from_name'] ?? 'Mousebook'));
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            // Derive a plain-text part from the HTML for non-HTML clients.
            $mail->AltBody = trim(html_entity_decode(
                strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html)),
                ENT_QUOTES
            ));

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            $error = $mail->ErrorInfo ?: $e->getMessage();
            return false;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            return false;
        }
    }
}
