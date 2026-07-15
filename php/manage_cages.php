<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
<?php
require_once __DIR__ . '/../includes/db.php';
/* issue #14: initialize first-load output variables to prevent PHP 8 undefined-variable warnings on first load */
$host = $accessun = $accesspw = null;
$lf = null;
$gf = null;
$mf = null;
$sf = null;
$locf = null;
$rolef = null;
$cf = null;
$sqlstatusclear = null;
//setup sql variables
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


//test login

// collect config values
$config = require '../config.php';
mb_debug_init($config);
//setup sql variables
$ubname = $config['server_user'];
$ubpass = $config['server_pass'];

//query userbook for accessable databases
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
mb_guard_write();
require_once __DIR__ . '/../includes/filters.php';


$conn = mb_connect($host, $accessun, $accesspw, $dbname);
//check connection
if ($conn->connect_error) {
	$xloginstatus = 'red';
	echo '<h2 class="centertext"> please connect to the database </h2>';
} else {
	$xloginstatus = 'green';
	$conn->close();
}




//*****purge this user's stale cageno reservations (mirrors add_animals); degrade gracefully if table absent*****
//skipped on the submit request so this user's reservation stays in place right up to the commit,
//closing the purge-then-insert micro-window; a successful submit releases it explicitly (see below).
if (!isset($_POST['submit_cages'])) {
	$conn = mb_connect($host, $accessun, $accesspw, $dbname);
	// B-2: was LOCK TABLES + DELETE + UNLOCK sent as one multi_query() string.
	// Three problems, all now gone: (1) the lock guarded a single DELETE, which is
	// already atomic; (2) under InnoDB LOCK TABLES implicitly COMMITs, which would
	// defeat mb_write()'s transaction (B-3); (3) multi_query() ENABLES STACKED
	// QUERIES on this connection -- the mechanism that turns a SQL injection from
	// "read one row" into "DROP TABLE". A prepared statement does the same work
	// with none of that. Table left unqualified: the connection's default database
	// is already $dbname.
	try {
		$stp = $conn->prepare("DELETE FROM `reservations_cages` WHERE `user` = ?");
		if ($stp) {
			$stp->bind_param('s', $xusername);
			$stp->execute();
			$stp->close();
		}
	} catch (\Throwable $e) {
		//reservations_cages not present yet — nothing to purge
	}
	$conn->close();
}


$conn = mb_connect($host, $accessun, $accesspw, $dbname);
//Add animals individually to cage1|2|3|4
if (isset($_POST['addcage1_single'])) {
	// P3 #21: the source list is a multi-select; add the whole selected subset.
	$sel = $_POST['animals_selection'] ?? array();
	mb_stage_add($dbname, 1, $sel);
	$sqlaction = 'add animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($sel));
	$sqlstatus = 'successful';
}
//cage2
if (isset($_POST['addcage2_single'])) {
	// P3 #21: the source list is a multi-select; add the whole selected subset.
	$sel = $_POST['animals_selection'] ?? array();
	mb_stage_add($dbname, 2, $sel);
	$sqlaction = 'add animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($sel));
	$sqlstatus = 'successful';
}
//cage3
if (isset($_POST['addcage3_single'])) {
	// P3 #21: the source list is a multi-select; add the whole selected subset.
	$sel = $_POST['animals_selection'] ?? array();
	mb_stage_add($dbname, 3, $sel);
	$sqlaction = 'add animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($sel));
	$sqlstatus = 'successful';
}
//cage4
if (isset($_POST['addcage4_single'])) {
	// P3 #21: the source list is a multi-select; add the whole selected subset.
	$sel = $_POST['animals_selection'] ?? array();
	mb_stage_add($dbname, 4, $sel);
	$sqlaction = 'add animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($sel));
	$sqlstatus = 'successful';
}
//Remove animals individually to cage1|2|3|4
if (isset($_POST['remcage1_single'])) {
	$sel = $_POST['cage1_selection'] ?? '';
	mb_stage_remove($dbname, 1, $sel);
	$sqlaction = 'rem animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($sel));
	$sqlstatus = 'successful';
}
//cage2
if (isset($_POST['remcage2_single'])) {
	$sel = $_POST['cage2_selection'] ?? '';
	mb_stage_remove($dbname, 2, $sel);
	$sqlaction = 'rem animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($sel));
	$sqlstatus = 'successful';
}
//cage3
if (isset($_POST['remcage3_single'])) {
	$sel = $_POST['cage3_selection'] ?? '';
	mb_stage_remove($dbname, 3, $sel);
	$sqlaction = 'rem animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($sel));
	$sqlstatus = 'successful';
}
//cage4
if (isset($_POST['remcage4_single'])) {
	$sel = $_POST['cage4_selection'] ?? '';
	mb_stage_remove($dbname, 4, $sel);
	$sqlaction = 'rem animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($sel));
	$sqlstatus = 'successful';
}
//Bulk add to cage 1|2|3|4
if (isset($_POST['addcage1_batch'])) {
	// "add all filtered": batchlist holds every currently-listed animal.
	$batch = $_POST['animals_batchlist'] ?? '';
	mb_stage_add($dbname, 1, $batch);
	$sqlaction = 'add animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($batch));
	$sqlstatus = 'successful';
}
//cage2
if (isset($_POST['addcage2_batch'])) {
	// "add all filtered": batchlist holds every currently-listed animal.
	$batch = $_POST['animals_batchlist'] ?? '';
	mb_stage_add($dbname, 2, $batch);
	$sqlaction = 'add animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($batch));
	$sqlstatus = 'successful';
}
//cage3
if (isset($_POST['addcage3_batch'])) {
	// "add all filtered": batchlist holds every currently-listed animal.
	$batch = $_POST['animals_batchlist'] ?? '';
	mb_stage_add($dbname, 3, $batch);
	$sqlaction = 'add animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($batch));
	$sqlstatus = 'successful';
}
//cage4
if (isset($_POST['addcage4_batch'])) {
	// "add all filtered": batchlist holds every currently-listed animal.
	$batch = $_POST['animals_batchlist'] ?? '';
	mb_stage_add($dbname, 4, $batch);
	$sqlaction = 'add animal(s):' . mb_stage_in_csv(mb_stage_normalize_ints($batch));
	$sqlstatus = 'successful';
}

