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

//add line
if (isset($_POST['button_addline'])){
$line=$_POST['textaddline'];
$ldesc=$_POST['textaddldesc'];
$strain=$_POST['strain_selection'];
$ucsd_number=$_POST['textadducsdnumber'];
$addcard_color=$_POST['cardcolor_list'];
$addstripe_color=$_POST['stripecolor_list'];
$deactiv=$_POST['textadddeactiv'];
$sqlaction='add line:';
$sqltext="INSERT INTO `".$dbname."`.`table_lines` (`line`,`line_description`,`strain`,`ucsd_number`,`color_assignment`,`deactivated_line`,`card_color`) VALUES ('".$line."','".$ldesc."', '".$strain."','".$ucsd_number."','".$addstripe_color."','".$deactiv."','".$addcard_color."');";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
		}

//delete line
if (isset($_POST['button_deleteline'])){
$line=$_POST['textdelline'];
$sqlaction='delete line:';
//remove records from line and allele by line tables
$sqltext="DELETE FROM `".$dbname."`.`table_lines` WHERE `line`='".$line."';DELETE FROM `".$dbname."`.`key_allelebyline` WHERE `line`='".$line."';";
if ($conn->multi_query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
		}
//edit line
if (isset($_POST['button_editline'])){
$line=$_POST['textselectedline'];
$nline=$_POST['texteditline'];
$nldesc=$_POST['texteditldesc'];
$nstrain=$_POST['curr_strain_selection'];
$nucsd_number=$_POST['texteditucsdnumber'];
$ncard_color=$_POST['currcardcolor_list'];
$nstripe_color=$_POST['currstripecolor_list'];
$ndeactivated=$_POST['texteditdeactiv'];
$sqlaction='edit line:';
$sqltext="UPDATE `".$dbname."`.`table_lines` SET `line`='".$nline."', `line_description`='".$nldesc."', `strain`='".$nstrain."', `ucsd_number`='".$nucsd_number."', `color_assignment`='".$nstripe_color."', `deactivated_line`='".$ndeactivated."', `card_color`='".$ncard_color."' WHERE `line`='".$line."';";

if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
		}
//add allele<-need check for selected line
if (isset($_POST['button_addallele'])){
$line=$_POST['textselectedline'];
$currline=$_POST['textselectedline'];
$addallele=$_POST['allele_selection'];
$sqlaction='add allele:';
$sqltext="INSERT INTO `".$dbname."`.`key_allelebyline` (`line`, `allelegroup`) VALUES ('".$line."', '".$addallele."');";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus='successful';} else {
$sqlstatus='failed '.$conn->error.'...'.$sqltext;
}
}

//del allele 
if (isset($_POST['button_delallele'])){
$line=$_POST['textselectedline'];
$currline=$_POST['textselectedline'];
$delallele=$_POST['allelebyline_selection'];
$sqlaction='remove allele:';
$sqltext="DELETE FROM `".$dbname."`.`key_allelebyline` WHERE (`line`='".$line."' and `allelegroup`='".$delallele."');";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus='successful';} else {
$sqlstatus='failed '.$conn->error.'...'.$sqltext;
}
}

$buttonmessage=$sqlaction.' '.$line.' - '.$sqlstatus;
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
$conn->close();

//get line and allele data

//allele table
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query("call get_allelegroups()");
//set up static portion of table
$allele_table= '<select id="allele_selection" name="allele_selection" size=10, class="largelistbox">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
$allele_table .= '<option value="'.$row["allelegroup"].'">'.$row["allelegroup"].'</option>';
}
//close the table
$allele_table .= '</select>';
$conn->close();

//line table
$currline=$_POST['line_selection'];
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query("call get_lines()");
//set up static portion of table
$line_table= '<select id="line_selection" name="line_selection" size=10 class="mediumlistbox" onchange="showLine(this.value)">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
//get results matched to current line - used for additional fields
if($row['line']===$currline){
$currldesc=$row['line_description'];
$currstrain=$row['strain'];
$currucsdnumber=$row['ucsd_number'];
$currcardcolor=$row['card_color'];
$currstripecolor=$row['color_assignment'];
$currdeactiv=$row['deactivated_line'];
$line_table .= '<option value="'.$row["line"].'" selected>'.$row["line"].'</option>';
} 
//get results for additional lines
else {
$line_table .= '<option value="'.$row["line"].'">'.$row["line"].'</option>';
}
}
//close the table
$line_table .= '</select>';
$conn->close();

