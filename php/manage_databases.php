<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<!--php code: login-->
<?php
// =============================================================
// php/manage_databases.php  (Phase G / issue #19)
// ADMIN. Register / edit colony databases in userbook.dbaccess.
//
// Scope note: this REGISTERS an animalbook-style colony db that was
// spun up separately (its schema + MySQL user/grants created out of
// band). Launching/spinning up new DB instances, backups and restores
// are explicitly OUT of scope for this phase.
//
// Runs in the userbook context (reach with dbname=userbook); admin
// gate checks admin-on-userbook; connects with the session's
// write-capable userbook credentials.
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

$mb           = mb_session_bootstrap($config);
$xusername    = $mb['username'];
$dbname       = $mb['dbname'] !== '' ? $mb['dbname'] : 'userbook';
$host         = $mb['host'];
$accessun     = $mb['accessun'];
$accesspw     = $mb['accesspw'];
$xloginstatus = $mb['loginstatus'];

mb_guard_admin();

$sqlstatus = '';

$conn = new mysqli((string)$host, (string)$accessun, (string)$accesspw, $dbname);
if ($conn->connect_error) {
    $xloginstatus = 'red';
    echo '<h2 class="centertext"> please connect to the user database </h2>';
} else {
    $xloginstatus = 'green';
}

// Fields shared by add/update (all varchar in dbaccess).
$fields = ['db_name', 'db_host', 'db_accessun', 'db_accesspw', 'db_formurl',
           'db_subject_plural', 'db_subject_single', 'db_guide1_title', 'db_guide1_url'];

if (!$conn->connect_error) {

    // ── Register a new colony ───────────────────────────────────
    if (isset($_POST['button_adddb'])) {
        $v = [];
        foreach ($fields as $f) { $v[$f] = trim((string)($_POST[$f] ?? '')); }
        if ($v['db_name'] === '' || $v['db_accessun'] === '' || $v['db_formurl'] === '') {
            $sqlstatus = 'failed: db_name, db_accessun and db_formurl are required';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO dbaccess
                   (db_name, db_host, db_accessun, db_accesspw, db_formurl,
                    db_subject_plural, db_subject_single, db_guide1_title, db_guide1_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssssssss',
                $v['db_name'], $v['db_host'], $v['db_accessun'], $v['db_accesspw'],
                $v['db_formurl'], $v['db_subject_plural'], $v['db_subject_single'],
                $v['db_guide1_title'], $v['db_guide1_url']);
            $sqlstatus = $stmt->execute()
                ? 'registered "' . htmlspecialchars($v['db_name'], ENT_QUOTES) . '"'
                : ('failed: ' . $conn->error);
            $stmt->close();
        }
    }

    // ── Update an existing colony ───────────────────────────────
    if (isset($_POST['button_updatedb'])) {
        $db_no = (int)($_POST['db_no'] ?? 0);
        $v = [];
        foreach ($fields as $f) { $v[$f] = trim((string)($_POST[$f] ?? '')); }
        if ($db_no <= 0 || $v['db_name'] === '') {
            $sqlstatus = 'failed: missing db row or db_name';
        } else {
            $stmt = $conn->prepare(
                "UPDATE dbaccess SET db_name=?, db_host=?, db_accessun=?, db_accesspw=?,
                        db_formurl=?, db_subject_plural=?, db_subject_single=?,
                        db_guide1_title=?, db_guide1_url=? WHERE db_no=?"
            );
            $stmt->bind_param('sssssssssi',
                $v['db_name'], $v['db_host'], $v['db_accessun'], $v['db_accesspw'],
                $v['db_formurl'], $v['db_subject_plural'], $v['db_subject_single'],
                $v['db_guide1_title'], $v['db_guide1_url'], $db_no);
            $sqlstatus = $stmt->execute() ? 'updated' : ('failed: ' . $conn->error);
            $stmt->close();
        }
    }

    // ── Unregister a colony (does NOT drop the actual database) ──
    if (isset($_POST['button_deletedb'])) {
        $db_no = (int)($_POST['db_no'] ?? 0);
        if ($db_no > 0) {
            $stmt = $conn->prepare("DELETE FROM dbaccess WHERE db_no = ?");
            $stmt->bind_param('i', $db_no);
            $sqlstatus = $stmt->execute() ? 'unregistered' : ('failed: ' . $conn->error);
            $stmt->close();
        }
    }
}

