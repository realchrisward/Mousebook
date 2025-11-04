<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
	<?php

//error reporting...
//ini_set('display_errors', 'On');
//error_reporting(E_ALL | E_STRICT);


	//setup sql variables
	//setup sql variables
	if(isset($_POST['xusername'])){
	$xusername=$_POST['xusername'];}
	if(isset($_POST['xpassword'])){
	$xpassword=$_POST['xpassword'];}
	if(isset($_POST['loginstatus'])){
	$xloginstatus=$_POST['loginstatus'];}
	$host="{server ip}";
	
	if (isset($_POST['button_login'])){
		$xusername=$_POST['xusername'];
		$xpassword=$_POST['xpassword'];
		if(isset($_POST['loginstatus'])){
		$xloginstatus=$_POST['loginstatus'];}
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
	
	//setup sql variables
	$ubname=$config['server_user'];
	$ubpass=$config['server_pass'];	

		//query userbook for accessable databases
		$sql="select dbaccess.db_name,db_host,db_accessun,db_accesspw,db_formurl from ".
		"(userpass join userdbaccess on userpass.user_idno=userdbaccess.user_idno) ".
		"join dbaccess on userdbaccess.db_name=dbaccess.db_name ".
		"where user_name='".$xusername."' and user_pass='".$xpassword."' and dbaccess.db_name='".$dbname."';";
	
		$conn=new mysqli($host,$ubname,$ubpass,"userbook");
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

//get posted variables

//add role
if (isset($_POST['button_addrole'])){
 $role=$_POST['textaddrole'];
 $status=$_POST['textaddstatus'];
 $contact=$_POST['textaddcontact'];
 $notes=$_POST['textaddnotes'];
 $sqlaction='add role';
 $sqltext="INSERT INTO `".$dbname."`.`list_cage_role_assignments` (`roleassignment_option`, `roleassignment_statuslist`, `maincontact`, `notes`) VALUES 
 ('".$role."', '".$status."', '".$contact."', '".$notes."')";
 if ($conn->query($sqltext) === TRUE) {
  $sqlstatus= 'successful';
  } else {
  $sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
  }
 }
 


//delete role
if (isset($_POST['button_deleterole'])){
 $role=$_POST['textdelrole'];
 $sqlaction='delete role:';
 $sqltext="DELETE FROM `".$dbname."`.`list_cage_role_assignments` WHERE `roleassignment_option`='".$role."';";
 if ($conn->query($sqltext) === TRUE) {
  $sqlstatus= 'successful';
  } else {
  $sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
  }
 }


//edit role
if (isset($_POST['button_editrole'])){
 
 $role=$_POST['textnewrole'];
 $status=$_POST['textnewstatus'];
 $contact=$_POST['textnewcontact'];
 $notes=$_POST['textnewnotes'];
 $sqlaction='edit strain:';
 $sqltext="UPDATE `".$dbname."`.`list_cage_role_assignments` SET `roleassignment_statuslist`='".$status."', 
  `maincontact`='".$contact."', `notes`='".$notes."' 
WHERE `roleassignment_option`='".$role."';";
 if ($conn->query($sqltext) === TRUE) {
  $sqlstatus= 'successful';
  } else {
  $sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
  }
//echo $sqltext;
 }
		
$conn->close();	
 

//create connection
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//check connection
if ($conn->connect_error) {
echo '<h2 class="centertext"> please connect to the database </h2>';
exit;
}

$sqltext="Select * from `".$dbname."`.`list_cage_role_assignments`;";

$results=$conn->query($sqltext);

//set up role table
if (isset($_POST['role_selection'])){
 $currrole=$_POST['role_selection']; 
 } else {
 $currrole="";
 }
$s_table= '<select id="role_selection" name="role_selection" size=12, class="largelistbox" onclick="showRole(this.value)">';
//loop the result set and prepare table

while($row=mysqli_fetch_array($results)) {
 //catch results of each row
 //get results matched to current line - used for additional fields

 if($row['roleassignment_option']===$currrole){
  $currstatus=$row['roleassignment_statuslist'];
  $curractive=$row['roleassignment_active'];
  $currcontact=$row['maincontact'];
  $currnotes=$row['notes'];
  }

 $s_table .= '<option value="'.$row["roleassignment_option"].'">'.$row['roleassignment_option'].'  ['.$row['maincontact'].']</option>';
 }
//close the table
$s_table .= '</select>';
//clear results and close connection
$results->close();
$conn->close();
/**/
?>			

<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Manage Roles - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>

			<div id="header">
					<form id="loginbox" action="" method="post">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Roles/Assignments-
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
			<form id="role_selection_form" method=post>
                        <p>Current Roles:</p>
			<?php echo $s_table; ?>
			


					<input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />

<h3>Add Role:</h3>				
<table>
				<tr>
					<th>Role</th>
					<th>Status Options</th>
					<th>Main Contact</th>
					<th>Notes</th>
				</tr>
				<tr>
					<td><input type=text id="textaddrole" name="textaddrole"></td>
					<td><input type=text id="textaddstatus" name="textaddstatus"></td>
					<td><input type=text id="textaddcontact" name="textaddcontact"></td>
					<td><input type=text id="textaddnotes" name="textaddnotes"></td>
				</tr>
				<tr>
					<td colspan="4"><input type=submit name="button_addrole"></td>
				</tr>
				</table>
				
				<h3>Delete Role:</h3>		
				<table>
				<tr>
					<td class="label">Del Role:</td>
					<td><input type=text id="textdelrole" name="textdelrole"</td>
					<td><input type=submit name="button_deleterole"></td>
				</tr>
				</table>
				
				<h3>Edit Role:</h3>
				<table>
				
<!--javascript to autoupdate selected strain box-->
<script type="text/javascript">
function showRole(newValue)
{
	document.getElementById("role_selection_form").submit();
}
</script>
				<tr>
					<th>Role</th>
					<th>Status Options</th>
					<th>Main Contact</th>
					<th>Notes</th>
				</tr>
				<tr>
					<td><input type=text id="textnewrole" name="textnewrole"
					readonly="readonly" value="<?php echo $currrole; ?>"></td>
					<td><input type=text id="textnewstatus" name="textnewstatus"
					value="<?php echo $currstatus; ?>"></td>
					<td><input type=text id="textnewcontact" name="textnewcontact"
					value="<?php echo $currcontact; ?>"></td>
					<td><input type=text id="textnewnotes" name="textnewnotes"
					value="<?php echo $currnotes; ?>"></td>
				</tr>
				<tr>
					<td><input type=submit name="button_editrole"></td>
				</tr>
				</table>
			</form>
			
			</div>
			</div>
			<div id="footer">
					 <p class="righttext">
					  NeurobehavioralCore.com &copy;2016
					 </p>
 			
			</div>

</body>
</html>