//Bulk remove from 1|2|3|4
if (isset($_POST['remcage1_batch'])) {
	$sqlaction = 'clear cage';
	mb_stage_clear($dbname, 1);
	$sqlstatus = 'successful';
}
//cage2
if (isset($_POST['remcage2_batch'])) {
	$sqlaction = 'clear cage';
	mb_stage_clear($dbname, 2);
	$sqlstatus = 'successful';
}
//cage3
if (isset($_POST['remcage3_batch'])) {
	$sqlaction = 'clear cage';
	mb_stage_clear($dbname, 3);
	$sqlstatus = 'successful';
}
//cage4
if (isset($_POST['remcage4_batch'])) {
	$sqlaction = 'clear cage';
	mb_stage_clear($dbname, 4);
	$sqlstatus = 'successful';
}

//clear all cages
if (isset($_POST['clear_cages'])) {
	$sqlactionclear = 'clear all cages';
	mb_stage_clear_all($dbname);
	$sqlstatusclear = 'successful';
}

//submit cages
if (isset($_POST['submit_cages'])) {
	$xcage1no = ($_POST['cage1no'] ?? '');
	$xcage2no = ($_POST['cage2no'] ?? '');
	$xcage3no = ($_POST['cage3no'] ?? '');
	$xcage4no = ($_POST['cage4no'] ?? '');
	$xcage1name = ($_POST['cage1name'] ?? '');
	$xcage2name = ($_POST['cage2name'] ?? '');
	$xcage3name = ($_POST['cage3name'] ?? '');
	$xcage4name = ($_POST['cage4name'] ?? '');
	$xcage1size = ($_POST['cage1size'] ?? '');
	$xcage2size = ($_POST['cage2size'] ?? '');
	$xcage3size = ($_POST['cage3size'] ?? '');
	$xcage4size = ($_POST['cage4size'] ?? '');
	$xcage1contents = ($_POST['cage1contents'] ?? '');
	$xcage2contents = ($_POST['cage2contents'] ?? '');
	$xcage3contents = ($_POST['cage3contents'] ?? '');
	$xcage4contents = ($_POST['cage4contents'] ?? '');
	$xcage1location = $_POST['cage1location'] ?? 'Limbo';
	$xcage2location = $_POST['cage2location'] ?? 'Limbo';
	$xcage3location = $_POST['cage3location'] ?? 'Limbo';
	$xcage4location = $_POST['cage4location'] ?? 'Limbo';
	$xcage1role = $_POST['cage1role'] ?? '';
	$xcage2role = $_POST['cage2role'] ?? '';
	$xcage3role = $_POST['cage3role'] ?? '';
	$xcage4role = $_POST['cage4role'] ?? '';
	if ($xcage1location === '') {
		$xcage1location = 'Limbo';
	}
	if ($xcage2location === '') {
		$xcage2location = 'Limbo';
	}
	if ($xcage3location === '') {
		$xcage3location = 'Limbo';
	}
	if ($xcage4location === '') {
		$xcage4location = 'Limbo';
	}
	$xline_assignment = ($_POST['line_assignment'] ?? '');
	$xmove_selection = ($_POST['move_selection'] ?? '');
	$xcategory_selection = ($_POST['category_selection'] ?? '');
	$xcageactive = 1;
	$xsetupdate = ($_POST['setupdate'] ?? '');

	$sqlaction = 'submit cage changes';

	$PrintCages = "";
	$c1values = $c2values = $c3values = $c4values = "";
	$c1updates = $c2updates = $c3updates = $c4updates = "";
	if ($xcage1size > 0) {
		// P2 residual: escape cage name / line / category / contents (were raw-interpolated).
		$esc_c1name = $conn->real_escape_string($xcage1name);
		$PrintCages .= "('" . $esc_c1name . "'),";
		$c1values = "('" . $esc_c1name . "','" . $conn->real_escape_string($xcategory_selection) . "','" . $conn->real_escape_string($xsetupdate) . "',1,'" . $conn->real_escape_string($xline_assignment) . "'," . (int)$xcage1no . ",'" . $conn->real_escape_string($xcage1contents) . "','" . $conn->real_escape_string($xcage1location) . "','" . $conn->real_escape_string($xcage1role) . "'),";
		// P3 Option A: move the session-staged animals via WHERE animalautono IN (...) instead of JOIN temp_cage1.
		$c1in = mb_stage_in_csv(mb_stage_slot($dbname, 1));
		if ($c1in !== '') {
			$esc_c1date = $conn->real_escape_string($xsetupdate);
			if ($xmove_selection === "Weaning") {
				$c1updates = "UPDATE `" . $dbname . "`.`table_animals` SET `currentcage`='" . $esc_c1name . "', `dow`='" . $esc_c1date . "' WHERE `animalautono` IN (" . $c1in . ");
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
SELECT `animalautono`, '" . $esc_c1date . "' as commentdate, 'moved to cage:" . $esc_c1name . "' as general_comment FROM `" . $dbname . "`.`table_animals` WHERE `animalautono` IN (" . $c1in . ");";
			} else {
				$c1updates = "UPDATE `" . $dbname . "`.`table_animals` SET `currentcage`='" . $esc_c1name . "' WHERE `animalautono` IN (" . $c1in . ");
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
SELECT `animalautono`, '" . $esc_c1date . "' as commentdate, 'moved to cage:" . $esc_c1name . "' as general_comment FROM `" . $dbname . "`.`table_animals` WHERE `animalautono` IN (" . $c1in . ");";
			}
		}
	}

	if ($xcage2size > 0) {
		// P2 residual: escape cage name / line / category / contents (were raw-interpolated).
		$esc_c2name = $conn->real_escape_string($xcage2name);
		$PrintCages .= "('" . $esc_c2name . "'),";
		$c2values = "('" . $esc_c2name . "','" . $conn->real_escape_string($xcategory_selection) . "','" . $conn->real_escape_string($xsetupdate) . "',1,'" . $conn->real_escape_string($xline_assignment) . "'," . (int)$xcage2no . ",'" . $conn->real_escape_string($xcage2contents) . "','" . $conn->real_escape_string($xcage2location) . "','" . $conn->real_escape_string($xcage2role) . "'),";
		// P3 Option A: move the session-staged animals via WHERE animalautono IN (...) instead of JOIN temp_cage2.
		$c2in = mb_stage_in_csv(mb_stage_slot($dbname, 2));
		if ($c2in !== '') {
			$esc_c2date = $conn->real_escape_string($xsetupdate);
			if ($xmove_selection === "Weaning") {
				$c2updates = "UPDATE `" . $dbname . "`.`table_animals` SET `currentcage`='" . $esc_c2name . "', `dow`='" . $esc_c2date . "' WHERE `animalautono` IN (" . $c2in . ");
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
SELECT `animalautono`, '" . $esc_c2date . "' as commentdate, 'moved to cage:" . $esc_c2name . "' as general_comment FROM `" . $dbname . "`.`table_animals` WHERE `animalautono` IN (" . $c2in . ");";
			} else {
				$c2updates = "UPDATE `" . $dbname . "`.`table_animals` SET `currentcage`='" . $esc_c2name . "' WHERE `animalautono` IN (" . $c2in . ");
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
SELECT `animalautono`, '" . $esc_c2date . "' as commentdate, 'moved to cage:" . $esc_c2name . "' as general_comment FROM `" . $dbname . "`.`table_animals` WHERE `animalautono` IN (" . $c2in . ");";
			}
		}
	}
	if ($xcage3size > 0) {
		// P2 residual: escape cage name / line / category / contents (were raw-interpolated).
		$esc_c3name = $conn->real_escape_string($xcage3name);
		$PrintCages .= "('" . $esc_c3name . "'),";
		$c3values = "('" . $esc_c3name . "','" . $conn->real_escape_string($xcategory_selection) . "','" . $conn->real_escape_string($xsetupdate) . "',1,'" . $conn->real_escape_string($xline_assignment) . "'," . (int)$xcage3no . ",'" . $conn->real_escape_string($xcage3contents) . "','" . $conn->real_escape_string($xcage3location) . "','" . $conn->real_escape_string($xcage3role) . "'),";
		// P3 Option A: move the session-staged animals via WHERE animalautono IN (...) instead of JOIN temp_cage3.
		$c3in = mb_stage_in_csv(mb_stage_slot($dbname, 3));
		if ($c3in !== '') {
			$esc_c3date = $conn->real_escape_string($xsetupdate);
			if ($xmove_selection === "Weaning") {
				$c3updates = "UPDATE `" . $dbname . "`.`table_animals` SET `currentcage`='" . $esc_c3name . "', `dow`='" . $esc_c3date . "' WHERE `animalautono` IN (" . $c3in . ");
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
SELECT `animalautono`, '" . $esc_c3date . "' as commentdate, 'moved to cage:" . $esc_c3name . "' as general_comment FROM `" . $dbname . "`.`table_animals` WHERE `animalautono` IN (" . $c3in . ");";
			} else {
				$c3updates = "UPDATE `" . $dbname . "`.`table_animals` SET `currentcage`='" . $esc_c3name . "' WHERE `animalautono` IN (" . $c3in . ");
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
SELECT `animalautono`, '" . $esc_c3date . "' as commentdate, 'moved to cage:" . $esc_c3name . "' as general_comment FROM `" . $dbname . "`.`table_animals` WHERE `animalautono` IN (" . $c3in . ");";
			}
		}
	}
	if ($xcage4size > 0) {
		// P2 residual: escape cage name / line / category / contents (were raw-interpolated).
		$esc_c4name = $conn->real_escape_string($xcage4name);
		$PrintCages .= "('" . $esc_c4name . "'),";
		$c4values = "('" . $esc_c4name . "','" . $conn->real_escape_string($xcategory_selection) . "','" . $conn->real_escape_string($xsetupdate) . "',1,'" . $conn->real_escape_string($xline_assignment) . "'," . (int)$xcage4no . ",'" . $conn->real_escape_string($xcage4contents) . "','" . $conn->real_escape_string($xcage4location) . "','" . $conn->real_escape_string($xcage4role) . "'),";
		// P3 Option A: move the session-staged animals via WHERE animalautono IN (...) instead of JOIN temp_cage4.
		$c4in = mb_stage_in_csv(mb_stage_slot($dbname, 4));
		if ($c4in !== '') {
			$esc_c4date = $conn->real_escape_string($xsetupdate);
			if ($xmove_selection === "Weaning") {
				$c4updates = "UPDATE `" . $dbname . "`.`table_animals` SET `currentcage`='" . $esc_c4name . "', `dow`='" . $esc_c4date . "' WHERE `animalautono` IN (" . $c4in . ");
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
SELECT `animalautono`, '" . $esc_c4date . "' as commentdate, 'moved to cage:" . $esc_c4name . "' as general_comment FROM `" . $dbname . "`.`table_animals` WHERE `animalautono` IN (" . $c4in . ");";
			} else {
				$c4updates = "UPDATE `" . $dbname . "`.`table_animals` SET `currentcage`='" . $esc_c4name . "' WHERE `animalautono` IN (" . $c4in . ");
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
SELECT `animalautono`, '" . $esc_c4date . "' as commentdate, 'moved to cage:" . $esc_c4name . "' as general_comment FROM `" . $dbname . "`.`table_animals` WHERE `animalautono` IN (" . $c4in . ");";
			}
		}
	}
	$xInsertValues = substr($c1values . $c2values . $c3values . $c4values, 0, -1);
	$PrintCages = substr($PrintCages, 0, -1);

	//insert into table_cages
	$insertTableCages = "INSERT INTO `" . $dbname . "`.`table_cages` (`cageid`,`cagetype`,`setupdate`,`cageactive`,`lineassignment`,`cageno`,`cagecontents`,`cagelocation_room`,`cagerole_assignment`) VALUES " . $xInsertValues . ";";
	//insert into printing list
	$printlist = "Insert INTO `" . $dbname . "`.`CagesForPrinting` (`cageid`) VALUES " . $PrintCages . ";";
	//staged animals live in the session (P3 Option A); cleared on success below, not via SQL.
	//merge queries

	$sqltext = $insertTableCages . $c1updates . $c2updates . $c3updates . $c4updates . $printlist;

	//commit-time collision guard: since this page was rendered, a concurrent transfer — or another of
	//this user's own open manage-cages windows (the top-of-request purge is keyed by user, so a newer
	//window can clear an older window's reservation) — may have already created these cage numbers.
	//Verify none of the target cage names already exist before moving anything, so a stale page can
	//never write a duplicate cage. Cage names encode line + category + number, so a name match is an
	//exact "this cage already exists" test.
	$cageCollision = '';
	$targetCageNames = array();
	if ($xcage1size > 0) {
		$targetCageNames[] = $xcage1name;
	}
	if ($xcage2size > 0) {
		$targetCageNames[] = $xcage2name;
	}
	if ($xcage3size > 0) {
		$targetCageNames[] = $xcage3name;
	}
	if ($xcage4size > 0) {
		$targetCageNames[] = $xcage4name;
	}
	if (!empty($targetCageNames)) {
		$escTargets = array();
		foreach ($targetCageNames as $tname) {
			$escTargets[] = "'" . $conn->real_escape_string($tname) . "'";
		}
		$checkSql = "SELECT `cageid` FROM `" . $dbname . "`.`table_cages` WHERE `cageid` IN (" . implode(',', $escTargets) . ");";
		$checkRes = $conn->query($checkSql);
		if ($checkRes && $checkRes->num_rows > 0) {
			$takenCages = array();
			while ($crow = $checkRes->fetch_assoc()) {
				$takenCages[] = $crow['cageid'];
			}
			$cageCollision = implode(', ', $takenCages);
		}
	}

	if ($xsetupdate == "") {
		$sqlstatus = 'FAILED - a setup date is required. Please enter a date (or click the "Today" button) and resubmit. No cages were changed.';
	} else if ($cageCollision !== '') {
		$sqlstatus = 'FAILED - the cage(s) [' . $cageCollision . '] were already created by another transfer since this page loaded, so no animals were moved. Your staged animals are still in place and fresh cage numbers have been generated below - please review the numbers and resubmit.';
	} else if ($conn->multi_query($sqltext) === TRUE) {
		//flush the mysql submission
		while (mysqli_next_result($conn));
		//P3 Option A: committed — clear this session's staged sets.
		mb_stage_clear_all($dbname);
		$sqlstatus = 'successful - ' . $sqltext;
		//micro-window hardening: the cages now exist in table_cages, so release this user's reservation
		//for the committed line+category. Scoped to line+category so other open windows keep theirs.
		$conn2 = mb_connect($host, $accessun, $accesspw, $dbname);
		// B-2: multi_query + LOCK TABLES -> prepared DELETE (see the purge above).
		try {
			$rel_user = $xusername ?? '';
			$rel_line = $xline_assignment ?? '';
			$rel_type = $xcategory_selection ?? '';
			$str = $conn2->prepare("DELETE FROM `reservations_cages` WHERE `user` = ? AND `lineassignment` = ? AND `cagetype` = ?");
			if ($str) {
				$str->bind_param('sss', $rel_user, $rel_line, $rel_type);
				$str->execute();
				$str->close();
			}
		} catch (\Throwable $e) {
			//reservations_cages absent — nothing to release
		}
		$conn2->close();
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}




$buttonmessage = ($sqlaction ?? '') . ' ' . ($xline_assignment ?? '') . ' - ' . ($sqlstatus ?? '');
$conn->close();
?>
<!--php script for display controls-->
<?php

// posted variables
$line_filter = ($_POST['line_filter'] ?? '');
$line_sync = ($_POST['line_sync'] ?? '');
$line_assignment = ($_POST['line_assignment'] ?? '');
$sex_filter = ($_POST['sex_filter'] ?? '');
$move_selection = ($_POST['move_selection'] ?? '');
$source_category_selection = ($_POST['source_category_selection'] ?? '');
$category_selection = ($_POST['category_selection'] ?? '');
$setupdate = ($_POST['setupdate'] ?? '');
$sourcecage_selection = ($_POST['sourcecage_selection'] ?? '');
$animals_selection = ($_POST['animals_selection'] ?? '');
$cage1_selection = ($_POST['cage1_selection'] ?? '');
$cage2_selection = ($_POST['cage2_selection'] ?? '');
$cage3_selection = ($_POST['cage3_selection'] ?? '');
$cage4_selection = ($_POST['cage4_selection'] ?? '');
$location_filter = $_POST['location_filter'] ?? 'all';
$role_filter     = $_POST['role_filter']     ?? 'all';

//sex filter
$sex_options = array('all', 'M', 'F', 'unk');
$sex_listbox = '<select id="sex_filter" name="sex_filter" onchange="submitForm()">';
foreach ($sex_options as $row) {
	if ($row === $sex_filter) {
		$sex_listbox .= '<option value="' . $row . '" selected>' . $row . '</option>';
	} else {
		$sex_listbox .= '<option value="' . $row . '" >' . $row . '</option>';
	}
}
$sex_listbox .= '</select>';

//move type filter
$move_options = array('Weaning', 'Cage Transfer');
$move_listbox = '<select id="move_selection" name="move_selection" onchange="submitForm()">';
foreach ($move_options as $row) {
	if ($row === $move_selection) {
		$move_listbox .= '<option value="' . $row . '" selected>' . $row . '</option>';
	} else {
		$move_listbox .= '<option value="' . $row . '" >' . $row . '</option>';
	}
}
$move_listbox .= '</select>';


//category type assignment
$category_options = array('Holding', 'Rearrange', 'Mating', 'Experimental', 'Sac');
$category_listbox = '<select id="category_selection" name="category_selection" onchange="submitForm()">';
foreach ($category_options as $row) {
	if ($row === $category_selection) {
		$category_listbox .= '<option value="' . $row . '" selected>' . $row . '</option>';
	} else {
		$category_listbox .= '<option value="' . $row . '" >' . $row . '</option>';
	}
}
$category_listbox .= '</select>';

//source category type filter
$source_category_options = array('all', 'Holding', 'Rearrange', 'Mating', 'Experimental', 'Litter', 'Founder', 'Sac');
$source_category_listbox = '<select id="source_category_selection" name="source_category_selection" onchange="submitForm()">';
foreach ($source_category_options as $row) {
	if ($row === $source_category_selection) {
		$source_category_listbox .= '<option value="' . $row . '" selected>' . $row . '</option>';
	} else {
		$source_category_listbox .= '<option value="' . $row . '" >' . $row . '</option>';
	}
}
$source_category_listbox .= '</select>';

//line lists
if ($line_sync == "") {
	$line_sync = $line_assignment;
}
if ($line_assignment <> $line_sync) {
	$line_assign_selected = $line_assignment;
} else {
	$line_assign_selected = $line_filter;
}
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
$sqltext = "call get_lines();";
$results = $conn->query($sqltext);
//set up static portion of table
$line_listbox = '<select id="line_filter" name="line_filter" size=1 class="mediumlistbox" onchange="submitForm()"><option value="all">all</option>';
$lineassign_listbox = '<select id="line_assignment" name="line_assignment" size=1 class="mediumlistbox" onchange="submitForm()">';
//loop the result set and prepare table
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	//catch results of each row
	//get results matched to current line - used for additional fields
	if ($row['line'] === $line_filter) {
		$line_listbox .= '<option value="' . $row["line"] . '" selected>' . $row["line"] . '</option>';
	}
	//get results for additional lines
	else {
		$line_listbox .= '<option value="' . $row["line"] . '">' . $row["line"] . '</option>';
	}
	if ($row['line'] === $line_assign_selected) {
		$lineassign_listbox .= '<option value="' . $row["line"] . '" selected>' . $row["line"] . '</option>';
	}
	//get results for additional lines
	else {
		$lineassign_listbox .= '<option value="' . $row["line"] . '">' . $row["line"] . '</option>';
	}
}
//close the table
$line_listbox .= '</select>';
$lineassign_listbox .= '</select>';
$conn->close();

