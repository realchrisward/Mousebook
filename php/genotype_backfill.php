<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
	<?php
/*
 * genotype_backfill.php — M1-E (issue #28): admin genotype-backfill repair surface.
 *
 * Purpose (see AUDIT_M1B_allele_line_sideeffects.md, side-effect #1): when an
 * allele group is added to a line that already has animals, those existing
 * animals get NO `table_genotypes` row for the new group (only animals created
 * afterward are genotyped for it). This admin tool finds live animals on a line
 * that are missing a genotype row for a currently-assigned allele group and lets
 * an admin fill each gap.
 *
 * IDEMPOTENT: `table_genotypes` has no unique key on (animalautono, allelegroup),
 * so the handler re-checks non-existence per gap before inserting — re-submitting
 * or concurrent use never creates a duplicate row. Editing EXISTING genotype rows
 * is out of scope (that belongs to manage_animals); this surface only fills gaps.
 */
	$host = $accessun = $accesspw = null;
	$backfill_status = null;
	$line_selection = null;

	$xusername = ($_POST['xusername'] ?? '');
	if (isset($_POST['button_login'])) {
		$xusername = ($_POST['xusername'] ?? '');
		$xloginstatus = ($_POST['loginstatus'] ?? '');
	}
	if (isset($_POST['button_disco'])) {
		$xusername = '';
		$xloginstatus = 'red';
	}
	$dbname = ($_POST['dbname'] ?? '');

	// collect config values
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
	// Admin gate: this is a repair surface. Neutralises button_backfill for
	// non-admins (and surfaces the denied notice via mb_render_nav()).
	mb_guard_admin();

	// connection test (matches the app's per-page pattern)
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	if ($conn->connect_error) {
		$xloginstatus = 'red';
		echo '<h2 class="centertext"> please connect to the database </h2>';
	} else {
		$xloginstatus = 'green';
		$conn->close();
	}

	$line_selection = ($_POST['line_selection'] ?? '');

	// ---- backfill handler --------------------------------------------------
	// Runs BEFORE the gap query below so the page reflects the inserts in the
	// same POST (no manual refresh). button_backfill is neutralised for
	// non-admins by mb_guard_admin() above, so this block is a no-op for them.
	if (isset($_POST['button_backfill'])) {
		$conn = new mysqli($host, $accessun, $accesspw, $dbname);
		$g_animal = $_POST['g_animal'] ?? array();
		$g_group  = $_POST['g_group']  ?? array();
		$g_allele = $_POST['g_allele'] ?? array();
		$inserted = 0; $present = 0; $deferred = 0; $failed = 0;
		$chk = $conn->prepare('SELECT 1 FROM `table_genotypes` WHERE `animalautono`=? AND `allelegroup`=? LIMIT 1');
		$ins = $conn->prepare('INSERT INTO `table_genotypes` (`allelegroup`,`allele`,`animalautono`) VALUES (?,?,?)');
		if ($chk && $ins) {
			foreach ($g_animal as $i => $autono_raw) {
				$autono = (int)$autono_raw;
				$grp = (string)($g_group[$i] ?? '');
				$all = (string)($g_allele[$i] ?? 'unk');
				if ($autono <= 0 || $grp === '') { continue; }
				if ($all === '__skip__') { $deferred++; continue; } // admin left this gap for later
				// idempotent guard: never create a second row for (animal, allelegroup)
				$chk->bind_param('is', $autono, $grp);
				$chk->execute();
				$chk->store_result();
				$exists = $chk->num_rows > 0;
				$chk->free_result();
				if ($exists) { $present++; continue; }
				$ins->bind_param('ssi', $grp, $all, $autono);
				if ($ins->execute()) { $inserted++; } else { $failed++; }
			}
			$chk->close();
			$ins->close();
		}
		$backfill_status = "Backfill: {$inserted} inserted, {$present} already present, {$deferred} left for later"
			. ($failed ? ", {$failed} FAILED" : "") . ".";
		$conn->close();
	}

	// ---- line dropdown -----------------------------------------------------
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	$line_listbox = '<select id="line_selection" name="line_selection" size=1 class="mediumlistbox" onchange="submitForm()"><option value="">-- select a line --</option>';
	$results = $conn->query("call get_lines();");
	while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
		$sel = ($row['line'] === $line_selection) ? ' selected' : '';
		$line_listbox .= '<option value="' . htmlspecialchars((string)$row['line']) . '"' . $sel . '>' . htmlspecialchars((string)$row['line']) . '</option>';
	}
	$line_listbox .= '</select>';
	$conn->close();

	// ---- allele options per allelegroup for the selected line --------------
	// Mirrors add_animals.php: options come from list_allele, bucketed by
	// sexspecific (M / F / all) so a gap's select can be sex-appropriate.
	$aglist = array();
	if ($line_selection !== '') {
		$conn = new mysqli($host, $accessun, $accesspw, $dbname);
		$stmt = $conn->prepare("SELECT `list_allele`.`allelegroup`,`allele`,`sexspecific` FROM `key_allelebyline` JOIN `list_allele` ON `key_allelebyline`.`allelegroup`=`list_allele`.`allelegroup` WHERE `key_allelebyline`.`line`=?");
		if ($stmt) {
			$stmt->bind_param('s', $line_selection);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($res && ($row = $res->fetch_assoc())) {
				$ag = (string)$row['allelegroup'];
				$ss = (string)($row['sexspecific'] ?? 'all');
				if (!isset($aglist[$ag])) { $aglist[$ag] = array('M' => '', 'F' => '', 'all' => ''); }
				if (!isset($aglist[$ag][$ss])) { $aglist[$ag][$ss] = ''; }
				$aglist[$ag][$ss] .= '<option value="' . htmlspecialchars((string)$row['allele']) . '">' . htmlspecialchars((string)$row['allele']) . '</option>';
			}
			$stmt->close();
		}
		$conn->close();
	}

	// ---- gap detection -----------------------------------------------------
	// Live animals (dod IS NULL) on the line that lack a table_genotypes row for
	// an allele group currently assigned to the line (key_allelebyline).
	$gaps = array();
	if ($line_selection !== '') {
		$conn = new mysqli($host, $accessun, $accesspw, $dbname);
		$stmt = $conn->prepare(
			"SELECT a.`animalautono`, a.`idno`, a.`sex`, k.`allelegroup`
			 FROM `table_animals` a
			 JOIN `key_allelebyline` k ON k.`line` = a.`line`
			 WHERE a.`line` = ? AND a.`dod` IS NULL
			   AND NOT EXISTS (SELECT 1 FROM `table_genotypes` g
			                   WHERE g.`animalautono` = a.`animalautono` AND g.`allelegroup` = k.`allelegroup`)
			 ORDER BY a.`animalautono`, k.`allelegroup`");
		if ($stmt) {
			$stmt->bind_param('s', $line_selection);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($res && ($row = $res->fetch_assoc())) { $gaps[] = $row; }
			$stmt->close();
		}
		$conn->close();
	}
	?>

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Genotype Backfill - <?php echo $dbname; ?></title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>

<body>

	<div id="header">
		<form id="loginbox" action="" method="post">
			<h2 class="centervert" style="position:absolute;top:0px;left:75px;">
				-Backfill-
			</h2>
			<h1 class="centervert" style="position:absolute;top:0px;left:350px;">
				<?php echo $dbname; ?>
				<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
			</h1>
			<button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
				width:20px;height:20px;border-radius:10px;position:absolute;top:15px;right:250px;"></button>
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
			<input type=submit id="loginbutton" name="button_login"
				style="font-size:10px;width:50px;height:20px;position:absolute;top:5px;right:10px;" value="connect" />
			<input type=submit id="discobutton" name="button_disco"
				style="font-size:10px;width:50px;height:20px;position:absolute;top:25px;right:10px;" value="disco" />
		</form>
	</div>

	<?php require_once __DIR__ . '/../includes/nav.php';
	mb_render_nav($dbname); ?>

	<div id="right_content" class="centertext">
		<div class="whitespace">
			<h2 class="centertext">Genotype Backfill (admin repair)</h2>
			<p>Fills missing genotype rows for <b>live</b> animals on a line that lack a row for an
				allele group now assigned to that line. Pick an allele where known, leave <i>unk</i> to
				record it as unknown, or choose <i>&mdash; skip &mdash;</i> to defer a gap. Re-running is
				safe: existing rows are never duplicated.</p>

			<form id="backfill_form" name="backfill_form" method=post>
				<input type=hidden name="dbname" value="<?php echo ($_POST['dbname'] ?? ''); ?>" />
				<input type=hidden name="button_login" value="connect" />
				<!--javascript to autoupdate form based on select option choices -->
				<script type="text/javascript">
					function submitForm() {
						document.getElementById("backfill_form").submit();
					}
				</script>

				<table>
					<tr>
						<th>Line:</th>
						<td><?php echo $line_listbox; ?></td>
					</tr>
				</table>
				<br>

				<?php
				if ($line_selection === '') {
					echo '<p>Select a line to see animals with missing genotype rows.</p>';
				} elseif (empty($gaps)) {
					echo '<p>No missing genotype rows for live animals on this line. Nothing to backfill.</p>';
				} else {
					echo '<p>' . count($gaps) . ' missing genotype row(s) found:</p>';
					echo '<table border=1 cellpadding=4 style="margin:0 auto;">';
					echo '<tr><th>Animal (idno)</th><th>autono</th><th>Sex</th><th>Allele group</th><th>Allele</th></tr>';
					foreach ($gaps as $g) {
						$ag = (string)$g['allelegroup'];
						$sex = (string)($g['sex'] ?? '');
						$bucket = $aglist[$ag] ?? array('M' => '', 'F' => '', 'all' => '');
						if ($sex === 'M') {
							$opts = $bucket['M'] . $bucket['all'];
						} elseif ($sex === 'F') {
							$opts = $bucket['F'] . $bucket['all'];
						} else {
							$opts = $bucket['M'] . $bucket['F'] . $bucket['all'];
						}
						echo '<tr>';
						echo '<td>' . htmlspecialchars((string)$g['idno']) . '</td>';
						echo '<td>' . (int)$g['animalautono'] . '</td>';
						echo '<td>' . htmlspecialchars($sex) . '</td>';
						echo '<td>' . htmlspecialchars($ag) . '</td>';
						echo '<td><input type=hidden name="g_animal[]" value="' . (int)$g['animalautono'] . '">';
						echo '<input type=hidden name="g_group[]" value="' . htmlspecialchars($ag) . '">';
						echo '<select name="g_allele[]"><option value="unk" selected>unk</option>'
							. '<option value="__skip__">&mdash; skip &mdash;</option>' . $opts . '</select></td>';
						echo '</tr>';
					}
					echo '</table><br>';
					echo '<input type=submit name="button_backfill" value="Backfill genotypes for this line">';
				}
				?>
			</form>
			<p><b><?php echo htmlspecialchars((string)$backfill_status); ?></b></p>
		</div>
	</div>
	<div id="footer">
		<p class="righttext">@realchrisward &copy; 2025</p>
	</div>

	<script src="../mousebook.js"></script>
</body>

</html>
