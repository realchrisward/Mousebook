<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/write.php';
/* issue #14: initialize first-load output variables to prevent PHP 8 undefined-variable warnings on first load */
$host = $accessun = $accesspw = null;
$lf = null; $gf = null; $doaf = null; $sf = null; $bbf = null; $baf = null;
$locf = null; $rolef = null; $cf = null;
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


//create connection
$conn = mb_connect($host, $accessun, $accesspw, $dbname);

//retreive animals data from db of animals
//*****generate temp list of animals*****

// issue #14: keep output vars defined even when neither branch below
// runs (e.g. first page load) so they are never Undefined at echo
$testtable = '';
$genepost = '';
$sqlreport = '';

if (isset($_POST['get_tempanimals'])) {



	// P2 Option B: rebuild WHERE from round-tripped filter VALUES (no client SQL)
	$conn = mb_connect($host, $accessun, $accesspw, $dbname);
	$mbvals = animals_filter_values_from_post($_POST);
	$sql_where_text = animals_where_build($conn, $mbvals) . cage_eq_where($conn, $_POST['sourcecage_selection'] ?? 'all');

	$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,sex,eartag,dob,dow,dod,matingcage,currentcage,parents, `table_cages`.`cagelocation_room` as cagelocation_room, `table_cages`.`cagerole_assignment` as cagerole_assignment FROM `table_animals` LEFT JOIN `table_cages` ON `table_animals`.`currentcage` = `table_cages`.`cageid` where " . $sql_where_text . " ORDER BY `line` asc, `animalautono` asc;";
	//echo $sqltext;
	$sqldatacomments = "SELECT `data_comments`.`animalautono` as 'man',`commentdate`,`general_comment` FROM `data_comments` JOIN `table_animals` ON `data_comments`.`animalautono` = `table_animals`.`animalautono` where " . $sql_where_text . " ;";
	$sqlgenotypes = "SELECT `table_genotypes`.`animalautono` as 'man',`allelegroup`,`allele` FROM `table_genotypes` JOIN `table_animals` ON `table_genotypes`.`animalautono` = `table_animals`.`animalautono` where " . $sql_where_text . " ;";
	//query table_animals
	$conn = mb_connect($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqltext);
	$animals_results = $results;
	//loop and grab data

	$i = 0;
	$arrayman = [];
	while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
		$i = $i + 1;
		$arrayman[$i] = $row['man'];
		$arrayline[$arrayman[$i]] = $row['line'];
		$arrayidno[$arrayman[$i]] = $row['idno'];
		$arraysex[$arrayman[$i]] = $row['sex'];
		$arrayeartag[$arrayman[$i]] = $row['eartag'];
		$arraydob[$arrayman[$i]] = $row['dob'];
		$arraydow[$arrayman[$i]] = $row['dow'];
		$arraydod[$arrayman[$i]] = $row['dod'];
		$arraymat[$arrayman[$i]] = $row['matingcage'];
		$arraycur[$arrayman[$i]] = $row['currentcage'];
		$arraylocation[$arrayman[$i]] = $row['cagelocation_room'];
		$arrayrole[$arrayman[$i]] = $row['cagerole_assignment'];
		$arraypar[$arrayman[$i]] = $row['parents'];
		$arraycom[$arrayman[$i]] = '';
		$arraybkc[$arrayman[$i]] = '';
	}
	$sqlerror = $conn->error;
	$conn->close();
	//query table_genotypes




	//query table_genotypes
	$aglist = [];
	$conn = mb_connect($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqlgenotypes);
	$geno_results = $results;

	// issue #13: initialise so a filter matching 0 genotypes does not
	// leave these undefined for the guard/consumers below
	$arraygeno = [];
	$genelist = [];
	$genoheader = '';
	while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
		$arraygeno[$row['allelegroup']][$row['man']] = "<option value='" . $row['allele'] . "' selected>" . $row['allele'] . "</option>";
		//echo $row['allelegroup'];
	}
	$conn->close();
	$aglist = [];
	if (!empty($arraygeno)) {
		foreach (array_keys($arraygeno) as $ag) {
			$genelist[] = $ag;
			$aglist[$ag] = array('M' => '', 'F' => '', 'all' => '');
			$agfilt[] = "`allelegroup`='" . $ag . "'";
		}
		$agfilt = implode(' or ', $agfilt);


		//get allelegroups
		$sqltext = "SELECT `allelegroup`,`allele`,`sexspecific` FROM `list_allele` WHERE " . $agfilt . ";";
		$conn = mb_connect($host, $accessun, $accesspw, $dbname);
		$results = $conn->query($sqltext);
		$aglist = [];
		while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
			$aglist[$row["allelegroup"]][$row["sexspecific"]] .= '<option value="' . $row['allele'] . '">' . $row['allele'] . '</option>';
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
					if ($arraysex[$j] === "M") {
						$arraygt[$ag][$j] = '<td ><select id="geno' . $i . '-' . $j . '" name="geno' . $i . '-' . $j . '">' . $arraygeno[$ag][$j] . $aglist[$ag]['M'] . $aglist[$ag]['all'] . '</select></td>';
					} elseif ($arraysex[$j] === "F") {
						$arraygt[$ag][$j] = '<td ><select id="geno' . $i . '-' . $j . '" name="geno' . $i . '-' . $j . '">' . $arraygeno[$ag][$j] . $aglist[$ag]['F'] . $aglist[$ag]['all'] . '</select></td>';
					} else {
						$arraygt[$ag][$j] = '<td ><select id="geno' . $i . '-' . $j . '" name="geno' . $i . '-' . $j . '">' . $arraygeno[$ag][$j] . $aglist[$ag]['M'] . $aglist[$ag]['F'] . $aglist[$ag]['all'] . '</select></td>';
					}
				}
			}
			$genoheader .= '<th>' . $ag . '</th>';
		}
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
	$conn = mb_connect($host, $accessun, $accesspw, $dbname);
	$results = $conn->query($sqldatacomments);
	$animals_results = $results;
	//loop and grab data
	while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
		$arraybkc[$row['man']] .= '|[' . $row['commentdate'] . ' : ' . $row['general_comment'] . ']|';
	}


	$temptable = '
