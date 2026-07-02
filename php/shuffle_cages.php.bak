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

//Add animals individually to shufcg1|2|3|4
if (isset($_POST['addshufcg1_single'])){
$animals_selection=$_POST['animals_selection'];
$sqlaction='add animal:'.$animals_selection;
$sqltext="INSERT INTO `".$dbname."`.`temp_shufcg1` (`animalautono`) VALUES (".$animals_selection.");";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
}

//Remove animals individually to shufcg1|2|3|4
if (isset($_POST['remshufcg1_single'])){
$animals_selection=$_POST['shufcg1_selection'];
$sqlaction='rem animal:'.$animals_selection;
$sqltext="DELETE FROM `".$dbname."`.`temp_shufcg1` WHERE `animalautono`=".$animals_selection.";";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
}

//Bulk add to cage 1|2|3|4
if (isset($_POST['addshufcg1_batch'])){
$animals_batch=$_POST['animals_batchlist'];
$sqlaction='add animal:'.$animals_batch;
$sqltext="INSERT INTO `".$dbname."`.`temp_shufcg1` (`animalautono`) VALUES ".$animals_batch.";";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
}

//Bulk remove from 1|2|3|4
if (isset($_POST['remshufcg1_batch'])||isset($_POST['clear_cages'])){
$sqlaction='clear cage';
$sqltext="DELETE FROM `".$dbname."`.`temp_shufcg1`;";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
}

//submit cages
if (isset($_POST['submit_cages'])){
$xshufcg1no=$_POST['shufcg1no'];

$xshufcg1name=$_POST['shufcg1name'];

$xshufcg1size=$_POST['shufcg1size'];

$xshufcg1contents=$_POST['shufcg1contents'];

$xline_assignment=$_POST['line_assignment'];
$xmove_selection=$_POST['move_selection'];
$xcategory_selection=$_POST['category_selection'];
$xcageactive=1;
$xsetupdate=$_POST['setupdate'];

$sqlaction='submit cage changes';

if ($xshufcg1size>0){
$c1values="('".$xshufcg1name."','".$xcategory_selection."','".$xsetupdate."',1,'".$xline_assignment."',".$xshufcg1no.",'".$xshufcg1contents."'),";
if ($xmove_selection==="Weaning"){
$c1updates="UPDATE `".$dbname."`.`table_animals` join `".$dbname."`.`temp_shufcg1` 
ON `table_animals`.`animalautono`=`temp_shufcg1`.`animalautono`
SET `table_animals`.`currentcage`='".$xshufcg1name."',
`table_animals`.`dow`='".$xsetupdate."';
INSERT INTO `".$dbname."`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '".$xsetupdate."' as commentdate, 'moved to cage:".$xshufcg1name."' as general_comment FROM `".$dbname."`.`temp_shufcg1`;";
} else {
$c1updates="UPDATE `".$dbname."`.`table_animals` join `".$dbname."`.`temp_shufcg1` 
ON `table_animals`.`animalautono`=`temp_shufcg1`.`animalautono`
SET `table_animals`.`currentcage`='".$xshufcg1name."';
INSERT INTO `".$dbname."`.data_comments (`animalautono`,`commentdate`,`general_comment`)
Select `animalautono`, '".$xsetupdate."' as commentdate, 'moved to cage:".$xshufcg1name."' as general_comment FROM `".$dbname."`.`temp_shufcg1`;";
}
}

$xInsertValues=substr($c1values.$c2values.$c3values.$c4values,0,-1);


//insert into table_cages NOT NEEDED, JUST REUPDATE
//$insertTableCages="INSERT INTO `".$dbname."`.`table_cages` (`cageid`,`cagetype`,`setupdate`,`cageactive`,`lineassignment`,`cageno`,`cagecontents`) VALUES ".$xInsertValues.";";
//clear cages
$sqltextclear="DELETE FROM `".$dbname."`.`temp_shufcg1`;";
//merge queries

$sqltext=$insertTableCages.$c1updates.$sqltextclear;

if ($conn->multi_query($sqltext) ===TRUE) {
$sqlstatus= 'successful - '.$sqltext;} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
$conn->close();

}




$buttonmessage=$sqlaction.' '.$line.' - '.$sqlstatus;
$conn->close();
?>
	<!--php script for display controls-->
<?php

