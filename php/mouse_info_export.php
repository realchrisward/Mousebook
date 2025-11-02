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
	
// posted variables
$line_filter=$_POST['line_filter'];
$gender_filter=$_POST['gender_filter'];
$source_category_selection=$_POST['source_category_selection'];
$category_selection=$_POST['category_selection'];
$setupdate=$_POST['setupdate'];
$cage_selection=$_POST['cage_selection'];
$cagelist_selection=$_POST['cagelist_selection'];
$contact1=$_POST['contactinfo1'];
$contact2=$_POST['contactinfo2'];	





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

//source category type filter
$source_category_options=array('all','Holding','Rearrange','Mating','Experimental','Litter','Founder','Sac');
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


//CagesFoInfo contents
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT `cageid` FROM `CagesForInfo`;";

$results=$conn->query($sqltext);
$cage_listbox='<select id="cagelist_selection" name="cagelist_selection" size=6 class="largelistbox onchange="">;';
//loop and prepare table
while($row=mysqli_fetch_array($results)){
//echo $row['cageid'];
if ($row['cageid']===$cagelist_selection){
$cage_listbox.='<option value="'.$row['cageid'].'" selected>'.$row['cageid'].'</option>';
}
else{
$cage_listbox.='<option value="'.$row['cageid'].'">'.$row['cageid'].'</option>';
}
}
//close the table
$cage_listbox.='</select>';

$conn->close();


//cage list filtered by line, gender, etc
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//set filter text
if ($line_filter==="all" or $line_filter===null){
$lf='';} else {
$lf='`line`="'.$line_filter.'" and ';}

if ($gender_filter==="all" or $gender_filter===null){
$gf='';} else {
$gf='`gender`="'.$gender_filter.'" and ';}

if ($source_category_selection==="all" or $source_category_selection===null) {
$sf='';} else {
$sf='left(`currentcage`,1)=left("'.$source_category_selection.'",1) and ';}

$sql_where_text=substr($lf.$gf.$sf,0,-4);
if (strlen($sql_where_text)>0){
$sql_where_text=' and '.$sql_where_text;}
$sqltext="SELECT `currentcage` FROM `table_mice` left join CagesForInfo on table_mice.currentcage=CagesForInfo.cageid where dod is null and
CagesForInfo.cageid is null ".$sql_where_text." GROUP BY `currentcage`;";
//echo $sqltext;
$results=$conn->query($sqltext);
$sourcecage_listbox='<select id="cage_selection" name="cage_selection" size=14 class="largelistbox" onchange="submitForm()"><option value="all">all</option>';
while($row=mysqli_fetch_array($results)){
if ($row['currentcage']===$cage_selection){
$sourcecage_listbox.='<option value="'.$row['currentcage'].'" selected>'.$row['currentcage'].'</option>';
}
else{
$sourcecage_listbox.='<option value="'.$row['currentcage'].'">'.$row['currentcage'].'</option>';
}
$cage_batchlist[]=$row['currentcage'];
}//close the table
$sourcecage_listbox.='</select>';
$conn->close();

$cage_batchlist='("'.implode('"),("',$cage_batchlist).'")';

//functions and form controls
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
//Add cage to list
if (isset($_POST['addcage_single'])){
$cage_selection=$_POST['cage_selection'];
$sqlaction='add cage:'.$cage_selection;
$sqltext="INSERT INTO `".$dbname."`.`CagesForInfo` (`cageid`) VALUES ('".$cage_selection."');";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
}

//Remove cage individually
if (isset($_POST['remcage_single'])){
$cagelist_selection=$_POST['cagelist_selection'];
$sqlaction='rem cage:'.$cagelist_selection;
$sqltext="DELETE FROM `".$dbname."`.`CagesForInfo` WHERE `cageid`='".$cagelist_selection."';";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
}

//Bulk add to cage list
if (isset($_POST['addcage_batch'])){
$cage_batch=$_POST['cage_batchlist'];
$sqlaction='add cage:'.$cage_batch;
$sqltext="INSERT INTO `".$dbname."`.`CagesForInfo` (`cageid`) VALUES ".$cage_batchlist.";";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
}

//Bulk remove from cage list
if (isset($_POST['remcage_batch'])){
$sqlaction='clear cage';
$sqltext="DELETE FROM `".$dbname."`.`CagesForInfo`;";
if ($conn->query($sqltext) === TRUE) {
$sqlstatus= 'successful';} else {
$sqlstatus= 'failed '.$conn->error.'...'.$sqltext;
}
}
//echo $sqltext;
//submit cages
//if (isset($_POST['submit_cages'])){