<table name="temptable" id="temptable">
<tr name="tempheader" id="tempheader">
<th>-</th>
<th>line</th>
<th>idno</th>
<th>sex</th>
<th>ear_tag</th>
<th>dob</th>
<th>dow</th>
<th>dod</th>
' . $genoheader . '
<th>current cage</th>
<th>location</th>
<th>role</th>
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
<td ><select class="" name="sex' . $ck . '" id="sex' . $ck . '"><option value=' . $arraysex[$ck] . ' selected> ' . $arraysex[$ck] . '
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
<td ><input class="smalllistbox" type=date name="dod' . $ck . '" id="dod' . $ck . '"' . ((isset($arraydod[$ck]) && trim((string)$arraydod[$ck]) !== '') ? ' data-locked="1"' : '') . ' oninput="mbMarkDirty(this)" value=' . $arraydod[$ck] . '>
	</td>' .
			$arraygensel[$ck] . '
<td ><input class="mediumlistbox" type=text name="currentcage' . $ck . '" id="currentcage' . $ck . '" readonly="readonly" value="' . $arraycur[$ck] . '" >
	</td>
<td ><input class="mediumlistbox" type=text name="location' . $ck . '" id="location' . $ck . '" readonly="readonly" value="' . $arraylocation[$ck] . '" >
	</td>
<td ><input class="mediumlistbox" type=text name="role' . $ck . '" id="role' . $ck . '" readonly="readonly" value="' . $arrayrole[$ck] . '" >
	</td>
<td ><input class="smalllistbox" type=text name="sourcecage' . $ck . '" id="sourcecage' . $ck . '" readonly="readonly" value="' . $arraymat[$ck] . '" >
	</td>
<td ><input class="smalllistbox" type=text name="parents' . $ck . '" id="parents' . $ck . '" readonly="readonly" value="' . $arraypar[$ck] . '" >
	</td>
