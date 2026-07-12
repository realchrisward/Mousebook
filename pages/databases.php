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
	require_once __DIR__ . '/../includes/session.php';
	mb_session_start();

	// ── Test server connection (status dot only) ───────
	$host   = $config['server_ip'];
	$ubname = $config['server_user'];
	$ubpass = $config['server_pass'];

	$conn = new mysqli($host, $ubname, $ubpass, mb_userbook_db($config));
	$xloginstatus = $conn->connect_error ? 'red' : 'green';
	if (!$conn->connect_error) {
		$conn->close();
	}

	// ── Logout: clear the whole session (all colonies) ─
	if (isset($_POST['button_disco'])) {
		$_SESSION = [];
		if (ini_get('session.use_cookies')) {
			$cp = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$cp['path'],
				$cp['domain'],
				$cp['secure'],
				$cp['httponly']
			);
		}
		session_destroy();
		mb_session_start(); // fresh empty session for this render
	}

	// ── Collect posted credentials ─────────────────────
	$xusername = isset($_POST['xusername']) ? $_POST['xusername'] : '';
	$xpassword = isset($_POST['xpassword']) ? $_POST['xpassword'] : '';

	// ── Look up accessible databases ───────────────────
	// mb_get_user_databases() verifies the password hash and returns
	// every colony DB this user can access. On success we authenticate
	// ONCE here and stash each colony's db-access credentials + tier in
	// the session, keyed by db name. The colony buttons below then carry
	// only the (non-sensitive) db name — no password, no db credentials
	// are emitted into the page. The colony pages read their connection
	// details from the session (same-origin) or, if login and colony are
	// ever hosted separately, re-establish the session from a one-time
	// credential post via the shared bootstrap fallback.
	$dbaccesstext = '';
	if ($xusername !== '' && $xpassword !== '' && !isset($_POST['button_disco'])) {
		$databases = mb_get_user_databases($config, $xusername, $xpassword);
		if (!empty($databases)) {
			session_regenerate_id(true);          // anti-fixation on login
			$_SESSION['mb_user'] = $xusername;
			foreach ($databases as $row) {
				$db = $row['db_name'];
				$_SESSION['mb_dbaccess'][$db] = [
					'host'           => $row['db_host']
						?? ($config['server_host'] ?? $config['server_ip']),
					'accessun'       => $row['db_accessun'],
					'accesspw'       => $row['db_accesspw'],
					'tier'           => mb_normalize_tier($row['db_accesstier'] ?? ''),
					'subject_plural' => $row['db_subject_plural'] ?? '',
					'subject_single' => $row['db_subject_single'] ?? '',
					'guide1_title'   => $row['db_guide1_title'] ?? '',
					'guide1_url'     => $row['db_guide1_url'] ?? '',
				];
				$formurl  = htmlspecialchars($row['db_formurl']);
				$dbname_h = htmlspecialchars($db);
				$dbaccesstext .=
					"<form id='dbaccessform' action='" . $formurl . "' method='post' target='_blank'>"
					. "<input type='hidden' name='dbname' value='" . $dbname_h . "'>"
					. "<input type='hidden' name='button_login' value='connect'>"
					. "<input type='submit' class='dbbutton' name='" . $dbname_h . "' value='" . $dbname_h . "'>"
					. "</form>";
			}
		}
	}
	?>

</head>

<body>
	<img class="logo" src="../images/logo.png" alt="Mouse Metabolism and Phenotyping Core" width="15%">
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
						<input type="password" name="xpassword" value="" />
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

				<tr>
					<td colspan=2 style="text-align:center;font-size:0.85em;">
						<a href="../php/forgot_password.php">Forgot password?</a>
					</td>
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