//temp_cage1 contents (P3 Option A: rendered from the session slot)
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
$cage1_in = mb_stage_in_csv(mb_stage_slot($dbname, 1));
$cage1size = 0;
$animalc1 = array();
$cage1_listbox = '<select id="cage1_selection" name="cage1_selection" size=6 class="largelistbox">';
if ($cage1_in !== '') {
	$sqltext = "SELECT `line`,`idno`,`sex`,`dob`,`currentcage`,`animalautono` FROM `table_animals` WHERE `animalautono` IN (" . $cage1_in . ") order by `sex` desc, `line`, `idno`;";
	$results = $conn->query($sqltext);
	if ($results instanceof mysqli_result) {
		$cage1size = mysqli_num_rows($results);
		while ($row = mysqli_fetch_array($results)) {
			$sel = ($row['animalautono'] === $cage1_selection) ? ' selected' : '';
			$cage1_listbox .= '<option value="' . $row['animalautono'] . '"' . $sel . '>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['sex'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
			$animalc1[] = $row['line'] . '-' . $row['idno'] . '(' . $row['sex'] . ')';
		}
	}
}
$cage1contents = implode(', ', $animalc1);
$cage1_listbox .= '</select>';
$conn->close();

//temp_cage2 contents (P3 Option A: rendered from the session slot)
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
$cage2_in = mb_stage_in_csv(mb_stage_slot($dbname, 2));
$cage2size = 0;
$animalc2 = array();
$cage2_listbox = '<select id="cage2_selection" name="cage2_selection" size=6 class="largelistbox">';
if ($cage2_in !== '') {
	$sqltext = "SELECT `line`,`idno`,`sex`,`dob`,`currentcage`,`animalautono` FROM `table_animals` WHERE `animalautono` IN (" . $cage2_in . ") order by `sex` desc, `line`, `idno`;";
	$results = $conn->query($sqltext);
	if ($results instanceof mysqli_result) {
		$cage2size = mysqli_num_rows($results);
		while ($row = mysqli_fetch_array($results)) {
			$sel = ($row['animalautono'] === $cage2_selection) ? ' selected' : '';
			$cage2_listbox .= '<option value="' . $row['animalautono'] . '"' . $sel . '>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['sex'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
			$animalc2[] = $row['line'] . '-' . $row['idno'] . '(' . $row['sex'] . ')';
		}
	}
}
$cage2contents = implode(', ', $animalc2);
$cage2_listbox .= '</select>';
$conn->close();

