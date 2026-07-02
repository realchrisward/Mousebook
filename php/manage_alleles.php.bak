<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
	<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
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

$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//add gene

if (isset($_POST['addgenebutton'])){
$textgene=$_POST['textgene'];
$sqlaction='add gene:'.$textgene;
$sqltext="INSERT INTO `list_gene` (`gene`) VALUES ('".$textgene."');";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= '-successful';} 
else {
$sqlstatus= '-failed '.$conn->error.'...'.$sqltext;
}
}

//remove gene - need checks/abort for deletion of genes already in use
if (isset($_POST['remgenebutton'])){
$textgene=$_POST['textgene'];
$sqlaction='delete gene:'.$textgene;
//remove records from line and allele by line tables
$sqltext="DELETE FROM `list_gene` WHERE `gene`='".$textgene."';";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= '-successful';} 
else {
$sqlstatus= '-failed '.$conn->error.'...'.$sqltext;
}
}

//edit gene - not currently implemented

//add allelegroup
if (isset($_POST['addallelegroupbutton'])){
$gene_selection=$_POST['gene_selection'];
$textallelegroup=$_POST['textallelegroup'];
$textallelegroupref=$_POST['textallelegroupref'];
$sqlaction='add allelegroup:'.$textallelegroup;
$sqltext="INSERT INTO `list_allelegroup` (`allelegroup`,`gene`,`reference`) VALUES('".$textallelegroup."','".$gene_selection."','".$textallelegroupref."');";
if ($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else{
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//remove allelegroup
if (isset($_POST['remallelegroupbutton'])){
$textallelegroup=$_POST['textallelegroup'];
$sqlaction='delete allelegroup:'.$textallelegroup;
$sqltext="DELETE FROM `list_allelegroup` WHERE `allelegroup`='".$textallelegroup."';";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else{
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//edit allelegroup
if (isset($_POST['editallelegroupbutton'])){
$texteditallelegroup=$_POST['texteditallelegroup'];
$texteditallelegroupref=$_POST['texteditallelegroupref'];
$sqlaction='edit allelegroup:'.$texteditallelegroup;
$sqltext="UPDATE `list_allelegroup` SET `reference`='".$texteditallelegroupref."' WHERE `allelegroup`='".$texteditallelegroup."';";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//add allele
if (isset($_POST['addallelebutton'])){
$alleleXgendspec=$_POST['allelegendspec'];
$textallele=$_POST['textallele'];
$allelegrp_selection=$_POST['allelegrp_selection'];
$sqlaction='add allele:'.$textallele.' to '.$allelegrp_selection;
$sqltext="INSERT INTO `list_allele` (`allelegroup`,`allele`,`genderspecific`) VALUES ('".$allelegrp_selection."','".$textallele."','".$alleleXgendspec."');";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//remove allele
if (isset($_POST['remallelebutton'])){
$textallele=$_POST['textallele'];
$allelegrp_selection=$_POST['allelegrp_selection'];
$sqlaction='delete allele:'.$textallele.' from '.$allelegrp_selection;
$sqltext="DELETE FROM `list_allele` WHERE (`allelegroup`='".$allelegrp_selection."' and `allele`='".$textallele."');";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}
//edit allele - not currently included

//add genotyping rxn
if (isset($_POST['addgenorxnbutton'])){
$textgenorxn=$_POST['textgenorxn'];
$textgenorxncom=$_POST['textgenorxncom'];
$textgenorxncyc=$_POST['textgenorxncyc'];
$sqlaction='add genorxn:'.$textgenorxn;
$sqltext="INSERT INTO `list_genotypingrxns` (`genotypingrxn`,`comments`,`recommendedcycle`) 
VALUES ('".$textgenorxn."','".$textgenorxncom."','".$textgenorxncyc."');";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//remove genotyping rxn
if (isset($_POST['remgenorxnbutton'])){
$textgenorxn=$_POST['textgenorxn'];
$sqlaction='delete genorxn:'.$textgenorxn;
$sqltext="DELETE FROM `list_genotypingrxns` WHERE `genotypingrxn`='".$textgenorxn."';";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//edit genotyping rxn
if (isset($_POST['editgenorxnbutton'])){
$texteditgenorxn=$_POST['texteditgenorxn'];
$texteditgenorxncom=$_POST['texteditgenorxncom'];
$texteditgenorxncyc=$_POST['texteditgenorxncyc'];
$sqlaction='edit:'.$texteditgenorxn;
$sqltext="UPDATE `list_genotypingrxns` SET `comments`='".$texteditgenorxncom."', 
`recommendedcycle`='".$texteditgenorxncyc."' WHERE `genotypingrxn`='".$texteditgenorxn."';";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//add primer
if(isset($_POST['addprimerbutton'])){
$textprimer=$_POST['textprimer'];
$textprimerseq=$_POST['textprimerseq'];
$textprimercom=$_POST['textprimercom'];
$genorxn_selection=$_POST['genorxn_selection'];
$sqlaction='add primer:'.$textprimer;
$sqltext="INSERT INTO `list_genotypingprimers` (`primerseq`,`primername`,`genotypingrxn`,`comments`) 
VALUES  ('".$textprimerseq."','".$textprimer."','".$genorxn_selection."','".$textprimercom."');";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}
//remove primer
if(isset($_POST['remprimerbutton'])){
$textprimer=$_POST['textprimer'];
$genorxn_selection=$_POST['genorxn_selection'];
$sqlaction='delete primer:'.$textprimer;
$sqltext="DELETE FROM `list_genotypingprimers` WHERE (`primername`='".$textprimer."' and `genotypingrxn`='".$genorxn_selection."');";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//edit primer
if(isset($_POST['editprimerbutton'])){
$texteditprimer=$_POST['texteditprimer'];
$texteditprimerseq=$_POST['texteditprimerseq'];
$texteditprimercom=$_POST['texteditprimercom'];
$genorxn_selection=$_POST['genorxn_selection'];
$sqlaction='edit primer:'.$texteditprimer;
$sqltext="UPDATE `list_genotypingprimers` 
SET `primerseq`='".$texteditprimerseq."',`comments`='".$texteditprimercom."' 
WHERE (`primername`='".$texteditprimer."' and `genotypingrxn`='".$genorxn_selection."');";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//add genorxn by allelegroup pair
if(isset($_POST['assigngenorxnbutton'])){
$genorxn_selection=$_POST['genorxn_selection'];
$allelegrp_selection=$_POST['allelegrp_selection'];
$sqlaction='add rxn:'.$genorxn_selection.' to allele group:'.$allelegrp_selection;
$sqltext="INSERT INTO key_allelegroupbygenotypingrxn (`allelegroup`,`genotypingrxn`) 
VALUES ('".$allelegrp_selection."','".$genorxn_selection."');";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

//remove genorxn by allelegroup pair
if(isset($_POST['deassigngenorxnbutton'])){
$genorxnbyallelegrp_selection=$_POST['genorxnbyallelegrp_selection'];
$allelegrp_selection=$_POST['allelegrp_selection'];
$sqlaction='remove rxn:'.$genorxn_selection.' from allele group:'.$allelegrp_selection;
$sqltext="DELETE FROM key_allelegroupbygenotypingrxn 
WHERE (`allelegroup`='".$allelegrp_selection."' and `genotypingrxn`='".$genorxnbyallelegrp_selection."');";
if($conn->query($sqltext)===TRUE){
$sqlstatus='-successful';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
}

$conn->close();
$sqlreport=$sqlaction.' - '.$sqlstatus
?>
	<!--php script for display controls-->
<?php

//get posted variables
$currgene=$_POST['gene_selection'];
$currallelegrp=$_POST['allelegrp_selection'];
$currgenorxn=$_POST['genorxn_selection'];
$currgenorxnfromag=$_POST['genorxnbyallelegrp_selection'];
$currprimer=$_POST['primer_selection'];

//gene table
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query("call get_genes();");
//set up static portion of table
$gene_table= '<select id="gene_selection" name="gene_selection" size=8class="smalllistbox" onchange="submitForm()">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
//get results matched to current line - used for additional fields
if($row['gene']===$currgene){
$gene_table .= '<option value="'.$row["gene"].'" selected>'.$row["gene"].'</option>';
} 
//get results for additional lines
else {
$gene_table .= '<option value="'.$row["gene"].'">'.$row["gene"].'</option>';
}
}
//close the table
$gene_table .= '</select>';
$conn->close();

//allelegroups filtered by gene
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="select * from list_allelegroup where gene='".$currgene."';";
$results=$conn->query($sqltext);
//set up static portion of table
$allelegrp_table= '<select id="allelegrp_selection" name="allelegrp_selection" size=8 class="largelistbox" onchange="submitForm()">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
//get results matched to current line - used for additional fields
if($row['allelegroup']===$currallelegrp){
$currallelegrpref=$row['reference'];
$allelegrp_table .= '<option value="'.$row["allelegroup"].'" selected>'.$row["allelegroup"].'</option>';
} 
//get results for additional lines
else {
$allelegrp_table .= '<option value="'.$row["allelegroup"].'">'.$row["allelegroup"].'</option>';
}
}
//close the table
$allelegrp_table .= '</select>';
$conn->close();

//alleles filtered by allelegroup
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT * FROM list_allele where allelegroup='".$currallelegrp."';";
$results=$conn->query($sqltext);
//set up static portion of table
$allele_table= '<select id="allele_selection" name="allele_selection" size=8 class="smalllistbox" onchange="">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
$allele_table .= '<option value="'.$row["allele"].'">'.$row["allele"].' | '.$row["genderspecific"].'</option>';
}
//close the table
$allele_table .= '</select>';
$conn->close();

//allelegroup x genotyping rxn pairs
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT * FROM key_allelegroupbygenotypingrxn WHERE allelegroup='".$currallelegrp."';";
$results=$conn->query($sqltext);
//set up static portion of table
$genorxnbyallelegrp_table= '<select id="genorxnbyallelegrp_selection" name="genorxnbyallelegrp_selection" size=5 class="mediumlistbox" onchange="">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
//get results matched to current line - used for additional fields
if($row['genotypingrxn']===$currgenorxn){
$genorxnbyallelegrp_table .= '<option value="'.$row["genotypingrxn"].'" selected>'.$row["genotypingrxn"].' X '.$currallelegrp.'</option>';
} 
//get results for additional lines
else {
$genorxnbyallelegrp_table .= '<option value="'.$row["genotypingrxn"].'">'.$row["genotypingrxn"].' X '.$currallelegrp.'</option>';
}
}
$genorxnbyallelegrp_table.= '</select>';
$conn->close();

//select * genotyping reactions
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query("call get_genorxns();");
//set up static portion of table
$genorxn_table= '<select id="genorxn_selection" name="genorxn_selection" size=5 class="mediumlistbox" onchange="submitForm()">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
if($row['genotypingrxn']===$currgenorxn){
$currgenorxncomments=$row['comments'];
$currgenorxncycle=$row['recommendedcycle'];
$genorxn_table .= '<option value="'.$row["genotypingrxn"].'" selected>'.$row["genotypingrxn"].'</option>';
} else {
$genorxn_table .= '<option value="'.$row["genotypingrxn"].'">'.$row["genotypingrxn"].'</option>'; 
}
}
//close the table
$genorxn_table .= '</select>';
$conn->close();

//genotyping primers filtered by genotyping rxn
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT * FROM list_genotypingprimers WHERE genotypingrxn='".$currgenorxn."';";
$results=$conn->query($sqltext);
//set up static portion of table
$primer_list='<table id="primer_list_table" name="primer_list_table"><tr><th>primer name</th><th>primer seq</th><th>comments</th></tr>';
$primer_table= '<select id="primer_selection" name="primer_selection" size=5 class="mediumlistbox" onchange="submitForm()">';
//loop the result set and prepare table

while($row=mysqli_fetch_array($results)) {
//catch results of each row
//get results matched to current primer - used for additional fields
if($row['primername']===$currprimer){
$currprimerseq=$row['primerseq'];
$currprimercom=$row['comments'];
$primer_table .= '<option value="'.$row["primername"].'" selected>'.$row["primername"].'</option>';
$primer_list.='<tr><td>'.$row["primername"].'</td><td>'.$row["primerseq"].'</td><td>'.$row["comments"].'</td></tr>';
} 
//get results for additional lines
else {
$primer_table .= '<option value="'.$row["primername"].'">'.$row["primername"].'</option>';
$primer_list.='<tr><td>'.$row["primername"].'</td><td>'.$row["primerseq"].'</td><td>'.$row["comments"].'</td></tr>';
}
}

$primer_table.='</select>';
$primer_list.='</table>';
$conn->close()

?>
<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Alleles - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>

			<div id="header">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Alleles-
					 </h2>
					 
					 <h1 class="centervert"
					 style="position:absolute;top:0px;left:350px;">
					<?php echo $dbname; ?>
					<input type=hidden name="dbname" value="<?php echo $dbname; ?>" />
					 </h1>
					 
					 <button id="statusbutton" style="background-color:<?php echo $xloginstatus; ?>;
					 width:20px;height:20px;border-radius:10px;position:absolute;
					 top:15px;right:250px;"></button>
					 
					 
					 <form id="loginbox" action="" method="get">
						<table class="logintable" style="color:white;font-size:10px;position:absolute;top:0px;right:60px;">
						<tr>
						<th>user:</th>
						<th><input type="text" name="username" 
						value="<?php echo $xusername; ?>" style="width:100px;font-size:10px;" /></th>
						</tr>
						<tr>
						<td>pass:</td>
						<td><input type="password" name="password" 
						value="<?php echo $xpassword; ?>" style="width:100px;font-size:10px;" /></td>
						</tr>
						</table>
						<input type=submit id="loginbutton" name="button_login"
						style="font-size:10px;width:50px;height:20px;
						position:absolute;top:5px;right:10px;"
						value="connect"
						/>
					</form>
					<form id="logoutbox" action="" method="get">
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
<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("allele_management_form").submit();
}
</script>
<!--posted and inferred variable list, currgene, currallelegrp, currgenorxn, currgenorxnfromag, currprimer, alleleXgendspec;
 currallelegrpref,currgenorxncomments, currgenorxncycle,currprimerseq,currprimercom;
 textgene, textallelegroup, textallelegroupref, texteditallelegroup, texteditallelegroupref, 
 textgenorxn, textgenorxncom, textgenorxncyc, texteditgenorxn, textgenorxncom, textgenorxncyc,
 textprimer, textprimerseq, textprimercom, texteditprimer, textprimerseq, textprimercom -->

<!--buttonlist: addgenebutton, remgenebutton, addallelegroupbutton, remallelegroupbutton, editallelegroupbutton,	
addallelebutton, remallelebutton, assigngenorxn, deasssigngenorxn, addgenorxn,
remgenorxn, editgenorxn, addprimer, remprimer, editprimer-->

			<form id="allele_management_form" name="allele_management_form" method=post>

					 <input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />

			<table id="gene_and_allele_management" name="gene_and_allele_management">
			<tr>
				<th>Genes</th>
				<th>Allele Groups</th>
				<th>Alleles</th>
			</tr>
			<tr>
				<td><?php echo $gene_table; ?></td>
				<td><?php echo $allelegrp_table; ?></td>
				<td><?php echo $allele_table; ?></td>
			</tr>
			</table>
			<table>
			<tr>
<!--GENES-->			<th class="mediumlistbox">Gene:</th>
				<td><input type="text" id="textgene" name="textgene" style="width:100%;"></td>
<!--buttons-->			<td>
					<input type=submit id="addgenebutton" name="addgenebutton" value="Add" style="width:40%;">
					<input type=submit id="remgenebutton" name="remgenebutton" value="Rem." style="width:40%;">
				</td>
			</tr>
			
			<tr><td colspan=3 style="background-color:#217190;">-</td></tr>
			<tr>
<!--ALLELE GROUPS-->		<th>Allele Group:</th>
				<th>Reference</th>
<!--buttons-->			<td>
					<input type=submit id="addallelegroupbutton" name="addallelegroupbutton" value="Add" style="width:40%;">
					<input type=submit id="remallelegroupbutton" name="remallelegroupbutton" value="Rem." style="width:40%;">
				</td>				
			</tr>
			<tr>
				<td><input type="text" id="textallelegroup" name="textallelegroup" style="width:100%;"></td>
				<td colspan=2><input type="text" id="textallelegroupref" name="textallelegroupref" style="width:100%;"</td>
			</tr>
			
			<tr><td colspan=3 style="background-color:#217190;"></td></tr>
			<tr>
				<th>Edit Allele Group:</th>
				<th>Reference</th>
<!--buttons-->			<td><input type=submit id="editallelegroupbutton" name="editallelegroupbutton" value="Edit" style="width:90%;"></td>				
			</tr>
			<tr>
				<td><input type="text" id="texteditallelegroup" name="texteditallelegroup"  value="<?php echo $currallelegrp; ?>" style="width:100%;" readonly="readonly"></td>
				<td colspan=2><input type="text" id="texteditallelegroupref" name="texteditallelegroupref" value="<?php echo $currallelegrpref; ?>" style="width:100%;"</td>
			</tr>
			
			<tr><td colspan=3 style="background-color:#217190;">-</td></tr>
			<tr>
<!--ALLELES-->			<th>Allele:</th>
				<td>
					<input type="text" id="textallele" name="textallele">
					<select id="allelegendspec" name="allelegendspec">
						<option value="all" selected>all</option>
						<option value="M" >M</option>
						<option value="F" >F</option>
					</select>
				</td>
				<td>
<!--buttons-->				<input type=submit id="addallelebutton" name="addallelebutton" value="Add" style="width:40%;">
					<input type=submit id="remallelebutton" name="remallelebutton" value="Rem." style="width:40%;">
				</td>
			</tr>
			</table>
			
			<table id="allele_and_genotyping_rxn_management" name="allele_and_genotyping_rxn_management">
			<tr>
				<th>GenoRxn X AlleleGrp</th>
				<th></th>
				<th>All Geno Rxns</th>
				<th>Primers</th>
			</tr>
			<tr>
<!--GENO X AG-->		<td><?php echo $genorxnbyallelegrp_table; ?></td>
				<td>
<!--buttons-->				<p><input type=submit id="assigngenorxnbutton" name="assigngenorxnbutton" value="&larr;"></p>
					
					<p><input type=submit id="deassigngenorxnbutton" name="deassigngenorxnbutton" value="&rarr;"></p>
				</td>
				<td><?php echo $genorxn_table; ?></td>
				<td><?php echo $primer_table; ?></td>
			</tr>
			<tr>
				<td colspan=4 ><?php echo $primer_list; ?></td>
			</tr>
			<tr>
				<td colspan=4 style="background-color:#217190;"></td>
			</tr>
			</table>
			<table>
			<tr>
				<th>Rxn</th>
				<th>Comments</th>
				<th>Cycle</th>
			</tr>
			<tr>
<!--GENO RXNS-->		<td><input type="text" id="textgenorxn" name="textgenorxn"></td>
				<td><input type="text" id="textenorxncom" name="textgenorxncom"></td>
				<td><input type="text" id="textgenorxncyc" name="textgenorxncyc"></td>
<!--buttons-->			<td>
					<input type=submit id="addgenorxnbutton" name="addgenorxnbutton" value="Add">
					<input type=submit id="remgenorxnbutton" name="remgenorxnbutton" value="Rem.">	
				</td>
			</tr>
			<tr>
				<td><input type="text" id="texteditgenorxn" name="texteditgenorxn" value="<?php echo $currgenorxn; ?>" readonly="readonly" ></td>
				<td><input type="text" id="texteditgenorxncom" name="texteditgenorxncom" value="<?php echo $currgenorxncomments; ?>" ></td>
				<td><input type="text" id="texteditgenorxncyc" name="texteditgenorxncyc" value="<?php echo $currgenorxncycle; ?>" ></td>			
<!--buttons-->			<td><input type=submit id="editgenorxnbutton" name="editgenorxnbutton" value="Edit"></td>
			</tr>
			<tr>
				<th>Primer</th>
				<th>Sequence</th>
				<th>Comments</th>
			</tr>			<tr>
<!--PRIMERS-->			<td><input type="text" id="textprimer" name="textprimer"></td>
				<td><input type="text" id="textprimerseq" name="textprimerseq"></td>
				<td><input type="text" id="textprimercom" name="textprimercom"></td>
<!--buttons-->			<td>
					<input type=submit id="addprimerbutton" name="addprimerbutton" value="Add">
					<input type=submit id="remprimerbutton" name="remprimerbutton" value="Rem.">
				</td>
			</tr>
			<tr>
				<td><input type="text" id="texteditprimer" name="texteditprimer" value="<?php echo $currprimer; ?>" readonly="readonly" ></td>
				<td><input type="text" id="texteditprimerseq" name="texteditprimerseq" value="<?php echo $currprimerseq; ?>" ></td>
				<td><input type="text" id="texteditprimercom" name="texteditprimercom" value="<?php echo $currprimercom; ?>" ></td>			
<!--buttons-->			<td><input type=submit id="editprimerbutton" name="editprimerbutton" value="Edit"></td>
			</tr>
			</table>		
			</form>
			
			</div>
			
			
			<div id="footer">
					 <p class="righttext">
					  @realchrisward &copy; 2025
					 </p>
 					<?php echo $sqlreport; ?>
			</div>


</body>
</html>