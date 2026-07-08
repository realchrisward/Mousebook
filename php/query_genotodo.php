<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
	<?php
/* issue #14: initialize first-load output variables to prevent PHP 8 undefined-variable warnings on first load */
$host = $accessun = $accesspw = null;
	//setup sql variables
	$xusername=($_POST['xusername'] ?? '');
	$xpassword=($_POST['xpassword'] ?? '');
	
	if (isset($_POST['button_login'])){
		$xusername=($_POST['xusername'] ?? '');
		$xpassword=($_POST['xpassword'] ?? '');
		$xloginstatus=($_POST['loginstatus'] ?? '');
		}
	if (isset($_POST['button_disco'])){
		$xusername='';
		$xpassword='';
		$xloginstatus='red';
		}
		
	$dbname=($_POST['dbname'] ?? '');

		
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
//  *****generate temp list of animals*****

$sqltext="SELECT `line`,`idno`,`dob`,`genotypingrxn` as generxn, `allele` FROM view_unkgenos join key_allelegroupbygenotypingrxn 
on view_unkgenos.allelegroup= key_allelegroupbygenotypingrxn.allelegroup
order by generxn asc, line asc, cast(idno as unsigned) asc, idno;";
//run query 
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query($sqltext);
$animals_results=$results;
//loop and grab data
//echo $sqltext;
$table=[];
$lineidtab=[];
$genelist=[];
while($row=mysqli_fetch_array($results)){

$table[$row['line'].'-'.$row['idno']][$row['generxn']]=$row['allele'];
$lineidtab[$row['line'].'-'.$row['idno']]=array('line'=>$row['line'],'idno'=>$row['idno'],'dob'=>$row['dob']);
$genelist[]=$row['generxn'];
}
$sqlerror=$conn->error;
$conn->close();





//process genelist
$genelist=array_unique($genelist);


$temptable='<table><tr><th>line</th><th>idno</th><th>dob</th>';
foreach($genelist as $g){
	$temptable.='<th>'.$g.'</th>';
	}
$temptable.='</tr>';
foreach(array_keys($table) as $i){
	//echo $i.$table[$i].'<br>';
	$temptable.='<tr>
	<td>'.$lineidtab[$i]['line'].'</td>
	<td>'.$lineidtab[$i]['idno'].'</td>
	<td>'.$lineidtab[$i]['dob'].'</td>';
	foreach($genelist as $g){
		//echo var_dump($table[$i]).'<br>';
		if($table[$i][$g]==""){
		$temptable.='<td>'.''.'</td>';
		} else {
		$temptable.='<td>'.'*[______]'.'</td>';
		}
		}
	$temptable.='</tr>';
}
$temptable.='</table>';

?>

<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Geno TODO - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />
	
</head>
<body>

			<div id="header">
					<form id="loginbox" action="" method="get">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Geno To Do-
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
						value="" style="width:100px;font-size:10px;" /></td>
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

				<?php require_once __DIR__ . '/../includes/nav.php';
	      mb_render_nav($dbname); ?>

<!--CONTENT SECTION-->
			<div id="right_content" class="centertext">
			<h2 class="centertext">animal Management</h2>
			<form id="query_genotodo" name="query_genotodo" method=post>

					 <input type=hidden name="dbname" value="<?php echo ($_POST['dbname'] ?? ''); ?>" />
					 <input type=hidden name="button_login" value="connect" />

<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("query_genotodo").submit();
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

<script src="../mousebook.js"></script>
</body>
</html>