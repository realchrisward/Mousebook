<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<!--php code: login-->
<?php
// =============================================================
// php/change_password.php  (Phase G / issue #19)
// Self-service password change for an ALREADY-LOGGED-IN user.
//
// Not admin-gated: any authenticated user may change THEIR OWN
// password. Identity comes from the session (mb_user), never from a
// form field, so a user can only change their own account. The
// current password must be re-verified (defends an unlocked session).
//
// Reachable from any colony context via the shared nav; identifies the
// user from the session and writes to userbook through the bootstrapped
// write connection (same path as set_password.php).
// =============================================================

$xusername = '';
$dbname = ($_POST['dbname'] ?? '');

$config = require '../config.php';
if (($config['debug_mode'] ?? 'False') == 'True') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/usertoken.php';

$mb           = mb_session_bootstrap($config);
$xusername    = $mb['username'];
$dbname       = $mb['dbname'];
$xloginstatus = $mb['loginstatus'];

$notice = '';
$done   = false;

if (isset($_POST['button_changepass'])) {
    if ($xusername === '') {
        $notice = 'Your session has expired — please sign in again.';
    } else {
        $cur = (string)($_POST['curpass'] ?? '');
        $n1  = (string)($_POST['newpass'] ?? '');
        $n2  = (string)($_POST['newpass2'] ?? '');
        $perr = mb_password_policy_error($n1);
        if ($cur === '' || $n1 === '') {
            $notice = 'All fields are required.';
        } elseif ($n1 !== $n2) {
            $notice = 'The two new passwords do not match.';
        } elseif ($perr !== '') {
            $notice = $perr;
        } elseif ($n1 === $cur) {
            $notice = 'The new password must differ from the current one.';
        } else {
            $dberr = '';
            $conn = mb_userbook_conn($config, $dberr);
            if ($conn === null) {
                $notice = 'The user database is temporarily unavailable. Please try again later.';
            } else {
                // Re-verify the current password for THIS session's user.
                $stmt = $conn->prepare("SELECT user_idno, user_pass FROM userpass WHERE user_name = ? LIMIT 1");
                $uid = 0; $hash = '';
                if ($stmt) {
                    $stmt->bind_param('s', $xusername);
                    $stmt->execute();
                    $stmt->bind_result($u_id, $u_hash);
                    if ($stmt->fetch()) { $uid = (int)$u_id; $hash = (string)$u_hash; }
                    $stmt->close();
                }
                if ($uid <= 0 || !password_verify($cur, $hash)) {
                    $notice = 'Your current password is incorrect.';
                } else {
                    $newhash = password_hash($n1, PASSWORD_BCRYPT);
                    $upd = $conn->prepare("UPDATE userpass SET user_pass = ? WHERE user_idno = ?");
                    if ($upd) {
                        $upd->bind_param('si', $newhash, $uid);
                        if ($upd->execute()) {
                            $done = true;
                            $notice = 'Your password has been changed.';
                        } else {
                            $notice = 'Could not save the new password. Please try again.';
                        }
                        $upd->close();
                    } else {
                        $notice = 'Could not save the new password. Please try again.';
                    }
                }
                $conn->close();
            }
        }
    }
}

$hdb = htmlspecialchars($dbname, ENT_QUOTES);
?>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Change Password - <?php echo $hdb; ?></title>
    <link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div id="header">
        <form id="loginbox" action="" method="post">
            <h2 class="centervert" style="position:absolute;top:0px;left:75px;">-Change Password-</h2>
            <h1 class="centervert" style="position:absolute;top:0px;left:350px;">
                <?php echo $hdb; ?>
                <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
            </h1>
            <button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
                width:20px;height:20px;border-radius:10px;position:absolute;top:15px;right:250px;"></button>
            <table style="color:white;font-size:10px;position:absolute;top:0px;right:60px;">
                <tr><th>user:</th>
                    <th><input type="text" name="xusername" value="<?php echo htmlspecialchars($xusername, ENT_QUOTES); ?>" style="width:100px;font-size:10px;" /></th></tr>
                <tr><td>pass:</td>
                    <td><input type="password" name="xpassword" value="" style="width:100px;font-size:10px;" /></td></tr>
            </table>
            <input type=submit id="loginbutton" name="button_login"
                style="font-size:10px;width:50px;height:20px;position:absolute;top:5px;right:10px;" value="connect" />
            <input type=submit id="discobutton" name="button_disco"
                style="font-size:10px;width:50px;height:20px;position:absolute;top:25px;right:10px;" value="disco" />
        </form>
    </div>

    <?php require_once __DIR__ . '/../includes/nav.php'; mb_render_nav($dbname); ?>

    <div id="main" style="max-width:520px;">
        <?php if ($notice !== ''): ?>
            <p class="centertext" style="padding:10px;border:1px solid #999;background:#f4f4f4;">
                <?php echo htmlspecialchars($notice, ENT_QUOTES); ?>
            </p>
        <?php endif; ?>

        <?php if ($xusername === ''): ?>
            <p class="centertext">
                You must be signed in to change your password.
                <a href="../pages/databases.php">Go to sign in &raquo;</a>
            </p>
        <?php elseif (!$done): ?>
            <p>Signed in as <strong><?php echo htmlspecialchars($xusername, ENT_QUOTES); ?></strong>.</p>
            <form action="change_password.php" method="post" autocomplete="off">
                <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
                <table>
                    <tr><td>Current password:</td>
                        <td><input type="password" name="curpass" style="width:220px;" /></td></tr>
                    <tr><td>New password:</td>
                        <td><input type="password" name="newpass" style="width:220px;" /></td></tr>
                    <tr><td>Confirm new password:</td>
                        <td><input type="password" name="newpass2" style="width:220px;" /></td></tr>
                </table>
                <p style="color:#555;font-size:12px;">At least 10 characters; must differ from the current one.</p>
                <input type="submit" class="button" name="button_changepass" value="Change password" />
            </form>
        <?php else: ?>
            <p class="centertext"><a href="../pages/databases.php">Back to Mousebook &raquo;</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
