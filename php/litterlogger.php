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


//posted variables
$line_selection=$_POST['line_selection'];
$source_selection=$_POST['source_selection'];
$bulkcomments=$_POST['bulkcomments'];
$dob=$_POST['dob'];
$numbermale=$_POST['numbermale'];
$numberfemale=$_POST['numberfemale'];
$numberunknown=$_POST['numberunknown'];

$testtable="";

if (isset($_POST['generate_litter'])){
//get line,dob,comments,source,parents
$xline_selection=$_POST['line_selection'];
$xdob=$_POST['dob'];
$xbulkcomments=$_POST['bulkcomments'];
$xsource_selection=$_POST['source_selection'];
//get obs date and observer
$xobs_date=date('Y-m-d');
$xobs_by=$_POST['xusername'];
//get number of animals
$xnumbermale=$_POST['numbermale'];
$xnumberfemale=$_POST['numberfemale'];
$xnumberunknown=$_POST['numberunknown'];
$xtotalnumber=$xnumbermale+$xnumberfemale+$xnumberunknown;
//generate litter name

if ($xsource_selection==="FOUNDER"){
$xcurrcage="FOUNDER".'-'.$xline_selection.' - '.$xdob;
} elseif($xsource_selection==""){
$xcurrcage="FOUNDER".'-'.$xline_selection.' - '.$xdob;
} else {
$xcurrcage="Litter-".$xsource_selection.' - '.$xdob;
}

//populate table
$testtable="<table><tr><th>line</th><th>mating cage</th><th>dob</th><th>#F</th><th>#M</th><th>#U</th><th>CLIP DATE</th><th>actual clip</th><th>WEAN DATE</th><th>actual wean</th><th>JUST SAC (y/n)</th><th>Manage animals</th><th>Comments</th></tr>"
."<tr><td>".$xline_selection."</td><td>".$xsource_selection."</td><td>".$xdob."</td>"
."<td>".$xnumberfemale."</td><td>".$xnumbermale."</td><td>".$xnumberunknown."</td>"
."<td>".date('Y-m-d', strtotime($xdob.' + 14 days'))."</td><td>Manage</td><td>".date('Y-m-d', strtotime($xdob. ' + 21 days'))."</td><td>Manage</td>"
."<td>---</td><td>Manage</td><td>".$xbulkcomments."</td>"
."</tr></table>";

//hidden inputs
$testtable.="<input type=hidden name='xobs_by' value='".$xobs_by."' />"
."<input type=hidden name='xdob' value='".$xdob."' />"
."<input type=hidden name='xline_selection' value='".$xline_selection."' />"
."<input type=hidden name='xsource_selection' value='".$xsource_selection."' />"
."<input type=hidden name='xobs_date' value='".$xobs_date."' />"
."<input type=hidden name='xcurrcage' value='".$xcurrcage."' />"
."<input type=hidden name='xnumbermale' value='".$xnumbermale."' />"
."<input type=hidden name='xnumberfemale' value='".$xnumberfemale."' />"
."<input type=hidden name='xnumberunknown' value='".$xnumberunknown."' />"
."<input type=hidden name='xbulkcomments' value='".$xbulkcomments."' />"

//confirm button
."<input type=submit id='confirm_litter' name='confirm_litter' value='confirm litter'>";


}

// add litter to db

if (isset($_POST['confirm_litter'])){
//capture values
$zdob=$_POST['xdob'];
$zline_assign=$_POST['xline_selection'];
$zcagename=$_POST['xsource_selection'];
$zactualobs=$_POST['xobs_date'];
$zobsby=$_POST['xobs_by'];
$zcurrcage=$_POST['xcurrcage'];
$zestmale=$_POST['xnumbermale'];
$zestfemale=$_POST['xnumberfemale'];
$zestunknown=$_POST['xnumberunknown'];
$zcomments=$_POST['xbulkcomments'];

$sqltext="INSERT INTO `".$dbname."`.`table_litterlog` (`dob`, `line_assign`, `cagename`, `actual_obs`, `obs_by`, `litter name`, `estimate_male`, `estimate_female`, `estimate_unknown`, `litter_comments`)"
." VALUES ('".$zdob."', '".$zline_assign."', '".$zcagename."', '".$zactualobs."', '".$zobsby."', '".$zcurrcage."', '".$zestmale."', '".$zestfemale."', '".$zestunknown."', '".$zcomments."');";

echo $sqltext;

//submit litter to db
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
if($conn->multi_query($sqltext)===TRUE){
	//flush the mysql submission
while (mysqli_next_result($conn));
$sqlstatus='-successful'.'...'.$sqltext;}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
$sqlreport=$sqlstatus;
$conn->close();

/**/

}
//litter log
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="select * from `".$dbname."`.`table_litterlog` where  `dob` > date_sub(curdate(), interval 2 month) order by `dob`;";
$results=$conn->query($sqltext);

