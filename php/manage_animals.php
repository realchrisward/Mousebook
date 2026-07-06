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


//create connection
$conn = new mysqli($host, $accessun, $accesspw, $dbname);

//retreive animals data from db of animals
//*****generate temp list of animals*****

if (isset($_POST['get_tempanimals'])) {



	$sql_where_text = $_POST['animals_sql_where_text'];

	$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,gender,eartag,dob,dow,dod,matingcage,currentcage,parents FROM `table_animals` where " . $sql_where_text . " ORDER BY `line` asc, `animalautono` asc;";
	//echo $sqltext;
	$sqldatacomments = "SELECT `data_comments`.`animalautono` as 'man',`commentdate`,`general_comment` FROM `data_comments` JOIN `table_animals` ON `data_comments`.`animalautono` = `table_animals`.`animalautono` where " . $sql_where_text . " ;";
	$sqlgenotypes = "SELECT `table_genotypes`.`animalautono` as 'man',`allelegroup`,`allele` FROM `table_genotypes` JOIN `table_animals` ON `table_genotypes`.`animalautono` = `table_animals`.`animalautono` where " . $sql_where_text . " ;";
	//query table_animals
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqltext);
	$animals_results = $results;
	//loop and grab data

	$i = 0;
	while ($row = mysqli_fetch_array($results)) {
		$i = $i + 1;
		$arrayman[$i] = $row['man'];
		$arrayline[$arrayman[$i]] = $row['line'];
		$arrayidno[$arrayman[$i]] = $row['idno'];
		$arraygender[$arrayman[$i]] = $row['gender'];
		$arrayeartag[$arrayman[$i]] = $row['eartag'];
		$arraydob[$arrayman[$i]] = $row['dob'];
		$arraydow[$arrayman[$i]] = $row['dow'];
		$arraydod[$arrayman[$i]] = $row['dod'];
		$arraymat[$arrayman[$i]] = $row['matingcage'];
		$arraycur[$arrayman[$i]] = $row['currentcage'];
		$arraypar[$arrayman[$i]] = $row['parents'];
		$arraycom[$arrayman[$i]] = '';
		$arraybkc[$arrayman[$i]] = '';
	}
	$sqlerror = $conn->error;
	$conn->close();
	//query table_genotypes




	//query table_genotypes
	$aglist = [];
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqlgenotypes);
	$geno_results = $results;

	while ($row = mysqli_fetch_array($results)) {
		$arraygeno[$row['allelegroup']][$row['man']] = "<option value='" . $row['allele'] . "' selected>" . $row['allele'] . "</option>";
		//echo $row['allelegroup'];
	}
	$conn->close();
	$aglist = [];
	foreach (array_keys($arraygeno) as $ag) {
		$genelist[] = $ag;
		$aglist[$ag] = array('M' => '', 'F' => '', 'all' => '');
		$agfilt[] = "`allelegroup`='" . $ag . "'";
	}
	$agfilt = implode(' or ', $agfilt);


	//get allelegroups
	$sqltext = "SELECT `allelegroup`,`allele`,`genderspecific` FROM `list_allele` WHERE " . $agfilt . ";";
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqltext);
	$aglist = [];
	while ($row = mysqli_fetch_array($results)) {
		$aglist[$row["allelegroup"]][$row["genderspecific"]] .= '<option value="' . $row['allele'] . '">' . $row['allele'] . '</option>';
	}
	$conn->close();
	//echo $sqltext;
	$genecount = count($genelist);
	$genepost = '';
	foreach (range(0, $genecount - 1, 1) as $i) {
		$genepost .= '<input type=hidden id="geno' . $i . '" name="geno' . $i . '" value="' . $genelist[$i] . '">';
	}
	$genepost .= '<input type=hidden id="genecount" name="genecount" value="' . $genecount . '" >';

	//-----prepare selection boxes for genotypes
	$genoheader = '';
	//getallelegroup array with alleles
	foreach (range(0, $genecount - 1, 1) as $i) {
		$ag = $genelist[$i];
		foreach ($arrayman as $j) {
			if ($arraygeno[$ag][$j] == "") {
				$arraygt[$ag][$j] = "<td>NA</td>";
			} else {
				if ($arraygender[$j] === "M") {
					$arraygt[$ag][$j] = '<td ><select id="geno' . $i . '-' . $j . '" name="geno' . $i . '-' . $j . '">' . $arraygeno[$ag][$j] . $aglist[$ag]['M'] . $aglist[$ag]['all'] . '</select></td>';
				} elseif ($arraygender[$j] === "F") {
					$arraygt[$ag][$j] = '<td ><select id="geno' . $i . '-' . $j . '" name="geno' . $i . '-' . $j . '">' . $arraygeno[$ag][$j] . $aglist[$ag]['F'] . $aglist[$ag]['all'] . '</select></td>';
				} else {
					$arraygt[$ag][$j] = '<td ><select id="geno' . $i . '-' . $j . '" name="geno' . $i . '-' . $j . '">' . $arraygeno[$ag][$j] . $aglist[$ag]['M'] . $aglist[$ag]['F'] . $aglist[$ag]['all'] . '</select></td>';
				}
			}
		}
		$genoheader .= '<th>' . $ag . '</th>';
	}

	//------rearraange selection boxes 
	foreach ($arrayman as $i) {
		$arraygensel[$i] = '';
	}
	foreach ($arrayman as $i) {
		foreach ($genelist as $gene) {
			$arraygensel[$i] .= $arraygt[$gene][$i];
		}
		//echo $arraygensel[$i];
	}

	//gather and concatenate comments
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqldatacomments);
	$animals_results = $results;
	//loop and grab data
	while ($row = mysqli_fetch_array($results)) {
		$arraybkc[$row['man']] .= '|[' . $row['commentdate'] . ' : ' . $row['general_comment'] . ']|';
	}


	$temptable = '