// posted variables
$line_filter=$_POST['line_filter'];
$line_sync=$_POST['line_sync'];
$line_assignment=$_POST['line_assignment'];
$gender_filter=$_POST['gender_filter'];
$dgender_filter=$_POST['dgender_filter'];
$move_selection=$_POST['move_selection'];
$source_category_selection=$_POST['source_category_selection'];
$category_selection=$_POST['category_selection'];
$setupdate=$_POST['setupdate'];
$sourcecage_selection=$_POST['sourcecage_selection'];
$destcage_selection=$_POST['destcage_selection'];
$animals_selection=$_POST['animals_selection'];
$shufcg1_selection=$_POST['shufcg1_selection'];
$cage2_selection=$_POST['cage2_selection'];
$cage3_selection=$_POST['cage3_selection'];
$cage4_selection=$_POST['cage4_selection'];

//gender filter
$gender_options=array('all','M','F','unk');
$gender_listbox='<select id="gender_filter" name="gender_filter" onchange="submitForm()">';
foreach ($gender_options as $row){
if ($row===$gender_filter){
$gender_listbox.='<option value="'.$row.'" selected>'.$row.'</option>';
} else {
$gender_listbox.='<option value="'.$row.'" >'.$row.'</option>';
}
}
$gender_listbox.='</select>';


//gender filter
$dgender_options=array('all','M','F','unk');
$dgender_listbox='<select id="dgender_filter" name="dgender_filter" onchange="submitForm()">';
foreach ($dgender_options as $row){
if ($row===$dgender_filter){
$dgender_listbox.='<option value="'.$row.'" selected>'.$row.'</option>';
} else {
$dgender_listbox.='<option value="'.$row.'" >'.$row.'</option>';
}
}
$dgender_listbox.='</select>';


//move type filter
$move_options=array('Cage Transfer');
$move_listbox='<select id="move_selection" name="move_selection" onchange="submitForm()">';
foreach ($move_options as $row){
if ($row===$move_selection){
$move_listbox.='<option value="'.$row.'" selected>'.$row.'</option>';
} else {
$move_listbox.='<option value="'.$row.'" >'.$row.'</option>';
}
}
$move_listbox.='</select>';


//category type assignment
$category_options=array('Holding','Mating','Experimental');
$category_listbox='<select id="category_selection" name="category_selection" onchange="submitForm()">';
foreach ($category_options as $row){
if ($row===$category_selection){
$category_listbox.='<option value="'.$row.'" selected>'.$row.'</option>';
} else {
$category_listbox.='<option value="'.$row.'" >'.$row.'</option>';
}
}
$category_listbox.='</select>';

//source category type filter
$source_category_options=array('all','Holding','Mating','Experimental','Litter','Founder');
$source_category_listbox='<select id="source_category_selection" name="source_category_selection" onchange="submitForm()">';
foreach ($source_category_options as $row){
if ($row===$source_category_selection){
$source_category_listbox.='<option value="'.$row.'" selected>'.$row.'</option>';
} else {
$source_category_listbox.='<option value="'.$row.'" >'.$row.'</option>';
}
}
$source_category_listbox.='</select>';

//line lists
if ($line_sync==""){$line_sync=$line_assignment;}
if ($line_assignment<>$line_sync){
$line_assign_selected=$line_assignment;
} else{
$line_assign_selected=$line_filter;
}
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="call get_lines();";
$results=$conn->query($sqltext);
//set up static portion of table
$line_listbox= '<select id="line_filter" name="line_filter" size=1 class="mediumlistbox" onchange="submitForm()"><option value="all">all</option>';
$lineassign_listbox= '<select id="line_assignment" name="line_assignment" size=1 class="mediumlistbox" onchange="submitForm()">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
//get results matched to current line - used for additional fields
if($row['line']===$line_filter){
$line_listbox .= '<option value="'.$row["line"].'" selected>'.$row["line"].'</option>';
} 
//get results for additional lines
else {
$line_listbox .= '<option value="'.$row["line"].'">'.$row["line"].'</option>';
}
if($row['line']===$line_assign_selected){
$lineassign_listbox .= '<option value="'.$row["line"].'" selected>'.$row["line"].'</option>';
} 
//get results for additional lines
else {
$lineassign_listbox .= '<option value="'.$row["line"].'">'.$row["line"].'</option>';
}
}
//close the table
$line_listbox .= '</select>';
$lineassign_listbox .= '</select>';
$conn->close();