// need line color code table - color codes now stored in line table
$colorkey=array(
	""=>array("R"=>255,"G"=>255,"B"=>255),
	"white"=>array("R"=>255,"G"=>255,"B"=>255),
	"grey"=>array("R"=>200,"G"=>200,"B"=>200),
	"red"=>array("R"=>255,"G"=>1,"B"=>1),
	"pink"=>array("R"=>255,"G"=>200,"B"=>225),
	"salmon"=>array("R"=>255,"G"=>100,"B"=>100),
	"orange"=>array("R"=>255,"G"=>165,"B"=>50),
	"yellow"=>array("R"=>255,"G"=>255,"B"=>100),
	"green"=>array("R"=>150,"G"=>255,"B"=>150),
	"olive"=>array("R"=>120,"G"=>150,"B"=>75),
	"blue"=>array("R"=>10,"G"=>180,"B"=>255),
	"cyan"=>array("R"=>1,"G"=>200,"B"=>200),
	"violet"=>array("R"=>255,"G"=>55,"B"=>255)
	);

//query allelegroups by line
// need query to grab and annotate genos
$cagegenokey=array();
$sqlallelegroups="SELECT `line`,`allelegroup` FROM key_allelebyline order by `allelegroup`;" ;

$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query($sqlallelegroups);

//loop and grab data
while ($row=mysqli_fetch_array($results)){
	if (array_key_exists($row['line'], $cagegenokey)) {
		$cagegenokey[$row['line']].=$row['allelegroup'];
	}
	else {
		$cagegenokey[$row['line']]=$row['allelegroup']."; ";
	}
}

$conn->close();


$sqltext="Select `table_cages`.`cageid`,`cagetype`,`cageno`,`lineassignment`,Date_Format(`setupdate`,'%m/%d/%y') as `setupdate`,`color_assignment`,`table_mice`.`line`,`idno`,Date_Format(`dob`,'%m/%d/%y') as `dob`,`eartag`,`mouseautono`,`gender`,`matingcage` ";
$sqltext.="from `table_mice` ";
$sqltext.="join ((`table_cages` join `table_lines` on `lineassignment`=`table_lines`.`line`) join `CagesForInfo` on `table_cages`.`cageid`=`CagesForInfo`.`cageid`) on `currentcage`=`CagesForInfo`.`cageid` where `dod` is null;";


//query table_mice
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query($sqltext);

//loop and grab data
$cages=array();
$mice=array();

while($row=mysqli_fetch_array($results)){

	$cages[$row['cageid']]=array(
	'type'=>$row['cagetype'],'cageline'=>$row['lineassignment'],
	'cagegender'=>$row['gender'],
        'setupdate'=>$row['setupdate'],
        'cageno'=>$row['cageno'],
	'cagegenos'=>$cagegenokey[$row['lineassignment']],
	'cardcolorR'=>$colorkey[$row['color_assignment']]["R"],
	'cardcolorG'=>$colorkey[$row['color_assignment']]["G"],
	'cardcolorB'=>$colorkey[$row['color_assignment']]["B"], 'mice'=>array());
	$mice[$row['mouseautono']]=array('cage'=>$row['cageid'],'line'=>$row['line'],'idno'=>$row['idno'],'dob'=>$row['dob'],'sourcecage'=>$row['matingcage'],'ear'=>$row['eartag'],'geno'=>'','gender'=>$row['gender']);
}
$conn->close();

//query table_genotypes	
// need query to grab and annotate genos
$sqlgenotypes="SELECT `table_genotypes`.`mouseautono`,`allelegroup`,`allele` " ;
$sqlgenotypes.="FROM `table_genotypes` INNER JOIN (`table_mice` INNER JOIN `CagesForInfo` on `currentcage`=`CagesForInfo`.`cageid`) ";
$sqlgenotypes.="ON `table_genotypes`.`mouseautono` = `table_mice`.`mouseautono` where `dod` is null order by `allelegroup`;";

$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$results=$conn->query($sqlgenotypes);
$geno_results=$results;
//loop and grab data
while ($row=mysqli_fetch_array($results)){
	$mice[$row['mouseautono']]['geno'].=$row['allele']."; ";
}
// need geno short hand table???

//combine mice into cage array

foreach($mice as $man=>$mdata){
	$cages[$mice[$man]['cage']]['mice'][$man]=$mice[$man];
//echo $mice[$man]['cage'];
}