//set up static portion of table
$litterlog=""
."<table><tr><th>line</th><th>mating cage</th><th>dob</th><th>#F</th><th>#M</th><th>#U</th><th>CLIP DATE</th><th>actual clip</th><th>WEAN DATE</th><th>actual wean</th><th>JUST SAC (y/n)</th><th>Manage animals</th><th>Comments</th></tr>";

//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row

$ManageClip=""
."<form action='../php/add_animals.php' method=post target='_blank'>"
."<input type=hidden name='xusername' value='".$xusername."' />"
."<input type=hidden name='xpassword' value='".$xpassword."' />"
."<input type=hidden name='dbname' value='".$dbname."' />"
."<input type=hidden name='button_login' value='connect' />"
."<input type=submit style='background-color:#217190; color:lightgrey;' value='Manage' />"

."<input type=hidden name='line_selection' value='".$row['line_assign']."' />"
."<input type=hidden name='source_selection' value='".$row['cagename']."' />"
."<input type=hidden name='bulkcomments' value='".$row['litter_comments']."' />"
."<input type=hidden name='dob' value='".$row['dob']."' />"
."<input type=hidden name='numbermale' value='".$row['estimate_male']."' />"
."<input type=hidden name='numberfemale' value='".$row['estimate_female']."' />"
."<input type=hidden name='numberunknown' value='".$row['estimate_unknown']."' />"
."</form>";


$ManageWean=""
."<form action='../php/manage_cages.php' method=post target='_blank'>"
."<input type=hidden name='xusername' value='".$xusername."' />"
."<input type=hidden name='xpassword' value='".$xpassword."' />"
."<input type=hidden name='dbname' value='".$dbname."' />"
."<input type=hidden name='button_login' value='connect' />"
."<input type=submit style='background-color:#217190; color:lightgrey;' value='Manage' />"

."<input type=hidden name='line_filter' value='".$row['line_assign']."' />"
."<input type=hidden name='line_assignment' value='".$row['line_assign']."' />"
."<input type=hidden name='line_sync' value='".$row['line_assign']."' />"
."<input type=hidden name='gender_filter' value='all' />"
."<input type=hidden name='source_category_selection' value='all' />"
."<input type=hidden name='sourcecage_selection' value='".$row['litter name']."' />"
."<input type=hidden name='setupdate' value='".date('Y-m-d', strtotime($row['dob']. ' + 21 days'))."' />"
."<input type=hidden name='category_selection' value='Holding' />";

if ($row['cagename']==="FOUNDER"){
$ManageWean.="<input type=hidden name='move_selection' value='Cage Transfer' />";
} else {
$ManageWean.="<input type=hidden name='move_selection' value='Weaning' />";
}
/**/
$ManageWean.="</form>";


$ManageSac=""
."<form action='../php/manage_animals.php' method=post target='_blank'>"
."<input type=hidden name='xusername' value='".$xusername."' />"
."<input type=hidden name='xpassword' value='".$xpassword."' />"
."<input type=hidden name='dbname' value='".$dbname."' />"
."<input type=hidden name='button_login' value='connect' />"
."<input type=submit style='background-color:#217190; color:lightgrey;' value='Manage' />"

."<input type=hidden name='line_filter' value='".$row['line_assign']."' />"
."<input type=hidden name='gender_filter' value='all' />"
."<input type=hidden name='source_category_selection' value='all' />"
."<input type=hidden name='deadoralive_filter' value='alive' />"
."<input type=hidden name='sourcecage_selection' value='".$row['litter name']."' />"
."</form>";