//temp_shufcg1 contents
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT `line`,`idno`,`gender`,`dob`,`currentcage`,`temp_shufcg1`.`animalautono` FROM `table_animals` JOIN `temp_shufcg1` ON `table_animals`.`animalautono`=`temp_shufcg1`.`animalautono`;";
$results=$conn->query($sqltext);
$shufcg1size=mysqli_num_rows($results);
$shufcg1_listbox='<select id="shufcg1_selection" name="shufcg1_selection" size=6 class="largelistbox onchange="">;';
//loop and prepare table
while($row=mysqli_fetch_array($results)){
if ($row['animalautono']===$shufcg1_selection){
$shufcg1_listbox.='<option value="'.$row['animalautono'].'" selected>'.$row['line'].'-'.$row['idno'].' | '.$row['gender'].' | '.$row['dob'].' | '.$row['currentcage'].'</option>';
}
else{
$shufcg1_listbox.='<option value="'.$row['animalautono'].'">'.$row['line'].'-'.$row['idno'].' | '.$row['gender'].' | '.$row['dob'].' | '.$row['currentcage'].'</option>';
}
$animalc1[]=$row['line'].'-'.$row['idno'];
}
$shufcg1contents=implode(', ',$animalc1);
//close the table
$shufcg1_listbox.='</select>';
$conn->close();


//cage list filtered by line, gender, etc
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//set filter text
if ($line_filter==="all"){
$lf='';} else {
$lf='`line`="'.$line_filter.'" and ';}

if ($gender_filter==="all"){
$gf='';} else {
$gf='`gender`="'.$gender_filter.'" and ';}

if ($move_selection==="Weaning") {
$mf='left(`currentcage`,1)="L" and ';} else {
$mf='';}

if ($source_category_selection==="all") {
$sf='';} else {
$sf='left(`currentcage`,1)=left("'.$source_category_selection.'",1) and ';}

$sql_where_text=substr($lf.$gf.$mf.$sf,0,-4);
if (strlen($sql_where_text)>0){
$sql_where_text=' and '.$sql_where_text;}
$sqltext="SELECT `currentcage` FROM `table_animals` left join temp_shufcg1 on table_animals.animalautono=temp_shufcg1.animalautono 
left join temp_cage2 on table_animals.animalautono=temp_cage2.animalautono left join temp_cage3 on table_animals.animalautono=temp_cage3.animalautono 
left join temp_cage4 on table_animals.animalautono=temp_cage4.animalautono where dod is null and
temp_shufcg1.animalautono is null and temp_cage2.animalautono is null and temp_cage3.animalautono is null and temp_cage4.animalautono is null".$sql_where_text." GROUP BY `currentcage`;";
$results=$conn->query($sqltext);
$sourcecage_listbox='<select id="sourcecage_selection" name="sourcecage_selection" size=14 class="mediumlistbox" onchange="submitForm()"><option value="all">all</option>';
while($row=mysqli_fetch_array($results)){
if ($row['currentcage']===$sourcecage_selection){
$sourcecage_listbox.='<option value="'.$row['currentcage'].'" selected>'.$row['currentcage'].'</option>';
}
else{
$sourcecage_listbox.='<option value="'.$row['currentcage'].'">'.$row['currentcage'].'</option>';
}
}
//close the table
$sourcecage_listbox.='</select>';
$conn->close();

//dest cage list filtered by line, gender, etc
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//set filter text
if ($line_assignment==="all"){
$dlf='';} else {
$dlf='`line`="'.$line_assignment.'" and ';}

if ($dgender_filter==="all"){
$dgf='';} else {
$dgf='`gender`="'.$dgender_filter.'" and ';}

if ($category_selection==="all") {
$dsf='';} else {
$dsf='left(`currentcage`,1)=left("'.$category_selection.'",1) and ';}

$dsql_where_text=substr($dlf.$dgf.$dsf,0,-4);
if (strlen($dsql_where_text)>0){
$dsql_where_text=' and '.$dsql_where_text;}
$dsqltext="SELECT `currentcage` FROM `table_animals` left join temp_shufcg1 on table_animals.animalautono=temp_shufcg1.animalautono 
where dod is null and
temp_shufcg1.animalautono is null ".$dsql_where_text." GROUP BY `currentcage`;";
$dresults=$conn->query($dsqltext);
//echo $dsqltext;
$destcage_listbox='<select id="destcage_selection" name="destcage_selection" size=14 class="mediumlistbox" onchange="submitForm()"><option value="all">all</option>';
while($row=mysqli_fetch_array($dresults)){
if ($row['currentcage']===$destcage_selection){
$destcage_listbox.='<option value="'.$row['currentcage'].'" selected>'.$row['currentcage'].'</option>';
}
else{
$destcage_listbox.='<option value="'.$row['currentcage'].'">'.$row['currentcage'].'</option>';
}
}
//close the table
$destcage_listbox.='</select>';
$conn->close();

