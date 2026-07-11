<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
<?php
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


$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//check connection
if ($conn->connect_error) {
	$xloginstatus = 'red';
	echo '<h2 class="centertext"> please connect to the database </h2>';
} else {
	$xloginstatus = 'green';
	$conn->close();
}

// posted variables
$line_filter = $_POST['line_filter'] ?? '';
$line_sync = $_POST['line_sync'] ?? '';
$line_assignment = $_POST['line_assignment'] ?? '';
$sex_filter = $_POST['sex_filter'] ?? '';
$source_category_selection = $_POST['source_category_selection'] ?? '';
$category_selection = $_POST['category_selection'] ?? '';
$setupdate = $_POST['setupdate'] ?? '';
$cage_selection = $_POST['cage_selection'] ?? '';
$cagelist_selection = $_POST['cagelist_selection'] ?? '';
$contact1 = $_POST['contactinfo1'] ?? '';
$contact2 = $_POST['contactinfo2'] ?? '';


//get animal history document - save as csv
/*select currentcage, line, idno, matingcage, group_concat(xcom.general_comment order by xcom.commentdate SEParator '->') as animal_history 
from ((table_animals join table_cages on currentcage=table_cages.cageid) 
join CagesForPrinting on currentcage=CagesForPrinting.cageid) 
left join (select * from data_comments  where data_comments.general_comment like '%moved to cage%') as xcom on table_animals.animalautono=xcom.animalautono 
group by table_animals.animalautono order by currentcage;
*/




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

//source category type filter
$source_category_options = array('all', 'Holding', 'Mating', 'Experimental', 'Litter', 'Founder', 'Euthanasia');
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


//CagesForPrinting contents
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT `cageid` FROM `CagesForPrinting`;";

$results = $conn->query($sqltext);
$cage_listbox = '<select id="cagelist_selection" name="cagelist_selection[]" multiple="multiple" size=6 class="largelistbox onchange="">;';
//loop and prepare table
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	//echo $row['cageid'];
	if ($row['cageid'] === $cagelist_selection) {
		$cage_listbox .= '<option value="' . $row['cageid'] . '" selected>' . $row['cageid'] . '</option>';
	} else {
		$cage_listbox .= '<option value="' . $row['cageid'] . '">' . $row['cageid'] . '</option>';
	}
}
//close the table
$cage_listbox .= '</select>';

$conn->close();


//cage list filtered by line, sex, etc
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//set filter text
if ($line_filter === "all" or $line_filter === null) {
	$lf = '';
} else {
	$lf = '`line`="' . $conn->real_escape_string($line_filter) . '" and ';
}

if ($sex_filter === "all" or $sex_filter === null) {
	$gf = '';
} else {
	$gf = '`sex`="' . $conn->real_escape_string($sex_filter) . '" and ';
}

if ($source_category_selection === "all" or $source_category_selection === null) {
	$sf = '';
} else {
	$sf = 'left(`currentcage`,1)=left("' . $conn->real_escape_string($source_category_selection) . '",1) and ';
}

$sql_where_text = substr($lf . $gf . $sf, 0, -4);
if (strlen($sql_where_text) > 0) {
	$sql_where_text = ' and ' . $sql_where_text;
}
$sqltext = "SELECT `currentcage` FROM `table_animals` left join CagesForPrinting on table_animals.currentcage=CagesForPrinting.cageid where dod is null and
CagesForPrinting.cageid is null " . $sql_where_text . " GROUP BY `currentcage`;";
//echo $sqltext;
$results = $conn->query($sqltext);
$sourcecage_listbox = '<select id="cage_selection" name="cage_selection[]" size=14 class="largelistbox" multiple="multiple" >';
$cage_batchlist = array();
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	if ($row['currentcage'] === $cage_selection) {
		$sourcecage_listbox .= '<option value="' . $row['currentcage'] . '" selected>' . $row['currentcage'] . '</option>';
	} else {
		$sourcecage_listbox .= '<option value="' . $row['currentcage'] . '">' . $row['currentcage'] . '</option>';
	}
	$cage_batchlist[] = $row['currentcage'];
} //close the table
$sourcecage_listbox .= '</select>';
$conn->close();

$cage_batchlist = '("' . implode('"),("', $cage_batchlist) . '")';