//temp_cage3 contents (P3 Option A: rendered from the session slot)
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
$cage3_in = mb_stage_in_csv(mb_stage_slot($dbname, 3));
$cage3size = 0;
$animalc3 = array();
$cage3_listbox = '<select id="cage3_selection" name="cage3_selection" size=6 class="largelistbox">';
if ($cage3_in !== '') {
	$sqltext = "SELECT `line`,`idno`,`sex`,`dob`,`currentcage`,`animalautono` FROM `table_animals` WHERE `animalautono` IN (" . $cage3_in . ") order by `sex` desc, `line`, `idno`;";
	$results = $conn->query($sqltext);
	if ($results instanceof mysqli_result) {
		$cage3size = mysqli_num_rows($results);
		while ($row = mysqli_fetch_array($results)) {
			$sel = ($row['animalautono'] === $cage3_selection) ? ' selected' : '';
			$cage3_listbox .= '<option value="' . $row['animalautono'] . '"' . $sel . '>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['sex'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
			$animalc3[] = $row['line'] . '-' . $row['idno'] . '(' . $row['sex'] . ')';
		}
	}
}
$cage3contents = implode(', ', $animalc3);
$cage3_listbox .= '</select>';
$conn->close();

//temp_cage4 contents (P3 Option A: rendered from the session slot)
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
$cage4_in = mb_stage_in_csv(mb_stage_slot($dbname, 4));
$cage4size = 0;
$animalc4 = array();
$cage4_listbox = '<select id="cage4_selection" name="cage4_selection" size=6 class="largelistbox">';
if ($cage4_in !== '') {
	$sqltext = "SELECT `line`,`idno`,`sex`,`dob`,`currentcage`,`animalautono` FROM `table_animals` WHERE `animalautono` IN (" . $cage4_in . ") order by `sex` desc, `line`, `idno`;";
	$results = $conn->query($sqltext);
	if ($results instanceof mysqli_result) {
		$cage4size = mysqli_num_rows($results);
		while ($row = mysqli_fetch_array($results)) {
			$sel = ($row['animalautono'] === $cage4_selection) ? ' selected' : '';
			$cage4_listbox .= '<option value="' . $row['animalautono'] . '"' . $sel . '>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['sex'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
			$animalc4[] = $row['line'] . '-' . $row['idno'] . '(' . $row['sex'] . ')';
		}
	}
}
$cage4contents = implode(', ', $animalc4);
$cage4_listbox .= '</select>';
$conn->close();