//echo $sqltext;

//animals list filtered by line|gender|cage
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//set filter text
if ($line_filter==="all"){
$lf='';} else {
$lf='`line`="'.$line_filter.'" and ';}

if ($gender_filter==="all"){
$gf='';} else {
$gf='`gender`="'.$gender_filter.'" and ';}

if ($move_selection==="Weaning") {
$mf='left(`currentcage`,1)="L" and ';} else {
$mf='';}

if ($source_category_selection==="all") {
$sf='';} else {
$sf='left(`currentcage`,1)=left("'.$source_category_selection.'",1) and ';}

if ($sourcecage_selection=="" or $sourcecage_selection==="all") {
$cf='';} else {
$cf='`currentcage`="'.$sourcecage_selection.'" and ';}

$sql_where_text=substr($lf.$gf.$mf.$sf.$cf,0,-4);
if (strlen($sql_where_text)>0){
$sql_where_text=' and '.$sql_where_text;}
$sqltext="SELECT table_animals.animalautono as 'man',line,idno,gender,dob,dod,currentcage FROM `table_animals` left join temp_shufcg1 on table_animals.animalautono=temp_shufcg1.animalautono 
left join temp_cage2 on table_animals.animalautono=temp_cage2.animalautono left join temp_cage3 on table_animals.animalautono=temp_cage3.animalautono 
left join temp_cage4 on table_animals.animalautono=temp_cage4.animalautono where dod is null and 
temp_shufcg1.animalautono is null and temp_cage2.animalautono is null and temp_cage3.animalautono is null and temp_cage4.animalautono is null".$sql_where_text." ;";
$results=$conn->query($sqltext);
$animals_results=$results;
$animals_listbox='<select id="animals_selection" name="animals_selection" size=15 class="largelistbox onchange="submitForm()">';
//loop and prepare table
while($row=mysqli_fetch_array($results)){
if ($row['man']===$animals_selection){
$animals_listbox.='<option value="'.$row['man'].'" selected>'.$row['line'].'-'.$row['idno'].' | '.$row['gender'].' | '.$row['dob'].' | '.$row['currentcage'].'</option>';
}
else{
$animals_listbox.='<option value="'.$row['man'].'">'.$row['line'].'-'.$row['idno'].' | '.$row['gender'].' | '.$row['dob'].' | '.$row['currentcage'].'</option>';
}
$animals_batchlist[]=$row['man'];
}
//close the table
$animals_listbox.='</select>';
$animals_batchlist='('.implode('),(',$animals_batchlist).')';

$conn->close();
//echo $sqltext;

//dest animals list filtered by line|gender|cage
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//set filter text

if ($destcage_selection=="" or $destcage_selection==="all") {
$cf='';} else {
$cf='`currentcage`="'.$destcage_selection.'" and ';}

$dsql_where_text=substr($cf,0,-4);
if (strlen($sql_where_text)>0){
$dsql_where_text=' and '.$dsql_where_text;}
$dsqltext="SELECT table_animals.animalautono as 'man',line,idno,gender,dob,dod,currentcage FROM `table_animals` 
where dod is null ".$dsql_where_text." ;";
echo $dsqltext;
$results=$conn->query($dsqltext);
//$danimals_results=$results;
$danimals_listbox='<select id="danimals_selection" name="danimals_selection" size=6 class="largelistbox" >';
//loop and prepare table
while($row=mysqli_fetch_array($results)){
$danimals_listbox.='<option value="'.$row['man'].'">'.$row['line'].'-'.$row['idno'].' | '.$row['gender'].' | '.$row['dob'].' | '.$row['currentcage'].'</option>';
$danimals_batchlist[]=$row['man'];
}
//close the table
$danimals_listbox.='</select>';
$danimals_batchlist='('.implode('),(',$animals_batchlist ?? []).')';

$conn->close();
//echo $sqltext;


//cage 1 tentative name
$shufcg1name=$destcage_selection;
$conn->close();