//functions and form controls
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//Add cage to list
if (isset($_POST['addcage_single'])) {
	$cage_selection = $_POST['cage_selection'] ?? array();
	$cageselection = '("' . implode('"),("', $cage_selection) . '")';
	$sqlaction = 'add cage:' . $cageselection;
	$sqltext = "INSERT INTO `" . $dbname . "`.`CagesForPrinting` (`cageid`) VALUES " . $cageselection . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful' . '...' . $sqltext;
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
	echo $sqlstatus;
}

//Remove cage individually
if (isset($_POST['remcage_single'])) {
	$cagelist_selection = $_POST['cagelist_selection'] ?? array();
	$cagelistselection = '"' . implode('","', $cagelist_selection) . '"';
	$sqlaction = 'rem cage:' . $cagelistselection;
	$sqltext = "DELETE FROM `" . $dbname . "`.`CagesForPrinting` WHERE `cageid` IN (" . $cagelistselection . ");";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful' . '...' . $sqltext;
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
	echo $sqlstatus;
}

//Bulk add to cage list
if (isset($_POST['addcage_batch'])) {
	$cage_batch = ($_POST['cage_batchlist'] ?? '');
	$sqlaction = 'add cage:' . $cage_batch;
	$sqltext = "INSERT INTO `" . $dbname . "`.`CagesForPrinting` (`cageid`) VALUES " . $cage_batchlist . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}

//Bulk remove from cage list
if (isset($_POST['remcage_batch'])) {
	$sqlaction = 'clear cage';
	$sqltext = "DELETE FROM `" . $dbname . "`.`CagesForPrinting`;";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful';
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
}

//Remove from cage list by card color (M1-A #30: used after a color's print run
//is done). Scoped to the currently selected color; 'all' is intentionally blocked
//so it can never clear the whole queue by accident (that is the bulk-remove button).
if (isset($_POST['remcage_color'])) {
	$colorlist = array('white', 'grey', 'pink', 'peach', 'yellow', 'green', 'blue', 'lavender');
	$remcolor = $_POST['colorfilt'] ?? 'all';
	if (in_array($remcolor, $colorlist, true)) {
		$sqlaction = 'rem cages by color:' . $remcolor;
		$sqltext = "DELETE `cfp` FROM `" . $dbname . "`.`CagesForPrinting` AS `cfp` INNER JOIN `" . $dbname . "`.`table_cages` AS `tc` ON `cfp`.`cageid`=`tc`.`cageid` INNER JOIN `" . $dbname . "`.`table_lines` AS `tl` ON `tc`.`lineassignment`=`tl`.`line` WHERE `tl`.`card_color`='" . $conn->real_escape_string($remcolor) . "';";
		if ($conn->query($sqltext) === TRUE) {
			$sqlstatus = 'successful';
		} else {
			$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
		}
	} else {
		$sqlaction = 'rem cages by color: blocked';
		$sqlstatus = "Unable to remove by color 'all'. You may clear the entire queue using the bulk-remove cages button";
	}
	echo $sqlstatus;
}
//echo $sqltext;
//submit cages
//if (isset($_POST['submit_cages'])){

// need line color code table - color codes now stored in line table
$colorkey = array(
	"" => array("R" => 255, "G" => 255, "B" => 255),
	"white" => array("R" => 255, "G" => 255, "B" => 255),
	"grey" => array("R" => 200, "G" => 200, "B" => 200),
	"red" => array("R" => 255, "G" => 1, "B" => 1),
	"pink" => array("R" => 255, "G" => 200, "B" => 225),
	"salmon" => array("R" => 255, "G" => 100, "B" => 100),
	"orange" => array("R" => 255, "G" => 165, "B" => 50),
	"yellow" => array("R" => 255, "G" => 255, "B" => 100),
	"green" => array("R" => 150, "G" => 255, "B" => 150),
	"olive" => array("R" => 120, "G" => 150, "B" => 75),
	"blue" => array("R" => 10, "G" => 180, "B" => 255),
	"cyan" => array("R" => 1, "G" => 200, "B" => 200),
	"violet" => array("R" => 255, "G" => 55, "B" => 255)
);

//query allelegroups by line
// need query to grab and annotate genos
$cagegenokey = array();
$sqlallelegroups = "SELECT `line`,`allelegroup` FROM key_allelebyline order by `allelegroup`;";

