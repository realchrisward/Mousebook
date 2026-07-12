<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<!--php code: login-->
<?php
/* issue #14: initialize first-load output variables to prevent PHP 8 undefined-variable warnings on first load */
$xusername = '';
$host = $accessun = $accesspw = null;
// PATCHED: Removed stale $host="{server ip}".
// Initial userbook connection now uses $config['server_host'].

if (isset($_POST['xusername'])) {
	$xusername = ($_POST['xusername'] ?? '');
}
if (isset($_POST['loginstatus'])) {
	$xloginstatus = ($_POST['loginstatus'] ?? '');
}

if (isset($_POST['button_login'])) {
	$xusername = ($_POST['xusername'] ?? '');
	if (isset($_POST['loginstatus'])) {
		$xloginstatus = ($_POST['loginstatus'] ?? '');
	}
}
if (isset($_POST['button_disco'])) {
	$xusername = '';
	$xloginstatus = 'red';
}

$dbname = ($_POST['dbname'] ?? '');

$config = require '../config.php';
if ($config['debug_mode'] == 'True') {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}
$ubname = $config['server_user'];
$ubpass = $config['server_pass'];

// [mb_auth_patched]
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/session.php';
$mb           = mb_session_bootstrap($config);
$xusername    = $mb['username'];
$dbname       = $mb['dbname'];
$host         = $mb['host'];
$accessun     = $mb['accessun'];
$accesspw     = $mb['accesspw'];
$xloginstatus = $mb['loginstatus'];
// Phase F tier gate: neutralise mutating actions for insufficient access.
mb_guard_admin();


$conn = new mysqli($host, $accessun, $accesspw, $dbname);
if ($conn->connect_error) {
	$xloginstatus = 'red';
	echo '<h2 class="centertext"> please connect to the database </h2>';
} else {
	$xloginstatus = 'green';
	$conn->close();
}

// DB operations
$conn = new mysqli($host, $accessun, $accesspw, $dbname);

if (isset($_POST['button_addrole'])) {
	$role = ($_POST['textaddrole'] ?? '');
	$status = ($_POST['textaddstatus'] ?? '');
	$contact = ($_POST['textaddcontact'] ?? '');
	$notes = ($_POST['textaddnotes'] ?? '');
	$sqltext = "INSERT INTO `" . $dbname . "`.`list_cage_role_assignments` (`roleassignment_option`, `roleassignment_statuslist`, `maincontact`, `notes`) VALUES ('" . $role . "', '" . $status . "', '" . $contact . "', '" . $notes . "')";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}

if (isset($_POST['button_deleterole'])) {
	$role = ($_POST['textdelrole'] ?? '');
	$sqltext = "DELETE FROM `" . $dbname . "`.`list_cage_role_assignments` WHERE `roleassignment_option`='" . $role . "';";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}

if (isset($_POST['button_editrole'])) {
	$role = ($_POST['textnewrole'] ?? '');
	$status = ($_POST['textnewstatus'] ?? '');
	$contact = ($_POST['textnewcontact'] ?? '');
	$notes = ($_POST['textnewnotes'] ?? '');
	$sqltext = "UPDATE `" . $dbname . "`.`list_cage_role_assignments` SET `roleassignment_statuslist`='" . $status . "', `maincontact`='" . $contact . "', `notes`='" . $notes . "' WHERE `roleassignment_option`='" . $role . "';";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}

$conn->close();

// Read roles for display
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
if ($conn->connect_error) {
	echo '<h2 class="centertext"> please connect to the database </h2>';
	exit;
}

$sqltext = "Select * from `" . $dbname . "`.`list_cage_role_assignments`;";
$results = $conn->query($sqltext);

$currrole = isset($_POST['role_selection']) ? ($_POST['role_selection'] ?? '') : "";
$currstatus = '';
$curractive = '';
$currcontact = '';
$currnotes = '';