$sqlaction='submit cages for printing';

$conn->close();

//geno conversion key
$genoconv=array(
'3f:tg/wt; ' => 'TG',
'3f:wt/wt; ' => 'WT',
'3f:wt/y; ' => 'WT',
'3f:tg;/y; ' => 'TG',
'ank3-flox:flox/wt; ' => 'FLOX',
'ank3-flox:wt/wt; ' => 'WT',
'b6-testers-dv:unk; ' => 'WT',
'b6-testers-dv:wt; ' => 'WT',
'brtc:stop/wt; tg/wt; cre/wt; ' => 'STOP,C,TG',
'brtc:stop/wt; tg/wt; wt/wt; ' => 'STOP, TG',
'brtc:stop/wt; wt/wt; cre/wt; ' => 'STOP, CRE',
'brtc:stop/wt; wt/wt; wt/wt; ' => 'STOP',
'brtc:stop/y; tg/wt; cre/wt; ' => 'STOP,C,TG',
'brtc:stop/y; tg/wt; wt/wt; ' => 'STOP, TG',
'brtc:stop/y; wt/wt; cre/wt; ' => 'STOP, CRE',
'brtc:stop/y; wt/wt; wt/wt; ' => 'STOP',
'brtc:unk; unk; unk; ' => 'UNK;',
'brtc:wt/wt; tg/wt; cre/wt; ' => 'TG, CRE',
'brtc:wt/wt; tg/wt; wt/wt; ' => 'TG',
'brtc:wt/wt; wt/wt; cre/wt; ' => 'CRE',
'brtc:wt/wt; wt/wt; wt/wt; ' => 'WT',
'brtc:wt/y; tg/wt; cre/wt; ' => 'TG, CRE',
'brtc:wt/y; tg/wt; wt/wt; ' => 'TG',
'brtc:wt/y; wt/wt; cre/wt; ' => 'CRE',
'brtc:wt/y; wt/wt; wt/wt; ' => 'WT',
'brtg129:stop/wt; tg/wt; ' => 'STOP, TG',
'brtg129:stop/wt; wt/wt; ' => 'STOP',
'brtg129:stop/y; tg/wt; ' => 'STOP, TG',
'brtg129:stop/y; wt/wt; ' => 'STOP',
'brtg129:wt/wt; tg/wt; ' => 'TG',
'brtg129:wt/wt; wt/wt; ' => 'WT',
'brtg129:wt/y; tg/wt; ' => 'TG',
'brtg129:wt/wt; wt/wt; ' => 'WT',
'cdkl5-d471fs-founder:mut; ' => 'MUT',
'cdkl5-d471fs-founder:other; ' => 'OTHER',
'cdkl5-d471fs-founder:unk; ' => 'UNK;',
'cdkl5-d471fs-founder:wt; ' => 'WT',
'cdkl5-ko:ko/wt; ' => 'HET',
'cdkl5-ko:ko/y; ' => 'KO',
'cdkl5-ko:unk; ' => 'UNK;',
'cdkl5-ko:wt/wt; ' => 'WT',
'cdkl5-ko:wt/y; ' => 'WT',
'esex:cre/wt; flox/flox; ' => 'F/F, CRE',
'esex:cre/wt; flox/wt; ' => 'F/+, CRE',
'esex:unk; unk; ' => 'UNK;',
'esex:wt/wt; flox/flox; ' => 'F/F',
'esex:wt/wt; flox/wt; ' => 'F/+',
'f1-nt9xf:null/wt; wt/wt; ' => 'NULL',
'f1-nt9xf:unk; unk; ' => 'UNK;',
'f1-nt9xf:wt/wt; wt/wt; ' => 'WT',
'f1-nt9xf:null/y; wt/wt; ' => 'NULL',
'f1-nt9xf:wt/y; wt/wt; ' => 'WT',
'f9:flox/wt; ' => 'FLOX',
'f9:flox/y; ' => 'FLOX',
'f9:unk; ' => 'UNK;',
'f9:wt/wt; ' => 'WT',
'f9:wt/y; ' => 'WT',
'fmr1-ko1:ko/y; ' => 'KO',
'fmr1-ko1:ko/wt; ' => 'KO',
'fmr1-ko1:wt/y; ' => 'WT',
'fmr1-ko1:wt/wt; ' => 'WT',
'fmr1-ko2:ko/y; ' => 'KO',
'fmr1-ko2:ko/wt; ' => 'KO',
'fmr1-ko2:wt/y; ' => 'WT',
'fmr1-ko2:wt/wt; ' => 'WT',
'g2tdt:cre/wt; tom/wt; ' => 'CRE, TDT',
'g2tdt:wt/wt; tom/wt; ' => 'TDT',
'g2tdt:cre/wt; wt/wt; ' => 'CRE',
'g2tdt:wt/wt; wt/wt; ' => 'WT',
'gad2-cre:cre/wt; ' => 'CRE',
'gad2-cre:wt/wt; ' => 'WT',
'gad2-creer:cre/wt; ' => 'CRE',
'gad2-creer:wt/wt; ' => 'WT',
'mfth:flox/wt; cre/wt; ' => 'FLOX, CRE',
'mfth:flox/wt; wt/wt; ' => 'FLOX',
'mfth:flox/y; cre/wt; ' => 'FLOX, CRE',
'mfth:flox/y; wt/wt; ' => 'FLOX',
'mfth:wt/wt; cre/wt; ' => 'CRE',
'mfth:wt/wt; wt/wt; ' => 'WT',
'mfth:wt/y; cre/wt; ' => 'CRE',
'mfth:wt/y; wt/wt; ' => 'WT',
'mir155-b6:mut/wt; ' => 'MUT',
'mir155-b6:wt/wt; ' => 'WT',
'mir155-b6:unk; ' => 'UNK;',
'nas:het; ' => 'HET',
'nas:wt; ' => 'WT',
'nas:null; ' => 'NULL',
'nas:p-het; ' => 'P-HET',
'nas:m-het; ' => 'M-HET',
'nestin-cre:cre/wt; ' => 'CRE',
'nestin-cre:wt/wt; ' => 'WT',
'ns-chemo:blind; ' => 'BLIND',
'nt9:null/wt; tg/wt; ' => 'HET, TG',
'nt9:null/wt; wt/wt; ' => 'HET',
'nt9:null/y; tg/wt; ' => 'NULL, TG',
'nt9:null/y; wt/wt; ' => 'NULL',
'nt9:wt/wt; tg/wt; ' => 'TG',
'nt9:wt/wt; wt/wt; ' => 'WT',
'nt9:wt/y; tg/wt; ' => 'TG',
'nt9:wt/y; wt/wt; ' => 'WT',
'ntb:null/wt; tg/wt; ' => 'HET, TG',
'ntb:null/wt; wt/wt; ' => 'HET',
'ntb:null/y; tg/wt; ' => 'NULL, TG',
'ntb:null/y; wt/wt; ' => 'NULL',
'ntb:wt/wt; tg/wt; ' => 'TG',
'ntb:wt/wt; wt/wt; ' => 'WT',
'ntb:wt/y; tg/wt; ' => 'TG',
'ntb:wt/y; wt/wt; ' => 'WT',
'ntf:null/wt; tg/wt; ' => 'HET, TG',
'ntf:null/wt; wt/wt; ' => 'HET',
'ntf:null/y; tg/wt; ' => 'NULL, TG',
'ntf:null/y; wt/wt; ' => 'NULL',
'ntf:wt/wt; tg/wt; ' => 'TG',
'ntf:wt/wt; wt/wt; ' => 'WT',
'ntf:wt/y; tg/wt; ' => 'TG',
'ntf:wt/y; wt/wt; ' => 'WT',
'oas:het; ' => 'HET',
'oas:wt; ' => 'WT',
'oas:null; ' => 'NULL',
'oas:p-het; ' => 'P-HET',
'oas:m-het; ' => 'M-HET',
'otp-cre:cre/wt; ' => 'CRE',
'otp-cre:wt/wt; ' => 'WT',
'pv-cre:cre/wt; ' => 'CRE',
'pv-cre:wt/wt; ' => 'WT',
'pwk:unk; ' => 'WT',
'pwk:wt; ' => 'WT',
'shank3:mut/wt; ' => 'HET',
'shank3:mut/mut; ' => 'NULL',
'shank3:wt/wt; ' => 'WT',
'th-cre:cre/wt; ' => 'CRE',
'th-cre:wt/wt; ' => 'WT',
'thy1-snca:tg/wt; ' => 'HET',
'thy1-snca:tg/y; ' => 'TG',
'thy1-snca:unk; ' => 'UNK;',
'thy1-snca:wt/wt; ' => 'WT',
'thy1-snca:wt/y; ' => 'WT',
'tsc2-mixed:mut/wt; ' => 'MUT',
'tsc2-mixed:unk; ' => 'UNK;',
'tsc2-mixed:wt/wt; ' => 'WT',
'v2tdt:tom/wt; cre/wt; ' => 'CRE, TDT',
'v2tdt:tom/wt; wt/wt; ' => 'TDT',
'v2tdt:wt/wt; cre/wt; ' => 'CRE',
'v2tdt:wt/wt; wt/wt; ' => 'WT',
'vglut2:cre/wt; ' => 'CRE',
'vglut2:wt/wt; ' => 'WT',
'wt-129s6:unk; ' => 'WT',
'wt-129s6:wt; ' => 'WT',
'wt-b6:unk; ' => 'WT',
'wt-b6:wt; ' => 'WT',
'wt-fvb:unk; ' => 'WT',
'wt-fvb:wt; ' => 'WT',
'fn:wt/wt; ' => 'WT',
'fn:mut/wt; ' => 'MUT/WT',
'fn:wt/y; ' => 'WT',
'fn:mut/y; ' => 'MUT/Y',
'fk:wt/wt; ' => 'WT',
'fk:mut/wt; ' => 'MUT/WT',
'fk:wt/y; ' => 'WT',
'fk:mut/y; ' => 'MUT/Y',
'fng:wt/y; wt/wt; ' => 'WT',
'fng:mut/y; wt/wt; ' => 'FN',
'fng:wt/y; cre/wt; ' => 'CRE',
'fng:mut/y; cre/wt; ' => 'FN, CRE',
'fnger:wt/y; wt/wt; ' => 'WT',
'fnger:mut/y; wt/wt; ' => 'FN',
'fnger:wt/y; cre/wt; ' => 'CRE',
'fnger:mut/y; cre/wt; ' => 'FN, CRE',
'fnv:wt/y; wt/wt; ' => 'WT',
'fnv:mut/y; wt/wt; ' => 'FN',
'fnv:wt/y; cre/wt; ' => 'CRE',
'fnv:mut/y; cre/wt; ' => 'FN, CRE',
'fkg:wt/y; wt/wt; ' => 'WT',
'fkg:mut/y; wt/wt; ' => 'FK',
'fkg:wt/y; cre/wt; ' => 'CRE',
'fkg:mut/y; cre/wt; ' => 'FK, CRE',
'fkv:wt/y; wt/wt; ' => 'WT',
'fkv:mut/y; wt/wt; ' => 'FK',
'fkv:wt/y; cre/wt; ' => 'CRE',
'fkv:mut/y; cre/wt; ' => 'FK, CRE',
'fkgtdt:wt/y; wt/wt; wt/wt; ' => 'WT',
'fkgtdt:mut/y; wt/wt; wt/wt; ' => 'FK',
'fkgtdt:wt/y; cre/wt; wt/wt; ' => 'CRE',
'fkgtdt:wt/y; wt/wt; tom/wt; ' => 'TDT',
'fkgtdt:mut/y; cre/wt; wt/wt; ' => 'FK, CRE',
'fkgtdt:mut/y; wt/wt; tom/wt; ' => 'FK, TDT',
'fkgtdt:wt/y; cre/wt; tom/wt; ' => 'CRE, TDT',
'fkgtdt:mut/y; cre/wt; tom/wt; ' => 'FK,C,TDT',
'fkvtdt:wt/y; wt/wt; wt/wt; ' => 'WT',
'fkvtdt:mut/y; wt/wt; wt/wt; ' => 'FK',
'fkvtdt:wt/y; tom/wt; wt/wt; ' => 'TDT',
'fkvtdt:wt/y; wt/wt; cre/wt; ' => 'CRE',
'fkvtdt:mut/y; tom/wt; wt/wt; ' => 'FK, TDT',
'fkvtdt:mut/y; wt/wt; cre/wt; ' => 'FK, CRE',
'fkvtdt:wt/y; tom/wt; cre/wt; ' => 'CRE, TDT',
'fkvtdt:mut/y; tom/wt; cre/wt; ' => 'FK,C,TDT',
);