<table name="temptable" id="temptable">
<tr name="tempheader" id="tempheader">
<th>-</th>
<th>line</th>
<th>idno</th>
<th>gender</th>
<th>ear_tag</th>
<th>dob</th>
<th>dow</th>
<th>dod</th>
' . $genoheader . '
<th>current cage</th>
<th>source cage</th>
<th>parents</th>	
<th>new comments</th>
<th>bulk comments</th>
</tr>
';

	$temprow = '';

	foreach ($arrayman as $ck) {

		$temprow .= '
<tr name="trow' . $ck . '" id="trow' . $ck . '">
<td ><input class="tinylistbox" type=hidden name="animalautono' . $ck . '" id="animalautono' . $ck . '" readonly="readonly" value=' . $ck . ' >
	</td>
<td ><input class="smalllistbox" type=text name="line' . $ck . '" id="line' . $ck . '" readonly="readonly" value="' . $arrayline[$ck] . '" >
	</td>
<td ><input class="tinylistbox" type=text name="idno' . $ck . '" id="idno' . $ck . '" readonly="readonly" value=' . $arrayidno[$ck] . ' >
	</td>
<td ><select class="" name="gender' . $ck . '" id="gender' . $ck . '"><option value=' . $arraygender[$ck] . ' selected> ' . $arraygender[$ck] . '
	</option><option value="M">M</option><option value="F">F</option><option value="unk">unk</option>
	</td>
<td><select id="eartag' . $ck . '" name="eartag' . $ck . '">
		<option value="' . $arrayeartag[$ck] . '" selected>' . $arrayeartag[$ck] . '</option>
		<option value="-" >-</option>
		<option value="R" >R</option>
		<option value="L" >L</option>
		<option value="RL" >RL</option>
		<option value="RR" >RR</option>
		<option value="LL" >LL</option>
		<option value="RRL" >RRL</option>
		<option value="RLL" >RLL</option>
		<option value="RRLL" >RRLL</option>
		<option value="RRR" >RRR</option>
		<option value="LLL" >LLL</option>
		<option value="RRRL" >RRRL</option>
		<option value="RRRLL" >RRRLL</option>
		<option value="RLLL" >RLLL</option>
		<option value="RRLLL" >RRLLL</option>
		<option value="RRRLLL" >RRRLLL</option>
		<option value="other" >other</option>
		<option value="unk" >unk</option>
	</select>
</td>
<td ><input class="smalllistbox" type=date name="dob' . $ck . '" id="dob' . $ck . '" value=' . $arraydob[$ck] . '>
	</td>
<td ><input class="smalllistbox" type=date name="dow' . $ck . '" id="dow' . $ck . '" value=' . $arraydow[$ck] . '>
	</td>
<td ><input class="smalllistbox" type=date name="dod' . $ck . '" id="dod' . $ck . '" value=' . $arraydod[$ck] . '>
	</td>' .
			$arraygensel[$ck] . '
<td ><input class="mediumlistbox" type=text name="currentcage' . $ck . '" id="currentcage' . $ck . '" readonly="readonly" value="' . $arraycur[$ck] . '" >
	</td>
<td ><input class="smalllistbox" type=text name="sourcecage' . $ck . '" id="sourcecage' . $ck . '" readonly="readonly" value="' . $arraymat[$ck] . '" >
	</td>
<td ><input class="smalllistbox" type=text name="parents' . $ck . '" id="parents' . $ck . '" readonly="readonly" value="' . $arraypar[$ck] . '" >
	</td>
<td ><input class="smalllistbox" type=text name="newcomments' . $ck . '" id="newcomments' . $ck . '" value="' . $arraycom[$ck] . '">
	</td>
<td ><input class="smalllistbox" type=text name="bulkcomments' . $ck . '" id="bulkcomments' . $ck . '" value="' . $arraybkc[$ck] . '">
	</td>
</tr>
';
	}
	//finish table and add key list for grabbing data from the table
	$testtable = $temptable . $temprow . '</table>