// ---- assign-mode Location + Role for each destination cage ----
// default location follows the first animal assigned to each temp cage; manual overrides preserved via *_locsync
$cage_location_listbox = array();
$cage_role_listbox     = array();
$cage_locsync          = array();
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
$loc_assign_values = location_assign_options($conn);
if (!in_array('Limbo', $loc_assign_values, true)) {
	array_unshift($loc_assign_values, 'Limbo');
}
$role_assign_values = role_assign_options($conn);
foreach (array(1, 2, 3, 4) as $cn) {
	$firstcur = '';
	$slot_in = mb_stage_in_csv(mb_stage_slot($dbname, $cn));
	if ($slot_in !== '') {
		$fr = $conn->query("SELECT currentcage FROM table_animals WHERE animalautono IN (" . $slot_in . ") ORDER BY sex desc, line, idno LIMIT 1;");
		if ($fr && ($frow = mysqli_fetch_array($fr))) {
			$firstcur = $frow['currentcage'];
		}
	}
	$deflorm = 'Limbo';
	if ($firstcur !== '' && $firstcur !== null) {
		$lr = $conn->query("SELECT cagelocation_room FROM table_cages WHERE cageid='" . $conn->real_escape_string($firstcur) . "' LIMIT 1;");
		if ($lr && ($lrow = mysqli_fetch_array($lr))) {
			if ($lrow['cagelocation_room'] !== null && $lrow['cagelocation_room'] !== '') {
				$deflorm = $lrow['cagelocation_room'];
			}
		}
	}
	$val  = $_POST["cage{$cn}location"] ?? '';
	$sync = $_POST["cage{$cn}locsync"] ?? chr(1);
	if ((string)$firstcur !== (string)$sync) {
		$val = $deflorm;
	}
	if ($val === '') {
		$val = $deflorm;
	}
	$cage_locsync[$cn] = (string)$firstcur;
	$locvals = $loc_assign_values;
	if ($val !== '' && !in_array($val, $locvals, true)) {
		array_unshift($locvals, $val);
	}
	$cage_location_listbox[$cn] = filter_selectbox($locvals, $val, "cage{$cn}location", 'submitForm()', false);
	$rval = $_POST["cage{$cn}role"] ?? '';
	$rl = '<select id="cage' . $cn . 'role" name="cage' . $cn . 'role" size=1 class="mediumlistbox" onchange="submitForm()">';
	$rl .= '<option value=""' . ($rval === '' ? ' selected' : '') . '>(none)</option>';
	foreach ($role_assign_values as $rv) {
		$rl .= '<option value="' . htmlspecialchars($rv) . '"' . ($rv === $rval ? ' selected' : '') . '>' . htmlspecialchars($rv) . '</option>';
	}
	$rl .= '</select>';
	$cage_role_listbox[$cn] = $rl;
}
$conn->close();