$s_table = '<select id="role_selection" name="role_selection" size=12 class="largelistbox" onclick="showRole(this.value)">';
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	if ($row['roleassignment_option'] === $currrole) {
		$currstatus = $row['roleassignment_statuslist'];
		$curractive = $row['active'];
		$currcontact = $row['maincontact'];
		$currnotes = $row['notes'];
	}
	$s_table .= '<option value="' . $row["roleassignment_option"] . '">' . $row['roleassignment_option'] . '  [' . $row['maincontact'] . ']</option>';
}
$s_table .= '</select>';
$results->close();
$conn->close();
?>

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Manage Roles - <?php echo $dbname; ?></title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>

<body>
	<div id="header">
		<form id="loginbox" action="" method="post">
			<h2 class="centervert" style="position:absolute;top:0px;left:75px;">-Roles/Assignments-</h2>
			<h1 class="centervert" style="position:absolute;top:0px;left:350px;">
				<?php echo $dbname; ?>
				<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
			</h1>
			<button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;width:20px;height:20px;border-radius:10px;position:absolute;top:15px;right:250px;"></button>
			<table style="color:white;font-size:10px;position:absolute;top:0px;right:60px;">
				<tr>
					<th>user:</th>
					<th><input type="text" name="xusername" value="<?php echo htmlspecialchars($xusername); ?>" style="width:100px;font-size:10px;" /></th>
				</tr>
				<tr>
					<td>pass:</td>
					<td><input type="password" name="xpassword" value="" style="width:100px;font-size:10px;" /></td>
				</tr>
			</table>
			<input type=submit id="loginbutton" name="button_login" style="font-size:10px;width:50px;height:20px;position:absolute;top:5px;right:10px;" value="connect" />
			<input type=submit id="discobutton" name="button_disco" style="font-size:10px;width:50px;height:20px;position:absolute;top:25px;right:10px;" value="disco" />
		</form>
	</div>

		<?php require_once __DIR__ . '/../includes/nav.php';
	      mb_render_nav($dbname); ?>

	<div id="right_content" class="centertext">
		<div class="whitespace">
			<form id="role_selection_form" method=post>
				<p>Current Roles:</p>
				<?php echo $s_table; ?>
				<input type=hidden name="dbname" value="<?php echo ($_POST['dbname'] ?? ''); ?>" />
				<input type=hidden name="button_login" value="connect" />

				<h3>Add Role:</h3>
				<table>
					<tr>
						<th>Role</th>
						<th>Status Options</th>
						<th>Main Contact</th>
						<th>Notes</th>
					</tr>
					<tr>
						<td><input type=text id="textaddrole" name="textaddrole"></td>
						<td><input type=text id="textaddstatus" name="textaddstatus"></td>
						<td><input type=text id="textaddcontact" name="textaddcontact"></td>
						<td><input type=text id="textaddnotes" name="textaddnotes"></td>
					</tr>
					<tr>
						<td colspan="4"><input type=submit name="button_addrole"></td>
					</tr>
				</table>

				<h3>Delete Role:</h3>
				<table>
					<tr>
						<td class="label">Del Role:</td>
						<td><input type=text id="textdelrole" name="textdelrole"></td>
						<td><input type=submit name="button_deleterole"></td>
					</tr>
				</table>

				<h3>Edit Role:</h3>
				<script type="text/javascript">
					function showRole(newValue) {
						document.getElementById("role_selection_form").submit();
					}
				</script>
				<table>
					<tr>
						<th>Role</th>
						<th>Status Options</th>
						<th>Main Contact</th>
						<th>Notes</th>
					</tr>
					<tr>
						<td><input type=text id="textnewrole" name="textnewrole" readonly="readonly" value="<?php echo $currrole; ?>"></td>
						<td><input type=text id="textnewstatus" name="textnewstatus" value="<?php echo $currstatus; ?>"></td>
						<td><input type=text id="textnewcontact" name="textnewcontact" value="<?php echo $currcontact; ?>"></td>
						<td><input type=text id="textnewnotes" name="textnewnotes" value="<?php echo $currnotes; ?>"></td>
					</tr>
					<tr>
						<td><input type=submit name="button_editrole"></td>
					</tr>
				</table>
			</form>
		</div>
	</div>

	<div id="footer">
		<p class="righttext">@realchrisward &copy; 2025</p>
	</div>
	<script src="../mousebook.js"></script>
</body>

</html>