$litterlog.="<tr><td>".$row['line_assign']."</td><td>".$row['cagename']."</td><td>".$row['dob']."</td>"
."<td>".$row['estimate_female']."</td><td>".$row['estimate_male']."</td><td>".$row['estimate_unknown']."</td>"
."<td>".date('Y-m-d', strtotime($row['dob'].' + 14 days'))."</td><td>".$ManageClip."</td><td>".date('Y-m-d', strtotime($row['dob']. ' + 21 days'))."</td><td>".$ManageWean."</td>"
."<td>".$row['just_sac']."</td><td>".$ManageSac."</td><td>".$row['litter_comments']."</td></tr>";

}
//close the table
$litterlog.="</table>";
/**/

$conn->close();


//line list
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//$sqltext="call get_lines();";
$sqltext="Select * from `".$dbname."`.`table_lines` order by `line`;";
$results=$conn->query($sqltext);
//set up static portion of table
$line_listbox= '<select id="line_selection" name="line_selection" size=1 class="mediumlistbox" onchange="submitForm()">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
//get results matched to current line - used for additional fields
if($row['line']===$line_selection){
$line_listbox .= '<option value="'.$row["line"].'" selected>'.$row["line"].'</option>';
$line_description=$row['line_description'];
$line_cardcolor=$row['card_color'];
$line_stripecolor=$row['color_assignment'];
$line_currentplan=$row['CurrentPlan'];
$line_HoldingGeno=$row['HoldingGeno'];
$line_HoldingSex=$row['HoldingSex'];
$line_ExpGeno=$row['ExperimentGeno'];
$line_ExpSex=$row['ExperimentSex'];
$line_Primary=$row['PrimaryContact'];
$line_Maintain=$row['MaintenanceContact'];
$line_UpdateDate=$row['UpdateDate'];
$line_UpdatedBy=$row['UpdatedBy'];
$line_Project=$row['ProjectGroup'];

$line_tip="<table><tr><th colspan=8>".$line_selection.":"
."</th></tr><tr >"
."<th>CARD:</th><td>".$line_cardcolor
."</td><th>STRIPE:</th><td>".$line_stripecolor
."</td><th>CONTACT:</th><td>".$line_Primary
."</td><th>Maintainer:</th><td>".$line_Maintain."</td></tr>"
."<tr><th colspan=8>Instructions:</th></tr><tr><td colspan=8>".$line_currentplan."</td></tr>"
."<tr><th>Holding Cage-Genotypes:</th><td colspan=7>".$line_HoldingGeno."</td></tr>"
."<tr><th>Holding Cage-------Sex:</th><td colspan=7>".$line_HoldingSex."</td></tr>"
."<tr><th>Exp Cage-Genotypes:</th><td colspan=7>".$line_ExpGeno."</td></tr>"
."<tr><th>Exp Cage-------Sex:</th><td colspan=7>".$line_ExpSex."</td></tr>"
."<tr><td colspan=8>  -".$line_UpdatedBy."     ".$line_UpdateDate."</td></tr></table>";
/**/

} 
//get results for additional lines
else {
$line_listbox .= '<option value="'.$row["line"].'">'.$row["line"].'</option>';
}
} 
//close the table
$line_listbox .= '</select>';
$conn->close();

//gender_listbox
$gender_options=array('M','F','unk');
$gender_listbox='<select id="gender_filter" name="gender_filter" onchange="submitForm()">';
foreach ($gender_options as $row){
if ($row===$gender_selection){
$gender_listbox.='<option value="'.$row.'" selected>'.$row.'</option>';
} else {
$gender_listbox.='<option value="'.$row.'" >'.$row.'</option>';
}
}
$gender_listbox.='</select>';


//mating list filtered by line
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT `currentcage`, `cagecontents`
FROM (`table_animals` join `table_cages` on `table_animals`.`currentcage`=`table_cages`.`cageid`)
where dod is null and left(`currentcage`,1)='M' and (`line`='".$line_selection."' or `lineassignment`='".$line_selection."') 
GROUP BY `currentcage`;";
$results=$conn->query($sqltext);
$source_listbox='<select id="source_selection" name="source_selection" size=10 class="largelistbox2" onchange="submitForm()">';