//echo serialize($cages);
//"submited";
//}

$temprow='
	<table >
		<tr name="header" id="header">
			<td >cage number
			</td>
			<td >cage
			</td>
			<td >line
			</td>
			<td >idno
			</td>
			<td >gender
			</td>
			<td >ear
			</td>
			<td >dob
			</td>
			<td >geno rxns
			</td>
			<td >genotype
			</td>
			<td >geno
			</td>
			<td >source cage
			</td>
		</tr>
	';


//prepare table of info by cage

foreach ($cages as $cg=>$cgdata){

	foreach ($cages[$cg]['mice'] as $man=>$mdata){
	
$temprow.='
	<tr name="trow'.$man.'" id="trow'.$man.'">
		<td >'.$cages[$cg]['cageno'].'
		</td>
		<td >'.$cg.'
		</td>
		<td >'.$cages[$cg]['mice'][$man]['line'].'
		</td>
		<td >'.$cages[$cg]['mice'][$man]['idno'].'
		</td>
		<td >'.$cages[$cg]['mice'][$man]['gender'].'
		</td>
		<td >'.$cages[$cg]['mice'][$man]['ear'].'
		</td>
		<td >'.$cages[$cg]['mice'][$man]['dob'].'
		</td>
		<td >'.$cages[$cg]['cagegenos'].'
		</td>
		<td >'.$cages[$cg]['mice'][$man]['geno'].'
		</td>
		<td >'.$genoconv[strtolower($cages[$cg]['mice'][$man]['line'].":".$cages[$cg]['mice'][$man]['geno'])].'
		</td>
		<td >'.$cages[$cg]['mice'][$man]['sourcecage'].'
		</td>

	</tr>
		';

	}
		
}