//cage list filtered by line, sex, etc
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
//set filter text
if ($line_filter === "all") {
	$lf = '';
} else {
	$lf = '`line`="' . $conn->real_escape_string($line_filter) . '" and ';
}

if ($sex_filter === "all") {
	$gf = '';
} else {
	$gf = '`sex`="' . $conn->real_escape_string($sex_filter) . '" and ';
}

if ($move_selection === "Weaning") {
	$mf = 'left(`currentcage`,1)="L" and ';
} else {
	$mf = '';
}

if ($source_category_selection === "all") {
	$sf = '';
} else {
	$sf = 'left(`currentcage`,1)=left("' . $conn->real_escape_string($source_category_selection) . '",1) and ';
}
//location + role (subquery form — no table_cages join on this page)
$location_listbox = filter_selectbox(location_filter_options($conn), $location_filter, 'location_filter', 'submitForm()', true);
$role_listbox     = filter_selectbox(role_filter_options($conn),     $role_filter,     'role_filter',     'submitForm()', true);
if ($location_filter === "all" || $location_filter === "") {
	$locf = '';
} else {
	$locf = 'currentcage IN (SELECT cageid FROM table_cages WHERE cagelocation_room="' . $conn->real_escape_string($location_filter) . '") and ';
}
if ($role_filter === "all" || $role_filter === "") {
	$rolef = '';
} else {
	$rolef = 'currentcage IN (SELECT cageid FROM table_cages WHERE cagerole_assignment="' . $conn->real_escape_string($role_filter) . '") and ';
}

$sql_where_text = substr($lf . $gf . $mf . $sf . $locf . $rolef, 0, -4);        // 594
if (strlen($sql_where_text) > 0) {
	$sql_where_text = ' and ' . $sql_where_text;
}
// P3 Option A: exclude the union of all four session-staged slots from both source lists.
// Computed once here (before the first consumer) so it is defined regardless of which slots hold animals.
$stage_excl = mb_stage_in_csv(mb_stage_union($dbname));
$excl_clause = ($stage_excl !== '') ? (' and table_animals.animalautono NOT IN (' . $stage_excl . ')') : '';
$sqltext = "SELECT `currentcage` FROM `table_animals` where dod is null" . $excl_clause . $sql_where_text . " GROUP BY `currentcage`;";
$results = $conn->query($sqltext);
//echo $sqltext;
$sourcecage_listbox = '<select id="sourcecage_selection" name="sourcecage_selection" size=14 class="largelistbox" onchange="submitForm()"><option value="all">all</option>';
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	if ($row['currentcage'] === $sourcecage_selection) {
		$sourcecage_listbox .= '<option value="' . $row['currentcage'] . '" selected>' . $row['currentcage'] . '</option>';
	} else {
		$sourcecage_listbox .= '<option value="' . $row['currentcage'] . '">' . $row['currentcage'] . '</option>';
	}
}
//close the table
$sourcecage_listbox .= '</select>';
$conn->close();
//echo $sqltext;

//animals list filtered by line|sex|cage
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
//set filter text
if ($line_filter === "all") {
	$lf = '';
} else {
	$lf = '`line`="' . $conn->real_escape_string($line_filter) . '" and ';
}

if ($sex_filter === "all") {
	$gf = '';
} else {
	$gf = '`sex`="' . $conn->real_escape_string($sex_filter) . '" and ';
}

if ($move_selection === "Weaning") {
	$mf = 'left(`currentcage`,1)="L" and ';
} else {
	$mf = '';
}

if ($source_category_selection === "all") {
	$sf = '';
} else {
	$sf = 'left(`currentcage`,1)=left("' . $conn->real_escape_string($source_category_selection) . '",1) and ';
}

if ($sourcecage_selection == "" or $sourcecage_selection === "all") {
	$cf = '';
} else {
	// P2 residual: escape sourcecage before interpolation.
	$cf = '`currentcage`="' . $conn->real_escape_string($sourcecage_selection) . '" and ';
}

$sql_where_text = substr($lf . $gf . $mf . $sf . $cf . $locf . $rolef, 0, -4);  // 649
if (strlen($sql_where_text) > 0) {
	$sql_where_text = ' and ' . $sql_where_text;
}
// $excl_clause was computed once above (before the source-cage query) and is reused here unchanged.
$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,sex,dob,dod,currentcage FROM `table_animals` where dod is null" . $excl_clause . $sql_where_text . " ;";
$results = $conn->query($sqltext);
$animals_results = $results;
// P3 #21: multi-select source list (no onchange auto-submit — pick a subset, then click add).
$animals_sel_set = array_map('strval', is_array($animals_selection) ? $animals_selection : array((string)$animals_selection));
$animals_listbox = '<select id="animals_selection" name="animals_selection[]" multiple size=15 class="largelistbox">';
//loop and prepare table
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	if (in_array((string)$row['man'], $animals_sel_set, true)) {
		$animals_listbox .= '<option value="' . $row['man'] . '" selected>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['sex'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	} else {
		$animals_listbox .= '<option value="' . $row['man'] . '">' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['sex'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	}
	$animals_batchlist[] = $row['man'];
}
//close the table
$animals_listbox .= '</select>';
$animals_batchlist = '(' . implode('),(', $animals_batchlist ?? []) . ')';

