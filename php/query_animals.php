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

//genotype filter function


if (isset($_POST['get_genofilt'])) {
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);

	$gtct = 0;
	$gfcount = (int)($_POST['genecount'] ?? 0);

	foreach (range(0, $gfcount - 1, 1) as $i) {
		$agarray[$i] = $_POST['geno' . $i];
		$gfarray[$i] = $_POST['genofilt' . $i];
		if (count($gfarray[$i]) > 0) {
			$gtct += 1;
		}
		foreach ($gfarray[$i] as $gt) {
			$gfor[] = '(allelegroup="' . $agarray[$i] . '" and allele="' . $gt . '")';
		}
	}
	$genofiltertext = implode('<br>', $gfor ?? []);
	$genowhere = implode(' or ', $gfor ?? []);
	$gfsql = 'select animalautono from ' .
		'(select animalautono, count(animalautono) as gtct from table_genotypes ' .
		'where ' . $genowhere . ' group by animalautono) as tmp_tab_gt ' .
		'where gtct=' . $gtct . ';';
	//echo $genowhere; 
	//echo $gfsql;

	$gfresults = $conn->query($gfsql);

	while (($gfresults) && ($row = mysqli_fetch_array($gfresults))) {
		$gtman[] = $row['animalautono'];
	}
	$conn->close();
}
//retreive animals data from db of animals
//*****generate temp list of animals*****

if (isset($_POST['get_tempanimals']) || isset($_POST['get_genofilt']) || isset($_POST['export_csv'])) {
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);

	$sql_where_text = $_POST['animals_sql_where_text'] ?? '';
	$commenttextfilter = $_POST['commenttextfilter'] ?? '';
	if ($commenttextfilter == "") {
		$sqlgenotypes = "SELECT `table_genotypes`.`animalautono` as 'man',`allelegroup`,`allele` FROM `table_genotypes` JOIN `table_animals` ON `table_genotypes`.`animalautono` = `table_animals`.`animalautono` where " . $sql_where_text . " ;";
		$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,gender,eartag,dob,dow,dod,matingcage,currentcage,parents FROM `table_animals` where " . $sql_where_text . " ORDER BY `line` asc, `animalautono` asc;";
	} else {
		$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,gender,eartag,dob,dow,dod,matingcage,currentcage,parents FROM `table_animals` JOIN (select animalautono, general_comment from data_comments where general_comment REGEXP '" . $commenttextfilter . "') as dc on table_animals.animalautono=dc.animalautono where " . $sql_where_text . " GROUP By table_animals.animalautono ORDER BY `line` asc, `table_animals`.`animalautono` asc;";

		$sqlgenotypes = "SELECT `table_genotypes`.`animalautono` as 'man',`allelegroup`,`allele` FROM `table_genotypes` JOIN `table_animals` ON `table_genotypes`.`animalautono` = `table_animals`.`animalautono` JOIN (select animalautono, general_comment from data_comments where general_comment REGEXP '" . $commenttextfilter . "') as dc on table_animals.animalautono=dc.animalautono WHERE " . $sql_where_text . " GROUP BY table_animals.animalautono,allelegroup,allele;";
	}
	//echo $sqlgenotypes;
	$sqldatacomments = "SELECT `data_comments`.`animalautono` as 'man',`commentdate`,`general_comment` FROM `data_comments` JOIN `table_animals` ON `data_comments`.`animalautono` = `table_animals`.`animalautono`  where " . $sql_where_text . " ;";
	//query table_animals
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqltext);
	$animals_results = $results;
	//loop and grab data

	$i = 0;
	while (($results) && ($row = mysqli_fetch_array($results))) {
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

	while (($results) && ($row = mysqli_fetch_array($results))) {
		$arraygeno[$row['allelegroup']][$row['man']] = "<option value='" . $row['allele'] . "' selected>" . $row['allele'] . "</option>";
		//echo $row['allelegroup'];
	}
	$conn->close();
	$aglist = [];
	foreach (array_keys($arraygeno ?? []) as $ag) {
		$genelist[] = $ag;
		$aglist[$ag] = array('M' => '', 'F' => '', 'all' => '');
		$agfilt[] = "`allelegroup`='" . $ag . "'";
	}
	if (isset($agfilt)) {
		$agfilt = " WHERE " . implode(' or ', $agfilt ?? []);
	} else {
		$agfilt = "";
	}

	//get allelegroups
	$sqltext = "SELECT `allelegroup`,`allele`,`genderspecific` FROM `list_allele` " . $agfilt . ";";
	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqltext);
	$aglist = [];
	while (($results) && ($row = mysqli_fetch_array($results))) {
		$aglist[$row["allelegroup"]][$row["genderspecific"]] .= '<option value="' . $row['allele'] . '">' . $row['allele'] . '</option>';
	}
	$conn->close();
	//echo $sqltext;
	$genecount = count($genelist ?? []);
	$genepost = '';
	foreach (range(0, $genecount - 1, 1) as $i) {
		$genepost .= '<input type=hidden id="geno' . $i . '" name="geno' . $i . '" value="' . $genelist[$i] . '">';
	}
	$genepost .= '<input type=hidden id="genecount" name="genecount" value="' . $genecount . '" >';

	//-----prepare text boxes for genotypes
	$genoheader = '';
	$genfiltheader = '';
	//getallelegroup array with alleles
	foreach (range(0, $genecount - 1, 1) as $i) {
		$ag = $genelist[$i];
		foreach ($arrayman as $j) {
			if ($arraygeno[$ag][$j] == "") {
				$arraygt[$ag][$j] = "<td>NA</td>";
			} else {
				$arraygt[$ag][$j] = "<td>" . $arraygeno[$ag][$j] . "</td>";
			}
		}
		$genfiltheader .= '<th valign="bottom">' .
			$ag . '<br><br>' .
			'<select multiple id="genofilt' . $i . '[]" name="genofilt' . $i . '[]" >' . $aglist[$ag]['M'] . $aglist[$ag]['F'] . $aglist[$ag]['all'] . '</select>' .
			'</th>';
		$genoheader .= '<th valign="bottom">' .
			$ag . '</th>';
	}
	//------rearrange selection boxes 
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
	while (($results) && ($row = mysqli_fetch_array($results))) {
		$arraybkc[$row['man']] .= '|[' . $row['commentdate'] . ' : ' . $row['general_comment'] . ']|';
	}


	$temptable = '
