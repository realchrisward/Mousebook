<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<!--php code: login-->
<?php
// =============================================================
// php/manage_users.php  (Phase G / issue #19)
// ADMIN. Manage userbook accounts and per-colony access.
//   * Provision a new user (username + email) → invite token + email
//   * Grant / change / revoke a user's access tier on a colony db
//   * Admin-initiated password reset (72h token + email)
//   * Update a user's email
//
// Runs in the userbook context: reach it with dbname=userbook so the
// Phase F session tier gate checks admin-on-userbook. Connects with the
// session's userbook credentials (the write-capable dbaccess creds).
// =============================================================

$xusername = ''; $xpassword = '';
$host = $accessun = $accesspw = null;

$dbname = ($_POST['dbname'] ?? 'userbook');

$config = require '../config.php';
if (($config['debug_mode'] ?? 'False') == 'True') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/usertoken.php';
require_once __DIR__ . '/../includes/mail.php';

$mb           = mb_session_bootstrap($config);
$xusername    = $mb['username'];
$dbname       = $mb['dbname'] !== '' ? $mb['dbname'] : 'userbook';
$host         = $mb['host'];
$accessun     = $mb['accessun'];
$accesspw     = $mb['accesspw'];
$xloginstatus = $mb['loginstatus'];

// Admin gate (userbook context). Neutralises mutating buttons if not admin.
mb_guard_admin();

if (!defined('MB_INVITE_TTL_DEFAULT')) { define('MB_INVITE_TTL_DEFAULT', 72); } // hours
if (!defined('MB_RESET_TTL_ADMIN'))    { define('MB_RESET_TTL_ADMIN', 72 * 3600); }

$TIERS = ['read-only', 'editor', 'admin'];

$sqlstatus = '';
$invite_link = '';   // shown to the admin after provisioning (email fallback)

// Connect to userbook with the session's write-capable credentials.
$conn = new mysqli((string)$host, (string)$accessun, (string)$accesspw, $dbname);
if ($conn->connect_error) {
    $xloginstatus = 'red';
    echo '<h2 class="centertext"> please connect to the user database </h2>';
} else {
    $xloginstatus = 'green';
}

// Helper to email an invite/reset link.
$send_link = function (string $to, string $subject, string $intro, string $link, string $expiry)
             use ($config): void {
    $safe = htmlspecialchars($link, ENT_QUOTES);
    $body = '<p>' . $intro . '</p>'
          . '<p><a href="' . $safe . '">' . $subject . '</a></p>'
          . '<p>Or paste this link into your browser:<br>' . $safe . '</p>'
          . '<p>This link expires in ' . htmlspecialchars($expiry, ENT_QUOTES)
          . ' and can be used once.</p>';
    $err = '';
    if (!mb_send_relay_mail($config, $to, $subject, $body, $err)) {
        error_log('manage_users mail failed: ' . $err);
    }
};