// ── Gather existing rows ────────────────────────────────────────
$rows = [];
if (!$conn->connect_error) {
    $res = $conn->query(
        "SELECT db_no, db_name, db_host, db_accessun, db_accesspw, db_formurl,
                db_subject_plural, db_subject_single, db_guide1_title, db_guide1_url
           FROM dbaccess ORDER BY db_name"
    );
    if ($res instanceof mysqli_result) {
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $res->close();
    }
    $conn->close();
}

$hdb = htmlspecialchars($dbname, ENT_QUOTES);

// Small helper to print an editable cell input.
function mb_dbcell($name, $val) {
    return '<input type="text" name="' . $name . '" value="'
         . htmlspecialchars((string)$val, ENT_QUOTES) . '" style="width:110px;" />';
}
?>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Manage Databases - <?php echo $hdb; ?></title>
    <link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div id="header">
        <form id="loginbox" action="" method="post">
            <h2 class="centervert" style="position:absolute;top:0px;left:75px;">-Manage Databases-</h2>
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
        <?php if ($sqlstatus !== ''): ?>
            <p class="centertext"><em><?php echo htmlspecialchars($sqlstatus, ENT_QUOTES); ?></em></p>
        <?php endif; ?>

        <h3>Register a colony database</h3>
        <p style="color:#555;font-size:12px;">The colony db, its MySQL user and grants must already exist.
           db_accessun / db_accesspw are the credentials Mousebook uses to reach that colony.</p>
        <form action="manage_databases.php" method="post" autocomplete="off">
            <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
            <table>
                <tr><td>db_name</td><td><?php echo mb_dbcell('db_name', ''); ?></td>
                    <td>db_host</td><td><?php echo mb_dbcell('db_host', ''); ?></td></tr>
                <tr><td>db_accessun</td><td><?php echo mb_dbcell('db_accessun', ''); ?></td>
                    <td>db_accesspw</td><td><?php echo mb_dbcell('db_accesspw', ''); ?></td></tr>
                <tr><td>db_formurl</td><td><?php echo mb_dbcell('db_formurl', ''); ?></td>
                    <td>subject (plural)</td><td><?php echo mb_dbcell('db_subject_plural', ''); ?></td></tr>
                <tr><td>subject (single)</td><td><?php echo mb_dbcell('db_subject_single', ''); ?></td>
                    <td>guide title</td><td><?php echo mb_dbcell('db_guide1_title', ''); ?></td></tr>
                <tr><td>guide url</td><td colspan="3"><input type="text" name="db_guide1_url" style="width:100%;" /></td></tr>
            </table>
            <input type="submit" class="button" name="button_adddb" value="Register database" />
        </form>

        <h3>Registered databases</h3>
        <?php foreach ($rows as $r): ?>
            <form action="manage_databases.php" method="post" style="border:1px solid #ccc;padding:6px;margin-bottom:8px;">
                <input type=hidden name="dbname" value="<?php echo $hdb; ?>" />
                <input type=hidden name="db_no" value="<?php echo (int)$r['db_no']; ?>" />
                <table>
                    <tr><td>db_name</td><td><?php echo mb_dbcell('db_name', $r['db_name']); ?></td>
                        <td>db_host</td><td><?php echo mb_dbcell('db_host', $r['db_host']); ?></td></tr>
                    <tr><td>db_accessun</td><td><?php echo mb_dbcell('db_accessun', $r['db_accessun']); ?></td>
                        <td>db_accesspw</td><td><?php echo mb_dbcell('db_accesspw', $r['db_accesspw']); ?></td></tr>
                    <tr><td>db_formurl</td><td><?php echo mb_dbcell('db_formurl', $r['db_formurl']); ?></td>
                        <td>subject (plural)</td><td><?php echo mb_dbcell('db_subject_plural', $r['db_subject_plural']); ?></td></tr>
                    <tr><td>subject (single)</td><td><?php echo mb_dbcell('db_subject_single', $r['db_subject_single']); ?></td>
                        <td>guide title</td><td><?php echo mb_dbcell('db_guide1_title', $r['db_guide1_title']); ?></td></tr>
                    <tr><td>guide url</td><td colspan="3">
                        <input type="text" name="db_guide1_url" value="<?php echo htmlspecialchars((string)$r['db_guide1_url'], ENT_QUOTES); ?>" style="width:100%;" /></td></tr>
                </table>
                <input type="submit" class="button" name="button_updatedb" value="Save changes" />
                <input type="submit" class="button" name="button_deletedb" value="Unregister"
                       onclick="return confirm('Unregister this colony from Mousebook? (The database itself is not dropped.)');" />
            </form>
        <?php endforeach; ?>
    </div>
</body>
</html>
