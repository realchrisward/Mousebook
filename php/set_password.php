<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<?php
require_once __DIR__ . '/../includes/db.php';
// =============================================================
// php/set_password.php  (Phase G / issue #19)
// PUBLIC, token-gated. Serves users who cannot log in yet:
//   * invitation  → set an initial password
//   * reset       → choose a new password
// Reached only via an emailed single-use, time-limited link
// (?token=<64 hex>). No session/login required; NOT behind the
// admin gate. See includes/usertoken.php.
// =============================================================

$config = require '../config.php';
mb_debug_init($config);
require_once __DIR__ . '/../includes/usertoken.php';

$token   = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$notice  = '';
$state   = 'form';        // 'form' | 'invalid' | 'done'
$purpose = '';            // 'invite' | 'reset'
$username = '';

$dberr = '';
$conn = mb_userbook_conn($config, $dberr);
if ($conn === null) {
    $state  = 'invalid';
    $notice = 'The user database is temporarily unavailable. Please try again later.';
    if (($config['debug_mode'] ?? 'False') == 'True' && $dberr !== '') {
        $notice .= ' [' . htmlspecialchars($dberr, ENT_QUOTES) . ']';
    }
}

// Resolve token + purpose (peek: does not consume).
$uid = null;
if ($conn !== null && $token !== '') {
    foreach (['invite', 'reset'] as $p) {
        $maybe = mb_token_peek($conn, $token, $p);
        if ($maybe !== null) { $uid = $maybe; $purpose = $p; break; }
    }
    if ($uid === null) {
        $state  = 'invalid';
        $notice = 'This link is invalid, has already been used, or has expired. '
                . 'Please request a new one.';
    } else {
        // Fetch the username for a friendly greeting.
        if ($stmt = $conn->prepare("SELECT user_name FROM userpass WHERE user_idno = ? LIMIT 1")) {
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $stmt->bind_result($un);
            if ($stmt->fetch()) { $username = (string)$un; }
            $stmt->close();
        }
    }
} elseif ($conn !== null && $token === '') {
    $state  = 'invalid';
    $notice = 'No token supplied. Use the link from your invitation or reset email.';
}

// Handle submission.
if ($state === 'form' && isset($_POST['button_setpass']) && $uid !== null) {
    $pw1 = (string)($_POST['newpass'] ?? '');
    $pw2 = (string)($_POST['newpass2'] ?? '');
    $perr = mb_password_policy_error($pw1);
    if ($pw1 !== $pw2) {
        $notice = 'The two passwords do not match.';
    } elseif ($perr !== '') {
        $notice = $perr;
    } else {
        // Consume the token atomically (single-use) BEFORE writing.
        $consumed = mb_token_consume($conn, $token, $purpose);
        if ($consumed === null || $consumed !== $uid) {
            $state  = 'invalid';
            $notice = 'This link was just used or has expired. Please request a new one.';
        } else {
            $hash = password_hash($pw1, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE userpass SET user_pass = ? WHERE user_idno = ?");
            if ($stmt) {
                $stmt->bind_param('si', $hash, $uid);
                $ok = $stmt->execute();
                $stmt->close();
                if ($ok) {
                    $state  = 'done';
                    $notice = ($purpose === 'invite')
                        ? 'Your password has been set. You can now sign in.'
                        : 'Your password has been reset. You can now sign in.';
                } else {
                    $notice = 'Could not save the new password. Please try again.';
                }
            } else {
                $notice = 'Could not save the new password. Please try again.';
            }
        }
    }
}
if ($conn !== null) { $conn->close(); }

$heading = ($purpose === 'reset') ? 'Reset your password' : 'Set your password';
?>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title><?php echo $heading; ?> - Mousebook</title>
    <link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div id="header">
        <h1 class="centervert" style="position:absolute;top:0px;left:20px;">
            <?php echo $heading; ?>
        </h1>
    </div>

    <div id="main" style="max-width:520px;margin:40px auto;padding:0 20px;">
        <?php if ($notice !== ''): ?>
            <p class="centertext" style="padding:10px;border:1px solid #999;background:#f4f4f4;">
                <?php echo htmlspecialchars($notice, ENT_QUOTES); ?>
            </p>
        <?php endif; ?>

        <?php if ($state === 'form'): ?>
            <?php if ($username !== ''): ?>
                <p>Account: <strong><?php echo htmlspecialchars($username, ENT_QUOTES); ?></strong></p>
            <?php endif; ?>
            <form action="set_password.php" method="post" autocomplete="off">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>" />
                <table>
                    <tr><td>New password:</td>
                        <td><input type="password" name="newpass" style="width:220px;" /></td></tr>
                    <tr><td>Confirm password:</td>
                        <td><input type="password" name="newpass2" style="width:220px;" /></td></tr>
                </table>
                <p style="color:#555;font-size:12px;">At least 10 characters.</p>
                <input type="submit" class="button" name="button_setpass" value="Save password" />
            </form>
        <?php elseif ($state === 'done'): ?>
            <p class="centertext">
                <a href="../pages/databases.php">Go to sign in &raquo;</a>
            </p>
        <?php else: /* invalid */ ?>
            <p class="centertext">
                <a href="forgot_password.php">Request a new reset link &raquo;</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