<table name="temptable" id="temptable">
<tr name="tempfilter" id="tempfilter">
<th>-</th>
<th>-</th>
<th>-</th>
<th>-</th>
<th>-</th>
<th>-</th>
<th>-</th>
<th>-</th>
' . $genfiltheader . '
<th>-</th>
<th>-</th>
<th>-</th>	
<th ><input type=text class="largelistbox" style="border:none" value="" readonly>-</th>
</tr>

</tr>
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
<th >bulk comments</th>
</tr>
';

	$temprow = '';

	foreach ($arrayman as $ck) {
		if (count($gtman ?? []) == 0) {
			$gtman = $arrayman;
		}
		if (in_array($ck, $gtman, true)) {
			$temprow .= '
<tr name="trow' . $ck . '" id="trow' . $ck . '">
<td >' . '-<input type="hidden" id="man' . $ck . '" name="man' . $ck . '" value="man' . $ck . '">' . '
	</td>
<td >' . $arrayline[$ck] . '
	</td>
<td >' . $arrayidno[$ck] . '
	</td>
<td >' . $arraygender[$ck] . '
	</td>
<td>' . $arrayeartag[$ck] . '
	</td>
<td >' . $arraydob[$ck] . '
	</td>
<td >' . $arraydow[$ck] . '
	</td>
<td >' . $arraydod[$ck] . '
	</td>' .
				$arraygensel[$ck] . '
<td >' . $arraycur[$ck] . '
	</td>
<td >' . $arraymat[$ck] . '
	</td>
<td >' . $arraypar[$ck] . '
	</td>
<td >' . $arraybkc[$ck] . '
	</td>
</tr>
';
		}
	}
	//finish table and add key list for grabbing data from the table
	$testtable = '<input type=submit id="get_genofilt" name="get_genofilt" value="Geno Filter" >' . '<br>' . $genofiltertext . '<br>' .
		$temptable . $temprow . '</table>