//form inputs for movement type, new cage category, new cage set-up date, new cage line assignment
//form displays (and temp tables in mysql) for 4 potential new cages
$sqlerror=$conn->error;

?>

<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Shuffle Cages - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />	
</head>
<body>

			<div id="header">
					<form id="loginbox" action="" method="post">
					<?php echo $sqlerror; ?>
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Shuffle Cages-
					 </h2>
					 
					 <h1 class="centervert"
					 style="position:absolute;top:0px;left:350px;">
					 <?php echo $dbname; ?>
					<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
					 </h1>
					 
					 <button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
					 width:20px;height:20px;border-radius:10px;position:absolute;
					 top:15px;right:250px;"></button>
					 
					 
					 
						<table class="logintable" style="color:white;font-size:10px;position:absolute;top:0px;right:60px;">
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
<!--CONTENT SECTION-->
			<form id="cage_management_form" name="cage_management_form" method=post>

					<input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />

<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("cage_management_form").submit();
}
</script>
			<table>
			<tr>
			<th>Line Filter:</th>
			<th>Gender Filter:</th>
			<th>Source Cage Category:</th>
			<th>Move Type:</th>
			</tr>
			<tr>
			<td><?php echo $line_listbox; ?></td>
			<td><?php echo $gender_listbox; ?></td>
			<td><?php echo $source_category_listbox; ?></td>
			<td><?php echo $move_listbox; ?></td>
			</tr>
			</table>
			
			<table>
			<tr>
				<th>Available animals</th>
				<th>Source Cage Selection:</th>
			</tr>
			<tr>
				<td><?php echo $animals_listbox; ?><input type=hidden name="animals_batchlist" id="animals_batchlist" value="<?php echo $animals_batchlist; ?>"></td>
				<td><?php echo $sourcecage_selection; ?><br>
				<?php echo $sourcecage_listbox; ?></td>
			</tr>
			</table>
			
			<table>
			<tr>
				<th>Line Assignment:</th>
                                <th>Gender in Destination Cage:</th>
				<th>Cage Category:</th>
				<th>Cage Set-up Date:</th>
			</tr>
			<tr>
				<td><?php echo $lineassign_listbox; ?><input type=hidden id="line_sync" name="line_sync" value="<?php echo $line_filter; ?>" >
				</td>
				<td><?php echo $dgender_listbox; ?></td>
				<td><?php echo $category_listbox; ?></td>
				<td><input type=date id="setupdate" name="setupdate" value="<?php echo $setupdate; ?>" ></td>
			</tr>
			</table>	

			<table>
			<tr>
				<th><input type=text id="shufcg1name" name="shufcg1name" value="<?php echo $shufcg1name; ?>" readonly="readonly">
					<input type=hidden id="shufcg1no" name="shufcg1no" value="<?php echo $shufcg1no; ?>" >
					<input type=hidden id="shufcg1contents" name="shufcg1contents" value="<?php echo $shufcg1contents; ?>" >
					</th>
				<td><input type=submit id="remshufcg1_single" name="remshufcg1_single" value="&uarr;(c1)"><input type=submit id="remshufcg1_batch" name="remshufcg1_batch" value="&uarr;&uarr;(c1)&uarr;&uarr;"></td>
				
			</tr>
			<tr>
				<td><input type=text id="shufcg1size" name="shufcg1size" value="<?php echo $shufcg1size; ?>"></td>
				<td><input type=submit id="addshufcg1_single" name="addshufcg1_single" value="&darr;(c1)"><input type=submit id="addshufcg1_batch" name="addshufcg1_batch" value="&darr;&darr;(c1)&darr;&darr;"></td>
				
			</tr>
			<tr>
				<td colspan=2>-animals TO ADD-<br><?php echo $shufcg1_listbox; ?><br>-animals IN CAGE-<br><?php echo $danimals_listbox; ?></td>
				<td>
				<?php echo $destcage_selection; ?><br><?php echo $destcage_listbox; ?>
				</td>
			</tr>
			
			
			</table>
			
			<input type=submit id="submit_cages" name="submit_cages" value="Submit">
			<input type=submit id="clear_cages" name="clear_cages" value="Clear Cages">

			</form>
			</div>

			
			<div id="footer">
					 <p class="righttext">
					  NeurobehavioralCore.com &copy;2016
					 </p>
 			
			</div>
<?php echo $buttonmessage; ?>
<br>
<?php echo $sqlstatusclear; ?>

</body>
</html>