<td ><input class="smalllistbox" type=text name="newcomments' . $ck . '" id="newcomments' . $ck . '" oninput="mbMarkDirty(this)" value="' . $arraycom[$ck] . '">
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
<!-- P4 (#20): group euth / group comment tools. Client-side only; values populate the per-row dod / newcomments inputs, which the user reviews and commits through the normal confirm_changes workflow. The group controls carry no name attribute, so they are never posted. -->
<div id="group_tools" style="margin:6px 0;">
<input type=button id="btn_group_euth" value="Group Euth" onclick="mbToggleGroupEuth()">
<span id="group_euth_ctrl" style="display:none;"> DOD for all managed animals: <input type=date id="group_dod" oninput="mbGroupEuthApply()"></span>
&nbsp;&nbsp;
<input type=button id="btn_group_comment" value="Group Comment" onclick="mbToggleGroupComment()">
<span id="group_comment_ctrl" style="display:none;"> comment for all managed animals: <input type=text id="group_comment" class="smalllistbox"> <input type=button id="btn_group_comment_apply" value="Apply to all" onclick="mbGroupCommentApply()"></span>
</div>
<input type=submit id="confirm_changes" name="confirm_changes" value="Confirm Changes">';
}

//confirm animals and update to db
if (isset($_POST['confirm_changes'])) {
	//get posted variables
	$arrayman = explode(',', ($_POST['mankey'] ?? ''));
	$genecount = ($_POST['genecount'] ?? '');
	foreach (range(0, $genecount - 1, 1) as $i) {
		$genearray[$i] = array(($_POST['geno' . $i] ?? ''));
		$genelist[$i] = ($_POST['geno' . $i] ?? '');
	}
	foreach ($arrayman as $man) {
		$line[$man] = ($_POST['line' . $man] ?? '');
		$idno[$man] = ($_POST['idno' . $man] ?? '');
		$sex[$man] = ($_POST['sex' . $man] ?? '');
		$eartag[$man] = ($_POST['eartag' . $man] ?? '');
		$dob[$man] = ($_POST['dob' . $man] ?? '');
		$dow[$man] = ($_POST['dow' . $man] ?? '');
		$dod[$man] = ($_POST['dod' . $man] ?? '');
		$comments[$man] = ($_POST['newcomments' . $man] ?? '');
		foreach (range(0, $genecount - 1, 1) as $i) {
			$genearray[$i][$man] = ($_POST['geno' . $i . '-' . $man] ?? '');
		}
	}

	// ---------------------------------------------------------------------
	// B-3: this save goes through mb_write(), the single write chokepoint.
	//
	// What it replaces: a multi-statement string, built by concatenating escaped
	// POST values, executed with multi_query(). That shape had three defects that
	// were invisible until you looked for them:
	//
	//   * NO TRANSACTION. Six animals in one submit meant six independent writes.
	//     A failure on the fourth left three saved and three not, with a page that
	//     reported "-failed" and no way to tell which.
	//   * LAST WRITE WINS. It re-wrote every column of every animal on every save,
	//     including columns the user never touched. mb_write() reads the row FOR
	//     UPDATE, diffs, and writes only what changed -- which cuts the write down
	//     to the user's actual edit, and gives Track D an audit trail of real
	//     changes rather than "someone opened the form".
	//
	//     BE CLEAR ABOUT WHAT THIS DOES *NOT* FIX: the diff compares the form
	//     against the CURRENT ROW, not against what the user was shown. If Bob's
	//     form was rendered before Alice saved, Bob's stale value still differs
	//     from the row and is still written -- Alice's edit is still lost. The
	//     lost-update bug (#27a / CONCURRENCY.md §3) is NOT closed by this patch.
	//     Closing it means passing the values the form was RENDERED with as
	//     mb_write's 'expect' option, which then refuses the stale save instead of
	//     clobbering. The seam exists and is tested; wiring the hidden fields into
	//     this form is C-1.
	//   * multi_query() ENABLED STACKED QUERIES on the connection carrying
	//     user-supplied text.
	//
	// It also dropped three whole-table sweeps (`UPDATE table_animals SET dob=NULL
	// WHERE dob=0`, and the same for dow/dod) that ran ONCE PER ANIMAL PER SAVE.
	// They existed to clean up zero-dates written by this very code path; mb_write()
	// writes a real NULL for an empty date, so nothing generates them any more.
	// Cleaning up any zero-dates left by the OLD code is a migration's job, not
	// something to re-run on every keystroke. (Noted for Track C.)
	// ---------------------------------------------------------------------
	$sqlreport = 'Edit animals ';
	$updated = 0; $unchanged = 0; $failed = 0; $detail = array();

	// One transaction around the whole submit: all animals save, or none do.
	mb_tx_begin($conn);

	foreach ($arrayman as $man) {
		$man_id = (int)$man;
		if ($man_id <= 0) { continue; }

		// Empty date fields mean "unknown", which is NULL -- not '' and not 0.
		$vals = array(
			'line'   => $line[$man],
			'idno'   => $idno[$man],
			'sex'    => $sex[$man],
			'eartag' => $eartag[$man],
			'dob'    => (($dob[$man] ?? '') === '') ? null : $dob[$man],
			'dow'    => (($dow[$man] ?? '') === '') ? null : $dow[$man],
			'dod'    => (($dod[$man] ?? '') === '') ? null : $dod[$man],
		);

		$res = mb_write($conn, 'table_animals', 'animalautono', $man_id, $vals);

		if ($res['status'] === 'updated') {
			$updated++;
			$detail[] = $man_id . ': ' . implode(', ', array_keys($res['changed']));
		} elseif ($res['status'] === 'unchanged') {
			$unchanged++;
		} else {
			$failed++;
			$detail[] = $man_id . ': ' . $res['status'] . ' ' . $res['error'];
			continue;
		}

		// data_comments: an append-only log, so there is no before-image to diff --
		// a new comment is a new row. Prepared, not concatenated.
		if (($comments[$man] ?? '') !== '') {
			$stc = $conn->prepare("INSERT INTO `data_comments` (`animalautono`,`commentdate`,`general_comment`) VALUES (?,curdate(),?)");
			if ($stc) {
				$stc->bind_param('is', $man_id, $comments[$man]);
				if (!$stc->execute()) { $failed++; $detail[] = $man_id . ': comment failed'; }
				$stc->close();
			} else {
				$failed++; $detail[] = $man_id . ': comment prepare failed';
			}
		}

		// table_genotypes: one row per (animal, allelegroup). Also a tracked write,
		// so it goes through the chokepoint too, keyed by its own primary key.
		foreach (range(0, $genecount - 1, 1) as $i) {
			if (($genearray[$i][$man] ?? '') === '') { continue; }

			$stg = $conn->prepare("SELECT `genoid` FROM `table_genotypes` WHERE `animalautono`=? AND `allelegroup`=?");
			if (!$stg) { $failed++; $detail[] = $man_id . ': geno lookup failed'; continue; }
			$stg->bind_param('is', $man_id, $genelist[$i]);
			$stg->execute();
			$grow = $stg->get_result()->fetch_assoc();
			$stg->close();
			if ($grow === null) { continue; }   // no such genotype row: unchanged from the old behaviour

			$gres = mb_write($conn, 'table_genotypes', 'genoid', (int)$grow['genoid'],
			                 array('allele' => $genearray[$i][$man]));
			if ($gres['status'] === 'error') {
				$failed++;
				$detail[] = $man_id . ': genotype ' . $genelist[$i] . ' ' . $gres['error'];
			}
		}
	}

	if ($failed > 0) {
		mb_tx_rollback($conn);
		$sqlstatus = '-failed (' . $failed . ') - NOTHING was saved: ' . implode('; ', $detail);
	} elseif (!mb_tx_commit($conn)) {
		$sqlstatus = '-failed - could not commit: ' . $conn->error;
	} else {
		$sqlstatus = '-successful... ' . $updated . ' changed, ' . $unchanged . ' unchanged'
		           . ($detail ? ' [' . implode('; ', $detail) . ']' : '');
	}
	$sqlreport .= $sqlstatus;
	$conn->close();
}