<br><br>
<input type=hidden id="mankey" name="mankey" value="' . implode(',', $arrayman) . '">
<input type=submit id="confirm_changes" name="confirm_changes" value="Confirm Changes">';
}

//confirm animals and update to db
if (isset($_POST['confirm_changes'])) {
	//get posted variables
	$arrayman = explode(',', $_POST['mankey']);
	$genecount = $_POST['genecount'];
	foreach (range(0, $genecount - 1, 1) as $i) {
		$genearray[$i] = array($_POST['geno' . $i]);
		$genelist[$i] = $_POST['geno' . $i];
	}
	foreach ($arrayman as $man) {
		$line[$man] = $_POST['line' . $man];
		$idno[$man] = $_POST['idno' . $man];
		$gender[$man] = $_POST['gender' . $man];
		$eartag[$man] = $_POST['eartag' . $man];
		$dob[$man] = $_POST['dob' . $man];
		$dow[$man] = $_POST['dow' . $man];
		$dod[$man] = $_POST['dod' . $man];
		$comments[$man] = $_POST['newcomments' . $man];
		foreach (range(0, $genecount - 1, 1) as $i) {
			$genearray[$i][$man] = $_POST['geno' . $i . '-' . $man];
		}
	}

	//prepare sql for dbupdate
	$sqltext = '';
	foreach ($arrayman as $man) {
		//echo $man.'<br>';
		//table_animals

		//check for null dob dow dod
		if (empty($dob[$man])) {
			$dob[$man] = 'null';
		} else {
			$dob[$man] = "'" . $dob[$man] . "'";
		}

		if (empty($dow[$man])) {
			$dow[$man] = 'null';
		} else {
			$dow[$man] = "'" . $dow[$man] . "'";
		}

		if (empty($dod[$man])) {
			$dod[$man] = 'null';
		} else {
			$dod[$man] = "'" . $dod[$man] . "'";
		}

		$sqltext .= "UPDATE `table_animals` 
		SET `line`='" . $line[$man] . "',`idno`='" . $idno[$man] . "',`gender`='" . $gender[$man] . "',`eartag`='" . $eartag[$man] . "',`dob`=" . $dob[$man] . ",`dow`=" . $dow[$man] . ",`dod`=" . $dod[$man] . " 
		WHERE `animalautono`=" . $man . ";" .
			"UPDATE `table_animals` 
		SET `dob`=NULL WHERE `dob`=0;" .
			"UPDATE `table_animals` 
		SET `dow`=NULL WHERE `dow`=0;" .
			"UPDATE `table_animals` 
		SET `dod`=NULL WHERE `dod`=0;";
		//data_comments
		if ($comments[$man] != "") {
			$sqltext .= "INSERT INTO `data_comments` (`animalautono`,`commentdate`,`general_comment`) VALUES (" . $man . ",curdate(),'" . $comments[$man] . "');";
		}
		//table_genotypes
		foreach (range(0, $genecount - 1, 1) as $i) {
			if ($genearray[$i][$man] != "") {
				$sqltext .= "UPDATE `table_genotypes` SET `allele`='" . $genearray[$i][$man] . "' WHERE `animalautono`=" . $man . " and `allelegroup`='" . $genelist[$i] . "';";
			}
		}
	}
	$sqlreport = 'Edit animals ';
	if ($conn->multi_query($sqltext) === TRUE) {
		$sqlstatus = '-successful' . '...' . $sqltext;
	} else {
		$sqlstatus = '-failed ' . $conn->error . '...' . $sqltext;
	}
	$sqlreport .= $sqlstatus;
	$conn->close();
}

