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

		//query userbook for accessable databases
		$sql="select dbaccess.db_name,db_host,db_accessun,db_accesspw,db_formurl from ".
		"(userpass join userdbaccess on userpass.user_idno=userdbaccess.user_idno) ".
		"join dbaccess on userdbaccess.db_name=dbaccess.db_name ".
		"where user_name='".$xusername."' and user_pass='".$xpassword."' and dbaccess.db_name='".$dbname."';";
	
		$conn=new mysqli("localhost",$ubname,$ubpass,"userbook");
		$results=$conn->query($sql);
		$conn->close();
		while($row=mysqli_fetch_array($results)){
			$accessun=$row['db_accessun'];
			$accesspw=$row['db_accesspw'];
			$host=$row['db_host'];
		
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

//create connection
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

//retreive animals data from db of animals
//*****generate temp list of animals*****



$querylist=array(
	'view_linestatus' =>'Line Status',
	'view_matingstatus' =>'Mating Status',
        'view_cagestatus' =>'Cage Status',
        'view_activeanimals' =>'Active animals'
	);
if (isset($_POST['querytorun'])){
	$xquerytorun=$_POST['querytorun'];

} else {
	$xquerytorun=array_keys($querylist)[0];
}

$curquery=$querylist[$xquerytorun];

$sqltext="SELECT * From ".$xquerytorun ?? 'view_activeanimals'.";";
//run query 
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query($sqltext);

//loop and grab data
$rowdata=array();
while($row=mysqli_fetch_assoc($results)){
	$rowdata[]=$row;
}
$sqlerror=$conn->error;
$conn->close();




//parse data
$headercode='<tr>';
//$headerset=array();
foreach ($rowdata[0] as $k =>$v){
	$headercode.='<th>'.$k.'</th>';
	//$headerset[]=$k;
}

$headercode.='</tr>';

$datacode='';
foreach ($rowdata as $row){
	$datacode.='<tr>';
	foreach($row as $k =>$v){
		$datacode.='<td>'.$v.'</td>';
	}
	$datacode.='</tr>';
}

$temptable='<table>'.$headercode.$datacode.'</table>';
/*
*/

if (isset($_POST['Download'])){

$xquerytorun=$_POST['querytorun'];

$querylist=array(
	'view_linestatus' =>'Line Status',
	'view_matingstatus' =>'Mating Status',
        'view_cagestatus' =>'Cage Status',
        'view_activeanimals' =>'Active animals'
	);


$curquery=$querylist[$xquerytorun];

$sqltext="SELECT * From ".$xquerytorun.";";
//run query 
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query($sqltext);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=DL_'.$querylist[$xquerytorun].'.csv');

$output=fopen("php://output","wb");
//loop and grab data

$rowdata=array();
while($row=mysqli_fetch_assoc($results)){
$rowdata[]=$row;	
}

$headerset=array();
foreach ($rowdata[0] as $k =>$v){
$headerset[]=$k;}

fputcsv($output,$headerset);

foreach ($rowdata as $row){
$dataset=array();
foreach($row as $k =>$v){
$dataset[]=$v;
}
fputcsv($output,$dataset);
}
/**/

$conn->close();
$sqlerror=$conn->error;
fclose($output);
exit;
/**/
}


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

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
			<h2 class="centertext">Query Viewer: <?php echo $curquery; ?></h2>
			<form id="query_viewer" name="query_viewer" method=post>

					<input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />

				<select id="querytorun" name="querytorun" onchange="submitForm()">
					<option value=<?php echo $xquerytorun; ?> selected></option>
					<option value="view_linestatus">Line Status</option>
					<option value="view_matingstatus">Mating Status</option>
                                        <option value="view_cagestatus">Cage Status</option>
                                        <option value="view_activeanimals">Active animals</option>
				</select>

<input type=submit class="button" name="Download"
					  value="Download" />
					 
<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("query_viewer").submit();
}
</script>

			<?php echo $temptable; ?>


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

</body>
</html>