//strain table
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query("call get_strains");
//set up static portion of table
$strain_table= '<select id="strain_selection" name="strain_selection" size=1 class="mediumlistbox"><option value="" selected></option>';
$currstrain_table='<select id="curr_strain_selection" name="curr_strain_selection" size=1 class="mediumlistbox"><option value=""></option>';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
//check for current strain
if($row['strains']===$currstrain){
$currstrain_table .= '<option value="'.$row["strains"].'" selected>'.$row['strains'].'</option>';
$strain_table .= '<option value="'.$row["strains"].'">'.$row['strains'].'</option>';
} 
//fill in additional strains
else {
$currstrain_table .= '<option value="'.$row["strains"].'">'.$row['strains'].'</option>';
$strain_table .= '<option value="'.$row["strains"].'">'.$row['strains'].'</option>';
}
}
//close the table
$strain_table .= '</select>';
$currstrain_table.='</select>';
$conn->close();

//allelesbyline table
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$allelebylinequery="SELECT * from `key_allelebyline` WHERE `line`='".$currline."';";
$results=$conn->query($allelebylinequery);
//set up static portion of table
$allelebyline_table= '<select id="allelebyline_selection" name="allelebyline_selection" size=5, class="mediumlistbox">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
$allelebyline_table .= '<option value="'.$row["allelegroup"].'">'.$row["allelegroup"].'</option>';
}
//close the table
$allelebyline_table .= '</select>';
$conn->close();

$cardcolor_list= '<select id="cardcolor_list" name="cardcolor_list">
<option value="white">white</option>
<option value="grey">grey</option>
<option value="pink">pink</option>
<option value="peach">peach</option>
<option value="yellow">yellow</option>
<option value="green">green</option>
<option value="blue">blue</option>
<option value="lavender">lavender</option>
</select>';

$currcardcolor_list= '<select id="currcardcolor_list" name="currcardcolor_list">
<option value="'.$currcardcolor.'" selected>'.$currcardcolor.'</option>
<option value="white">white</option>
<option value="grey">grey</option>
<option value="pink">pink</option>
<option value="peach">peach</option>
<option value="yellow">yellow</option>
<option value="green">green</option>
<option value="blue">blue</option>
<option value="lavender">lavender</option>
</select>';

$stripecolor_list= '<select id="stripecolor_list" name="stripecolor_list">
<option value="white">white</option>
<option value="grey">grey</option>
<option value="pink">pink</option>
<option value="red">red</option>
<option value="salmon">salmon</option>
<option value="orange">orange</option>
<option value="yellow">yellow</option>
<option value="olive">olive</option>
<option value="green">green</option>
<option value="cyan">cyan</option>
<option value="blue">blue</option>
<option value="violet">violet</option>
</select>';

$currstripecolor_list= '<select id="currstripecolor_list" name="currstripecolor_list">
<option calue="'.$currstripecolor.'" selected>'.$currstripecolor.'</option>
<option value="white">white</option>
<option value="grey">grey</option>
<option value="pink">pink</option>
<option value="red">red</option>
<option value="salmon">salmon</option>
<option value="orange">orange</option>
<option value="yellow">yellow</option>
<option value="olive">olive</option>
<option value="green">green</option>
<option value="cyan">cyan</option>
<option value="blue">blue</option>
<option value="violet">violet</option>
</select>';

?>
<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Manage Lines - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />			
</head>
<body>

			<div id="header">
					<form id="loginbox" action="" method="get">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Lines-
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
<!--CONTENT SECTION-->
			<div id="right_content" class="centertext">		
			<br>
			<form id="line_selection_form" method=post class="centertext">

					<input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />

			<table>
			<tr>
			<td>
			<p>current lines:</p>
			<?php echo $line_table; ?>
			</td>

			<td>
			<p>alleles for selected line:</p>
			<?php echo $allelebyline_table; ?>
			</td>
			
			<td>
			<p>add</p>
			<input type=submit name="button_addallele" value="&larr;" >
			
			<p>remove</p>
			<input type=submit name="button_delallele" value="&rarr;" >
			</td>
			
			<td>			
			<p>all alleles within colony:</p>
			<?php echo $allele_table; ?>			
			</td>
			</table>