if (!$conn->connect_error) {

    // ── Provision a new user + invite ───────────────────────────
    if (isset($_POST['button_adduser'])) {
        $newuser  = trim((string)($_POST['newuser'] ?? ''));
        $newemail = trim((string)($_POST['newemail'] ?? ''));
        $ttl_h    = (int)($_POST['invite_ttl'] ?? MB_INVITE_TTL_DEFAULT);
        if ($ttl_h < 1)    { $ttl_h = MB_INVITE_TTL_DEFAULT; }
        if ($ttl_h > 720)  { $ttl_h = 720; } // cap at 30 days

        if ($newuser === '') {
            $sqlstatus = 'failed: username is required';
        } elseif ($newemail !== '' && filter_var($newemail, FILTER_VALIDATE_EMAIL) === false) {
            $sqlstatus = 'failed: invalid email address';
        } else {
            // Placeholder hash that can never verify until the invite is used.
            $pending = 'INVITED-PENDING';
            $stmt = $conn->prepare(
                "INSERT INTO userpass (user_name, user_pass) VALUES (?, ?)"
            );
            $stmt->bind_param('ss', $newuser, $pending);
            if ($stmt->execute()) {
                $new_id = (int)$conn->insert_id;
                $stmt->close();

                if ($newemail !== '') {
                    $d = $conn->prepare(
                        "INSERT INTO userdetail (user_idno, user_email) VALUES (?, ?)"
                    );
                    $d->bind_param('is', $new_id, $newemail);
                    $d->execute();
                    $d->close();
                }

                $raw = mb_token_generate($conn, $new_id, 'invite', $ttl_h * 3600, $xusername);
                if ($raw !== null) {
                    $base = rtrim((string)($config['base_url'] ?? ''), '/');
                    $invite_link = $base . '/php/set_password.php?token=' . $raw;
                    if ($newemail !== '') {
                        $send_link($newemail, 'Mousebook invitation',
                            'You have been invited to Mousebook. Set your password to activate your account (username: '
                            . htmlspecialchars($newuser, ENT_QUOTES) . ').',
                            $invite_link, $ttl_h . ' hours');
                    }
                    $sqlstatus = 'user "' . htmlspecialchars($newuser, ENT_QUOTES)
                               . '" created; invite '
                               . ($newemail !== '' ? 'emailed' : 'link generated')
                               . ' (expires in ' . $ttl_h . 'h)';
                } else {
                    $sqlstatus = 'user created but invite token generation failed';
                }
            } else {
                $sqlstatus = 'failed: ' . $conn->error;
                $stmt->close();
            }
        }
    }

    // ── Grant / change access tier on a colony ──────────────────
    if (isset($_POST['button_setaccess'])) {
        $uid   = (int)($_POST['user_idno'] ?? 0);
        $db    = (string)($_POST['access_db'] ?? '');
        $tier  = (string)($_POST['access_tier'] ?? '');
        if (!in_array($tier, $TIERS, true)) {
            $sqlstatus = 'failed: invalid tier';
        } elseif ($uid <= 0 || $db === '') {
            $sqlstatus = 'failed: user and database are required';
        } else {
            // Upsert: update existing link, else insert.
            $sel = $conn->prepare(
                "SELECT link_number FROM userdbaccess WHERE user_idno = ? AND db_name = ? LIMIT 1"
            );
            $sel->bind_param('is', $uid, $db);
            $sel->execute();
            $sel->bind_result($link_no);
            $exists = $sel->fetch();
            $sel->close();

            if ($exists) {
                $u = $conn->prepare("UPDATE userdbaccess SET db_accesstier = ? WHERE link_number = ?");
                $u->bind_param('si', $tier, $link_no);
                $ok = $u->execute();
                $u->close();
            } else {
                $i = $conn->prepare(
                    "INSERT INTO userdbaccess (user_idno, db_name, db_accesstier) VALUES (?, ?, ?)"
                );
                $i->bind_param('iss', $uid, $db, $tier);
                $ok = $i->execute();
                $i->close();
            }
            $sqlstatus = $ok ? 'access updated' : ('failed: ' . $conn->error);
        }
    }

    // ── Revoke an access link ───────────────────────────────────
    if (isset($_POST['button_revokeaccess'])) {
        $link = (int)($_POST['link_number'] ?? 0);
        if ($link > 0) {
            $d = $conn->prepare("DELETE FROM userdbaccess WHERE link_number = ?");
            $d->bind_param('i', $link);
            $sqlstatus = $d->execute() ? 'access revoked' : ('failed: ' . $conn->error);
            $d->close();
        }
    }

    // ── Update a user's email ───────────────────────────────────
    if (isset($_POST['button_setemail'])) {
        $uid   = (int)($_POST['user_idno'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        if ($uid <= 0) {
            $sqlstatus = 'failed: no user';
        } elseif ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $sqlstatus = 'failed: invalid email';
        } else {
            $sel = $conn->prepare("SELECT 1 FROM userdetail WHERE user_idno = ? LIMIT 1");
            $sel->bind_param('i', $uid);
            $sel->execute();
            $sel->store_result();
            $has = $sel->num_rows > 0;
            $sel->close();
            if ($has) {
                $u = $conn->prepare("UPDATE userdetail SET user_email = ? WHERE user_idno = ?");
                $u->bind_param('si', $email, $uid);
                $ok = $u->execute(); $u->close();
            } else {
                $i = $conn->prepare("INSERT INTO userdetail (user_idno, user_email) VALUES (?, ?)");
                $i->bind_param('is', $uid, $email);
                $ok = $i->execute(); $i->close();
            }
            $sqlstatus = $ok ? 'email updated' : ('failed: ' . $conn->error);
        }
    }

    // ── Admin-initiated password reset (72h) ────────────────────
    if (isset($_POST['button_resetpass'])) {
        $uid = (int)($_POST['user_idno'] ?? 0);
        if ($uid > 0) {
            $sel = $conn->prepare(
                "SELECT p.user_name, d.user_email FROM userpass p
                   LEFT JOIN userdetail d ON d.user_idno = p.user_idno
                  WHERE p.user_idno = ? LIMIT 1"
            );
            $sel->bind_param('i', $uid);
            $sel->execute();
            $sel->bind_result($u_name, $u_email);
            $found = $sel->fetch();
            $sel->close();

            if ($found) {
                $raw = mb_token_generate($conn, $uid, 'reset', MB_RESET_TTL_ADMIN, $xusername);
                if ($raw !== null) {
                    $base = rtrim((string)($config['base_url'] ?? ''), '/');
                    $invite_link = $base . '/php/set_password.php?token=' . $raw;
                    if ($u_email) {
                        $send_link((string)$u_email, 'Mousebook password reset',
                            'A password reset was initiated for your Mousebook account ('
                            . htmlspecialchars((string)$u_name, ENT_QUOTES) . ').',
                            $invite_link, '72 hours');
                        $sqlstatus = 'reset link emailed to ' . htmlspecialchars((string)$u_name, ENT_QUOTES);
                    } else {
                        $sqlstatus = 'reset link generated (no email on file — copy it below)';
                    }
                } else {
                    $sqlstatus = 'failed: token generation error';
                }
            } else {
                $sqlstatus = 'failed: user not found';
            }
        }
    }
}

// ── Gather data for display ─────────────────────────────────────
$users = [];
$dbnames = [];
if (!$conn->connect_error) {
    $res = $conn->query(
        "SELECT p.user_idno, p.user_name,
                (p.user_pass = 'INVITED-PENDING') AS pending,
                d.user_email
           FROM userpass p
           LEFT JOIN userdetail d ON d.user_idno = p.user_idno
          ORDER BY p.user_name"
    );
    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_assoc()) {
            $row['access'] = [];
            $users[(int)$row['user_idno']] = $row;
        }
        $res->close();
    }
    // Per-user access rows.
    $ares = $conn->query(
        "SELECT user_idno, link_number, db_name, db_accesstier
           FROM userdbaccess ORDER BY db_name"
    );
    if ($ares instanceof mysqli_result) {
        while ($a = $ares->fetch_assoc()) {
            $uid = (int)$a['user_idno'];
            if (isset($users[$uid])) { $users[$uid]['access'][] = $a; }
        }
        $ares->close();
    }
    // Colony db list for the assignment dropdown.
    $dres = $conn->query("SELECT db_name FROM dbaccess ORDER BY db_name");
    if ($dres instanceof mysqli_result) {
        while ($d = $dres->fetch_assoc()) { $dbnames[] = $d['db_name']; }
        $dres->close();
    }
    $conn->close();
}

