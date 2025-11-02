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
	
//create connection
$conn=new mysqli($host,$accessun,$accesspw,$dbname);


//---generate temp list of mice---
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

if (isset($_POST['generate_mice'])){
//get deadpup info
$xdob=$_POST['dob'];
$xdod=$_POST['dod'];
$xbulkcomments=$_POST['bulkcomments'];
$xsource_selection=$_POST['source_selection'];
$xdeath_type=$_POST['death_type'];

//populate table

$temptable='
<br><br>
<table name="temptable" id="temptable">
<tr name="tempheader" id="tempheader">
	<th>cage</th>
	<th>dob</th>
	<th>dod</th>
	<th>death_type</th>
	<th>comments</th>
</tr>
<tr name="tempdata" id="tempdata">
	<td><input type="text" id="sqlcage" name="sqlcage" value="'.$xsource_selection.'" readonly></td>
	<td><input type="date" id="sqldob" name="sqldob" value="'.$xdob.'"></td>
	<td><input type="date" id="sqldod" name="sqldod" value="'.$xdod.'"></td>
	<td><select id="sqldeath_type" name="sqldeath_type">
		<option value="'.$xdeath_type.'" selected>'.$xdeath_type.'</option>
		<option value="dead">dead</option>
		<option value="eaten">eaten</option>
		</select></td>
	<td><input type="text" id="sqlcomments" name="sqlcomments" value="'.$xbulkcomments.'"></td>
</tr>
</table>
';

$testtable=$temptable.'<br><br>
<input type=submit id="confirm_mice" name="confirm_mice" value="confirm mice">';

}
//--------------------confirm mice and add to db------------------------------------

if (isset($_POST['confirm_mice'])){

$sqltext='';

$dob=$_POST['sqldob'];
$dod=$_POST['sqldod'];
$comments=$_POST['sqlcomments'];

$death_type=$_POST['sqldeath_type'];
$cageid=$_POST['sqlcage'];

$sqltext='INSERT INTO `'.$dbname.'`.`table_deadpups` 
(`cageid`,`dob`,`dod`,`comments`,`death_type`)
VALUES 
("'.$cageid.'","'.$dob.'","'.$dod.'","'.$comments.'","'.$death_type.'");';

//echo $sqltext;

$sqlreport='Dead Pup Report ';
if($conn->multi_query($sqltext)===TRUE){
	$sqlstatus='-successful'.'...'.$sqltext;}
else {
	$sqlstatus='-failed '.$conn->error.'...'.$sqltext;}
$sqlreport.=$sqlstatus;
$conn->close();

}

?>

	<!--php script for display controls-->
<?php
//posted variables

$line_selection=$_POST['line_selection'];
$source_selection=$_POST['source_selection'];
$bulkcomments=$_POST['bulkcomments'];
$dob=$_POST['dob'];
$dod=$_POST['dod'];
$death_type=$_POST['death_type'];


//line list
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="call get_lines();";
$results=$conn->query($sqltext);
//set up static portion of table
$line_listbox= '<select id="line_selection" name="line_selection" size=1 class="mediumlistbox" onchange="submitForm()">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
//get results matched to current line - used for additional fields
if($row['line']===$line_selection){
$line_listbox .= '<option value="'.$row["line"].'" selected>'.$row["line"].'</option>';
} 
//get results for additional lines
else {
$line_listbox .= '<option value="'.$row["line"].'">'.$row["line"].'</option>';
}
} 
//close the table
$line_listbox .= '</select>';
$conn->close();

//mating list filtered by line
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT `currentcage` 
FROM (`table_mice` join `table_cages` on `table_mice`.`currentcage`=`table_cages`.`cageid`)
where dod is null and left(`currentcage`,1)='M' and (`line`='".$line_selection."' or `lineassignment`='".$line_selection."') 
GROUP BY `currentcage`;";
$results=$conn->query($sqltext);
$source_listbox='<select id="source_selection" name="source_selection" size=12 class="mediumlistbox" onchange="submitForm()">';

while($row=mysqli_fetch_array($results)){
$cage[]=$row['currentcage'];
}
foreach($cage as $source) {
if($source===$source_selection){
$source_listbox.='<option value="'.$source.'" selected>'.$source.'</option>';
}
else{
$source_listbox.='<option value="'.$source.'">'.$source.'</option>';
}
}
//close the table
$source_listbox.='</select>';
$conn->close();


//mating cage contents current
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

$sqltext="SELECT table_mice.mouseautono as 'man',line,idno,gender,dob,dod,currentcage FROM `table_mice` where dod is null and `currentcage`='".$source_selection."' ;";
$results=$conn->query($sqltext);
$mice_results=$results;
$mice_listbox='<select id="mice_selection" name="mice_selection" size=6 class="mediumlistbox onchange="submitForm()">;';
//loop and prepare table
while($row=mysqli_fetch_array($results)){
$mice_listbox.='<option value="'.$row['man'].'">'.$row['line'].'-'.$row['idno'].' | '.$row['gender'].'</option>';
}
//close the table
$mice_listbox.='</select>';
$conn->close();


//mating cage contents historical
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

$sqltext="SELECT table_cages.cagecontents FROM `table_cages` where `cageid`='".$source_selection."' ;";
$results=$conn->query($sqltext);
$mice_results=$results;

//loop and prepare table
while($row=mysqli_fetch_array($results)){
$mice_string=$source_selection.' | '.$row['cagecontents'];
$mice_string_display=$source_selection.' <br> '.$row['cagecontents'];
}
//close the table
$conn->close();


?>
<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Record Dead Pups - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />	
</head>
<body>

			<div id="header">
					<form id="loginbox" action="" method="post">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Record Dead Pups-
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
					 <form action="../php/add_mice.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Add Mice" />
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
					 <form action="../php/manage_mice.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Mice" />
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
					 <form action="../php/query_mice.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="View Mice" />
					 </form>
					  
			</div>


<!--CONTENT SECTION-->
			<div id="right_content" class="centertext">
			<form id="add_mice_form" name="add_mice_form" method=post>


					 <input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />

<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("add_mice_form").submit();
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
					<td rowspan=10><?php echo $source_listbox; ?></td>
					<td rowspan=10>
						<table>
						<tr><td>
						<?php echo $mice_listbox; ?></td></tr>
						<tr><th>Original Contents:</th></tr>
						<tr><td><?php echo $mice_string_display; ?></td></tr>
						
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
					<th>DOD:</th>
				</tr>
				<tr>
					<td><input type=date id="dod" name="dod" value="<?php echo $dod; ?>"></td>
				</tr>
				<tr>
					<th>Comments:</th>
				</tr>
				<tr>
					<td><textarea id="bulkcomments" name="bulkcomments" rows=3><?php echo $bulkcomments; ?></textarea></td>
				</tr>
				<tr>
					<th><p>Death Type:</p></th>
				</tr>
				<tr>
					<td><select id="death_type" name="death_type">
							<option value="<?php echo $death_type; ?>" selected><?php echo $death_type; ?></option>
							<option value="dead">dead</option>
							<option value="eaten">eaten</option>
						</select>
						</td>
					
				</tr>
			</table>
			<input type=submit id="generate_mice" name="generate_mice" value="generate mice">
			
			<?php echo $testtable; ?>
			<br>
			<?php echo $sqlreport; ?>
			
			
			</form>
			</div>

			
			<div id="footer">

					 <p class="righttext">
					 NeurobehavioralCore.com &copy; 2016
					 </p>
					
			</div>


</body>
</html>