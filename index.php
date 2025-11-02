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

	// use userbook to check credentials	
	$ubname="{server login}";
	$ubpass="{server pass}";

		//query userbook for accessable databases
		$sql="select dbaccess.db_name,db_host,db_accessun,db_accesspw,db_formurl,db_subject_plural,db_subject_single,db_guide1_title,db_guide1_url from ".
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
			$subjectPlural=$row['db_subject_plural'];
			$subjectSingle=$row['db_subject_single'];
			$guide1title=$row['db_guide1_title'];
			$guide1url=$row['db_guide1_url'];
		
		}
		
	$conn=new mysqli($host,$accessun,$accesspw,$dbname);
	//check connection
	if ($conn->connect_error) {
		$xloginstatus='red';
		}
	else {
		$xloginstatus='green';
		$conn->close();
		}
	
	?>	
<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Home - <?php echo $dbname; ?></title>
			<link href="mousebook.css" rel="stylesheet" type="text/css" />			
</head>
<body>

			<div id="header">
					 <form id="loginbox" action="" method="post">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Home-
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
					 
					 <form action="./index.php" method=post>
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  style="background-color:#217190; color:lightgrey;"
					  value="Home" />
					  <br>
					  </form>
					 <form action="./php/manage_alleles.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Alleles" />
					 </form>					 
					 <form action="./php/manage_strains.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Strains" />
					 </form>					 
					 <form action="./php/manage_lines.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Lines" />
					 </form>
					
					 </form>					 
					 <form action="./php/add_mice.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Add Mice" />
					 </form>
					  <form action="./php/record_dead_pups.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Record Dead Pups" />
					 </form>
					 </form>					 
					 <form action="./php/manage_mice.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Mice" />
					 </form>
					 </form>					 
					 <form action="./php/manage_cages.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Cages" />
					 </form>
					 
					 <form action="./php/query_genotodo.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Plan Genotyping" />
					 </form>
					 <form action="./php/query_viewer.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="View Database Queries" />
					 </form>
					 <form action="./php/query_mice.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="View Mice" />
					 </form>
                                         <form action="./php/cagecard_printer.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Card Printer" />
					 </form>

                                         <form action="./php/mouse_info_export.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Export Mouse Info" />
					 </form>

                                         <form action="./php/manage_roles.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Roles/Exp." />
					 </form>

                                         <form action="./php/cagerole.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Assign Cages to Roles" />
					 </form>
					  
			</div>


	<!-- php script for page-->				 						
<?php 




//create connection
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//check connection
if ($conn->connect_error) {
echo '<h2 class="centertext"> please connect to the database </h2>';
exit;
}
//run stored proc for colony stats
$result = $conn->query("call get_colonystats()") ;
$conn->close();
//loop the result set
$row = $result->fetch_array();   
$totalmiceindb=$row['totalmiceindb'];
$alivemice=$row['alivemice'];
$deadmice=$row['deadmice'];
$micewithnodob=$row['micewithnodob'];
$currentmonthyr=$row['currentmonthyr'];
$x1monthprev=$row['1monthprev'];
$x2monthprev=$row['2monthprev'];
$x3monthprev=$row['3monthprev'];
$x4monthprev=$row['4monthprev'];
$x5monthprev=$row['5monthprev'];
$x6monthprev=$row['6monthprev'];
$births_currmonth=$row['births_currmonth'];
$births_1monthprev=$row['births_1monthprev'];
$births_2monthprev=$row['births_2monthprev'];
$births_3monthprev=$row['births_3monthprev'];
$births_4monthprev=$row['births_4monthprev'];
$births_5monthprev=$row['births_5monthprev'];
$births_6monthprev=$row['births_6monthprev'];
$deaths_currmonth=$row['deaths_currmonth'];
$deaths_1monthprev=$row['deaths_1monthprev'];
$deaths_2monthprev=$row['deaths_2monthprev'];
$deaths_3monthprev=$row['deaths_3monthprev'];
$deaths_4monthprev=$row['deaths_4monthprev'];
$deaths_5monthprev=$row['deaths_5monthprev'];
$deaths_6monthprev=$row['deaths_6monthprev'];



//get weaning list
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//$results = $conn->query("call get_weanlist();");

$sqltext = "select `currentcage`, max(datediff(curdate(),`dob`)) as age, `dob`, `line` from `".$dbname."`.`table_mice` "
."where (dod is null and (left(currentcage,1)='L' or left(currentcage,1)='F')) "
."group by `currentcage` order by age desc, currentcage asc;";

//$sqltext = "call get_weanlist();"

$results = $conn->query("$sqltext");