$temprow.='</table>';


?>
<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Mouse Info Export - <?php echo $dbname; ?></title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />	
</head>
<body>

			<div id="header">
					<form id="loginbox" action="" method="post">
					<?php echo $sqlerror; ?>
					 <h2 class="centervert"
					 style="position:absolute;top:0px;left:75px;"> 
					  -Mouse Info Export-
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
					 <form action="../php/manage_mice.php" method=post target="_blank">
					 <input type=hidden name="xusername" value="<?php echo $xusername; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $xpassword; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
					 <input type=submit class="button" name=""
					  value="Manage Mice" />
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
			<div id="right_content" class="centertext">	
<!--CONTENT SECTION-->
			<form id="cage_selection_form" name="cage_selection_form" method=post>

					 <input type=hidden name="xusername" value="<?php echo $_POST['xusername']; ?>" />
					 <input type=hidden name="xpassword" value="<?php echo $_POST['xpassword']; ?>" />
					 <input type=hidden name="dbname" value="<?php echo $_POST['dbname']; ?>" />
					 <input type=hidden name="button_login" value="connect" />
<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("cage_selection_form").submit();
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
			<td>x</td>
			</tr>
			</table>
			
			<table>
			<tr>
				
				<th>Current Cages:</th>
			</tr>
			<tr>
				<td><?php echo $cage_selection; ?><br>
				<?php echo $sourcecage_listbox; ?></td>
			</tr>
			</table>
			
			<table>
			<tr>
				
				<td><input type=submit id="remcage_single" name="remcage_single" value="&uarr;(c1)"><input type=submit id="remcage_batch" name="remcage_batch" value="&uarr;&uarr;(c1)&uarr;&uarr;"></td>
			</tr>
			<tr>
				<td><input type=submit id="addcage_single" name="addcage_single" value="&darr;(c1)"><input type=submit id="addcage_batch" name="addcage_batch" value="&darr;&darr;(c1)&darr;&darr;"></td>
			</tr>
			<tr>
				<td colspan=2><?php echo $cage_listbox; ?></td>
			</tr>
			</table>
   <textarea  name='contactinfo1' id='contactinfo1' ><?php echo $contact1; ?></textarea>
   <textarea  name='contactinfo2' id='contactinfo2' ><?php echo $contact2; ?></textarea>
 </form>
 <form id="cagecard_gen" action="http://chriswardlab.com/pages/cagecard_gen3.php" method="POST" target="_blank">

  <p>
   <input type='hidden' name='cages' id='cages' value='<?php echo serialize($cages); ?>' />
  </p>
  <p>
			<input type=hidden id='contactinfo1' name='contactinfo1' value='<?php echo $contact1; ?>' />
			<input type=hidden id='contactinfo2' name='contactinfo2' value='<?php echo $contact2; ?>' />

  </p>
   <INPUT type="submit" id="submit_cages" name="submit_cages" value="Generate Cards">



</form>
<?php echo $temprow; ?>
			</div>

			
			<div id="footer">
					 <p class="righttext">
					  NeurobehavioralCore.com &copy; 2016
					 </p>
 			
			</div>
<?php echo $buttonmessage; ?>
<br>
<?php echo $sqlstatusclear; ?>

</body>
</html>