$denied = $GLOBALS['mb_denied_notice'] ?? '';
$hdb = htmlspecialchars($dbname, ENT_QUOTES);
?>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Manage Users - <?php echo $hdb; ?></title>
    <link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div id="header">
        <form id="loginbox" action="" method="post">
            <h2 class="centervert" style="position:absolute;top:0px;left:75px;">-Manage Users-</h2>
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

    <div id="main">
        <?php if ($denied !== ''): ?>
            <p class="centertext" style="color:#a00;"><?php echo htmlspecialchars($denied, ENT_QUOTES); ?></p>
        <?php endif; ?>
        <?php if ($sqlstatus !== ''): ?>
            <p class="centertext"><em><?php echo htmlspecialchars($sqlstatus, ENT_QUOTES); ?></em></p>
        <?php endif; ?>
        <?php if ($invite_link !== ''): ?>
            <p class="centertext" style="word-break:break-all;">
                Link (hand off if email is unavailable):<br>
                <code><?php echo htmlspecialchars($invite_link, ENT_QUOTES); ?></code>
            </p>
        <?php endif; ?>

        <h3>Add a user</h3>
        <form action="manage_users.php" method="post" autocomplete="off">
            <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
            username: <input type="text" name="newuser" />
            email: <input type="text" name="newemail" />
            invite valid (hours): <input type="number" name="invite_ttl" value="72" min="1" max="720" style="width:70px;" />
            <input type="submit" class="button" name="button_adduser" value="Create + invite" />
        </form>

        <h3>Users</h3>
        <table border="1" cellpadding="4" cellspacing="0">
            <tr><th>User</th><th>Email</th><th>Access (db : tier)</th><th>Actions</th></tr>
            <?php foreach ($users as $uid => $u): ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($u['user_name'], ENT_QUOTES); ?>
                    <?php if (!empty($u['pending'])): ?><br><small style="color:#a60;">(invite pending)</small><?php endif; ?>
                </td>
                <td>
                    <form action="manage_users.php" method="post" style="margin:0;">
                        <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
                        <input type=hidden name="user_idno" value="<?php echo (int)$uid; ?>" />
                        <input type="text" name="email" value="<?php echo htmlspecialchars((string)($u['user_email'] ?? ''), ENT_QUOTES); ?>" style="width:150px;" />
                        <input type="submit" class="button" name="button_setemail" value="save" />
                    </form>
                </td>
                <td>
                    <?php foreach ($u['access'] as $a): ?>
                        <form action="manage_users.php" method="post" style="margin:0;display:inline-block;">
                            <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
                            <input type=hidden name="link_number" value="<?php echo (int)$a['link_number']; ?>" />
                            <?php echo htmlspecialchars($a['db_name'], ENT_QUOTES); ?>:<strong><?php echo htmlspecialchars($a['db_accesstier'], ENT_QUOTES); ?></strong>
                            <input type="submit" class="button" name="button_revokeaccess" value="x" title="revoke" />
                        </form><br>
                    <?php endforeach; ?>
                    <form action="manage_users.php" method="post" style="margin-top:4px;">
                        <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
                        <input type=hidden name="user_idno" value="<?php echo (int)$uid; ?>" />
                        <select name="access_db">
                            <?php foreach ($dbnames as $dn): ?>
                                <option value="<?php echo htmlspecialchars($dn, ENT_QUOTES); ?>"><?php echo htmlspecialchars($dn, ENT_QUOTES); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="access_tier">
                            <?php foreach ($TIERS as $t): ?>
                                <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="button" name="button_setaccess" value="grant/set" />
                    </form>
                </td>
                <td>
                    <form action="manage_users.php" method="post" style="margin:0;">
                        <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
                        <input type=hidden name="user_idno" value="<?php echo (int)$uid; ?>" />
                        <input type="submit" class="button" name="button_resetpass" value="send reset" />
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