?>
<!--php script for display controls-->
<?php
//line list
//gender list
//allele group list
//allele list filtered by selected allelegroups|lines
//cage list
//autogen table for animals editing

// posted variables
$line_filter = $_POST['line_filter'];
$gender_filter = $_POST['gender_filter'];
$source_category_selection = $_POST['source_category_selection'];
$sourcecage_selection = $_POST['sourcecage_selection'];
$animals_selection = $_POST['animals_selection'];
$deadoralive_filter = $_POST['deadoralive_filter'];
$location_filter = $_POST['location_filter'] ?? 'all';
$role_filter     = $_POST['role_filter']     ?? 'all';
$bornbefore = $_POST['bornbefore'];
$bornafter = $_POST['bornafter'];

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

//deadoralive filter
$deadoralive_options = array('alive', 'dead', 'all');
$deadoralive_listbox = '<select id="deadoralive_filter" name="deadoralive_filter" onchange="submitForm()">';
foreach ($deadoralive_options as $row) {
	if ($row === $deadoralive_filter) {
		$deadoralive_listbox .= '<option value="' . $row . '" selected>' . $row . '</option>';
	} else {
		$deadoralive_listbox .= '<option value="' . $row . '">' . $row . '</option>';
	}
}
$deadoralive_listbox .= '</select>';

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
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "call get_lines();";
$results = $conn->query($sqltext);
//set up static portion of table
$line_listbox = '<select id="line_filter" name="line_filter" size=8 class="mediumlistbox" onchange="submitForm()">';
if ($line_filter === "all") {
	$line_listbox .= '<option value="all" selected>all</option>';
} else {
	$line_listbox .= '<option value="all">all</option>';
}
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
}
//close the table
$line_listbox .= '</select>';
$conn->close();

$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$location_listbox = filter_selectbox(location_filter_options($conn), $location_filter, 'location_filter', 'submitForm()', true);
$role_listbox     = filter_selectbox(role_filter_options($conn),     $role_filter,     'role_filter',     'submitForm()', true);
$conn->close();

//cage list filtered by line, gender, etc
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//set filter text

if ($deadoralive_filter === "dead") {
	$doaf = '`dod` is not NULL and ';
} elseif ($deadoralive_filter === "alive") {
	$doaf = '`dod` is NULL and ';
} else {
	$doaf = '';
}

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

if ($source_category_selection === "all") {
	$sf = '';
} else {
	$sf = 'left(`currentcage`,1)=left("' . $source_category_selection . '",1) and ';
}

if ($location_filter === "all" || $location_filter === "") {
	$locf = "";
} else {
	$locf = "currentcage IN (SELECT cageid FROM table_cages WHERE cagelocation_room='" . $conn->real_escape_string($location_filter) . "') and ";
}
if ($role_filter === "all" || $role_filter === "") {
	$rolef = "";
} else {
	$rolef = "currentcage IN (SELECT cageid FROM table_cages WHERE cagerole_assignment='" . $conn->real_escape_string($role_filter) . "') and ";
}

if ($bornbefore == "") {
	$bbf = '';
} else {
	$bbf = "`dob`<='" . $bornbefore . "' and ";
}

if ($bornafter == "") {
	$baf = '';
} else {
	$baf = "`dob`>='" . $bornafter . "' and ";
}

//$sql_where_textbak = substr('`line`=`line` and ' . $lf . $gf . $doaf . $sf, 0, -4);
$sql_where_text = substr('`line`=`line` and ' . $lf . $gf . $doaf . $sf . $bbf . $baf . $locf . $rolef, 0, -4);   // 463
//echo $sql_where_text;
$sqltext = "SELECT `currentcage` FROM `table_animals` where " . $sql_where_text . " GROUP BY `currentcage` ORDER BY `currentcage`;";
//echo $sqltext;
$results = $conn->query($sqltext);
$sourcecage_listbox = '<select id="sourcecage_selection" name="sourcecage_selection" size=8 class="largelistbox" onchange="submitForm()"><option value="all">all</option>';
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

///
if ($deadoralive_filter === "dead") {
	$doaf = "`dod` is not NULL and ";
} elseif ($deadoralive_filter === "alive") {
	$doaf = "`dod` is NULL and ";
} else {
	$doaf = "";
}

//set filter text
if ($line_filter === "all") {
	$lf = '';
} else {
	$lf = "`line`='" . $line_filter . "' and ";
}

