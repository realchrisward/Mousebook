<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

<!--php code: login-->
	<?php
/* issue #14: initialize first-load output variables to prevent PHP 8 undefined-variable warnings on first load */
$host = $accessun = $accesspw = null;
$cage = null; $animals_string_display = null; $testtable = null; $sqlreport = null;
	//setup sql variables
	$xusername=($_POST['xusername'] ?? '');
	$xpassword=($_POST['xpassword'] ?? '');
	
	if (isset($_POST['button_login'])){
		$xusername=($_POST['xusername'] ?? '');
		$xpassword=($_POST['xpassword'] ?? '');
		$xloginstatus=($_POST['loginstatus'] ?? '');
		}
	if (isset($_POST['button_disco'])){
		$xusername='';
		$xpassword='';
		$xloginstatus='red';
		}
		
	$dbname=($_POST['dbname'] ?? '');

		
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
		// [mb_auth_patched]
		require_once __DIR__ . '/../includes/auth.php';
		$_mb_conn = mb_get_connection($config, $xusername, $xpassword, $dbname);
		if ($_mb_conn) {
			[$host, $accessun, $accesspw] = $_mb_conn;
		}
		require_once __DIR__ . '/../includes/filters.php';

		
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


//---generate temp list of animals---
$conn=new mysqli($host,$accessun,$accesspw,$dbname);

if (isset($_POST['generate_animals'])){
//get deadpup info
$xdob=($_POST['dob'] ?? '');
$xdod=($_POST['dod'] ?? '');
$xbulkcomments=($_POST['bulkcomments'] ?? '');
$xsource_selection=($_POST['source_selection'] ?? '');
$xdeath_type=($_POST['death_type'] ?? '');

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
<input type=submit id="confirm_animals" name="confirm_animals" value="confirm animals">';

}
//--------------------confirm animals and add to db------------------------------------

if (isset($_POST['confirm_animals'])){

$sqltext='';

$dob=($_POST['sqldob'] ?? '');
$dod=($_POST['sqldod'] ?? '');
$comments=($_POST['sqlcomments'] ?? '');

$death_type=($_POST['sqldeath_type'] ?? '');
$cageid=($_POST['sqlcage'] ?? '');

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

$line_selection=($_POST['line_selection'] ?? '');
$location_selection=$_POST['location_selection'] ?? 'all';
$source_selection=($_POST['source_selection'] ?? '');
$bulkcomments=($_POST['bulkcomments'] ?? '');
$dob=($_POST['dob'] ?? '');
$dod=($_POST['dod'] ?? '');
$death_type=($_POST['death_type'] ?? '');


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

//location filter dropdown (filter mode)
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$location_listbox=filter_selectbox(location_filter_options($conn), $location_selection, 'location_selection', 'submitForm()', true);
$conn->close();

//mating list filtered by line (+ location)
$conn=new mysqli($host,$accessun,$accesspw,$dbname);
$sqltext="SELECT `currentcage` 
FROM (`table_animals` join `table_cages` on `table_animals`.`currentcage`=`table_cages`.`cageid`)
where dod is null and left(`currentcage`,1)='M' and (`line`='".$line_selection."' or `lineassignment`='".$line_selection."') "
.location_where_join($conn, $location_selection)."
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

$sqltext="SELECT table_animals.animalautono as 'man',line,idno,sex,dob,dod,currentcage FROM `table_animals` where dod is null and `currentcage`='".$source_selection."' ;";
$results=$conn->query($sqltext);
$animals_results=$results;
$animals_listbox='<select id="animals_selection" name="animals_selection" size=6 class="mediumlistbox onchange="submitForm()">;';
//loop and prepare table
while($row=mysqli_fetch_array($results)){
$animals_listbox.='<option value="'.$row['man'].'">'.$row['line'].'-'.$row['idno'].' | '.$row['sex'].'</option>';
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

				<?php require_once __DIR__ . '/../includes/nav.php';
	      mb_render_nav($xusername, $xpassword, $_POST['dbname'] ?? ''); ?>


<!--CONTENT SECTION-->
			<div id="right_content" class="centertext">
			<form id="add_animals_form" name="add_animals_form" method=post>


					 <input type=hidden name="xusername" value="<?php echo ($_POST['xusername'] ?? ''); ?>" />
					 <input type=hidden name="xpassword" value="<?php echo ($_POST['xpassword'] ?? ''); ?>" />
					 <input type=hidden name="dbname" value="<?php echo ($_POST['dbname'] ?? ''); ?>" />
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
						<br>Location:<br><?php echo $location_listbox; ?>
					</td>
					<td rowspan=10><?php echo $source_listbox; ?></td>
					<td rowspan=10>
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
			<input type=submit id="generate_animals" name="generate_animals" value="generate animals">
			
			<?php echo $testtable; ?>
			<br>
			<?php echo $sqlreport; ?>
			
			
			</form>
			</div>

			
			<div id="footer">

					 <p class="righttext">
					 @realchrisward &copy; 2025
					 </p>
					
			</div>


<script src="../mousebook.js"></script>
</body>
</html>