$conn->close();
//echo $sqltext;

//cage tentative names + cageno reservation (concurrency-safe; mirrors add_animals reservation pattern)
//one read of the committed MAX(cageno) and one of the reserved MAX, so two users staging the
//same line+category at once cannot mint duplicate cage numbers/names.
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
$esc_line = $conn->real_escape_string($line_assignment ?? '');
$esc_type = $conn->real_escape_string($category_selection ?? '');
//highest committed cageno for this line+category
$sqltext = "SELECT MAX(`cageno`) as maxcageno from `" . $dbname . "`.`table_cages` where `lineassignment`='" . $esc_line . "' and `cagetype`='" . $esc_type . "';";
$results = $conn->query($sqltext);
$row = ($results instanceof mysqli_result) ? mysqli_fetch_array($results) : false;
$realmaxcageno = ($row && $row[0] !== null) ? (int)$row[0] : 0;
//highest reserved cageno for this line+category (other users' in-flight transfers); degrade gracefully if table absent
$resmaxcageno = 0;
try {
	$sqltext = "SELECT MAX(`maxcageno`) as maxcageno from `" . $dbname . "`.`reservations_cages` where `lineassignment`='" . $esc_line . "' and `cagetype`='" . $esc_type . "';";
	$results = $conn->query($sqltext);
	$row = ($results instanceof mysqli_result) ? mysqli_fetch_array($results) : false;
	$resmaxcageno = ($row && $row[0] !== null) ? (int)$row[0] : 0;
} catch (\Throwable $e) {
	$resmaxcageno = 0;
}
$basecageno = max($realmaxcageno, $resmaxcageno);
$cage1no = $basecageno + 1;
$cage2no = $basecageno + 2;
$cage3no = $basecageno + 3;
$cage4no = $basecageno + 4;
$cage1name = $category_selection . ' : ' . $line_assignment . ' : ' . strval($cage1no);
$cage2name = $category_selection . ' : ' . $line_assignment . ' : ' . strval($cage2no);
$cage3name = $category_selection . ' : ' . $line_assignment . ' : ' . strval($cage3no);
$cage4name = $category_selection . ' : ' . $line_assignment . ' : ' . strval($cage4no);
//reserve this block of cagenos so a concurrent transfer on the same line+category cannot reuse them
if (($line_assignment ?? '') !== '' && ($category_selection ?? '') !== '') {
	// B-2: multi_query + LOCK TABLES -> prepared INSERT (see the purge above).
	try {
		$res_user   = $xusername ?? '';
		$res_line   = $line_assignment ?? '';
		$res_type   = $category_selection ?? '';
		$res_maxno  = (int)$cage4no;
		$str = $conn->prepare("INSERT INTO `reservations_cages` (`user`,`lineassignment`,`cagetype`,`maxcageno`,`timestamp`) VALUES (?,?,?,?,now())");
		if ($str) {
			$str->bind_param('sssi', $res_user, $res_line, $res_type, $res_maxno);
			$str->execute();
			$str->close();
		}
	} catch (\Throwable $e) {
		//reservation unavailable (e.g. table not yet migrated) — fall back to committed-MAX behavior
	}
}
$sqlerror = $conn->error;
$conn->close();

//form inputs for movement type, new cage category, new cage set-up date, new cage line assignment
//form displays (and temp tables in mysql) for 4 potential new cages


?>

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Manage Cages - <?php echo $dbname; ?></title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>