if ($gender_filter === "all") {
	$gf = '';
} else {
	$gf = "`gender`='" . $gender_filter . "' and ";
}


if ($source_category_selection === "all") {
	$sf = '';
} else {
	$sf = "left(`currentcage`,1)=left('" . $source_category_selection . "',1) and ";
}

if ($sourcecage_selection == "" or $sourcecage_selection === "all") {
	$cf = '';
} else {
	$cf = "`currentcage`='" . $sourcecage_selection . "' and ";
}

if ($bornbefore == "") {
	$bbf = '';
} else {
	$bbf = "`dob`<='" . $bornbefore . "' and ";
}

if ($bornafter == "") {
	$baf = '';
} else {
	$baf = "`dob`>='" . $bornafter . "' and ";
}

$sql_where_text = substr("`line`=`line` and " . $lf . $gf . $doaf . $sf . $cf . $bbf . $baf . $locf . $rolef, 0, -4); // 523
$animals_sql_where_text = $sql_where_text;

$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,gender,dob,dod,currentcage FROM `table_animals` where " . $sql_where_text . " ORDER BY `line` asc, `animalautono` asc;";
//echo $sqltext;
$results = $conn->query($sqltext);
$animals_results = $results;
$animals_listbox = '<select id="animals_selection" name="animals_selection" size=8 class="mediumlistbox onchange="submitForm()">;';
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
$sqlerror = $conn->error;
$conn->close();



?>

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Manage animals - <?php echo $dbname; ?></title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />

</head>

<body>

	<div id="header">
		<form id="loginbox" action="" method="post">
			<h2 class="centervert"
				style="position:absolute;top:0px;left:75px;">
				-Manage animals-
			</h2>

			<h1 class="centervert"
				style="position:absolute;top:0px;left:350px;">
				<?php echo $dbname; ?>
				<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
			</h1>

			<button id=statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
					 width:20px;height:20px;border-radius:10px;position:absolute;
					 top:15px;right:250px;"></button>



			<table style="color:white;font-size:10px;position:absolute;top:0px;right:60px;">
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

		<?php require_once __DIR__ . '/../includes/nav.php';
	      mb_render_nav($xusername, $xpassword, $_POST['dbname'] ?? ''); ?>

	<!--CONTENT SECTION-->
	<div id="right_content" class="centertext">
		<h2 class="centertext">animal Management</h2>
		<form id="animals_management_form" name="allele_management_form" method=post>
			<input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
			<input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
			<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
			<input type=hidden name="button_login" value="connect" />

			<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
			<script type="text/javascript">
				function submitForm() {
					document.getElementById("animals_management_form").submit();
				}
			</script>
			<table>
				<tr>
					<th>Line Filter</th>
					<th>Cage Filter</th>
					<th>animal List</th>

				</tr>
				<tr>
					<td><?php echo $line_listbox; ?></td>
					<td><?php echo $sourcecage_listbox; ?></td>
					<td><?php echo $animals_listbox; ?></td>

				</tr>
				<tr>
					<td>
						Dead or Alive<br><?php echo $deadoralive_listbox; ?>
						<br>
						Gender<br><?php echo $gender_listbox; ?>
					</td>
					<td>
						Cage Type Filter
						<br>
						<?php echo $source_category_listbox; ?>
					</td>
					<td>
						Location<br><?php echo $location_listbox; ?>
						<br>
						Role<br><?php echo $role_listbox; ?>
					</td>
					<td>
						born before<br><input type=date id="bornbefore" name="bornbefore" value="<?php echo $bornbefore; ?>" onchange="submitForm()">
						<br>
						born after<br><input type=date id="bornafter" name="bornafter" value="<?php echo $bornafter; ?>" onchange="submitForm()">
					</td>
			</table>
			<input type=submit id="get_tempanimals" name="get_tempanimals" value="Get animals">
			<br>
			<?php echo $sqlreport; ?>
			<br>
			<input type=hidden id="animals_sql_where_text" name="animals_sql_where_text" value="<?php echo htmlspecialchars($animals_sql_where_text, ENT_QUOTES); ?>">



			<?php echo $testtable; ?>
			<br>
			<!--hidden tags used for reparsing submitted table
-->
			<?php echo $genepost; ?>
		</form>
	</div>
	</div>
	<!--footer removed to acomodate page size
			<div id="footer">
					 <p class="righttext">
					  NeurobehaviorCore.com &copy; 2016
					 </p>
 			
			</div>
-->

	<script src="../mousebook.js"></script>
</body>

</html>