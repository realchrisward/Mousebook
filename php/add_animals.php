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


//*****purge old animal number reservations*****
$sqlpurge="LOCK table `".$dbname."`.`reservations_animals` WRITE; DELETE FROM `".$dbname."`.`reservations_animals` WHERE `user`='".$xusername."'; UNLOCK tables;";
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
if($conn->multi_query($sqlpurge)===TRUE){
	//flush the mysql submission
while (mysqli_next_result($conn));
$sqlstatus='-autonumber reservations cleared'.'...';}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
$sqlreport.=$sqlstatus;
$conn->close();




//*****generate temp list of animals*****
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

if (isset($_POST['generate_animals'])){

//release last reservation

//get line
$line_selection=$_POST['line_selection'];
//get max animalautono
////$xmaxanimalautono=$_POST['maxanimalautono'];
//get max animalinline
////$xmaxanimalinline=$_POST['maxanimalinline'];
//get line,dob,comments,source,parents
$xline_selection=$_POST['line_selection'];
$xdob=$_POST['dob'];
$xbulkcomments=$_POST['bulkcomments'];
$xsource_selection=$_POST['source_selection'];
$xanimals_string=$_POST['animals_string'];
//get number of animals
$xnumbermale=$_POST['numbermale'];
$xnumberfemale=$_POST['numberfemale'];
$xnumberunknown=$_POST['numberunknown'];
$xtotalnumber=$xnumbermale+$xnumberfemale+$xnumberunknown;

//maxanimalautono
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT max(animalautono) as maxanimalautono FROM `".$dbname."`.`table_animals`;";
$results=$conn->query($sqltext);
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
$maxanimalautono1=$row['maxanimalautono'];
}
//maxanimalautono - reserved
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT max(maxautono) as maxanimalautono FROM `".$dbname."`.`reservations_animals`;";
$results=$conn->query($sqltext);
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
$maxanimalautono2=$row['maxanimalautono'];
}
 
$xmaxanimalautono=max($maxanimalautono1,$maxanimalautono2);

//maxanimalinline
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT max(cast(`idno` as unsigned)) as maxanimalinline FROM `".$dbname."`.`table_animals` where `line`='".$line_selection."' ;";
$results=$conn->query($sqltext);
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
$maxanimalinline1=$row['maxanimalinline'];
}
if ($maxanimalinline1==""){
$maxanimalinline1=0;}

//maxanimalinline - reserved
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT max(maxidno) as maxanimalinline FROM `".$dbname."`.`reservations_animals` where `line`='".$line_selection."';";
$results=$conn->query($sqltext);
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
$maxanimalinline2=$row['maxanimalinline'];
}
if ($maxanimalinline2==""){
$maxanimalinline2=0;}

$xmaxanimalinline=max($maxanimalinline1,$maxanimalinline2);
 
//deposit user, xmaxanimalautono, line, maxanimalinline into reservation list
$sqlres="LOCK table `".$dbname."`.`reservations_animals` WRITE; INSERT INTO `".$dbname."`.`reservations_animals` (`user`, `line`, `maxautono`, `maxidno`, `timestamp`) "
."VALUES ('".$xusername."', '".$xline_selection."', '".($xmaxanimalautono+$xtotalnumber)."', '".($xmaxanimalinline+$xtotalnumber)."', now())"
." ON DUPLICATE KEY UPDATE `line`='".$xline_selection."', `maxautono`='".($xmaxanimalautono+$xtotalnumber)."', "
."`maxidno`='".($xmaxanimalinline+$xtotalnumber)."', `timestamp`=now(); UNLOCK tables;";
//echo $sqlres;
if($conn->multi_query($sqlres)===TRUE){
	//flush the mysql submission
while (mysqli_next_result($conn));
$sqlstatus='-successful'.'...'.$sqltext;}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
$sqlreport.=$sqlstatus;
$conn->close();
/**/



if ($xsource_selection==="FOUNDER"){
$xcurrcage="FOUNDER".'-'.$xline_selection.' - '.$xdob;
} elseif($xsource_selection==""){
$xcurrcage="FOUNDER".'-'.$xline_selection.' - '.$xdob;
} else {
$xcurrcage="Litter-".$xsource_selection.' - '.$xdob;
}


//populate table
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