<body>

	<div id="header">
		<form id="loginbox" action="" method="post">
			<?php echo $sqlerror; ?>
			<h2 class="centervert"
				style="position:absolute;top:0px;left:75px;">
				-Manage Cages-
			</h2>

			<h1 class="centervert"
				style="position:absolute;top:0px;left:350px;">
				<?php echo $dbname; ?>
				<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
			</h1>

			<button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
					 width:20px;height:20px;border-radius:10px;position:absolute;
					 top:15px;right:250px;"></button>



			<table class="logintable" style="color:white;font-size:10px;position:absolute;top:0px;right:60px;">
				<tr>
					<th>user:</th>
					<th><input type="text" name="xusername"
							value="<?php echo htmlspecialchars($xusername); ?>" style="width:100px;font-size:10px;" /></th>
				</tr>
				<tr>
					<td>pass:</td>
					<td><input type="password" name="xpassword"
							value="" style="width:100px;font-size:10px;" /></td>
				</tr>
			</table>
			<input type=submit id="loginbutton" name="button_login"
				style="font-size:10px;width:50px;height:20px;
						position:absolute;top:5px;right:10px;"
				value="connect" />

			<input type=submit id="discobutton" name="button_disco"
				style="font-size:10px;width:50px;height:20px;
						position:absolute;top:25px;right:10px;"
				value="disco" />
		</form>
	</div>

	<?php require_once __DIR__ . '/../includes/nav.php';
	mb_render_nav($dbname); ?>
	<div id="right_content" class="centertext">
		<!--CONTENT SECTION-->
		<form id="cage_management_form" name="cage_management_form" method=post>

			<input type=hidden name="dbname" value="<?php echo ($_POST['dbname'] ?? ''); ?>" />
			<input type=hidden name="button_login" value="connect" />
			<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
			<script type="text/javascript">
				function submitForm() {
					document.getElementById("cage_management_form").submit();
				}
			</script>
			<table>
				<tr>
					<th>Line Filter:</th>
					<th>Sex Filter:</th>
					<th>Source Cage Category:</th>
					<th>Move Type:</th>
					<th>Location Filter:</th>
					<th>Role Filter:</th>
				</tr>
				<tr>
					<td><?php echo $line_listbox; ?></td>
					<td><?php echo $sex_listbox; ?></td>
					<td><?php echo $source_category_listbox; ?></td>
					<td><?php echo $move_listbox; ?></td>
					<td><?php echo $location_listbox; ?></td>
					<td><?php echo $role_listbox; ?></td>
				</tr>
			</table>

			<table>
				<tr>
					<th>Available animals</th>
					<th>Source Cage Selection:</th>
				</tr>
				<tr>
					<td><?php echo $animals_listbox; ?><input type=hidden name="animals_batchlist" id="animals_batchlist" value="<?php echo $animals_batchlist; ?>"></td>
					<td><?php echo $sourcecage_selection; ?><br>
						<?php echo $sourcecage_listbox; ?></td>
				</tr>
			</table>

			<table>
				<tr>
					<th>Line Assignment:</th>
					<th>Cage Category:</th>
					<th>Cage Set-up Date:</th>
				</tr>
				<tr>
					<td><?php echo $lineassign_listbox; ?><input type=hidden id="line_sync" name="line_sync" value="<?php echo $line_filter; ?>">
					</td>
					<td><?php echo $category_listbox; ?></td>
					<td><input type=date id="setupdate" name="setupdate" value="<?php echo $setupdate; ?>"><input type=button value="Today" title="Fill in today's date" style="margin-left:6px" onclick="var d=new Date();document.getElementById('setupdate').value=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');"></td>
				</tr>
			</table>

			<table>
				<tr>
					<th><input type=text id="cage1name" name="cage1name" value="<?php echo $cage1name; ?>" readonly="readonly">
						<input type=hidden id="cage1no" name="cage1no" value="<?php echo $cage1no; ?>">
						<input type=hidden id="cage1contents" name="cage1contents" value="<?php echo $cage1contents; ?>">
					</th>
					<td><input type=submit id="remcage1_single" name="remcage1_single" value="&uarr;(c1)"><input type=submit id="remcage1_batch" name="remcage1_batch" value="&uarr;&uarr;(c1)&uarr;&uarr;"></td>
					<th><input type=text id="cage2name" name="cage2name" value="<?php echo $cage2name; ?>" readonly="readonly">
						<input type=hidden id="cage2no" name="cage2no" value="<?php echo $cage2no; ?>">
						<input type=hidden id="cage2contents" name="cage2contents" value="<?php echo $cage2contents; ?>">
					</th>
					<td><input type=submit id="remcage2_single" name="remcage2_single" value="&uarr;(c2)"><input type=submit id="remcage2_batch" name="remcage2_batch" value="&uarr;&uarr;(c2)&uarr;&uarr;"></td>
				</tr>
				<tr>
					<td><input type=text id="cage1size" name="cage1size" value="<?php echo $cage1size; ?>"></td>
					<td><input type=submit id="addcage1_single" name="addcage1_single" value="&darr;(c1)"><input type=submit id="addcage1_batch" name="addcage1_batch" value="&darr;&darr;(c1)&darr;&darr;"></td>
					<td><input type=text id="cage2size" name="cage2size" value="<?php echo $cage2size; ?>"></td>
					<td><input type=submit id="addcage2_single" name="addcage2_single" value="&darr;(c2)"><input type=submit id="addcage2_batch" name="addcage2_batch" value="&darr;&darr;(c2)&darr;&darr;"></td>
				</tr>
				<tr>
					<td colspan=2><?php echo $cage1_listbox; ?></td>
					<td colspan=2><?php echo $cage2_listbox; ?></td>
				</tr>
				<tr>
					<td colspan=2>Location: <?php echo $cage_location_listbox[1]; ?> Role: <?php echo $cage_role_listbox[1]; ?>
						<input type=hidden name="cage1locsync" value="<?php echo htmlspecialchars($cage_locsync[1] ?? ''); ?>">
					</td>
					<td colspan=2>Location: <?php echo $cage_location_listbox[2]; ?> Role: <?php echo $cage_role_listbox[2]; ?>
						<input type=hidden name="cage2locsync" value="<?php echo htmlspecialchars($cage_locsync[2] ?? ''); ?>">
					</td>
				</tr>
				<tr>
					<th><input type=text id="cage3name" name="cage3name" value="<?php echo $cage3name; ?>" readonly="readonly">
						<input type=hidden id="cage3no" name="cage3no" value="<?php echo $cage3no; ?>">
						<input type=hidden id="cage3contents" name="cage3contents" value="<?php echo $cage3contents; ?>">
					</th>
					<td><input type=submit id="remcage3_single" name="remcage3_single" value="&uarr;(c3)"><input type=submit id="remcage3_batch" name="remcage3_batch" value="&uarr;&uarr;(c3)&uarr;&uarr;"></td>
					<th><input type=text id="cage4name" name="cage4name" value="<?php echo $cage4name; ?>" readonly="readonly">
						<input type=hidden id="cage4no" name="cage4no" value="<?php echo $cage4no; ?>">
						<input type=hidden id="cage4contents" name="cage4contents" value="<?php echo $cage4contents; ?>">
					</th>
					<td><input type=submit id="remcage4_single" name="remcage4_single" value="&uarr;(c4)"><input type=submit id="remcage4_batch" name="remcage4_batch" value="&uarr;&uarr;(c4)&uarr;&uarr;"></td>
				</tr>
				<tr>
					<td><input type=text id="cage3size" name="cage3size" value="<?php echo $cage3size; ?>"></td>
					<td><input type=submit id="addcage3_single" name="addcage3_single" value="&darr;(c3)"><input type=submit id="addcage3_batch" name="addcage3_batch" value="&darr;&darr;(c3)&darr;&darr;"></td>
					<td><input type=text id="cage4size" name="cage4size" value="<?php echo $cage4size; ?>"></td>
					<td><input type=submit id="addcage4_single" name="addcage4_single" value="&darr;(c4)"><input type=submit id="addcage4_batch" name="addcage4_batch" value="&darr;&darr;(c4)&darr;&darr;"></td>
				</tr>
				<tr>
					<td colspan=2><?php echo $cage3_listbox; ?></td>
					<td colspan=2><?php echo $cage4_listbox; ?></td>
				</tr>
				<tr>
					<td colspan=2>Location: <?php echo $cage_location_listbox[3]; ?> Role: <?php echo $cage_role_listbox[3]; ?>
						<input type=hidden name="cage3locsync" value="<?php echo htmlspecialchars($cage_locsync[3] ?? ''); ?>">
					</td>
					<td colspan=2>Location: <?php echo $cage_location_listbox[4]; ?> Role: <?php echo $cage_role_listbox[4]; ?>
						<input type=hidden name="cage4locsync" value="<?php echo htmlspecialchars($cage_locsync[4] ?? ''); ?>">
					</td>
				</tr>
			</table>

			<input type=submit id="submit_cages" name="submit_cages" value="Submit">
			<input type=submit id="clear_cages" name="clear_cages" value="Clear Cages">

		</form>
	</div>


	<div id="footer">
		<p class="righttext">
			@realchrisward &copy; 2025
		</p>

	</div>
	<?php echo $buttonmessage; ?>
	<br>
	<?php echo $sqlstatusclear; ?>

	<script src="../mousebook.js"></script>
</body>

</html>