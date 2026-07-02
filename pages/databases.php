<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Home - Databases</title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />
	<!--php code-->
	<?php
	// ── Config ─────────────────────────────────────────
	$config = require '../config.php';

	if ($config['debug_mode'] == 'True') {
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
	}

	// ── Auth helper ────────────────────────────────────
	// Uses password_verify() against bcrypt hashes —
	// replaces the old plain-text WHERE user_pass=... SQL.
	require_once __DIR__ . '/../includes/auth.php';

	// ── Test server connection (status dot only) ───────
	$host   = $config['server_ip'];
	$ubname = $config['server_user'];
	$ubpass = $config['server_pass'];

	$conn = new mysqli($host, $ubname, $ubpass, 'userbook');
	$xloginstatus = $conn->connect_error ? 'red' : 'green';
	$conn->close();

	// ── Collect posted credentials ─────────────────────
	$xusername = isset($_POST['xusername']) ? $_POST['xusername'] : '';
	$xpassword = isset($_POST['xpassword']) ? $_POST['xpassword'] : '';

	if (isset($_POST['button_disco'])) {
		$xusername = '';
		$xpassword = '';
	}

	// ── Look up accessible databases ───────────────────
	// mb_get_user_databases() verifies the password hash
	// and returns all colony DBs this user can access.
	$dbaccesstext = '';
	if ($xusername !== '' && $xpassword !== '') {
		$databases = mb_get_user_databases($config, $xusername, $xpassword);
		foreach ($databases as $row) {
			$dbaccesstext .=
				"<form id='dbaccessform' action='" . htmlspecialchars($row['db_formurl']) . "' method='post' target='_blank'>"
				. "<input type='hidden' name='xusername' value='" . htmlspecialchars($xusername) . "'>"
				. "<input type='hidden' name='xpassword' value='" . htmlspecialchars($xpassword) . "'>"
				. "<input type='hidden' name='accessun'  value='" . htmlspecialchars($row['db_accessun']) . "'>"
				. "<input type='hidden' name='accesspw'  value='" . htmlspecialchars($row['db_accesspw']) . "'>"
				. "<input type='hidden' name='dbname'    value='" . htmlspecialchars($row['db_name']) . "'>"
				. "<input type='submit' class='dbbutton' name='" . htmlspecialchars($row['db_name']) . "' value='" . htmlspecialchars($row['db_name']) . "'>"
				. "</form>";
		}
	}
	?>

</head>

<body>
	<img class="logo" src="../images/logo.jpg" alt="Mouse Metabolism and Phenotyping Core" width="15%">
	<div class="content-center">

		<h1 class="section">
			Databases
		</h1>

		<form id="loginbox" action="" method="post">
			<table>
				<tr>
					<th>Server Connection Status</th>
					<td>
						<button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
                                                width:20px;height:20px;border-radius:10px;"></button>
					</td>
				</tr>
			</table>
			<table>
				<tr>
					<th>USERNAME</th>
					<td>
						<input type="text" name="xusername" value="<?php echo htmlspecialchars($xusername); ?>" />
					</td>
				</tr>
				<tr>
					<th>PASSWORD</th>
					<td>
						<input type="password" name="xpassword" value="<?php echo htmlspecialchars($xpassword); ?>" />
					</td>
				</tr>

				<tr>
					<th colspan=2>
						<input type=submit id="loginbutton" name="button_login"
							style="font-size:1em;width:100%;"
							value="connect" />
					</th>
				</tr>

				<tr>
					<th colspan=2>
						<input type=submit id="discobutton" name="button_disco"
							style="font-size:1em;width:100%;"
							value="disco" />
					</th>
				</tr>
			</table>

		</form>
		<table>
			<tr>
				<td>
					<?php echo $dbaccesstext; ?>
				</td>
			</tr>
		</table>

	</div>

	<div>
		<p class="footer">
			@realchrisward &copy; 2025
		</p>
	</div>

	<script src="../mousebook.js"></script>
</body>

</html>