<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
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
	

//create connection
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

//get posted variables
//add strain
if (isset($_POST['button_addstrain'])){
$strain=$_POST['textaddstrain'];
$sqlaction='add strain:';
$sqltext="INSERT INTO `".$dbname."`.`list_strains` (`strains`) VALUES ('".$strain."');";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
		}

//delete strain
if (isset($_POST['button_deletestrain'])){
$strain=$_POST['textdelstrain'];
$sqlaction='delete strain:';
$sqltext="DELETE FROM `".$dbname."`.`list_strains` WHERE `strains`='".$strain."';";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
		}
//edit strain
if (isset($_POST['button_editstrain'])){
$strain=$_POST['textselectedstrain'];
$strainnewtext=$_POST['texteditstrain'];
$sqlaction='edit strain:';
$sqltext="UPDATE `".$dbname."`.`list_strains` SET `strains`='".$strainnewtext."' WHERE `strains`='".$strain."';";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
		}
$buttonmessage=$sqlaction.' '.$strain.' - '.$sqlstatus;

//$buttonmessage=$sqltext;
$conn->close();	
?>
	<!-- php script for content !!!place this after *function calls*!!!-->				 						
<?php 

//create connection
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//check connection
if ($conn->connect_error) {
echo '<h2 class="centertext"> please connect to the database </h2>';
exit;
}
$results=$conn->query("call get_strains()");
//set up static portion of table
$s_table= '<select id="strain_selection" size=20, class="mediumlistbox" onclick="showStrain(this.value)">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
$s_table .= '<option value="'.$row["strains"].'">'.$row['strains'].'</option>';
}
//close the table
$s_table .= '</select>';
//clear results and close connection
$results->close();
$conn->close();
?>			

<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Manage Strains - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>

			<div id="header">
					<form id="loginbox" action="" method="post">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Strains-
					 </h2>
					 
					 <h1 class="centervert"
					 style="position:absolute;top:0px;left:350px;">
					  <?php echo $dbname; ?>
					<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
					 </h1>
					 
					 
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


	

			<div id="right_content" class="centertext">
			<div class="whitespace">
			<h2 class="centertext">Strain Management</h2>
			<p>Current Strains:</p>
			<?php echo $s_table; ?>
			<br>
			<br>
			<form method=post>


					<input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />

				<table>
				<tr>
				<td class="label">Add Strain:</td>
				<td><input type=text id="textaddstrain" name="textaddstrain"</td>
				<td><input type=submit name="button_addstrain"></td>
				</table>
		
				<table>
				<tr>
				<td class="label">Del Strain:</td>
				<td><input type=text id="textdelstrain" name="textdelstrain"</td>
				<td><input type=submit name="button_deletestrain"></td>
				</table>
			
				<table>
				<tr>
				<td class="label">Selected:</td>
				<td><input type=text id="textselectedstrain" name="textselectedstrain" readonly="readonly"></td>
<!--javascript to autoupdate selected strain box-->
<script type="text/javascript">
function showStrain(newValue)
{
	document.getElementById("textselectedstrain").value=newValue;
}
</script>
		
				<td>&rarr;</td>
				<td><input type=text id="texteditstrain" name="texteditstrain"</td>
				<td><input type=submit name="button_editstrain"></td>
				</table>
			</form>
			<p> <?php echo $buttonmessage; ?> </p>
			
			</div>
			</div>
			<div id="footer">
					 <p class="righttext">
					  NeurobehavioralCore.com &copy;2016
					 </p>
 			
			</div>

<script src="../mousebook.js"></script>
</body>
</html>