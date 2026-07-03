<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
<?php
//setup sql variables
$xusername = $_POST['xusername'];
$xpassword = $_POST['xpassword'];

if (isset($_POST['button_login'])) {
	$xusername = $_POST['xusername'];
	$xpassword = $_POST['xpassword'];
	$xloginstatus = $_POST['loginstatus'];
}
if (isset($_POST['button_disco'])) {
	$xusername = '';
	$xpassword = '';
	$xloginstatus = 'red';
}

$dbname = $_POST['dbname'];


//test login

// collect config values
$config = require '../config.php';
if ($config['debug_mode'] == 'True') {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}
//setup sql variables
$ubname = $config['server_user'];
$ubpass = $config['server_pass'];

//query userbook for accessable databases
// [mb_auth_patched]
require_once __DIR__ . '/../includes/auth.php';
$_mb_conn = mb_get_connection($config, $xusername, $xpassword, $dbname);
if ($_mb_conn) {
	[$host, $accessun, $accesspw] = $_mb_conn;
}
require_once __DIR__ . '/../includes/filters.php';


$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//check connection
if ($conn->connect_error) {
	$xloginstatus = 'red';
	echo '<h2 class="centertext"> please connect to the database </h2>';
} else {
	$xloginstatus = 'green';
	$conn->close();
}