$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$results = $conn->query($sqlallelegroups);

//loop and grab data
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	if (array_key_exists($row['line'], $cagegenokey)) {
		$cagegenokey[$row['line']] .= $row['allelegroup'];
	} else {
		$cagegenokey[$row['line']] = $row['allelegroup'] . "; ";
	}
}

$conn->close();


$sqltext = "Select `x`.`cageid`, `x`.`cagetype`, `x`.`lineassignment`,`x`.`setupdate`,`x`.`color_assignment`,`x`.`card_color`,`x`.`line`, `x`.`idno`, `x`.`dob`,`x`.`eartag`, `x`.`animalautono`,`x`.`sex`, `x`.`dod`, `x`.`genorxn`, `x`.`genotype`, `conversion_geno`.`genoshort` from (Select `table_cages`.`cageid`,`cagetype`,`lineassignment`, Date_Format(`setupdate`,'%m/%d/%y') as `setupdate`, `color_assignment`,`card_color`,`table_animals`.`line`,`idno`, Date_Format(`dob`,'%m/%d/%y') as `dob`,`eartag`, `table_animals`.`animalautono`,`sex`,Date_Format(`dod`,'%m/%d/%y') as 'dod', GROUP_CONCAT(`table_genotypes`.`allelegroup` ORDER BY `table_genotypes`.`allelegroup` ASC SEPARATOR '; ') AS `genorxn`, GROUP_CONCAT(`table_genotypes`.`allele` ORDER BY `table_genotypes`.`allelegroup` ASC             SEPARATOR '; ') AS `genotype` from `table_animals` join ((`table_cages` join `table_lines` on `lineassignment`=`table_lines`.`line`) join `CagesForPrinting` on `table_cages`.`cageid`=`CagesForPrinting`.`cageid`) on `currentcage`=`CagesForPrinting`.`cageid` left join `table_genotypes` on `table_animals`.`animalautono`=`table_genotypes`.`animalautono` group by `table_animals`.`animalautono` having `dod` is null) as `x` LEFT JOIN `conversion_geno` ON (((`x`.`genorxn` = CONVERT( `conversion_geno`.`allelegroupscombo` USING UTF8)) AND (`x`.`genotype` = CONVERT( `conversion_geno`.`genotype` USING UTF8)))) order by `x`.`animalautono`";

/* old query
"Select `table_cages`.`cageid`,`cagetype`,`lineassignment`,Date_Format(`setupdate`,'%m/%d/%y') as `setupdate`,`color_assignment`,`card_color`,`table_animals`.`line`,`idno`,Date_Format(`dob`,'%m/%d/%y')  `dob`,`eartag`,`animalautono`,`sex` ";
$sqltext.="from `table_animals` ";
$sqltext.="join ((`table_cages` join `table_lines` on `lineassignment`=`table_lines`.`line`) join `CagesForPrinting` on `table_cages`.`cageid`=`CagesForPrinting`.`cageid`) on `currentcage`=`CagesForPrinting`.`cageid` where `dod` is null;";
*/

//query table_animals
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$results = $conn->query($sqltext);

//loop and grab data
$cages = array();
$animals = array();

while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {

	$cages[$row['cageid']] = array(
		'type' => $row['cagetype'],
		'cageline' => $row['lineassignment'],
		'cagesex' => $row['sex'],
		'setupdate' => $row['setupdate'],
		'cagegenos' => $cagegenokey[$row['lineassignment']],
		'papercolor' => $row['card_color'],
		'cardcolorR' => $colorkey[$row['color_assignment']]["R"],
		'cardcolorG' => $colorkey[$row['color_assignment']]["G"],
		'cardcolorB' => $colorkey[$row['color_assignment']]["B"],
		'animals' => array()
	);
	$animals[$row['animalautono']] = array('cage' => $row['cageid'], 'line' => $row['line'], 'idno' => $row['idno'], 'dob' => $row['dob'], 'ear' => $row['eartag'], 'geno' => '', 'sex' => $row['sex'], 'geno' => $row['genoshort']);
}
$conn->close();