$xautonos=range($xmaxanimalautono+1,$xmaxanimalautono+$xtotalnumber,1);
$autonoskv=array_combine($xautonos,$xautonos);
$xidnos=array_combine($xautonos,range($xmaxanimalinline+1,$xmaxanimalinline+$xtotalnumber,1));

$xtn=range(1,$xtotalnumber,1);

$xearkeys = array(
	1=>"-",
	2=>"R",
	3=>"L",
	4=>"RL",
	5=>"RR",
	6=>"LL",
	7=>"RRL",
	8=>"RLL",
	9=>"RRLL",
	10=>"RRR",
	11=>"LLL",
	12=>"RRRL",
	13=>"RRRLL",
	14=>"RLLL",
	15=>"RRLLL",
	16=>"RRRLLL",
	17=>"other",
);

foreach ($xtn as $i){
//echo $i;
	if ($i<=$xnumberfemale){
	//echo 'f';
		$xgendarray[]='F';
		if ($i<=17){
			$xeararray[]=$xearkeys[$i];}
		else {$xeararray[]="other";}
	}
	elseif ($i<=$xnumberfemale+$xnumbermale){
		//echo 'm';
		$xgendarray[]='M';
		if (($i-$xnumberfemale)<=17){
			$xeararray[]=$xearkeys[$i-$xnumberfemale];}
		else {$xeararray[]="other";}
	}
	else {
		//echo 'unk';
		$xgendarray[]='unk';
		if (($i-$xnumberfemale-$xnumbermale)<=17){
			$xeararray[]=$xearkeys[$i-$xnumberfemale-$xnumbermale];}
		else {$xeararray[]="other";}
	}
}
//print_r($xgendarray);
//print_r($xeararray);
$xgendarray=array_combine($xautonos,array_values($xgendarray));
$xeararray=array_combine($xautonos,$xeararray);
foreach ($xeararray as $i){
//echo $i;
//echo $xeararray[$i];
}
foreach ($xautonos as $i){
//echo $i;
//echo $xeararray[$i];
}
//echo var_dump($xgendarray);
//get allelegroups
$sqltext="SELECT `allelegroup` FROM `".$dbname."`.`key_allelebyline` WHERE `line`='".$xline_selection."' GROUP BY `allelegroup`;";
$results=$conn->query($sqltext);
while($row=mysqli_fetch_array($results)){
$aglist[$row['allelegroup']]=array('M'=>'','F'=>'','all'=>'');
$genelist[]=$row['allelegroup'];
}

$genecount=count($genelist);
$genepost='';
foreach (range(0,$genecount-1,1) as $i){
$genepost.='<input type=hidden id="geno'.$i.'" name="geno'.$i.'" value="'.$genelist[$i].'">';
}
//echo $genepost;

//get genotype dialogs prepared
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT `line`,`list_allele`.`allelegroup`,`allele`,`genderspecific` FROM `".$dbname."`.`key_allelebyline` 
JOIN `".$dbname."`.`list_allele` ON `key_allelebyline`.`allelegroup`=`list_allele`.`allelegroup` 
WHERE `key_allelebyline`.`line`='".$xline_selection."';";
$results=$conn->query($sqltext);


while($row=mysqli_fetch_array($results)){
$aglist[$row['allelegroup']][$row['genderspecific']].='<option value="'.$row['allele'].'">'.$row['allele'].'</option>';

}
//echo var_dump($aglist);
//close the table
$conn->close();

