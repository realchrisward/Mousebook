<?php

//get posted variables
/*
$clipper=$_POST['xusername']
$clipdate=$_POST['clipdate']
*/
$clipper='Chris W';
$clipdate=date();

$cages=array('Litter : demo-1 : mating : 2'-> array('matingcage'->'demo-1 : mating : 2', 'mominfo'->'line1 idno1 ear1 geno1;line2 idno2 ear2 geno2',
'dadinfo'->'line3 idno3, ear3, geno 3','dob'->'2018-07-04','lineassign'->'demo-1','assays'->'test1/test2','pupsmale'->array(array('idno'->'101','ear'->'-'),array('idno'->'102','ear'->'R'),array('idno'->'103','ear'->'L'))));

//$cages=unserialize($_POST['cages']);

//echo $_POST['cages'];

function ClippedLitter($pdf,$cagearray,$cagename){
//extract cage and litter info
$MatingCage=$cagearray[$cagename]['matingcage'];
$MomInfo=$cagearray[$cagename]['mominfo'];
$DadInfo=$cagearray[$cagename]['dadinfo'];
$dob=$cagearray[$cagename]['dob'];
$assays=$cagearray['assays'];
$malpuparray=$cagearray['pupsmale'];
$fempuparray=$cagearray['pupsfemale'];
$unkpuparray=$cagearray['pupsunk'];

$pdf->SetFont('Arial','',12);
$pdf->Cell(60,12,$cagename,0,0,'C');
$pdf->Cell(60,12,$mominfo,0,0,'C');
$pdf->Cell(60,12,$dadinfo,0,0,'C');
$pdf->Cell(60,12,$dob,0,0,'C');
$pdf->Cell(60,12,$assays,0,0,'C');
$pdf->Cell(60,12,$lineassign,0,0,'C');
$pdf->Cell(60,12,'female',0,0,'C');
foreach(array_keys($fempuparray) as $fempup){
	$pdf->Cell(60,12,$fempuparray['fempup']['idno']."(".$fempuparray['fempup']['ear'].")";	
}
$pdf->Cell(60,12,'male',0,0,'C');
foreach(array_keys($malpuparray) as $malpup){
	$pdf->Cell(60,12,$malpuparray['malpup']['idno']."(".$malpuparray['malpup']['ear'].")";	
}
$pdf->Cell(60,12,'unk',0,0,'C');
foreach(array_keys($unkpuparray) as $unkpup){
	$pdf->Cell(60,12,$fempuparray['unkpup']['idno']."(".$unkpuparray['unkpup']['ear'].")";	
}

}

/*
$oldY=$pdf->GetY();
$pdf->Cell(24,5,$contact1,'L',2,'C');
$pdf->Cell(24,5,$contact2,'L',0,'C');
$pdf->SetY($oldY,False);
*/

//use fpdf to make document
require('fpdf.php');
//initialize pdf and set up the page
$pdf = new FPDF('L','mm','Letter');

//$pdf->AddPage();
$pdf->SetFillColor(255,255,255);
foreach(array_keys($cages as $litter){
	$pdf->AddPage();
	$pdf->SetXY(10,31);
	$pdf->Cell(60,12,$xusername,0,0,'C');
	$pdf->Cell(60,12,$clipdate,0,0,'C');
	//page _ of _
	ClippedLitter($pdf,$cages,$litter);
	
}
/*
//count cages
$card=0;
foreach(array_keys($cages) as $litter){
	$card=$card+1;
	if($card>4){
	        $card=1;
		$pdf->AddPage();
                //$pdf->SetXY(10,31);
		//$pdf->SetMargins(10,31);

	}
	if ($card==1){
		$pdf->SetXY(10,31);
		$pdf->SetMargins(10,31);
	}
	elseif ($card==2){
		$pdf->SetXY(143,31);
		$pdf->SetMargins(143,31);
	}
	elseif ($card==3){
		$pdf->SetXY(10,111);
		$pdf->SetMargins(10,111);
	}
	elseif ($card==4){
		$pdf->SetXY(143,111);
		$pdf->SetMargins(143,111);
	}


		HoldingCard($pdf,$cages,$cagename,$ContactInfo1,$ContactInfo2,$Genoconver);
	}
	*/
}
//echo var_dump($cages);
$pdf->Output();
?>