/*not needed anymore - integrated into new query
//query table_genotypes	
// need query to grab and annotate genos
$sqlgenotypes="SELECT `table_genotypes`.`animalautono`,`allelegroup`,`allele` " ;
$sqlgenotypes.="FROM `table_genotypes` INNER JOIN (`table_animals` INNER JOIN `CagesForPrinting` on `currentcage`=`CagesForPrinting`.`cageid`) ";
$sqlgenotypes.="ON `table_genotypes`.`animalautono` = `table_animals`.`animalautono` where `dod` is null order by `allelegroup`;";

$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query($sqlgenotypes);
$geno_results=$results;
//loop and grab data
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	$animals[$row['animalautono']]['geno'].=$row['allele']."; ";
}
// need geno short hand table???
*/

//combine animals into cage array

foreach ($animals as $man => $mdata) {
	$cages[$animals[$man]['cage']]['animals'][$man] = $animals[$man];
	//echo $animals[$man]['cage'];
}

$sqlaction = 'submit cages for printing';

// M1-A (#30): snapshot the card colors present in the FULL print queue BEFORE the
// filter below prunes $cages, so the on-page summary can list every queued color
// (with counts) even while a single color is selected for printing. $cages here
// keys all queued cages (dod-null, in CagesForPrinting).
$queue_color_counts = array();
foreach ($cages as $qc) {
	$pc = ($qc['papercolor'] ?? '');
	if ($pc === '' || $pc === null) {
		$pc = '(unset)';
	}
	$queue_color_counts[$pc] = ($queue_color_counts[$pc] ?? 0) + 1;
}
ksort($queue_color_counts);

//get color cages
$colorfilt = $_POST['colorfilt'] ?? '';
$colorlist = array('white', 'grey', 'pink', 'peach', 'yellow', 'green', 'blue', 'lavender');


foreach (array_keys($cages) as $cagename) {

	if (in_array($colorfilt, $colorlist)) {
		if ($cages[$cagename]['papercolor'] <> $colorfilt) {
			unset($cages[$cagename]);
		}
	} else {
		$colorfilt = 'all';
	}
}

$colorfilt_listbox = '<select id="colorfilt" name="colorfilt" size=1 class="mediumlistbox" onchange="submitForm()">';
$colorfilt_listbox .= '<option value="' . $colorfilt . '" selected >' . $colorfilt . '</option>';
$colorfilt_listbox .= '<option value="all">all</option>';
$colorfilt_listbox .= '<option value="white" >white</option>';
$colorfilt_listbox .= '<option value="grey" >grey</option>';
$colorfilt_listbox .= '<option value="pink" >pink</option>';
$colorfilt_listbox .= '<option value="peach" >peach</option>';
$colorfilt_listbox .= '<option value="yellow" >yellow</option>';
$colorfilt_listbox .= '<option value="green" >green</option>';
$colorfilt_listbox .= '<option value="blue" >blue</option>';
$colorfilt_listbox .= '<option value="lavender" >lavender</option>';
$colorfilt_listbox .= '</select>';

// M1-A (#30): colors-in-queue summary line (from the pre-prune snapshot above,
// with per-color counts).
if (!empty($queue_color_counts)) {
	$qcs_parts = array();
	foreach ($queue_color_counts as $cname => $cnt) {
		$qcs_parts[] = htmlspecialchars($cname) . ' (' . (int) $cnt . ')';
	}
	$queue_color_summary = 'In queue: ' . implode(', ', $qcs_parts);
} else {
	$queue_color_summary = 'In queue: (none)';
}

// M1-A (#30): "show cages of selected color" read-only readout. After the prune
// above, $cages already holds exactly the cages matching $colorfilt (or the whole
// queue when 'all'), so its keys are the ids to list; only shown on demand.
$showcage_readout = '';
if (isset($_POST['showcage_color'])) {
	$matchids = array_keys($cages);
	sort($matchids);
	$lbl = ($colorfilt === 'all') ? 'all colors' : ("color '" . $colorfilt . "'");
	if (!empty($matchids)) {
		$showcage_readout = 'Cages in queue for ' . htmlspecialchars($lbl) . ' (' . count($matchids) . '): ' . htmlspecialchars(implode(', ', $matchids));
	} else {
		$showcage_readout = 'No cages in queue for ' . htmlspecialchars($lbl) . '.';
	}
}