/**/
$conn->close();
$weantable="<table><tr><th>Cages to be weaned</th><th>DOB</th><th>AGE</th><th>---</th><th>---</th></tr>";
while($row=mysqli_fetch_array($results)){

$ManageWean=""
."<form action='../php/manage_cages.php' method=post target='_blank'>"
."<input type=hidden name='xusername' value='".$xusername."' />"
."<input type=hidden name='xpassword' value='".$xpassword."' />"
."<input type=hidden name='dbname' value='".$dbname."' />"
."<input type=hidden name='button_login' value='connect' />"
."<input type=submit style='background-color:#217190; color:lightgrey;' value='WEAN' />"

."<input type=hidden name='line_filter' value='".$row['line']."' />"
."<input type=hidden name='line_assignment' value='".$row['line']."' />"
."<input type=hidden name='line_sync' value='".$row['line']."' />"

."<input type=hidden name='gender_filter' value='all' />"
."<input type=hidden name='source_category_selection' value='all' />"
."<input type=hidden name='sourcecage_selection' value='".$row['currentcage']."' />"
."<input type=hidden name='setupdate' value='".date('Y-m-d', strtotime($row['dob']. ' + 21 days'))."' />"
."<input type=hidden name='category_selection' value='Holding' />";

if (substr($row['currentcage'],0,1)==="F"){
$ManageWean.="<input type=hidden name='move_selection' value='Cage Transfer' />";
} else {
$ManageWean.="<input type=hidden name='move_selection' value='Weaning' />";
}
/**/
$ManageWean.="</form>";


$ManageGeno=""
."<form action='../php/manage_mice.php' method=post target='_blank'>"
."<input type=hidden name='xusername' value='".$xusername."' />"
."<input type=hidden name='xpassword' value='".$xpassword."' />"
."<input type=hidden name='dbname' value='".$dbname."' />"
."<input type=hidden name='button_login' value='connect' />"
."<input type=submit style='background-color:#217190; color:lightgrey;' value='Geno' />"

."<input type=hidden name='line_filter' value='".$row['line']."' />"
."<input type=hidden name='gender_filter' value='all' />"
."<input type=hidden name='source_category_selection' value='all' />"
."<input type=hidden name='deadoralive_filter' value='alive' />"
."<input type=hidden name='sourcecage_selection' value='".$row['currentcage']."' />"
."</form>";


$weantable.="<tr><td>".$row['currentcage']."</td><td>".date('Y-m-d',strtotime($row['dob']))."</td><td>".$row['age']."</td><td>".$ManageWean."</td><td>".$ManageGeno."</td></tr>";
}
$weantable.="</table>";

?>
			<div id="right_content">
			<h2 class="centertext">Current Colony Stats</h2>
<table style="width:400px;"> 
	<tr>
		<th>Total Mice</th>
		<th>Alive Mice</th>
		<th>Dead Mice</th>
		<th>'Deleted' Mice</th>
	</tr>
	<tr>
		<td><?php echo $totalmiceindb; ?></td>
		<td><?php echo $alivemice; ?></td>
		<td><?php echo $deadmice; ?></td>
		<td><?php echo $micewithnodob; ?></td>
	</tr>
</table>
<table style="width:400px;">
	<tr>
		<th>Month</th>
		<th>Births</th>
		<th>Deaths</th>
	</tr>
	<tr>
		<td><?php echo $currentmonthyr; ?></td>
		<td><?php echo $births_currmonth; ?></td>
		<td><?php echo $deaths_currmonth; ?></td>
	</tr>
	<tr>
		<td><?php echo $x1monthprev; ?></td>
		<td><?php echo $births_1monthprev; ?></td>
		<td><?php echo $deaths_1monthprev; ?></td>
	</tr>
	<tr>
		<td><?php echo $x2monthprev; ?></td>
		<td><?php echo $births_2monthprev; ?></td>
		<td><?php echo $deaths_2monthprev; ?></td>
	</tr>
	<tr>
		<td><?php echo $x3monthprev; ?></td>
		<td><?php echo $births_3monthprev; ?></td>
		<td><?php echo $deaths_3monthprev; ?></td>
	</tr>
	<tr>
		<td><?php echo $x4monthprev; ?></td>
		<td><?php echo $births_4monthprev; ?></td>
		<td><?php echo $deaths_4monthprev; ?></td>
	</tr>
	<tr>
		<td><?php echo $x5monthprev; ?></td>
		<td><?php echo $births_5monthprev; ?></td>
		<td><?php echo $deaths_5monthprev; ?></td>
	</tr>
	<tr>
		<td><?php echo $x6monthprev; ?></td>
		<td><?php echo $births_6monthprev; ?></td>
		<td><?php echo $deaths_6monthprev; ?></td>
	</tr>

</table>

<br><br>
<table><tr><td>
					 <form action="./php/litterlogger.php" method=post>
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  style="background-color:#217190; color:lightgrey;"
					  value="Litter Log" />
</form>
</td></tr></table>

<br><br>
<?php echo $weantable; ?>

			</div>
			
			<div id="footer">
				<p class="righttext">
				<a href="<?php echo $guide1url; ?>" target="_blank"><?php echo $guide1title; ?> </a>
<br>
				<a href="https://docs.google.com/document/d/1SVCbMhUZNA8dnEj0Wl-aS00G71mFNtdfBKGzZiXsyWk/edit?usp=sharing" target="_blank">Feature Requests and Bug Reports </a>

				</p>
					 <p class="righttext">
					  NeurobehavioralCore.com &copy; 2016
					 </p>
					 
 		  </div>


</body>
</html>