$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//Add animals individually to cage1|2|3|4
if (isset($_POST['addcage1_single'])) {
	$animals_selection = $_POST['animals_selection'];
	$sqlaction = 'add animal:' . $animals_selection;
	$sqltext = "INSERT INTO `" . $dbname . "`.`temp_cage1` (`animalautono`) VALUES (" . $animals_selection . ");";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage2
if (isset($_POST['addcage2_single'])) {
	$animals_selection = $_POST['animals_selection'];
	$sqlaction = 'add animal:' . $animals_selection;
	$sqltext = "INSERT INTO `" . $dbname . "`.`temp_cage2` (`animalautono`) VALUES (" . $animals_selection . ");";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage3
if (isset($_POST['addcage3_single'])) {
	$animals_selection = $_POST['animals_selection'];
	$sqlaction = 'add animal:' . $animals_selection;
	$sqltext = "INSERT INTO `" . $dbname . "`.`temp_cage3` (`animalautono`) VALUES (" . $animals_selection . ");";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage4
if (isset($_POST['addcage4_single'])) {
	$animals_selection = $_POST['animals_selection'];
	$sqlaction = 'add animal:' . $animals_selection;
	$sqltext = "INSERT INTO `" . $dbname . "`.`temp_cage4` (`animalautono`) VALUES (" . $animals_selection . ");";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//Remove animals individually to cage1|2|3|4
if (isset($_POST['remcage1_single'])) {
	$animals_selection = $_POST['cage1_selection'];
	$sqlaction = 'rem animal:' . $animals_selection;
	$sqltext = "DELETE FROM `" . $dbname . "`.`temp_cage1` WHERE `animalautono`=" . $animals_selection . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage2
if (isset($_POST['remcage2_single'])) {
	$animals_selection = $_POST['cage2_selection'];
	$sqlaction = 'rem animal:' . $animals_selection;
	$sqltext = "DELETE FROM `" . $dbname . "`.`temp_cage2` WHERE `animalautono`=" . $animals_selection . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage3
if (isset($_POST['remcage3_single'])) {
	$animals_selection = $_POST['cage3_selection'];
	$sqlaction = 'rem animal:' . $animals_selection;
	$sqltext = "DELETE FROM `" . $dbname . "`.`temp_cage3` WHERE `animalautono`=" . $animals_selection . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage4
if (isset($_POST['remcage4_single'])) {
	$animals_selection = $_POST['cage4_selection'];
	$sqlaction = 'rem animal:' . $animals_selection;
	$sqltext = "DELETE FROM `" . $dbname . "`.`temp_cage4` WHERE `animalautono`=" . $animals_selection . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//Bulk add to cage 1|2|3|4
if (isset($_POST['addcage1_batch'])) {
	$animals_batch = $_POST['animals_batchlist'];
	$sqlaction = 'add animal:' . $animals_batch;
	$sqltext = "INSERT INTO `" . $dbname . "`.`temp_cage1` (`animalautono`) VALUES " . $animals_batch . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage2
if (isset($_POST['addcage2_batch'])) {
	$animals_batch = $_POST['animals_batchlist'];
	$sqlaction = 'add animal:' . $animals_batch;
	$sqltext = "INSERT INTO `" . $dbname . "`.`temp_cage2` (`animalautono`) VALUES " . $animals_batch . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage3
if (isset($_POST['addcage3_batch'])) {
	$animals_batch = $_POST['animals_batchlist'];
	$sqlaction = 'add animal:' . $animals_batch;
	$sqltext = "INSERT INTO `" . $dbname . "`.`temp_cage3` (`animalautono`) VALUES " . $animals_batch . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage4
if (isset($_POST['addcage4_batch'])) {
	$animals_batch = $_POST['animals_batchlist'];
	$sqlaction = 'add animal:' . $animals_batch;
	$sqltext = "INSERT INTO `" . $dbname . "`.`temp_cage4` (`animalautono`) VALUES " . $animals_batch . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}

//Bulk remove from 1|2|3|4
if (isset($_POST['remcage1_batch'])) {
	$sqlaction = 'clear cage';
	$sqltext = "DELETE FROM `" . $dbname . "`.`temp_cage1`;";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage2
if (isset($_POST['remcage2_batch'])) {
	$sqlaction = 'clear cage';
	$sqltext = "DELETE FROM `" . $dbname . "`.`temp_cage2`;";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage3
if (isset($_POST['remcage3_batch'])) {
	$sqlaction = 'clear cage';
	$sqltext = "DELETE FROM `" . $dbname . "`.`temp_cage3`;";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}
//cage4
if (isset($_POST['remcage4_batch'])) {
	$sqlaction = 'clear cage';
	$sqltext = "DELETE FROM `" . $dbname . "`.`temp_cage4`;";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}

//clear all cages
if (isset($_POST['clear_cages'])) {
	$sqlactionclear = 'clear all cages';
	$sqltextclear = "DELETE FROM `" . $dbname . "`.`temp_cage1`;DELETE FROM `" . $dbname . "`.`temp_cage2`;
DELETE FROM `" . $dbname . "`.`temp_cage3`;DELETE FROM `" . $dbname . "`.`temp_cage4`;";
	if ($conn->multi_query($sqltextclear) === TRUE) {
		$sqlstatusclear = 'successful';
	} else {
		$sqlstatusclear = 'failed ' . $conn->error . '...' . $sqltextclear;
	}
}

//submit cages
if (isset($_POST['submit_cages'])) {
	$xcage1no = $_POST['cage1no'];
	$xcage2no = $_POST['cage2no'];
	$xcage3no = $_POST['cage3no'];
	$xcage4no = $_POST['cage4no'];
	$xcage1name = $_POST['cage1name'];
	$xcage2name = $_POST['cage2name'];
	$xcage3name = $_POST['cage3name'];
	$xcage4name = $_POST['cage4name'];
	$xcage1size = $_POST['cage1size'];
	$xcage2size = $_POST['cage2size'];
	$xcage3size = $_POST['cage3size'];
	$xcage4size = $_POST['cage4size'];
	$xcage1contents = $_POST['cage1contents'];
	$xcage2contents = $_POST['cage2contents'];
	$xcage3contents = $_POST['cage3contents'];
	$xcage4contents = $_POST['cage4contents'];
	$xline_assignment = $_POST['line_assignment'];
	$xmove_selection = $_POST['move_selection'];
	$xcategory_selection = $_POST['category_selection'];
	$xcageactive = 1;
	$xsetupdate = $_POST['setupdate'];

	$sqlaction = 'submit cage changes';

	$PrintCages = "";
	$c1values = $c2values = $c3values = $c4values = "";
	$c1updates = $c2updates = $c3updates = $c4updates = "";
	if ($xcage1size > 0) {
		$PrintCages .= "('" . $xcage1name . "'),";
		$c1values = "('" . $xcage1name . "','" . $xcategory_selection . "','" . $xsetupdate . "',1,'" . $xline_assignment . "'," . $xcage1no . ",'" . $xcage1contents . "'),";
		if ($xmove_selection === "Weaning") {
			$c1updates = "UPDATE `" . $dbname . "`.`table_animals` join `" . $dbname . "`.`temp_cage1` 
ON `table_animals`.`animalautono`=`temp_cage1`.`animalautono`
SET `table_animals`.`currentcage`='" . $xcage1name . "',
`table_animals`.`dow`='" . $xsetupdate . "';
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '" . $xsetupdate . "' as commentdate, 'moved to cage:" . $xcage1name . "' as general_comment FROM `" . $dbname . "`.`temp_cage1`;";
		} else {
			$c1updates = "UPDATE `" . $dbname . "`.`table_animals` join `" . $dbname . "`.`temp_cage1` 
ON `table_animals`.`animalautono`=`temp_cage1`.`animalautono`
SET `table_animals`.`currentcage`='" . $xcage1name . "';
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '" . $xsetupdate . "' as commentdate, 'moved to cage:" . $xcage1name . "' as general_comment FROM `" . $dbname . "`.`temp_cage1`;";
		}
	}

	if ($xcage2size > 0) {
		$PrintCages .= "('" . $xcage2name . "'),";
		$c2values = "('" . $xcage2name . "','" . $xcategory_selection . "','" . $xsetupdate . "',1,'" . $xline_assignment . "'," . $xcage2no . ",'" . $xcage2contents . "'),";
		if ($xmove_selection === "Weaning") {
			$c2updates = "UPDATE `" . $dbname . "`.`table_animals` join `" . $dbname . "`.`temp_cage2` 
ON `table_animals`.`animalautono`=`temp_cage2`.`animalautono`
SET `table_animals`.`currentcage`='" . $xcage2name . "',
`table_animals`.`dow`='" . $xsetupdate . "';
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '" . $xsetupdate . "' as commentdate, 'moved to cage:" . $xcage2name . "' as general_comment FROM `" . $dbname . "`.`temp_cage2`;";
		} else {
			$c2updates = "UPDATE `" . $dbname . "`.`table_animals` join `" . $dbname . "`.`temp_cage2` 
ON `table_animals`.`animalautono`=`temp_cage2`.`animalautono`
SET `table_animals`.`currentcage`='" . $xcage2name . "';
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '" . $xsetupdate . "' as commentdate, 'moved to cage:" . $xcage2name . "' as general_comment FROM `" . $dbname . "`.`temp_cage2`;";
		}
	}
	if ($xcage3size > 0) {
		$PrintCages .= "('" . $xcage3name . "'),";
		$c3values = "('" . $xcage3name . "','" . $xcategory_selection . "','" . $xsetupdate . "',1,'" . $xline_assignment . "'," . $xcage3no . ",'" . $xcage3contents . "'),";
		if ($xmove_selection === "Weaning") {
			$c3updates = "UPDATE `" . $dbname . "`.`table_animals` join `" . $dbname . "`.`temp_cage3` 
ON `table_animals`.`animalautono`=`temp_cage3`.`animalautono`
SET `table_animals`.`currentcage`='" . $xcage3name . "',
`table_animals`.`dow`='" . $xsetupdate . "';
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '" . $xsetupdate . "' as commentdate, 'moved to cage:" . $xcage3name . "' as general_comment FROM `" . $dbname . "`.`temp_cage3`;";
		} else {
			$c3updates = "UPDATE `" . $dbname . "`.`table_animals` join `" . $dbname . "`.`temp_cage3` 
ON `table_animals`.`animalautono`=`temp_cage3`.`animalautono`
SET `table_animals`.`currentcage`='" . $xcage3name . "';
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '" . $xsetupdate . "' as commentdate, 'moved to cage:" . $xcage3name . "' as general_comment FROM `" . $dbname . "`.`temp_cage3`;";
		}
	}
	if ($xcage4size > 0) {
		$PrintCages .= "('" . $xcage4name . "'),";
		$c4values = "('" . $xcage4name . "','" . $xcategory_selection . "','" . $xsetupdate . "',1,'" . $xline_assignment . "'," . $xcage4no . ",'" . $xcage4contents . "'),";
		if ($xmove_selection === "Weaning") {
			$c4updates = "UPDATE `" . $dbname . "`.`table_animals` join `" . $dbname . "`.`temp_cage4` 
ON `table_animals`.`animalautono`=`temp_cage4`.`animalautono`
SET `table_animals`.`currentcage`='" . $xcage4name . "',
`table_animals`.`dow`='" . $xsetupdate . "';
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '" . $xsetupdate . "' as commentdate, 'moved to cage:" . $xcage4name . "' as general_comment FROM `" . $dbname . "`.`temp_cage4`;";
		} else {
			$c4updates = "UPDATE `" . $dbname . "`.`table_animals` join `" . $dbname . "`.`temp_cage4` 
ON `table_animals`.`animalautono`=`temp_cage4`.`animalautono`
SET `table_animals`.`currentcage`='" . $xcage4name . "';
INSERT INTO `" . $dbname . "`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '" . $xsetupdate . "' as commentdate, 'moved to cage:" . $xcage4name . "' as general_comment FROM `" . $dbname . "`.`temp_cage4`;";
		}
	}
	$xInsertValues = substr($c1values . $c2values . $c3values . $c4values, 0, -1);
	$PrintCages = substr($PrintCages, 0, -1);

	//insert into table_cages
	$insertTableCages = "INSERT INTO `" . $dbname . "`.`table_cages` (`cageid`,`cagetype`,`setupdate`,`cageactive`,`lineassignment`,`cageno`,`cagecontents`) VALUES " . $xInsertValues . ";";
	//insert into printing list
	$printlist = "Insert INTO `" . $dbname . "`.`CagesForPrinting` (`cageid`) VALUES " . $PrintCages . ";";
	//clear cages
	$sqltextclear = "DELETE FROM `" . $dbname . "`.`temp_cage1`;DELETE FROM `" . $dbname . "`.`temp_cage2`;
DELETE FROM `" . $dbname . "`.`temp_cage3`;DELETE FROM `" . $dbname . "`.`temp_cage4`;";
	//merge queries

	$sqltext = $insertTableCages . $c1updates . $c2updates . $c3updates . $c4updates . $sqltextclear . $printlist;

	if ($xsetupdate == "") {
		$sqlstatus = 'FAILED - a setup date is required. Please enter a date (or click the "Today" button) and resubmit. No cages were changed.';
	} else if ($conn->multi_query($sqltext) === TRUE) {
		//flush the mysql submission
		while (mysqli_next_result($conn));
		$sqlstatus = 'successful - ' . $sqltext;
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
$line_filter = $_POST['line_filter'];
$line_sync = $_POST['line_sync'];
$line_assignment = $_POST['line_assignment'];
$gender_filter = $_POST['gender_filter'];
$move_selection = $_POST['move_selection'];
$source_category_selection = $_POST['source_category_selection'];
$category_selection = $_POST['category_selection'];
$setupdate = $_POST['setupdate'];
$sourcecage_selection = $_POST['sourcecage_selection'];
$animals_selection = $_POST['animals_selection'];
$cage1_selection = $_POST['cage1_selection'];
$cage2_selection = $_POST['cage2_selection'];
$cage3_selection = $_POST['cage3_selection'];
$cage4_selection = $_POST['cage4_selection'];
$location_filter = $_POST['location_filter'] ?? 'all';
$role_filter     = $_POST['role_filter']     ?? 'all';

//gender filter
$gender_options = array('all', 'M', 'F', 'unk');
$gender_listbox = '<select id="gender_filter" name="gender_filter" onchange="submitForm()">';
foreach ($gender_options as $row) {
	if ($row === $gender_filter) {
		$gender_listbox .= '<option value="' . $row . '" selected>' . $row . '</option>';
	} else {
		$gender_listbox .= '<option value="' . $row . '" >' . $row . '</option>';
	}
}
$gender_listbox .= '</select>';

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
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "call get_lines();";
$results = $conn->query($sqltext);
//set up static portion of table
$line_listbox = '<select id="line_filter" name="line_filter" size=1 class="mediumlistbox" onchange="submitForm()"><option value="all">all</option>';
$lineassign_listbox = '<select id="line_assignment" name="line_assignment" size=1 class="mediumlistbox" onchange="submitForm()">';
//loop the result set and prepare table
while ($row = mysqli_fetch_array($results)) {
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

//temp_cage1 contents
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT `line`,`idno`,`gender`,`dob`,`currentcage`,`temp_cage1`.`animalautono` FROM `table_animals` JOIN `temp_cage1` ON `table_animals`.`animalautono`=`temp_cage1`.`animalautono` order by `gender` desc, `line`, `idno`;";
$results = $conn->query($sqltext);
$cage1size = mysqli_num_rows($results);
$cage1_listbox = '<select id="cage1_selection" name="cage1_selection" size=6 class="largelistbox onchange="">;';
//loop and prepare table
while ($row = mysqli_fetch_array($results)) {
	if ($row['animalautono'] === $cage1_selection) {
		$cage1_listbox .= '<option value="' . $row['animalautono'] . '" selected>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	} else {
		$cage1_listbox .= '<option value="' . $row['animalautono'] . '">' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	}
	$animalc1[] = $row['line'] . '-' . $row['idno'] . '(' . $row['gender'] . ')';
}
$cage1contents = implode(', ', $animalc1 ?? []);
//close the table
$cage1_listbox .= '</select>';
$conn->close();

//temp_cage2 contents
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT `line`,`idno`,`gender`,`dob`,`currentcage`,`temp_cage2`.`animalautono` FROM `table_animals` JOIN `temp_cage2` ON `table_animals`.`animalautono`=`temp_cage2`.`animalautono` order by `gender` desc, `line`, `idno`;";
$results = $conn->query($sqltext);
$cage2size = mysqli_num_rows($results);
$cage2_listbox = '<select id="cage2_selection" name="cage2_selection" size=6 class="largelistbox onchange="">;';
//loop and prepare table
while ($row = mysqli_fetch_array($results)) {
	if ($row['animalautono'] === $cage2_selection) {
		$cage2_listbox .= '<option value="' . $row['animalautono'] . '" selected>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	} else {
		$cage2_listbox .= '<option value="' . $row['animalautono'] . '">' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	}
	$animalc2[] = $row['line'] . '-' . $row['idno'] . '(' . $row['gender'] . ')';
}
$cage2contents = implode(', ', $animalc2 ?? []);
//close the table
$cage2_listbox .= '</select>';
$conn->close();

//temp_cage3 contents
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT `line`,`idno`,`gender`,`dob`,`currentcage`,`temp_cage3`.`animalautono` FROM `table_animals` JOIN `temp_cage3` ON `table_animals`.`animalautono`=`temp_cage3`.`animalautono` order by `gender` desc, `line`, `idno`;";
$results = $conn->query($sqltext);
$cage3size = mysqli_num_rows($results);
$cage3_listbox = '<select id="cage3_selection" name="cage3_selection" size=6 class="largelistbox onchange="">;';
//loop and prepare table
while ($row = mysqli_fetch_array($results)) {
	if ($row['animalautono'] === $cage3_selection) {
		$cage3_listbox .= '<option value="' . $row['animalautono'] . '" selected>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	} else {
		$cage3_listbox .= '<option value="' . $row['animalautono'] . '">' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	}
	$animalc3[] = $row['line'] . '-' . $row['idno'] . '(' . $row['gender'] . ')';
}
$cage3contents = implode(', ', $animalc3 ?? []);
//close the table
$cage3_listbox .= '</select>';
$conn->close();

//temp_cage4 contents
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT `line`,`idno`,`gender`,`dob`,`currentcage`,`temp_cage4`.`animalautono` FROM `table_animals` JOIN `temp_cage4` ON `table_animals`.`animalautono`=`temp_cage4`.`animalautono` order by `gender` desc, `line`, `idno`;";
$results = $conn->query($sqltext);
$cage4size = mysqli_num_rows($results);
$cage4_listbox = '<select id="cage4_selection" name="cage4_selection" size=6 class="largelistbox onchange="">;';
//loop and prepare table
while ($row = mysqli_fetch_array($results)) {
	if ($row['animalautono'] === $cage4_selection) {
		$cage4_listbox .= '<option value="' . $row['animalautono'] . '" selected>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	} else {
		$cage4_listbox .= '<option value="' . $row['animalautono'] . '">' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	}
	$animalc4[] = $row['line'] . '-' . $row['idno'] . '(' . $row['gender'] . ')';
}
$cage4contents = implode(', ', $animalc4 ?? []);
//close the table
$cage4_listbox .= '</select>';
$conn->close();


//cage list filtered by line, gender, etc
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//set filter text
if ($line_filter === "all") {
	$lf = '';
} else {
	$lf = '`line`="' . $line_filter . '" and ';
}

if ($gender_filter === "all") {
	$gf = '';
} else {
	$gf = '`gender`="' . $gender_filter . '" and ';
}

if ($move_selection === "Weaning") {
	$mf = 'left(`currentcage`,1)="L" and ';
} else {
	$mf = '';
}

if ($source_category_selection === "all") {
	$sf = '';
} else {
	$sf = 'left(`currentcage`,1)=left("' . $source_category_selection . '",1) and ';
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
$sqltext = "SELECT `currentcage` FROM `table_animals` left join temp_cage1 on table_animals.animalautono=temp_cage1.animalautono 
left join temp_cage2 on table_animals.animalautono=temp_cage2.animalautono left join temp_cage3 on table_animals.animalautono=temp_cage3.animalautono 
left join temp_cage4 on table_animals.animalautono=temp_cage4.animalautono where dod is null and
temp_cage1.animalautono is null and temp_cage2.animalautono is null and temp_cage3.animalautono is null and temp_cage4.animalautono is null" . $sql_where_text . " GROUP BY `currentcage`;";
$results = $conn->query($sqltext);
$sourcecage_listbox = '<select id="sourcecage_selection" name="sourcecage_selection" size=14 class="largelistbox" onchange="submitForm()"><option value="all">all</option>';
while ($row = mysqli_fetch_array($results)) {
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

//animals list filtered by line|gender|cage
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//set filter text
if ($line_filter === "all") {
	$lf = '';
} else {
	$lf = '`line`="' . $line_filter . '" and ';
}

if ($gender_filter === "all") {
	$gf = '';
} else {
	$gf = '`gender`="' . $gender_filter . '" and ';
}

if ($move_selection === "Weaning") {
	$mf = 'left(`currentcage`,1)="L" and ';
} else {
	$mf = '';
}

if ($source_category_selection === "all") {
	$sf = '';
} else {
	$sf = 'left(`currentcage`,1)=left("' . $source_category_selection . '",1) and ';
}

if ($sourcecage_selection == "" or $sourcecage_selection === "all") {
	$cf = '';
} else {
	$cf = '`currentcage`="' . $sourcecage_selection . '" and ';
}

$sql_where_text = substr($lf . $gf . $mf . $sf . $cf . $locf . $rolef, 0, -4);  // 649
if (strlen($sql_where_text) > 0) {
	$sql_where_text = ' and ' . $sql_where_text;
}
$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,gender,dob,dod,currentcage FROM `table_animals` left join temp_cage1 on table_animals.animalautono=temp_cage1.animalautono 
left join temp_cage2 on table_animals.animalautono=temp_cage2.animalautono left join temp_cage3 on table_animals.animalautono=temp_cage3.animalautono 
left join temp_cage4 on table_animals.animalautono=temp_cage4.animalautono where dod is null and 
temp_cage1.animalautono is null and temp_cage2.animalautono is null and temp_cage3.animalautono is null and temp_cage4.animalautono is null" . $sql_where_text . " ;";
$results = $conn->query($sqltext);
$animals_results = $results;
$animals_listbox = '<select id="animals_selection" name="animals_selection" size=15 class="largelistbox onchange="submitForm()">;';
//loop and prepare table
while ($row = mysqli_fetch_array($results)) {
	if ($row['man'] === $animals_selection) {
		$animals_listbox .= '<option value="' . $row['man'] . '" selected>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	} else {
		$animals_listbox .= '<option value="' . $row['man'] . '">' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['gender'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	}
	$animals_batchlist[] = $row['man'];
}
//close the table
$animals_listbox .= '</select>';
$animals_batchlist = '(' . implode('),(', $animals_batchlist ?? []) . ')';

$conn->close();
//echo $sqltext;

//cage 1 tentative name
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT MAX(`cageno`) as maxcageno from `table_cages` where `lineassignment`='" . $line_assignment . "' and `cagetype`='" . $category_selection . "';";
$results = $conn->query($sqltext);
$row = mysqli_fetch_array($results);
$cage1no = $row[0] + 1;
$cage1name = $category_selection . ' : ' . $line_assignment . ' : ' . strval($row[0] + 1);
$conn->close();
//cage 2 tentative name
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT MAX(`cageno`) as maxcageno from `table_cages` where `lineassignment`='" . $line_assignment . "' and `cagetype`='" . $category_selection . "';";
$results = $conn->query($sqltext);
$row = mysqli_fetch_array($results);
$cage2no = $row[0] + 2;
$cage2name = $category_selection . ' : ' . $line_assignment . ' : ' . strval($row[0] + 2);
$conn->close();
//cage 3 tentative name
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT MAX(`cageno`) as maxcageno from `table_cages` where `lineassignment`='" . $line_assignment . "' and `cagetype`='" . $category_selection . "';";
$results = $conn->query($sqltext);
$row = mysqli_fetch_array($results);
$cage3no = $row[0] + 3;
$cage3name = $category_selection . ' : ' . $line_assignment . ' : ' . strval($row[0] + 3);
$conn->close();
//cage 4 tentative name
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT MAX(`cageno`) as maxcageno from `table_cages` where `lineassignment`='" . $line_assignment . "' and `cagetype`='" . $category_selection . "';";
$results = $conn->query($sqltext);
$row = mysqli_fetch_array($results);
$cage4no = $row[0] + 4;
$cage4name = $category_selection . ' : ' . $line_assignment . ' : ' . strval($row[0] + 4);
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
							value="<?php echo $xusername; ?>" style="width:100px;font-size:10px;" /></th>
				</tr>
				<tr>
					<td>pass:</td>
					<td><input type="password" name="xpassword"
							value="<?php echo $xpassword; ?>" style="width:100px;font-size:10px;" /></td>
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

	<div id="left_navmenu">

		<form action="../index.php" method=post>
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				style="background-color:#217190; color:lightgrey;"
				value="Home" />
			<br>
		</form>
		<form action="../php/manage_alleles.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Manage Alleles" />
		</form>
		<form action="../php/manage_strains.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Manage Strains" />
		</form>
		<form action="../php/manage_lines.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Manage Lines" />
		</form>

		</form>
		<form action="../php/add_animals.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Add animals" />
		</form>
		<form action="../php/record_dead_pups.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Record Dead Pups" />
		</form>
		</form>
		<form action="../php/manage_animals.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Manage animals" />
		</form>
		</form>
		<form action="../php/manage_cages.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Manage Cages" />
		</form>

		<form action="../php/query_genotodo.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Plan Genotyping" />
		</form>
		<form action="../php/query_viewer.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="View Database Queries" />
		</form>
		<form action="../php/query_animals.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="View animals" />
		</form>
		<form action="../php/cagecard_printer.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Card Printer" />
		</form>
		<form action="../php/cage_location.php" method=post target="_blank">
			<input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />
			<input type=submit class="button" name=""
				value="Cage Location Manager" />
		</form>

	</div>
	<div id="right_content" class="centertext">
		<!--CONTENT SECTION-->
		<form id="cage_management_form" name="cage_management_form" method=post>

			<input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
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
					<th>Gender Filter:</th>
					<th>Source Cage Category:</th>
					<th>Move Type:</th>
					<th>Location Filter:</th>
					<th>Role Filter:</th>
				</tr>
				<tr>
					<td><?php echo $line_listbox; ?></td>
					<td><?php echo $gender_listbox; ?></td>
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