?>

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Cage Card Printing Selector - <?php echo $dbname; ?></title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>

<body>

	<div id="header">
		<form id="loginbox" action="" method="post">
			<?php echo $sqlerror; ?>
			<h2 class="centervert"
				style="position:absolute;top:0px;left:75px;">
				-Cage Card Printer-
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
		<form id="cage_selection_form" name="cage_selection_form" method=post>

			<input type=hidden name="dbname" value="<?php echo ($_POST['dbname'] ?? ''); ?>" />
			<input type=hidden name="button_login" value="connect" />
			<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
			<script type="text/javascript">
				function submitForm() {
					document.getElementById("cage_selection_form").submit();
				}
			</script>
			<table>
				<tr>
					<th>Line Filter:</th>
					<th>Sex Filter:</th>
					<th>Source Cage Category:</th>
				</tr>
				<tr>
					<td><?php echo $line_listbox; ?></td>
					<td><?php echo $sex_listbox; ?></td>
					<td><?php echo $source_category_listbox; ?></td>
				</tr>
			</table>

			<table>
				<tr>

					<th>Current Cages:</th>
				</tr>
				<tr>
					<td><?php echo $cage_selection; ?><br>
						<?php echo $sourcecage_listbox; ?></td>
				</tr>
			</table>

			<table>
				<tr>

					<td><input type=submit id="remcage_single" name="remcage_single" value="&uarr;(c1)"><input type=submit id="remcage_batch" name="remcage_batch" value="&uarr;&uarr;(c1)&uarr;&uarr;"></td>
				</tr>
				<tr>
					<td><input type=submit id="addcage_single" name="addcage_single" value="&darr;(c1)"><input type=submit id="addcage_batch" name="addcage_batch" value="&darr;&darr;(c1)&darr;&darr;"></td>
				</tr>
				<tr>
					<td colspan=2><?php echo $cage_listbox; ?></td>
				</tr>
			</table>
			<INPUT type="submit" id="capturedata" name="capturedata" value="capture data/update">
			<br />
			<textarea name='contactinfo1' id='contactinfo1'><?php echo $contact1; ?></textarea>
			<textarea name='contactinfo2' id='contactinfo2'><?php echo $contact2; ?></textarea>

			<!-- M1-A (#30): card-color print controls, relocated next to the generate
			     buttons. The color filter scopes the print run (as before); the two
			     buttons let the user inspect and, after printing, clear a color. -->
			<div id="colorcontrols" class="centertext">
				<p><?php echo $queue_color_summary; ?></p>
				<table>
					<tr>
						<th>Card Color Filter:</th>
						<td><?php echo $colorfilt_listbox; ?></td>
						<td><input type="submit" id="showcage_color" name="showcage_color" value="Show cages of color"></td>
						<td><input type="submit" id="remcage_color" name="remcage_color" value="Remove cages of color"></td>
					</tr>
				</table>
				<?php if ($showcage_readout !== '') {
					echo '<p>' . $showcage_readout . '</p>';
				} ?>
			</div>
		</form>
		<form id="cagecard_gen" action="../php/cagecard_gen5rs.php" method="POST" target="_blank">

			<p>
				<input type='hidden' name='cages' id='cages' value='<?php echo base64_encode(serialize($cages)); ?>' />
			</p>
			<p>
				<input type=hidden id='contactinfo1' name='contactinfo1' value='<?php echo $contact1; ?>' />
				<input type=hidden id='contactinfo2' name='contactinfo2' value='<?php echo $contact2; ?>' />

			</p>
			<INPUT type="submit" id="submit_cages" name="submit_cages" value="Generate Cards (RS)">



		</form>

		<form id="cagecard_gen" action="../php/cagecard_gen5rs-blindgeno.php" method="POST" target="_blank">

			<p>
				<input type='hidden' name='cages' id='cages' value='<?php echo base64_encode(serialize($cages)); ?>' />
			</p>
			<p>
				<input type=hidden id='contactinfo1' name='contactinfo1' value='<?php echo $contact1; ?>' />
				<input type=hidden id='contactinfo2' name='contactinfo2' value='<?php echo $contact2; ?>' />

			</p>
			<INPUT type="submit" id="submit_cages" name="submit_cages" value="Generate Genotype Blind Cards">



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