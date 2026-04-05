<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<title>Home - Databases</title>
	<link href="../mousebook.css" rel="stylesheet" type="text/css" />
<!--php code-->
	<?php
	//collect config values
	$config = require '../config.php';

	if ($config['debug_mode']=='True'){
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
	}
	

	//setup sql variables
	$host=$config['server_ip'];
	$port=$config['server_port'];
	$socket="";
	$dbname="userbook";
	$ubname=$config['server_user'];
	$ubpass=$config['server_pass'];
	//test connection
	$conn=new mysqli($host,$ubname,$ubpass,$dbname);
	//check connection
	if ($conn->connect_error) {
		$xloginstatus='red';
		}
	else {
		$xloginstatus='green';
		$conn->close();
		}
	
	//get info provided by user
	if (isset($_POST['xusername'])){
		$xusername=$_POST['xusername'];
	} else {
		$xusername='';
	}
	if (isset($_POST['xpassword'])){
		$xpassword=$_POST['xpassword'];
	} else {
		$xpassword='';
	}

	
	
	if (isset($_POST['button_disco'])){
		$xusername="";
		$xpassword="";
	}
	
	if (isset($_POST['button_login'])){}
		//query userbook for accessable databases
		$sql="select dbaccess.db_name,db_accessun,db_accesspw,db_formurl from ".
		"(userpass join userdbaccess on userpass.user_idno=userdbaccess.user_idno) ".
		"join dbaccess on userdbaccess.db_name=dbaccess.db_name ".
		"where user_name='".$xusername."' and user_pass='".$xpassword."';";
		
		$conn=new mysqli($host,$ubname,$ubpass,$dbname);
		$results=$conn->query($sql);
		$conn->close();
		$dbaccesstext='';
		while($row=mysqli_fetch_array($results)){
			
		
		$dbaccesstext.="<form id='dbaccessform' action=".$row['db_formurl']." method='post' target='_blank'>".
		"	<input type='hidden' name='xusername' value='".$xusername."'>".
		"	<input type='hidden' name='xpassword' value='".$xpassword."'>".
		"	<input type='hidden' name='accessun' value='".$row['db_accessun']."'>".
		"	<input type='hidden' name='accesspw' value='".$row['db_accesspw']."'>".
		"	<input type='hidden' name='dbname' value='".$row['db_name']."'>".
		"	<input type='submit' class='dbbutton' name='".$row['db_name']."' value='".$row['db_name']."'>".
		"</form>";
		
		{}
	}
	
	?>	
			
</head>
<body>
	<img class="logo" src="../images/logo.jpg" alt="Mouse Metabolism and Phenotyping Core" width="15%">
	<div class="content-center">
		 
		<h1 class="section">
		Databases
		</h1>
					 	 
		<form id="loginbox" action="" method="post">
			<table>
				<tr>
					<th>Server Connection Status</th>
					<td>
						<button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
						width:20px;height:20px;border-radius:10px;"></button>
					</td>
				</tr>
			</table>
			<table>
				<tr>
					<th>USERNAME</th>
					<td>
						<input type="text" name="xusername" value="<?php echo $xusername; ?>"  />
					</td>
				</tr>
				<tr>
					<th>PASSWORD</th>
					<td>
						<input type="password" name="xpassword" value="<?php echo $xpassword; ?>"  />
					</td>
				</tr>

				<tr>
					<th colspan=2>
						<input type=submit id="loginbutton" name="button_login"
						style="font-size:1em;width:100%;"
						value="connect"
						/>
					</th>
				</tr>

				<tr>
					<th colspan=2>
						<input type=submit id="discobutton" name="button_disco"
						style="font-size:1em;width:100%;"
						value="disco"
						/>
					</th>
				</tr>
			</table>

		</form>
		<table>
			<tr>
				<td>
					<?php echo $dbaccesstext; ?>
				</td>
			</tr>
		</table>
		
	</div>

	<div >
		<p class="footer">
		@realchrisward &copy 2025
		</p>
	</div>


</body>
</html>