<br><br>
<input type=hidden id="mankey" name="mankey" value="' . implode(',', $arrayman ?? []) . '">';
	$conn->close();
}
// ---- CSV export: mirrors the on-screen "Get animals" table (genotype columns + bulk comments) ----
if (isset($_POST['export_csv'])) {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}

	$genelist = $genelist ?? array();
	$arrayman = $arrayman ?? array();
	if (empty($gtman)) {
		$gtman = $arrayman;
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=DL_query_animals_' . date('Ymd_His') . '.csv');
	$out = fopen('php://output', 'wb');

	$headerrow = array('animalautono', 'line', 'idno', 'gender', 'ear_tag', 'dob', 'dow', 'dod');
	foreach ($genelist as $ag) {
		$headerrow[] = $ag;
	}
	array_push($headerrow, 'current cage', 'source cage', 'parents', 'bulk comments');
	fputcsv($out, $headerrow);

	foreach ($arrayman as $ck) {
		if (!in_array($ck, $gtman, true)) {
			continue;
		}
		$rowout = array(
			$ck,
			$arrayline[$ck]   ?? '',
			$arrayidno[$ck]   ?? '',
			$arraygender[$ck] ?? '',
			$arrayeartag[$ck] ?? '',
			$arraydob[$ck]    ?? '',
			$arraydow[$ck]    ?? '',
			$arraydod[$ck]    ?? '',
		);
		foreach ($genelist as $ag) {
			$cell = isset($arraygeno[$ag][$ck]) ? trim(strip_tags($arraygeno[$ag][$ck])) : '';
			$rowout[] = ($cell === '') ? 'NA' : $cell;
		}
		$rowout[] = $arraycur[$ck] ?? '';
		$rowout[] = $arraymat[$ck] ?? '';
		$rowout[] = $arraypar[$ck] ?? '';
		$rowout[] = $arraybkc[$ck] ?? '';
		fputcsv($out, $rowout);
	}
	fclose($out);
	exit;
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
$line_filter = $_POST['line_filter'] ?? '';
$gender_filter = $_POST['gender_filter'] ?? '';
$source_category_selection = $_POST['source_category_selection'] ?? '';
$sourcecage_selection = $_POST['sourcecage_selection'] ?? '';
$animals_selection = $_POST['animals_selection'] ?? '';
$deadoralive_filter = $_POST['deadoralive_filter'] ?? '';
//for dob filter
$bornbefore = $_POST['bornbefore'] ?? '';
$bornafter = $_POST['bornafter'] ?? '';
//for dod filter
$deadbefore = $_POST['deadbefore'] ?? '';
$deadafter = $_POST['deadafter'] ?? '';
$linetextfilter = $_POST['linetextfilter'] ?? '';
$idnotextfilter = $_POST['idnotextfilter'] ?? '';
$sourcetextfilter = $_POST['sourcetextfilter'] ?? '';
$parenttextfilter = $_POST['parenttextfilter'] ?? '';
$location_filter = $_POST['location_filter'] ?? 'all';
$role_filter     = $_POST['role_filter']     ?? 'all';

//gender filter — via shared library
$gender_listbox = filter_selectbox(gender_options(), $gender_filter, 'gender_filter', 'submitForm()', true, '');

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

//source category (cage type) filter — canonical vocab via shared library
$source_category_listbox = filter_selectbox(cagetype_options(), $source_category_selection, 'source_category_selection', 'submitForm()', true, '');

//line filter — via shared library
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$line_listbox = filter_selectbox(line_filter_options($conn), $line_filter, 'line_filter', 'submitForm()', true, 'mediumlistbox', 8);
$conn->close();

//location and role filters
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$location_listbox = filter_selectbox(location_filter_options($conn), $location_filter, 'location_filter', 'submitForm()', true);
$role_listbox     = filter_selectbox(role_filter_options($conn),     $role_filter,     'role_filter',     'submitForm()', true);
$conn->close();

//cage list filtered by line, gender, etc
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
//set filter text

if ($deadoralive_filter === "dead") {
	$doaf = "`dod` is not NULL and ";
} elseif ($deadoralive_filter === "alive") {
	$doaf = "`dod` is NULL and ";
} else {
	$doaf = "";
}

if ($line_filter === "all") {
	$lf = "";
} else {
	$lf = "`line`='" . $line_filter . "' and ";
}

if ($gender_filter === "all") {
	$gf = "";
} else {
	$gf = "`gender`='" . $gender_filter . "' and ";
}

if ($source_category_selection === "all") {
	$sf = "";
} else {
	$sf = "left(`currentcage`,1)=left('" . $source_category_selection . "',1) and ";
}

if ($bornbefore == "") {
	$bbf = "";
} else {
	$bbf = "`dob`<='" . $bornbefore . "' and ";
}

if ($bornafter == "") {
	$baf = "";
} else {
	$baf = "`dob`>='" . $bornafter . "' and ";
}

if ($deadbefore == "") {
	$dbf = "";
} else {
	$dbf = "`dod`<='" . $deadbefore . "' and ";
}

if ($deadafter == "") {
	$daf = "";
} else {
	$daf = "`dod`>='" . $deadafter . "' and ";
}

if ($linetextfilter == "") {
	$ltf = "";
} else {
	$ltf = "`line` REGEXP '\\\\b" . $linetextfilter . "\\\\b' and ";
}

if ($idnotextfilter == "") {
	$itf = "";
} else {
	$itf = "`idno` REGEXP '\\\\b" . $idnotextfilter . "\\\\b' and ";
}

if ($sourcetextfilter == "") {
	$stf = "";
} else {
	$stf = "`matingcage` REGEXP '\\\\b" . $sourcetextfilter . "\\\\b' and ";
}

if ($parenttextfilter == "") {
	$ptf = "";
} else {
	$ptf = "`parents` REGEXP '\\\\b" . $parenttextfilter . "\\\\b' and ";
}

if ($location_filter === "all" || $location_filter === "") {
	$locf = "";
} else {
	$locf = 'currentcage IN (SELECT cageid FROM table_cages WHERE cagelocation_room="' . $conn->real_escape_string($location_filter) . '") and ';
}

if ($role_filter === "all" || $role_filter === "") {
	$rolef = "";
} else {
	$rolef = 'currentcage IN (SELECT cageid FROM table_cages WHERE cagerole_assignment="' . $conn->real_escape_string($role_filter) . '") and ';
}

$sql_where_untrim = '`line`=`line` and ' . $lf . $gf . $doaf . $sf . $bbf . $baf . $dbf . $daf . $ltf . $itf . $stf . $ptf . $locf . $rolef;
$sql_where_text = substr('`line`=`line` and ' . $lf . $gf . $doaf . $sf . $bbf . $baf . $dbf . $daf . $ltf . $itf . $stf . $ptf . $locf . $rolef, 0, -4);
//echo $sql_where_text;
$sqltext = "SELECT `currentcage` FROM `table_animals` where " . $sql_where_text . " GROUP BY `currentcage` ORDER BY `currentcage`;";
//echo $sqltext;
$results = $conn->query($sqltext);
$sourcecage_listbox = '<select id="sourcecage_selection" name="sourcecage_selection" size=8 class="largelistbox" onchange="submitForm()"><option value="all">all</option>';
while (($results) && ($row = mysqli_fetch_array($results))) {
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

/*
if($deadoralive_filter==="dead"){
$doaf="`dod` is not NULL and ";}
elseif ($deadoralive_filter==="alive"){
$doaf="`dod` is NULL and ";}
else {
$doaf="";}

//set filter text
if ($line_filter==="all"){
$lf='';} else {
$lf="`line`='".$line_filter."' and ";}

if ($gender_filter==="all"){
$gf='';} else {
$gf="`gender`='".$gender_filter."' and ";}






if ($bornbefore==""){
$bbf='';}
else {
$bbf="`dob`<='".$bornbefore ."' and ";
}

if ($bornafter==""){
$baf='';}
else {
$baf="`dob`>='".$bornafter ."' and ";
}

if ($deadbefore==""){
	$dbf='';}
else {
	$dbf="`dod`<='".$deadbefore."' and ";
}

if ($deadafter==""){
	$daf='';}
else {
	$daf="`dod`>='".$deadafter."' and ";
}
*/
if ($sourcecage_selection == "" or $sourcecage_selection === "all") {
	$cf = '';
} else {
	$cf = "`currentcage`='" . $sourcecage_selection . "' and ";
}

$animals_sql_where_text = substr($sql_where_untrim . $cf, 0, -4);
//$animals_sql_where_text=substr("`line`=`line` and ".$lf.$gf.$doaf.$sf.$cf.$bbf.$baf.$dbf.$daf,0,-4);
//$animals_sql_where_text=$sql_where_text;

$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,gender,dob,dod,currentcage FROM `table_animals` where " . $animals_sql_where_text . " ORDER BY `line` asc, `animalautono` asc;";
$results = $conn->query($sqltext);
//echo $sqltext;

$animals_results = $results;
$animals_listbox = '<select id="animals_selection" name="animals_selection" size=8 class="mediumlistbox onchange="submitForm()">;';
//loop and prepare table
while (($results) && ($row = mysqli_fetch_array($results))) {
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

// ---- CSV export of the current animal filter set ----
if (isset($_POST['export_csv'])) {
	while (ob_get_level() > 0) {
		ob_end_clean();
	} // drop buffered HTML so the stream is clean

	$conn = new mysqli($host, $accessun, $accesspw, $dbname);
	$csvsql = "SELECT table_animals.animalautono AS 'man', line, idno, gender, eartag, dob, dow, dod, matingcage, currentcage, parents "
		. "FROM `table_animals` WHERE " . $animals_sql_where_text
		. " ORDER BY `line` asc, `animalautono` asc;";
	$csvres = $conn->query($csvsql);

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=DL_query_animals_' . date('Ymd_His') . '.csv');

	$out = fopen('php://output', 'wb');
	$wroteheader = false;
	while (($csvres) && ($row = mysqli_fetch_assoc($csvres))) {
		if (!$wroteheader) {
			fputcsv($out, array_keys($row));
			$wroteheader = true;
		}
		fputcsv($out, $row);
	}
	if (!$wroteheader) {
		fputcsv($out, array('man', 'line', 'idno', 'gender', 'eartag', 'dob', 'dow', 'dod', 'matingcage', 'currentcage', 'parents'));
	}
	$conn->close();
	fclose($out);
	exit;
}


?>

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Query animals - <?php echo $dbname; ?></title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>

<body>

	<div id="header">
		<form id="loginbox" action="" method="post">
			<h2 class="centervert"
				style="position:absolute;top:0px;left:75px;">
				-Query animals-
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

	</div>

	<!--CONTENT SECTION-->
	<div id="right_content" class="centertext">
		<h2 class="centertext">Query animals</h2>
		<form id="animals_management_form" name="animals_management_form" method=post>

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
					<td colspan=3>
						<table>
							<tr>

								<td>
									<table>
										<tr>
											<th>Dead/Alive</th>
											<td><?php echo $deadoralive_listbox; ?></td>
										</tr>
										<tr>
											<th>Gender</th>
											<td><?php echo $gender_listbox; ?></td>
										</tr>
										<tr>
											<th>Line</th>
											<td><input type="text" class="smallerlistbox" name="linetextfilter" id="linetextfilter" value="<?php echo $linetextfilter; ?>" onchange="submitForm()"></td>
										</tr>
										<tr>
											<th>IdNo</th>
											<td><input type="text" class="smallerlistbox" name="idnotextfilter" id="idnotextfilter" value="<?php echo $idnotextfilter; ?>" onchange="submitForm()"></td>
										<tr>
											<th>Location</th>
											<td><?php echo $location_listbox; ?></td>
										</tr>
										<tr>
											<th>Role</th>
											<td><?php echo $role_listbox; ?></td>
										</tr>
							</tr>
						</table>
					</td>

					<td>
						<table>
							<tr>
								<th>Cage Type</th>
								<td><?php echo $source_category_listbox; ?></td>
							</tr>
							<tr>
								<th colspan=2>Source Cage</th>
							</tr>
							<tr>
								<td colspan=2><input type="text" class="mediumlistbox" name="sourcetextfilter" id="sourcetextfilter" value="<?php echo $sourcetextfilter; ?>" onchange="submitForm()"></td>
							</tr>
							<tr>
								<th>Parents</th>
								<td><input type="text" class="smallerlistbox" name="parenttextfilter" id="parenttextfilter" value="<?php echo $parenttextfilter; ?>" onchange="submitForm()"></td>
							</tr>
						</table>
					</td>

					<td>
						<table>
							<tr>
								<th>born before</th>
								<th>born after</th>
							</tr>
							<tr>
								<td>
									<input type=date id="bornbefore" name="bornbefore" value="<?php echo $bornbefore; ?>" onchange="submitForm()">
								</td>
								<td>
									<input type=date id="bornafter" name="bornafter" value="<?php echo $bornafter; ?>" onchange="submitForm()">
								</td>
							</tr>
							<tr>
								<th>died before</th>
								<th>died after</th>
							</tr>
							<tr>
								<td>
									<input type=date id="deadbefore" name="deadbefore" value="<?php echo $deadbefore; ?>" onchange="submitForm()">
								</td>
								<td>
									<input type=date id="deadafter" name="deadafter" value="<?php echo $deadafter; ?>" onchange="submitForm()">
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			</td>
			</tr>
			<tr>
				<th>Comments filter:</th>
				<td colspan=2>
					<input class="largelistbox" type="text" name="commenttextfilter" id="commenttextfilter" value="<?php echo $commenttextfilter; ?>">
				</td>
			</tr>
			</table>
			<input type=submit id="get_tempanimals" name="get_tempanimals" value="Get animals">
			<input type=submit id="export_csv" name="export_csv" value="Download CSV">
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
					  @realchrisward &copy; 2025
					 </p>
 			
			</div>
-->

	<script src="../mousebook.js"></script>
</body>

</html>