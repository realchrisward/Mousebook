<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<?php
// =============================================================
// php/forgot_password.php  (Phase G / issue #19)
// PUBLIC. User-initiated password reset request.
//   * User enters their username or email.
//   * If it matches an account WITH an email on file, a 30-minute,
//     single-use reset token is generated and emailed as a link to
//     set_password.php.
//   * The response is ALWAYS the same neutral message regardless of
//     whether the account exists (no account/email enumeration).
// Admin-initiated resets (72h) are issued from manage_users.php.
// =============================================================

$config = require '../config.php';
if (($config['debug_mode'] ?? 'False') == 'True') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
require_once __DIR__ . '/../includes/usertoken.php';
require_once __DIR__ . '/../includes/mail.php';

// User-initiated resets expire quickly.
if (!defined('MB_RESET_TTL_USER')) { define('MB_RESET_TTL_USER', 30 * 60); }

$submitted = false;
$neutral = 'If an account matches what you entered and has an email address on file, '
         . 'a password-reset link has been sent. The link expires in 30 minutes.';

if (isset($_POST['button_forgot'])) {
    $submitted = true;
    $ident = trim((string)($_POST['identifier'] ?? ''));

    if ($ident !== '') {
        $dberr = '';
        $conn = mb_userbook_conn($config, $dberr);
        if ($conn !== null) {
            // Match on username OR email (userdetail).
            $uid = null; $email = null; $uname = null;
            $stmt = $conn->prepare(
                "SELECT p.user_idno, p.user_name, d.user_email
                   FROM userpass p
                   LEFT JOIN userdetail d ON d.user_idno = p.user_idno
                  WHERE p.user_name = ? OR d.user_email = ?
                  LIMIT 1"
            );
            if ($stmt) {
                $stmt->bind_param('ss', $ident, $ident);
                $stmt->execute();
                $stmt->bind_result($u_id, $u_name, $u_email);
                if ($stmt->fetch()) {
                    $uid = (int)$u_id; $uname = (string)$u_name;
                    $email = $u_email !== null ? (string)$u_email : null;
                }
                $stmt->close();

                if ($uid !== null && $email) {
                    $raw = mb_token_generate($conn, $uid, 'reset', MB_RESET_TTL_USER, 'self');
                    if ($raw !== null) {
                        $base = rtrim((string)($config['base_url'] ?? ''), '/');
                        $link = $base . '/php/set_password.php?token=' . $raw;
                        $subject = 'Mousebook password reset';
                        $safe_link = htmlspecialchars($link, ENT_QUOTES);
                        $body = '<p>A password reset was requested for your Mousebook account ('
                              . htmlspecialchars($uname, ENT_QUOTES) . ').</p>'
                              . '<p><a href="' . $safe_link . '">Reset your password</a></p>'
                              . '<p>Or paste this link into your browser:<br>' . $safe_link . '</p>'
                              . '<p>This link expires in 30 minutes and can be used once. '
                              . 'If you did not request this, you can ignore this email.</p>';
                        $mailerr = '';
                        // Best-effort; failures are logged, never surfaced (no enumeration).
                        if (!mb_send_relay_mail($config, $email, $subject, $body, $mailerr)) {
                            error_log('forgot_password mail failed: ' . $mailerr);
                        }
                    }
                }
            }
            $conn->close();
        }
    }
}
?>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Forgot password - Mousebook</title>
    <link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div id="header">
        <h1 class="centervert" style="position:absolute;top:0px;left:20px;">
            Forgot password
        </h1>
    </div>

    <div id="main" style="max-width:520px;margin:40px auto;padding:0 20px;">
        <?php if ($submitted): ?>
            <p class="centertext" style="padding:10px;border:1px solid #999;background:#f4f4f4;">
                <?php echo htmlspecialchars($neutral, ENT_QUOTES); ?>
            </p>
            <p class="centertext"><a href="../pages/databases.php">Back to sign in &raquo;</a></p>
        <?php else: ?>
            <p>Enter your username or email address and we'll send a reset link.</p>
            <form action="forgot_password.php" method="post" autocomplete="off">
                <table>
                    <tr><td>Username or email:</td>
                        <td><input type="text" name="identifier" style="width:240px;" /></td></tr>
                </table>
                <input type="submit" class="button" name="button_forgot" value="Send reset link" />
            </form>
            <p class="centertext" style="margin-top:20px;">
                <a href="../pages/databases.php">Back to sign in</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
