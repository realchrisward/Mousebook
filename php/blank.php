<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
			<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
			<title>Blank - Mousebook -- a chriswardlab.com production</title>
			<link href="../mousebook.css" rel="stylesheet" type="text/css" />
</head>
<body>

<?php 

//db login settings
$host="107.180.12.130";
$xusername='wardchriss';
$xpassword='Ncc1701a';
$dbname="mousebook";
//create connection
$conn=new mysqli($host,$xusername,$xpassword,$dbname);
//check connection
if ($conn->connect_error) {
echo 'error';
} else {
echo 'connected';
$conn->close();
}

$currgene='Mecp2';
//allelegroups filtered by gene
$conn=new mysqli($host,$xusername,$xpassword,$dbname);
$aaaa="select * from ".$dbname.".list_allelegroup where gene='".$currgene."';";
//$results=$conn->query("select * from ".$dbname.".list_allelegroup where gene='".$currgene."';");
$results=$conn->query($aaaa);
//set up static portion of table
$allelegrp_table= '<select id="allelegrp_selection" name="allelegrp_selection" size=10 class="mediumlistbox" onchange="submitForm()">';
//loop the result set and prepare table
while($row=mysqli_fetch_array($results)) {
//catch results of each row
echo $row['allelegroup'];
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
?>
</div>
<!--CONTENT SECTION-->
			<form id="cage_management_form" name="allele_management_form" method=post>

<!--javascript to autoupdate form based on select option choices (genes, allelegroups, genotyping rxns) -->
<script type="text/javascript">
function submitForm()
{
	document.getElementById("cage_management_form").submit();
}
</script>
			
	

			<table>
			

			<tr>
				<td><input type=text id="cage3size" name="cage3size" value="<?php echo $cage3size; ?>"></td>
				<td><input type=submit id="addcage3_single" name="addcage3_single" value="&darr;(c3)"><input type=submit id="addcage3_batch" name="addcage3_batch" value="&darr;&darr;(c3)&darr;&darr;"></td>
				<td><input type=text id="cage4size" name="cage4size" value="<?php echo $cage4size; ?>"></td>
				<td><input type=submit id="addcage4_single" name="addcage4_single" value="&darr;(c4)"><input type=submit id="addcage4_batch" name="addcage4_batch" value="&darr;&darr;(c4)&darr;&darr;"></td>
			</tr>
			<tr>
				<td colspan=2><?php echo $cage3_listbox; ?></td>
				<td colspan=2><?php echo $cage4_listbox; ?></td>
			</tr>
			</table>


			<p>Buttons</p>
			<?php echo $buttonmessage; ?>
			
			
			
			
			
			
			
			<input type=submit id="submit_cages" name="submit_cages" value="Submit">
			<input type=submit id="clear_cages" name="clear_cages" value="Clear Cages">
			<input type=reset id="reset_form" name="reset_form" value="Reset">
			</form>
			</div>

			
			<div id="footer">
					 <p class="righttext">
					  a chriswardlab.com production &copy;2015
					 </p>
 			
			</div>
	
	


</body>
</html>