?>
<!--php script for display controls-->
<?php
//line list
//sex list
//allele group list
//allele list filtered by selected allelegroups|lines
//cage list
//autogen table for animals editing

// posted variables
$line_filter = ($_POST['line_filter'] ?? '');
$sex_filter = ($_POST['sex_filter'] ?? '');
$source_category_selection = ($_POST['source_category_selection'] ?? '');
$sourcecage_selection = ($_POST['sourcecage_selection'] ?? '');
$animals_selection = ($_POST['animals_selection'] ?? '');
$deadoralive_filter = ($_POST['deadoralive_filter'] ?? '');
$location_filter = $_POST['location_filter'] ?? 'all';
$role_filter     = $_POST['role_filter']     ?? 'all';
$bornbefore = ($_POST['bornbefore'] ?? '');
$bornafter = ($_POST['bornafter'] ?? '');

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
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
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
}
//close the table
$line_listbox .= '</select>';
$conn->close();

$conn = mb_connect($host, $accessun, $accesspw, $dbname);
$location_listbox = filter_selectbox(location_filter_options($conn), $location_filter, 'location_filter', 'submitForm()', true);
$role_listbox     = filter_selectbox(role_filter_options($conn),     $role_filter,     'role_filter',     'submitForm()', true);
$conn->close();

