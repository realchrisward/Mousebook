<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<?php
	//setup sql variables
	$xusername=$_POST['xusername'];
	$xpassword=$_POST['xpassword'];

	if (isset($_POST['button_login'])){
		$xusername=$_POST['xusername'];
		$xpassword=$_POST['xpassword'];
		$xloginstatus=$_POST['loginstatus'];
		}
	if (isset($_POST['button_disco'])){
		$xusername='';
		$xpassword='';
		$xloginstatus='red';
		}

	$dbname=$_POST['dbname'];

	//test login

	// collect config values
	$config = require'../config.php';
	if ($config['debug_mode']=='True'){
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
	}
	//setup sql variables
	$ubname=$config['server_user'];
	$ubpass=$config['server_pass'];

		//query userbook for accessible databases
		// [mb_auth_patched]
		require_once __DIR__ . '/../includes/auth.php';
		$_mb_conn = mb_get_connection($config, $xusername, $xpassword, $dbname);
		if ($_mb_conn) {
			[$host, $accessun, $accesspw] = $_mb_conn;
		}


	$conn=new mysqli($host,$accessun,$accesspw,$dbname);
	//check connection
	if ($conn->connect_error) {
		$xloginstatus='red';
		echo '<h2 class="centertext"> please connect to the database </h2>';
		}
	else {
		$xloginstatus='green';
		$conn->close();
		}

//retrieve animals data from db of animals

$querylist=array(
	'view_linestatus'   => 'Line Status',
	'view_matingstatus' => 'Mating Status',
	'view_cagestatus'   => 'Cage Status',
	'view_activeanimals'=> 'Active Animals'
	);

if (isset($_POST['querytorun'])){
	$xquerytorun=$_POST['querytorun'];
} else {
	$xquerytorun=array_keys($querylist)[0];
}

$curquery=$querylist[$xquerytorun];

// -------------------------------------------------------
// PATCHED: fixed null-coalescing operator precedence bug.
// Original: "SELECT * From ".$xquerytorun ?? 'view_activeanimals'.";";
// The ?? was applied to the whole concatenated string, not
// just $xquerytorun, so the fallback never functioned correctly.
// -------------------------------------------------------
$sqltext="SELECT * FROM ".($xquerytorun ?? 'view_activeanimals').";";

// Handle CSV download before any HTML output
if (isset($_POST['Download'])){
	$xquerytorun=$_POST['querytorun'];
	$sqltext="SELECT * FROM ".$xquerytorun.";";

	$conn=new mysqli($host,$accessun,$accesspw,$dbname);
	$results=$conn->query($sqltext);

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=DL_'.$querylist[$xquerytorun].'.csv');

	$output=fopen("php://output","wb");
	$rowdata=array();
	while($row=mysqli_fetch_assoc($results)){
		$rowdata[]=$row;
	}

	$headerset=array();
	foreach ($rowdata[0] as $k => $v){
		$headerset[]=$k;
	}
	fputcsv($output,$headerset);

	foreach ($rowdata as $row){
		$dataset=array();
		foreach($row as $k => $v){
			$dataset[]=$v;
		}
		fputcsv($output,$dataset);
	}

	$conn->close();
	fclose($output);
	exit;
}

// Run the view query for on-screen display
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query($sqltext);

$rowdata=array();
while($row=mysqli_fetch_assoc($results)){
	$rowdata[]=$row;
}
$sqlerror=$conn->error;
$conn->close();

// Build HTML table
if (!empty($rowdata)){
	$headercode='<tr>';
	foreach ($rowdata[0] as $k => $v){
		$headercode.='<th>'.$k.'</th>';
	}
	$headercode.='</tr>';

	$datacode='';
	foreach ($rowdata as $row){
		$datacode.='<tr>';
		foreach($row as $k => $v){
			$datacode.='<td>'.$v.'</td>';
		}
		$datacode.='</tr>';
	}
	$temptable='<table>'.$headercode.$datacode.'</table>';
} else {
	$temptable='<p>(No results returned)</p>';
}
?>

<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Query Viewer - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>

			<div id="header">
					<form id="loginbox" action="" method="post">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;">
					  -Query Viewer-
					 </h2>

					 <h1 class="centervert"
					 style="position:absolute;top:0px;left:350px;">
					 <?php echo $dbname; ?>
					<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
					 </h1>

					 <!-- PATCHED: fixed malformed id attribute (missing opening quote) -->
					 <button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
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
						value="connect"
						/>
						<input type=submit id="discobutton" name="button_disco"
						style="font-size:10px;width:50px;height:20px;
						position:absolute;top:25px;right:10px;"
						value="disco"
						/>
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
					 <form action="../php/manage_animals.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Animals" />
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
					 <form action="../php/query_animals.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="View Animals" />
					 </form>
					 <form action="../php/cagecard_printer.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Card Printer" />
					 </form>
					 <form action="../php/animal_info_export.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Export Animal Info" />
					 </form>

			</div>

<!--CONTENT SECTION-->
			<div id="right_content" class="centertext">
			<h2 class="centertext">Query Viewer: <?php echo $curquery; ?></h2>
			<form id="query_viewer" name="query_viewer" method=post>

					<input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					<input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					<input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					<input type=hidden name="button_login" value="connect" />

				<select id="querytorun" name="querytorun" onchange="submitForm()">
					<option value="<?php echo $xquerytorun; ?>" selected><?php echo $curquery; ?></option>
					<option value="view_linestatus">Line Status</option>
					<option value="view_matingstatus">Mating Status</option>
					<option value="view_cagestatus">Cage Status</option>
					<option value="view_activeanimals">Active Animals</option>
				</select>

				<input type=submit class="button" name="Download" value="Download" />

<!--javascript to autoupdate form on select change -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("query_viewer").submit();
}
</script>

			<?php echo $temptable; ?>

			</form>
			</div>

			<div id="footer">
					 <p class="righttext">
					  @realchrisward &copy; 2025
					 </p>
			</div>

<script src="../mousebook.js"></script>
</body>
</html>