while($row=mysqli_fetch_array($results)){
$cage[]=$row['currentcage'];
$cgcont[$row['currentcage']]=$row['cagecontents'];
}
$cage[]='FOUNDER';
$cgcont['FOUNDER']='N/A';
foreach($cage as $source) {
if($source===$source_selection){
$source_listbox.='<option value="'.$source.'" selected>'.$source.' | '.$cgcont[$source].'</option>';
}
else{
$source_listbox.='<option value="'.$source.'">'.$source.' | '.$cgcont[$source].'</option>';
}
}
//close the table
$source_listbox.='</select>';
$conn->close();


//mating cage contents current
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

$sqltext="SELECT table_animals.animalautono as 'man',line,idno,gender,dob,dod,currentcage FROM `table_animals` where dod is null and `currentcage`='".$source_selection."' ;";
$results=$conn->query($sqltext);
$animals_results=$results;
$animals_listbox='<select id="animals_selection" name="animals_selection" size=5 class="mediumlistbox onchange="submitForm()">;';
//loop and prepare table
while($row=mysqli_fetch_array($results)){
$animals_listbox.='<option value="'.$row['man'].'">'.$row['line'].'-'.$row['idno'].' | '.$row['gender'].'</option>';
}
//close the table
$animals_listbox.='</select>';
$conn->close();


//mating cage contents historical
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

$sqltext="SELECT table_cages.cagecontents FROM `table_cages` where `cageid`='".$source_selection."' ;";
$results=$conn->query($sqltext);
$animals_results=$results;
//loop and prepare table
while($row=mysqli_fetch_array($results)){
$animals_string=$source_selection.' | '.$row['cagecontents'];
$animals_string_display=$source_selection.' <br> '.$row['cagecontents'];
}
//close the table
$conn->close();


?>	
<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Litter Log - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />

	
	
</head>
<body>

			<div id="header">
					 <form id="loginbox" action="" method="post">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Litter Log-
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
					 <form action="../php/add_animals_includestoppedmatings.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Add animals - Include Stopped Matings" />
					 </form>
					  
			</div>


<!--CONTENT SECTION-->
			<div id="right_content" class="centertext">
			<form id="add_animals_form" name="add_animals_form" method=post>
                                         <input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("add_animals_form").submit();
}
</script>
			<table>
				<tr>
					<th>Line Selection:</th>
					<th>Source Selection:</th>
					<th>Mating Cage Contents:</th>
				</tr>
				<tr>
					<td><?php echo $line_listbox; ?>
						</td>
					<td rowspan=5><?php echo $source_listbox; ?></td>
					<td rowspan=5>
						<table>
						<tr><td>
						<?php echo $animals_listbox; ?></td></tr>
						<tr><th>Original Contents:</th></tr>
						<tr><td><?php echo $animals_string_display; ?></td></tr>
						</table>
						</td>
				</tr>
				<tr>
					<th>DOB:</th>
				</tr>
				<tr>
					<td><input type=date id="dob" name="dob" value="<?php echo $dob; ?>"></td>
				</tr>
				<tr>
					<th>Comments:</th>
				</tr>
				<tr>
					<td><textarea id="bulkcomments" name="bulkcomments" rows=5><?php echo $bulkcomments; ?></textarea></td>
				</tr>
				<tr>
					<th colspan=3><p>Number of animals to Add:</p></th>
				</tr>
				<tr>
					
					<th>Female:</th>
					<th>Male:</th>
					<th>Unknown:</th>
				</tr>
				<tr>
					<td><input type=number id="numberfemale" name="numberfemale" value="<?php echo $numberfemale; ?>" min="0"></td>
					<td><input type=number id="numbermale" name="numbermale" value="<?php echo $numbermale; ?>" min="0"></td>
					<td><input type=number id="numberunknown" name="numberunknown" value="<?php echo $numberunknown; ?>" min="0"></td>
				</tr>
			</table>
			<input type=submit id="generate_litter" name="generate_litter" value="generate litter">
			<br><br>
			<?php echo $testtable; ?>
<br>
<br>
			<?php echo $line_tip; ?>
		
</form>
			<?php echo $litterlog; ?>
			<?php echo $sqlreport; ?>
	
			</div>

			
			<div id="footer">
<!--footer hidden to allow more space for dataentry
					 <p class="righttext">
					  a neurobehavioralcore.com &copy;2016
					 </p>
					 
--> 			
					
			</div>


</body>
</html>


