<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
<?php
/* issue #14: initialize first-load output variables to prevent PHP 8 undefined-variable warnings on first load */
$xusername = ''; $xpassword = '';
$host = $accessun = $accesspw = null;
$roleB_selection = null; $line_filter = null; $gender_filter = null; $source_category_selection = null; $roleA_selection = null; $lf = null;
$gf = null; $sf = null; $locf = null;

// -------------------------------------------------------
// PATCHED:
// 1. Removed stale $host="{server ip}" - credentials come
//    exclusively from config.php
// 2. animalbook.list_cage_role_assignments -> $dbname
// 3. animalbook.table_cages in UPDATE -> $dbname
// 4. Fixed malformed <select> HTML (missing closing quote)
// -------------------------------------------------------

//setup sql variables
if (isset($_POST['xusername'])) {
	$xusername = ($_POST['xusername'] ?? '');
}
if (isset($_POST['xpassword'])) {
	$xpassword = ($_POST['xpassword'] ?? '');
}
if (isset($_POST['loginstatus'])) {
	$xloginstatus = ($_POST['loginstatus'] ?? '');
}

if (isset($_POST['button_login'])) {
	$xusername = ($_POST['xusername'] ?? '');
	$xpassword = ($_POST['xpassword'] ?? '');
	if (isset($_POST['loginstatus'])) {
		$xloginstatus = ($_POST['loginstatus'] ?? '');
	}
}
if (isset($_POST['button_disco'])) {
	$xusername = '';
	$xpassword = '';
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
$_mb_conn = mb_get_connection($config, $xusername, $xpassword, $dbname);
if ($_mb_conn) {
	[$host, $accessun, $accesspw] = $_mb_conn;
}


$conn = new mysqli($host, $accessun, $accesspw, $dbname);
if ($conn->connect_error) {
	$xloginstatus = 'red';
	echo '<h2 class="centertext"> please connect to the database </h2>';
} else {
	$xloginstatus = 'green';
	$conn->close();
}

// posted variables
if (isset($_POST['line_filter'])) {
	$line_filter = ($_POST['line_filter'] ?? '');
} else {
	$line_filter = 'all';
}

if (isset($_POST['gender_filter'])) {
	$gender_filter = ($_POST['gender_filter'] ?? '');
} else {
	$gender_filter = 'all';
}

if (isset($_POST['source_category_selection'])) {
	$source_category_selection = ($_POST['source_category_selection'] ?? '');
} else {
	$source_category_selection = 'all';
}

if (isset($_POST['category_selection'])) {
	$category_selection = ($_POST['category_selection'] ?? '');
} else {
	$category_selection = '';
}

if (isset($_POST['cage_selection'])) {
	if (is_array(($_POST['cage_selection'] ?? ''))) {
		$cage_selection = ($_POST['cage_selection'] ?? '');
	} else {
		$cage_selection = array(($_POST['cage_selection'] ?? ''));
	}
} else {
	$cage_selection = array('');
}

if (isset($_POST['cagelist_selection'])) {
	$cagelist_selection = ($_POST['cagelist_selection'] ?? '');
} else {
	$cagelist_selection = '';
}

if (isset($_POST['roleA_selection'])) {
	$roleA_selection = ($_POST['roleA_selection'] ?? '');
} else {
	$roleA_selection = 'all';
}

if (isset($_POST['roleB_selection'])) {
	$roleB_selection = ($_POST['roleB_selection'] ?? '');
} else {
	$roleB_selection = 'Limbo';
}

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

//source category type filter
$source_category_options = array('all', 'Holding', 'Mating', 'Experimental', 'Litter', 'Founder', 'Rearrange', 'Sac');
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
$line_listbox = '<select id="line_filter" name="line_filter" size=1 class="mediumlistbox" onchange="submitForm()"><option value="all">all</option>';
while ($row = mysqli_fetch_array($results)) {
	if ($row['line'] === $line_filter) {
		$line_listbox .= '<option value="' . $row["line"] . '" selected>' . $row["line"] . '</option>';
	} else {
		$line_listbox .= '<option value="' . $row["line"] . '">' . $row["line"] . '</option>';
	}
}
$line_listbox .= '</select>';
$conn->close();

// PATCHED: replaced hardcoded `animalbook.list_cage_role_assignments`
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT * FROM `" . $dbname . "`.`list_cage_role_assignments`";
$results = $conn->query($sqltext);
$locA_listbox = '<select id="roleA_selection" name="roleA_selection" size=1 class="mediumlistbox" onchange="submitForm()"><option value="all">all</option>';
$locB_listbox = '<select id="roleB_selection" name="roleB_selection" size=1 class="mediumlistbox" onchange="submitForm()">';
while ($row = mysqli_fetch_array($results)) {
	if ($row['roleassignment_option'] === $roleA_selection) {
		$locA_listbox .= '<option value="' . $row["roleassignment_option"] . '" selected>' . $row["roleassignment_option"] . '</option>';
	} else {
		$locA_listbox .= '<option value="' . $row["roleassignment_option"] . '">' . $row["roleassignment_option"] . '</option>';
	}
	if ($row['roleassignment_option'] === $roleB_selection) {
		$locB_listbox .= '<option value="' . $row["roleassignment_option"] . '" selected>' . $row["roleassignment_option"] . '</option>';
	} else {
		$locB_listbox .= '<option value="' . $row["roleassignment_option"] . '">' . $row["roleassignment_option"] . '</option>';
	}
}
$locA_listbox .= '</select>';
$locB_listbox .= '</select>';
$conn->close();

// roleB contents
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
$sqltext = "SELECT `cageid` FROM `table_cages` where cagerole_assignment ='" . $roleB_selection . "';";
$results = $conn->query($sqltext);
// PATCHED: fixed malformed HTML attribute (missing closing quote on onchange)
$cage_listbox = '<select id="cagelist_selection" name="cagelist_selection[]" multiple="multiple" size=6 class="largelistbox" onchange="">';
while ($row = mysqli_fetch_array($results)) {
	if ($row['cageid'] === $cagelist_selection) {
		$cage_listbox .= '<option value="' . $row['cageid'] . '" selected>' . $row['cageid'] . '</option>';
	} else {
		$cage_listbox .= '<option value="' . $row['cageid'] . '">' . $row['cageid'] . '</option>';
	}
}
$cage_listbox .= '</select>';
$conn->close();

// roleA contents - cage list filtered by line, gender, etc
$conn = new mysqli($host, $accessun, $accesspw, $dbname);
if ($line_filter === "all" or $line_filter === null) {
	$lf = '';
} else {
	$lf = '`line`="' . $line_filter . '" and ';
}

if ($gender_filter === "all" or $gender_filter === null) {
	$gf = '';
} else {
	$gf = '`gender`="' . $gender_filter . '" and ';
}

if ($source_category_selection === "all" or $source_category_selection === null) {
	$sf = '';
} else {
	$sf = 'left(`currentcage`,1)=left("' . $source_category_selection . '",1) and ';
}

if ($roleA_selection === "all" or $roleA_selection === null) {
	$locf = '';
} elseif ($roleA_selection === "unknown") {
	$locf = '(`cagerole_assignment` is null or `cagerole_assignment`="unknown") and ';
} else {
	$locf = '`cagerole_assignment`="' . $roleA_selection . '" and ';
}

$sql_where_text = substr($lf . $gf . $sf . $locf, 0, -4);
if (strlen($sql_where_text) > 0) {
	$sql_where_text = ' and ' . $sql_where_text;
}
$sqltext = "SELECT `currentcage` FROM `table_animals` join `table_cages`
on `table_animals`.`currentcage`=`table_cages`.`cageid`
where dod is null and dob is not null " . $sql_where_text . "
GROUP BY `currentcage`
order by `lineassignment`, field(`cagetype`, 'holding', 'rearrange', 'experimental', 'mating', 'litter', 'sac'), `cageno`;";
$results = $conn->query($sqltext);
$sourcecage_listbox = '<select id="cage_selection" name="cage_selection[]" size=14 class="largelistbox" multiple="multiple" >';
while ($row = mysqli_fetch_array($results)) {
	$nkey = array_search($row['currentcage'], $cage_selection);
	if ($nkey !== false) {
		$sourcecage_listbox .= '<option value="' . $row['currentcage'] . '" selected>' . $row['currentcage'] . '</option>';
	} else {
		$sourcecage_listbox .= '<option value="' . $row['currentcage'] . '">' . $row['currentcage'] . '</option>';
	}
	$cage_batchlist[] = $row['currentcage'];
}
$sourcecage_listbox .= '</select>';
$conn->close();

$cage_batchlist = '("' . implode('"),("', $cage_batchlist) . '")';

$conn = new mysqli($host, $accessun, $accesspw, $dbname);
// PATCHED: replaced hardcoded `animalbook`.`table_cages` with `$dbname`.`table_cages`
if (isset($_POST['addcage_single'])) {
	$cage_selection = ($_POST['cage_selection'] ?? '');
	$cageselection = '("' . implode('","', $cage_selection) . '")';
	$sqltext = "UPDATE `" . $dbname . "`.`table_cages` SET `cagerole_assignment`='" . $roleB_selection . "' WHERE `cageid` in " . $cageselection . ";";
	if ($conn->query($sqltext) === TRUE) {
		$sqlstatus = 'successful' . '...' . $sqltext;
	} else {
		$sqlstatus = 'failed ' . $conn->error . '...' . $sqltext;
	}
	echo $sqlstatus;
}
?>

<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Cage Role- <?php echo $dbname; ?></title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>

<body>
	<div id="header">
		<form id="loginbox" action="" method="post">
			<h2 class="centervert" style="position:absolute;top:0px;left:75px;">
				-Cage Role Manager-
			</h2>
			<h1 class="centervert" style="position:absolute;top:0px;left:350px;">
				<?php echo $dbname; ?>
				<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
			</h1>
			<button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
					 width:20px;height:20px;border-radius:10px;position:absolute;
					 top:15px;right:250px;"></button>
			<table class="logintable" style="color:white;font-size:10px;position:absolute;top:0px;right:60px;">
				<tr>
					<th>user:</th>
					<th><input type="text" name="xusername" value="<?php echo $xusername; ?>" style="width:100px;font-size:10px;" /></th>
				</tr>
				<tr>
					<td>pass:</td>
					<td><input type="password" name="xpassword" value="<?php echo $xpassword; ?>" style="width:100px;font-size:10px;" /></td>
				</tr>
			</table>
			<input type=submit id="loginbutton" name="button_login"
				style="font-size:10px;width:50px;height:20px;position:absolute;top:5px;right:10px;"
				value="connect" />
			<input type=submit id="discobutton" name="button_disco"
				style="font-size:10px;width:50px;height:20px;position:absolute;top:25px;right:10px;"
				value="disco" />
		</form>
	</div>

	<?php require_once __DIR__ . '/../includes/nav.php';
	      mb_render_nav($xusername, $xpassword, $_POST['dbname'] ?? ''); ?>

	<div id="right_content" class="centertext">
		<!--CONTENT SECTION-->
		<form id="cage_selection_form" name="cage_selection_form" method=post>
			<input type=hidden name="xusername" value="<?php echo ($_POST['xusername'] ?? ''); ?>" />
			<input type=hidden name="xpassword" value="<?php echo ($_POST['xpassword'] ?? ''); ?>" />
			<input type=hidden name="dbname" value="<?php echo ($_POST['dbname'] ?? ''); ?>" />
			<input type=hidden name="button_login" value="connect" />
			<script type="text/javascript">
				function submitForm() {
					document.getElementById("cage_selection_form").submit();
				}
			</script>
			<table>
				<tr>
					<th>Line Filter:</th>
					<th>Gender Filter:</th>
					<th>Source Cage Category:</th>
					<th>Current Role:</th>
				</tr>
				<tr>
					<td><?php echo $line_listbox; ?></td>
					<td><?php echo $gender_listbox; ?></td>
					<td><?php echo $source_category_listbox; ?></td>
					<td><?php echo $locA_listbox; ?></td>
				</tr>
			</table>
			<table>
				<tr>
					<th>Current Cages (select to move):</th>
				</tr>
				<tr>
					<td><?php echo $sourcecage_listbox; ?></td>
				</tr>
			</table>
			<table>
				<tr>
					<td><input type=submit id="addcage_single" name="addcage_single" value="&#8595; Assign Selected Cages to Role Below"></td>
				</tr>
				<tr>
					<th>Destination Role:</th>
				</tr>
				<tr>
					<td><?php echo $locB_listbox; ?></td>
				</tr>
				<tr>
					<th>Cages already with this role:</th>
				</tr>
				<tr>
					<td colspan=2><?php echo $cage_listbox; ?></td>
				</tr>
			</table>
			<INPUT type="submit" id="REFRESH" name="REFRESH" value="REFRESH">
		</form>
	</div>
	<div id="footer">
		<p class="righttext">@realchrisward &copy; 2025</p>
	</div>
	<script src="../mousebook.js"></script>
</body>

</html>