//cage list filtered by line, sex, etc
$conn = mb_connect($host, $accessun, $accesspw, $dbname);
//set filter text — built server-side via includes/filters.php (P2 Option B)
$mbvals = animals_filter_values_from_post($_POST);
$sql_where_text = animals_where_build($conn, $mbvals);   // filters only (no cage) — feeds the source-cage dropdown
//echo $sql_where_text;
$sqltext = "SELECT `currentcage` FROM `table_animals` where " . $sql_where_text . " GROUP BY `currentcage` ORDER BY `currentcage`;";
//echo $sqltext;
$results = $conn->query($sqltext);
$sourcecage_listbox = '<select id="sourcecage_selection" name="sourcecage_selection" size=8 class="largelistbox" onchange="submitForm()"><option value="all">all</option>';
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

///  P2 Option B: with-cage WHERE via builder
$mbvals = animals_filter_values_from_post($_POST);
$sql_where_text = animals_where_build($conn, $mbvals) . cage_eq_where($conn, $sourcecage_selection);
$animals_sql_where_text = $sql_where_text;

$sqltext = "SELECT table_animals.animalautono as 'man',line,idno,sex,dob,dod,currentcage FROM `table_animals` where " . $sql_where_text . " ORDER BY `line` asc, `animalautono` asc;";
//echo $sqltext;
$results = $conn->query($sqltext);
$animals_results = $results;
$animals_listbox = '<select id="animals_selection" name="animals_selection" size=8 class="mediumlistbox onchange="submitForm()">;';
//loop and prepare table
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) {
	if ($row['man'] === $animals_selection) {
		$animals_listbox .= '<option value="' . $row['man'] . '" selected>' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['sex'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
	} else {
		$animals_listbox .= '<option value="' . $row['man'] . '">' . $row['line'] . '-' . $row['idno'] . ' | ' . $row['sex'] . ' | ' . $row['dob'] . ' | ' . $row['currentcage'] . '</option>';
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

	<!--CONTENT SECTION-->
	<div id="right_content" class="centertext">
		<h2 class="centertext">animal Management</h2>
		<form id="animals_management_form" name="allele_management_form" method=post>
			<input type=hidden name="dbname" value="<?php echo ($_POST['dbname'] ?? ''); ?>" />
			<input type=hidden name="button_login" value="connect" />

			<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
			<script type="text/javascript">
				function submitForm() {
					document.getElementById("animals_management_form").submit();
				}
				// P4 (#20): group euth / group comment. Fan one value across every managed row,
				// skipping any field the user has manually edited this session (data-dirty).
				// Programmatic .value writes do not fire oninput, so group-applies never mark a
				// field dirty; only real typing does.
				function mbManagedKeys() {
					var mk = document.getElementById("mankey");
					if (!mk || mk.value === "") { return []; }
					return mk.value.split(",");
				}
				function mbMarkDirty(el) { if (el) { el.setAttribute("data-dirty", "1"); } }
				function mbToggleGroupEuth() {
					var e = document.getElementById("group_euth_ctrl");
					e.style.display = (e.style.display === "none") ? "inline" : "none";
				}
				function mbToggleGroupComment() {
					var e = document.getElementById("group_comment_ctrl");
					e.style.display = (e.style.display === "none") ? "inline" : "none";
				}
				function mbGroupEuthApply() {
					var d = document.getElementById("group_dod").value;
					mbManagedKeys().forEach(function (man) {
						var f = document.getElementById("dod" + man);
						// skip fields the user hand-edited this session (data-dirty) and animals that
						// already had a DOD when the record was retrieved (data-locked, set server-side)
						// so a pre-existing DOD is never overwritten by a group euth.
						if (f && f.getAttribute("data-dirty") !== "1" && f.getAttribute("data-locked") !== "1") { f.value = d; }
					});
				}
				function mbGroupCommentApply() {
					var c = document.getElementById("group_comment").value;
					mbManagedKeys().forEach(function (man) {
						var f = document.getElementById("newcomments" + man);
						if (f && f.getAttribute("data-dirty") !== "1") { f.value = c; }
					});
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
						Sex<br><?php echo $sex_listbox; ?>
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