<!--javascript to autoupdate form based on lineselection -->
<script type="text/javascript">
function showLine(newValue)
{
	document.getElementById("line_selection_form").submit();
}
</script>
			
					
			<table>
				<tr>
				<th colspan=5>Add Line</th>
				</tr>
				<tr>
				<th>Line:</th>
				<th>Strain:</th>
				<th>UCSD Number:</th>
                                <th>Card Color:</th>
				<th>Deactivated:</th>
				</tr>
				<tr>
				<td><input type=text id="textaddline" name="textaddline"></td>
				<td><?php echo $strain_table; ?></td>
				<td><input type=text id="textadducsdnumber" name="textadducsdnumber"></td>
                                <td><?php echo $cardcolor_list; ?></td>
				<td><input type=number id="textadddeactiv" name="textadddeactiv" value=0 min=0 max=1></td>
				</tr>
				<th colspan=3>Description:</th>
                                <th>Stripe Color:</th>
				<td colspan=1 rowspan=2><input type=submit name="button_addline" style="height:90%;width:90%;"></td>
				</tr>
				<tr>
				<td colspan=3><input type=text style="width:100%;" id="textaddldesc" name="textaddldesc"></td>
				<td><?php echo $stripecolor_list; ?></td>
                                </tr>
		
				<tr>
				<th colspan=5 rowspan=1 style="background-color:#217190;">-</th>
				</tr>
				
				<tr>
				<th>Del Line:</th>
				<td colspan=3><input type=text style="width:100%;" id="textdelline" name="textdelline"</td>
				<td><input type=submit name="button_deleteline" style="width:90%;"></td>
				</tr>
				
				<tr>
				<th colspan=5 rowspan=1 style="background-color:#217190;">-</th>
				</tr>
				
				<tr>
				<th colspan=5>Selected:</th>
				</tr>
				<tr>
				<th>Line:</th>
				<th>Strain:</th>
				<th>UCSD Number:</th>
                                <th>Card Color:</th>
				<th>Deactivated:</th>
				</tr>
				<tr>
				<td><input type=text id="textselectedline" name="textselectedline" value="<?php echo $currline; ?>" readonly="readonly"></td>
				<td><input type=text id="textselectedstrain" name="textselectedstrain" value="<?php echo $currstrain; ?>" readonly="readonly"></td>
				<td><input type=text id="textselucsdnumber" name="textselucsdnumber" value="<?php echo $currucsdnumber; ?>" readonly="readonly"></td>
                                <td><?php echo $currcardcolor; ?></td>
				<td><input type=number id="textseldeactiv" name="textseldeactiv" value="<?php echo $currdeactiv; ?>" readonly="readonly"></td>
				</tr>
				<tr>
				<th colspan=3>Description:</th>
                                <td>Stripe Color:</td>
				</tr>
				<tr>
                                <td colspan=3><input type=text style="width:100%;" id="textseldesc" name="textseldesc" value="<?php echo $currldesc; ?>" readonly="readonly"></td>
                                <td><?php echo $currstripecolor; ?></td>
				</tr>

				<tr>
				<th colspan=5 rowspan=1 style="background-color:grey;">-</th>
				</tr>
				
				<tr>
				<th colspan=5 >Edit:</th>
				</tr>
				<tr>
				<th>Line:</th>
				<th>Strain:</th>
				<th>UCSD Number:</th>
                                <th>Card Color:</th>
				<th>Deactivated:</th>
				</tr>
				<tr>
				<td><input type=text id="texteditline" name="texteditline" value="<?php echo $currline; ?>" ></td>
				<td><?php echo $currstrain_table; ?></td>
				<td><input type=text id="texteditucsdnumber" name="texteditucsdnumber" value="<?php echo $currucsdnumber; ?>" ></td>
				<td><?php echo $currcardcolor_list; ?></td>
                                <td><input type=number id="texteditdeactiv" name="texteditdeactiv" value="<?php echo $currdeactiv; ?>" min=0 max=1></td>
				</tr>
				<tr>
				<th colspan=3>Description:</th>
                                <th>Stripe Color:</th>
				<th colspan=1 rowspan=2><input type=submit name="button_editline" style="height:90%;width:90%;"></th>
				</tr>
                                <tr>
				<td colspan=3><input type=text style="width:100%;" id="texteditldesc" name="texteditldesc" value="<?php echo $currldesc; ?>" ></td>
				<td><?php echo $currstripecolor_list; ?></td>
                                </tr>
				
			</table>

			
			<p> <?php echo $buttonmessage; ?> </p>
			<p> <?php echo $sqltext; ?> </p>
			</form>
			
		
				
			

			
			
			</div>
			<div id="footer">
					 <p class="righttext">
					  @realchrisward &copy; 2025
					 </p>
					 
			</div>
</body>
</html>