//getallelegroup array with alleles
$genoheader='';
$genou='';
$genom='';
$genof='';
//foreach (array_keys($aglist) as $ag){
foreach (range(0,$genecount-1,1) as $i){
$ag=$genelist[$i];
//$genou.='<td id="geno'.$ag.'[]" name="geno'.$ag.'[]"><select><option value="unk" selected>unk</option>'.$aglist[$ag]['M'].$aglist[$ag]['F'].$aglist[$ag]['all'].'</select></td>';
$genou.='<td ><select id="geno'.$i.'-[]" name="geno'.$i.'-[]"><option value="unk" selected>unk</option>'.$aglist[$ag]['M'].$aglist[$ag]['F'].$aglist[$ag]['all'].'</select></td>';
//$genom.='<td id="geno'.$ag.'[]" name="geno'.$ag.'[]"><select><option value="unk" selected>unk</option>'.$aglist[$ag]['M'].$aglist[$ag]['all'].'</select></td>';
$genom.='<td ><select id="geno'.$i.'-[]" name="geno'.$i.'-[]"><option value="unk" selected>unk</option>'.$aglist[$ag]['M'].$aglist[$ag]['all'].'</select></td>';
//$genof.='<td id="geno'.$ag.'[]" name="geno'.$ag.'[]"><select><option value="unk" selected>unk</option>'.$aglist[$ag]['F'].$aglist[$ag]['all'].'</select></td>';
$genof.='<td ><select id="geno'.$i.'-[]" name="geno'.$i.'-[]"><option value="unk" selected>unk</option>'.$aglist[$ag]['F'].$aglist[$ag]['all'].'</select></td>';
$genoheader.='<th>'.$ag.'</th>';

}
//echo $genoheader;
//echo $genou;
//echo $genom;
//echo $genof;


foreach ($xautonos as $an){
if($xgendarray[$an]==='M'){
$xgsgeno[$an]=$genom;}
if($xgendarray[$an]==='F'){
$xgsgeno[$an]=$genof;}
if($xgendarray[$an]==='unk'){
$xgsgeno[$an]=$genou;}
}


$temptable='
<table name="temptable" id="temptable">
<tr name="tempheader" id="tempheader">
<th></th>
<th>line</th>
<th>idno</th>
<th>gender</th>
<th>ear tag</th>
<th>dob</th>
'.$genoheader.'
<th>currentcage</th>
<th>sourcecage</th>
<th>parents</th>	
<th>bulkcomments</th>
</tr>
';

$temprow='';
foreach ($xautonos as $ck){

//$ck=0;
$temprow.='
<tr name="trow'.$ck.'" id="trow'.$ck.'">
<td ><input class="smalllistbox" type=hidden name="animalautono'.$ck.'" id="animalautono'.$ck.'" readonly="readonly" value='.$ck.' >
	</td>
<td ><input class="smalllistbox" type=text name="line'.$ck.'" id="line'.$ck.'" readonly="readonly" value="'.$xline_selection.'" >
	</td>
<td ><input class="smalllistbox" type=text name="idno'.$ck.'" id="idno'.$ck.'" readonly="readonly" value='.$xidnos[$ck].' >
	</td>
<td ><select class="smalllistbox" name="gender'.$ck.'" id="gender'.$ck.'"><option value='.$xgendarray[$ck].' selected> '.$xgendarray[$ck].'
	</option><option value="M">M</option><option value="F">F</option><option value="unk">unk</option></select>
	</td>
<td><select id="eartag'.$ck.'" name="eartag'.$ck.'">
	<option value='.$xeararray[$ck].' selected>'.$xeararray[$ck].'</option>
	<option value="-" >-</option>
	<option value="R" >R</option>
	<option value="L" >L</option>
	<option value="RL" >RL</option>
	<option value="RR" >RR</option>
	<option value="LL" >LL</option>
	<option value="RRL" >RRL</option>
	<option value="RLL" >RLL</option>
	<option value="RRLL" >RRLL</option>
	<option value="RRR" >RRR</option>
	<option value="LLL" >LLL</option>
	<option value="RRRL" >RRRL</option>
	<option value="RRRLL" >RRRLL</option>
	<option value="RLLL" >RLLL</option>
	<option value="RRLLL" >RRLLL</option>
	<option value="RRRLLL" >RRRLLL</option>
	<option value="other" >other</option>
	</select>
	</td>
<td ><input class="smalllistbox" type=date name="dob'.$ck.'" id="dob'.$ck.'" value="'.$xdob.'">
	</td>
'.str_replace("[]","$ck",$xgsgeno[$ck]).'
<td ><input class="smalllistbox" type=text name="currentcage'.$ck.'" id="currentcage'.$ck.'" readonly="readonly" value="'.$xcurrcage.'" >
	</td>
<td ><input class="smalllistbox" type=text name="sourcecage'.$ck.'" id="sourcecage'.$ck.'" readonly="readonly" value="'.$xsource_selection.'" >
	</td>
<td ><input class="smalllistbox" type=text name="parents'.$ck.'" id="parents'.$ck.'" readonly="readonly" value="'.$xanimals_string.'" >
	</td>
<td ><input class="smalllistbox" type=text name="bulkcomments'.$ck.'" id="bulkcomments'.$ck.'" value="'.$xbulkcomments.'">
	</td>
</tr>';
}

$testtable=$temptable.$temprow.'</table>.
<br><br>
<input type=submit id="confirm_animals" name="confirm_animals" value="confirm animals">';
$minauto=min($xautonos);
$maxauto=max($xautonos);

}
//--------------------confirm animals and add to db------------------------------------

if (isset($_POST['confirm_animals'])){

$xminauto=$_POST['minauto'];
$xmaxauto=$_POST['maxauto'];
$xgenecount=$_POST['genecount'];

foreach (range(0,$xgenecount-1,1) as $i){
$xgenelist[]=$_POST['geno'.$i];
$genotypes[$xgenelist[$i]]=[];
foreach (range($xminauto,$xmaxauto,1) as $j){
$genotypes[$xgenelist[$i]][$j]=$_POST['geno'.$i.'-'.$j];
//echo 'geno'.$i.'-'.$j.'<br>';
}
}

$sqltext='';
foreach (range($xminauto,$xmaxauto,1) as $i){
$man[$i]=$_POST['animalautono'.$i];
$line[$i]=$_POST['line'.$i];
$idno[$i]=$_POST['idno'.$i];
$gender[$i]=$_POST['gender'.$i];
$eartag[$i]=$_POST['eartag'.$i];
$dob[$i]=$_POST['dob'.$i];
$currentcage[$i]=$_POST['currentcage'.$i];
$sourcecage[$i]=$_POST['sourcecage'.$i];
$parents[$i]=$_POST['parents'.$i];
$bulkcomments[$i]=$_POST['bulkcomments'.$i];
/*
echo '<br>'.$man[$i].'|'.$line[$i].'|'.$idno[$i].'|'.$gender[$i].'|'.
$dob[$i].'|'.$currentcage[$i].'|'.$sourcecage[$i].'|'.
$parents[$i].'|'.$bulkcomments[$i].'|';
foreach ($xgenelist as $gene){
echo $gene.':'.$genotypes[$gene][$i].'|';
}
*/

//check for null in dob field
if(empty($dob[$i])){$dob[$i]='null';}
else{$dob[$i]='"'.$dob[$i].'"';}

$sqltext_table_cages='INSERT INTO `'.$dbname.'`.`table_cages` 
(`cageid`,`cagetype`,`setupdate`,`cageactive`,`lineassignment`
,`cageno`,`cagecontents`) VALUES 
("'.$currentcage[$i].'","Litter",'.$dob[$i].',"1","'.$line[$i].'",0,"pups")
ON DUPLICATE KEY UPDATE `cageno`=`cageno`;';



$sqltext_table_animals='INSERT INTO `'.$dbname.'`.`table_animals` (`animalautono`,`line`,`idno`,`gender`,`eartag`,`dob`,`matingcage`,`currentcage`,
`parents`) VALUES ('.$man[$i].',"'.$line[$i].'",'.$idno[$i].',"'.$gender[$i].
'","'.$eartag[$i].'",'.$dob[$i].',"'.$sourcecage[$i].'","'.$currentcage[$i].
'","'.$parents[$i].'");';

$sqltext_data_comments='INSERT INTO `'.$dbname.'`.`data_comments`
(`animalautono`,`commentdate`,`general_comment`) VALUES
('.$man[$i].',curdate(),"'.$xusername.': animal created - '.$bulkcomments[$i].'");';

//need for loop to populate genotype sql
$sqltext_table_genotypes='';
foreach ($xgenelist as $gene){
//echo $gene.':'.$genotypes[$gene][$i].'|';
$sqltext_table_genotypes.='INSERT INTO `'.$dbname.'`.`table_genotypes`
(`allelegroup`,`allele`,`animalautono`) VALUES
("'.$gene.'","'.$genotypes[$gene][$i].'",'.$man[$i].');';
}
$sqltext.=$sqltext_table_cages.$sqltext_table_animals.$sqltext_data_comments.$sqltext_table_genotypes;
}
$sqlreport='Addition of new animals ';
if($conn->multi_query($sqltext)===TRUE){
	//flush the mysql submission
while (mysqli_next_result($conn));
$sqlstatus='-successful'.'...'.$sqltext;}
else {
$sqlstatus='-failed '.$conn->error.'...'.$sqltext;
}
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
$numbermale=$_POST['numbermale'];
$numberfemale=$_POST['numberfemale'];
$numberunknown=$_POST['numberunknown'];



//


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

//maxanimalautono
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT max(animalautono) as maxanimalautono FROM `".$dbname."`.`table_animals`;";
$results=$conn->query($sqltext);
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
$maxanimalautono1=$row['maxanimalautono'];
}
//maxanimalautono - reserved
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT max(maxautono) as maxanimalautono FROM `".$dbname."`.`reservations_animals`;";
$results=$conn->query($sqltext);
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
$maxanimalautono2=$row['maxanimalautono'];
}
 
$maxanimalautono=max($maxanimalautono1,$maxanimalautono2);

//maxanimalinline
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT max(cast(`idno` as unsigned)) as maxanimalinline FROM `".$dbname."`.`table_animals` where `line`='".$line_selection."' ;";
$results=$conn->query($sqltext);
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
$maxanimalinline1=$row['maxanimalinline'];
}
if ($maxanimalinline1==""){
$maxanimalinline1=0;}

//maxanimalinline - reserved
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT max(maxidno) as maxanimalinline FROM `".$dbname."`.`reservations_animals` where `line`='".$line_selection."';";
$results=$conn->query($sqltext);
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
$maxanimalinline2=$row['maxanimalinline'];
}
if ($maxanimalinline2==""){
$maxanimalinline2=0;}

$maxanimalinline=max($maxanimalinline1,$maxanimalinline2);

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

$sqltext="SELECT table_animals.animalautono as 'man',line,idno,gender,dob,dod,currentcage FROM `table_animals` where dod is null and `currentcage`='".$source_selection."' order by gender desc, line, idno ;";
$results=$conn->query($sqltext);
$animals_results=$results;
$curparents="";
$animals_listbox='<select id="animals_selection" name="animals_selection" size=5 class="mediumlistbox onchange="submitForm()">;';
//loop and prepare table
while($row=mysqli_fetch_array($results)){
$animals_listbox.='<option value="'.$row['man'].'">'.$row['line'].'-'.$row['idno'].' | '.$row['gender'].'</option>';
$curparents.=$row['line']."-".$row['idno']." (".$row['gender']."),";
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
$animals_string=$source_selection.' | '.$row['cagecontents'].' | ['.$curparents.']';
$animals_string_display=$source_selection.' <br> '.$row['cagecontents'].' <br>['.$curparents.']';
}
//close the table
$conn->close();


?>	
<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Add animals - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />

	
	
</head>
<body>

			<div id="header">
					 <form id="loginbox" action="" method="post">
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Add animals-
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
						<input type=hidden id="maxanimalautono" name="maxanimalautono" value="<?php echo $maxanimalautono; ?>">
						<input type=hidden id="maxanimalinline" name="maxanimalinline" value="<?php echo $maxanimalinline; ?>">
						</td>
					<td rowspan=5><?php echo $source_listbox; ?></td>
					<td rowspan=5>
						<table>
						<tr><td>
						<?php echo $animals_listbox; ?></td></tr>
						<tr><th>Original Contents:</th></tr>
						<tr><td><?php echo $animals_string_display; ?></td></tr>
						<input type=hidden name="animals_string" id="animals_string" value="<?php echo $animals_string; ?>">
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
			<input type=submit id="generate_animals" name="generate_animals" value="generate animals">
			<br><br>
			<?php echo $testtable; ?>
			<?php echo $line_tip; ?>
			<?php echo $sqlreport; ?>
			
			<input type=hidden id="minauto" name="minauto" value="<?php echo $minauto; ?>">
			<input type=hidden id="maxauto" name="maxauto" value="<?php echo $maxauto; ?>">
			<input type=hidden id="genecount" name="genecount" value="<?php echo $genecount;?>"
			<!--genelist inputs for array cap--><?php echo $genepost; ?>
			</form>
			</div>

			
			<div id="footer">
<!--footer hidden to allow more space for dataentry
					 <p class="righttext">
					  a neurobehaviorcore.com &copy;2016
					 </p>
					 
--> 			
					
			</div>


</body>
</html>