<?php

//SETUP CAGE VARIABLE

//genotype conversion array
$Genoconver = array(
	'3f:tg/wt; ' => 'TG',
	'3f:wt/wt; ' => 'WT',
	'3f:wt/y; ' => 'WT',
	'3f:tg/y; ' => 'TG',
	'ank3-flox:flox/wt; ' => 'FLOX',
	'ank3-flox:wt/wt; ' => 'WT',
	'b6-testers-dv:unk; ' => 'WT',
	'b6-testers-dv:wt; ' => 'WT',

	'f1-nt3fx9:null/wt; wt/wt; wt/wt; ' => 'HET',
	'f1-nt3fx9:wt/wt; wt/wt; wt/wt; ' => 'WT',
	'f1-nt3fx9:null/wt; wt/wt; unk; ' => 'HET(~)',
	'f1-nt3fx9:wt/wt; wt/wt; unk; ' => 'WT(~)',
	'f1-nt3fx9:null/wt; tg/wt; unk; ' => 'HET TG-u',
	'f1-nt3fx9:wt/wt; tg/wt; unk; ' => 'WT TG-u',
	'f1-nt3fx9:null/wt; tg/wt; wt/wt; ' => 'HET TG1',
	'f1-nt3fx9:wt/wt; tg/wt; wt/wt; ' => 'TG1',
	'f1-nt3fx9:null/wt; wt/wt; tg/wt; ' => 'HET TG3',
	'f1-nt3fx9:wt/wt; wt/wt; tg/wt; ' => 'TG3',


	'f1-nt3fx9:null/y; wt/wt; wt/y; ' => 'NULL',
	'f1-nt3fx9:wt/y; wt/wt; wt/y; ' => 'WT',
	'f1-nt3fx9:null/y; wt/wt; unk; ' => 'NULL',
	'f1-nt3fx9:wt/y; wt/wt; unk; ' => 'WT',
	'f1-nt3fx9:null/y; tg/wt; unk; ' => 'NULL TG-u',
	'f1-nt3fx9:wt/y; tg/wt; unk; ' => 'WT TG-u',
	'f1-nt3fx9:null/y; tg/wt; wt/y; ' => 'NULL TG1',
	'f1-nt3fx9:wt/y; tg/wt; wt/y; ' => 'TG1',
	'f1-nt3fx9:null/y; wt/wt; tg/y; ' => 'NULL TG3',
	'f1-nt3fx9:wt/y; wt/wt; tg/y; ' => 'TG3',


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

	'brtgb6:stop/wt; tg/wt; ' => 'STOP, TG',
	'brtgb6:stop/wt; wt/wt; ' => 'STOP',
	'brtgb6:stop/y; tg/wt; ' => 'STOP, TG',
	'brtgb6:stop/y; wt/wt; ' => 'STOP',
	'brtgb6:wt/wt; tg/wt; ' => 'TG',
	'brtgb6:wt/wt; wt/wt; ' => 'WT',
	'brtgb6:wt/y; tg/wt; ' => 'TG',
	'brtgb6:wt/y; wt/wt; ' => 'WT',

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

//var_dump($genoconver);

//Contact Info
$ContactInfo1 = ($_POST['contactinfo1'] ?? '');
$ContactInfo2 = ($_POST['contactinfo2'] ?? '');

$cages = unserialize(base64_decode($_POST['cages'] ?? ''));
if (!is_array($cages)) {
	$cages = array();
}

//echo $_POST['cages'];


function MatingCard($pdf, $cagearray, $cagename, $contact1, $contact2)
{

	//top row - cage name and contact info
	$pdf->SetFont('Arial', '', 10);
	$pdf->Cell(69, 10, $cagename, 0, 0, 'C');
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(15, 10, $cagearray[$cagename]['setupdate'], 'L', 0, 'C');
	$pdf->Cell(40, 5, $contact1, 'L', 2, 'C');
	$pdf->Cell(40, 5, $contact2, 'L', 1, 'C');
	$pdf->SetFont('Arial', '', 10);
	//second row - cagetype

	$pdf->SetFillColor(intval($cagearray[$cagename]['cardcolorR']), intval($cagearray[$cagename]['cardcolorG']), intval($cagearray[$cagename]['cardcolorB']));
	$pdf->Cell(44, 6, $cagearray[$cagename]['type'], 'TBR', 0, 'C', 1);
	$pdf->Cell(84, 6, $cagearray[$cagename]['cageline'], 'TBL', 1, 'C', 1);
	$pdf->SetFillColor(255, 255, 255);

	//grab and sort animals
	$maleanimals = 0;
	$femaleanimals = 0;
	$fatherarray = array();
	$motherarray = array();
	foreach (array_keys($cagearray[$cagename]['animals']) as $animalauto) {
		$animalarray = $cagearray[$cagename]['animals'][$animalauto];

		if (strtolower($animalarray['gender']) == 'f') {
			$femaleanimals = $femaleanimals + 1;
			$motherarray[$femaleanimals] = $animalauto;
		}
		if (strtolower($animalarray['gender']) == 'm') {
			$maleanimals = $maleanimals + 1;
			$fatherarray[$maleanimals] = $animalauto;
		}
	}

	//Father row - table headers
	$pdf->Cell(4, 5);
	$pdf->Cell(20, 5, 'Father:', 'TB', 0, 'C');
	$fa = (isset($fatherarray[1]) && isset($cagearray[$cagename]['animals'][$fatherarray[1]]))
		? $cagearray[$cagename]['animals'][$fatherarray[1]] : array();
	$father1string = $fa ? (($fa['line'] ?? '') . ' - ' . ($fa['idno'] ?? '') . ' (' . ($fa['ear'] ?? '') . ') ' . ($fa['dob'] ?? '')) : '';

	$pdf->Cell(96, 5, $father1string, 'TB', 1);

	//Mother row
	$pdf->Cell(4, 5);
	$pdf->Cell(20, 10, 'Mother(s):', 'TB', 0, 'C');
	$m1 = (isset($motherarray[1]) && isset($cagearray[$cagename]['animals'][$motherarray[1]]))
		? $cagearray[$cagename]['animals'][$motherarray[1]] : array();
	$mother1string = $m1 ? (($m1['line'] ?? '') . ' - ' . ($m1['idno'] ?? '') . ' (' . ($m1['ear'] ?? '') . ') ' . ($m1['dob'] ?? '')) : '';
	$pdf->Cell(96, 5, $mother1string, 'TB', 1);
	if ($femaleanimals > 1) {
		$pdf->Cell(24, 5);
		$m2 = (isset($motherarray[2]) && isset($cagearray[$cagename]['animals'][$motherarray[2]]))
			? $cagearray[$cagename]['animals'][$motherarray[2]] : array();
		$mother2string = $m2 ? (($m2['line'] ?? '') . ' - ' . ($m2['idno'] ?? '') . ' (' . ($m2['ear'] ?? '') . ') ' . ($m2['dob'] ?? '')) : '';
		$pdf->Cell(96, 5, $mother2string, 'TB', 1);
	} else {
		$pdf->Cell(24, 5);
		$pdf->Cell(96, 5, '', 'TB', 1);
	}
	//Litter Rows
	//table headers
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(4, 5);
	$pdf->Cell(10, 5, 'Litter', 1, 0, 'C');
	$pdf->Cell(15, 5, 'DOB', 1, 0, 'C');
	$pdf->Cell(10, 5, 'Male', 1, 0, 'C');
	$pdf->Cell(10, 5, 'Female', 1, 0, 'C');
	$pdf->Cell(10, 5, 'Total', 1, 0, 'C');
	$pdf->Cell(15, 5, 'DOW', 1, 0, 'C');
	$pdf->Cell(50, 5, 'Comments', 1, 1, 'C');
	//Litter Rows
	for ($i = 1; $i <= 7; $i++) {
		$pdf->Cell(4, 5);
		$pdf->Cell(10, 5, $i, 1, 0, 'C');
		$pdf->Cell(15, 5, '', 1, 0, 'C');
		$pdf->Cell(10, 5, '', 1, 0, 'C');
		$pdf->Cell(10, 5, '', 1, 0, 'C');
		$pdf->Cell(10, 5, '', 1, 0, 'C');
		$pdf->Cell(15, 5, '', 1, 0, 'C');
		$pdf->Cell(50, 5, '', 1, 1, 'C');
	}
}

function HoldingCard($pdf, $cagearray, $cagename, $contact1, $contact2, $genoconver)
{

	//top row - cage name and contact info
	$pdf->SetFont('Arial', 'B', 20);
	//$pdf->Cell(69,10,$cagename,0,0,'C');
	$pdf->Cell(70, 10, substr($cagearray[$cagename]['cageline'], 0, 15), 0, 0, 'C');
	//$pdf->SetFont('Arial','',8);
	//$pdf->Cell(15,10,$cagearray[$cagename]['setupdate'],'L',0,'C');

	$pdf->SetFont('Arial', '', 8);
	$oldY = $pdf->GetY();
	$pdf->Cell(24, 5, $contact1, 'L', 2, 'C');
	$pdf->Cell(24, 5, $contact2, 'L', 0, 'C');
	$pdf->SetY($oldY, False);
	$pdf->SetFont('Arial', 'B', 20);


	//color the gender block
	if ($cagearray[$cagename]['cagegender'] == "F") {
		$pdf->SetFillColor(255, 200, 200);
	} elseif ($cagearray[$cagename]['cagegender'] == "M") {
		$pdf->SetFillColor(100, 255, 255);
	}

	$pdf->Cell(14, 10, $cagearray[$cagename]['cagegender'], 'L', 0, 'C', 1);

	$pdf->SetFillColor(255, 255, 255);

	$pdf->Cell(20, 10, substr($cagename, strrpos($cagename, ':') + 1), 'L', 1, 'C');

	//preferred font size 20pt for body text of card
	$pdf->SetFont('Arial', 'B', 16);
	//second row - cagetype
	//$pdf->SetFillColor(intval($cagearray[$cagename]['cardcolorR']),intval($cagearray[$cagename]['cardcolorG']),intval($cagearray[$cagename]['cardcolorB']));
	//$pdf->Cell(44,6,$cagearray[$cagename]['type'],'TBR',0,'C',1);
	//$pdf->Cell(64,6,$cagearray[$cagename]['cageline'],1,0,'C',1);
	/*
if ($cagearray[$cagename][cagegender]=="F"){
$pdf->SetFillColor(255,200,200);
}
elseif ($cagearray[$cagename][cagegender]=="M"){
$pdf->SetFillColor(100,255,255);
}
$pdf->Cell(20,6,$cagearray[$cagename]['cagegender'],'TBL',1,'C',1);
*/

	//$pdf->Cell(108,6,$cagename,'TBL',0,'C',1);
	//cage type

	$pdf->SetFillColor(intval($cagearray[$cagename]['cardcolorR']), intval($cagearray[$cagename]['cardcolorG']), intval($cagearray[$cagename]['cardcolorB']));
	$pdf->Cell(34, 6, '', 'TBL', 0, 'C', 1);
	$pdf->SetFillColor(255, 255, 255);

	$pdf->Cell(60, 6, substr($cagename, 0, strpos($cagename, ':') - 1), 'TBL', 0, 'C', 1);

	//cage stripe block - no text inside
	$pdf->SetFillColor(intval($cagearray[$cagename]['cardcolorR']), intval($cagearray[$cagename]['cardcolorG']), intval($cagearray[$cagename]['cardcolorB']));
	$pdf->Cell(34, 6, '', 'TBL', 1, 'C', 1);
	$pdf->SetFillColor(255, 255, 255);

	//third row - table headers

	$pdf->SetFont('Arial', '', 12);
	$pdf->Cell(4, 5);
	//$pdf->Cell(25,5,'Line',1,0,'C');
	$pdf->Cell(20, 5, 'ID', 1, 0, 'C');
	$pdf->Cell(30, 5, 'DOB', 1, 0, 'C');
	$pdf->Cell(30, 5, 'EAR', 1, 0, 'C');
	//consider modifying size or truncating field based on length
	//$pdf->SetFont('Arial','',5);
	//$pdf->Cell(55,5,$cagearray[$cagename]['cagegenos'],'TBR',1,'L');
	//$pdf->Cell(55,5,substr($cagearray[$cagename]['cagegenos'],0,50),'TBR',1,'L');

	$pdf->Cell(40, 5, 'GENO/NOTES', 'TBR', 1, 'L');

	//$pdf->SetFont('Arial','',10);
	//add animals

	//preferred font size 14pt for body text of card
	$pdf->SetFont('Arial', '', 20);

	$cagesize = count($cagearray[$cagename]['animals']);

	foreach (array_keys($cagearray[$cagename]['animals']) as $animalauto) {
		$animalarray = $cagearray[$cagename]['animals'][$animalauto];

		$pdf->Cell(4, 8);
		//$pdf->Cell(25,8,substr($animalarray['line'],0,12),1,0,'C');
		$pdf->Cell(20, 8, $animalarray['idno'], 1, 0, 'C');
		$pdf->Cell(30, 8, $animalarray['dob'], 1, 0, 'C');
		$pdf->Cell(30, 8, $animalarray['ear'], 1, 0, 'C');
		$genoneedle = strtolower($animalarray['line']) . ":" . strtolower($animalarray['geno']);
		if (array_key_exists($genoneedle, $genoconver)) {
			$pdf->Cell(40, 8, $genoconver[$genoneedle], 1, 1, 'L');
		} else {
			//$pdf->Cell(40,8,$genoneedle,1,1,'L');
			$pdf->Cell(40, 8, $animalarray['geno'], 1, 1, 'L');
		}
	}

	//add blanks to 6 slots
	if ($cagesize < 6) {

		for ($i = 1; $i <= 6 - $cagesize; $i++) {
			$pdf->Cell(4, 8);
			//$pdf->Cell(25,8,'',1,0,'C');
			$pdf->Cell(20, 8, '', 1, 0, 'C');
			$pdf->Cell(30, 8, '', 1, 0, 'C');
			$pdf->Cell(30, 8, '', 1, 0, 'C');
			$pdf->Cell(40, 8, '', 1, 1, 'L');
		}
	}
}


//use fpdf to make document
require('fpdf.php');
//initialize pdf and set up the page
$pdf = new FPDF('L', 'mm', 'Letter');

$pdf->AddPage();
$pdf->SetFillColor(255, 255, 255);
//count cages
$card = 0;
foreach (array_keys($cages) as $cagename) {
	$card = $card + 1;
	if ($card > 4) {
		$card = 1;
		$pdf->AddPage();
		//$pdf->SetXY(10,31);
		//$pdf->SetMargins(10,31);

	}
	if ($card == 1) {
		$pdf->SetXY(10, 31);
		$pdf->SetMargins(10, 31);
	} elseif ($card == 2) {
		$pdf->SetXY(143, 31);
		$pdf->SetMargins(143, 31);
	} elseif ($card == 3) {
		$pdf->SetXY(10, 111);
		$pdf->SetMargins(10, 111);
	} elseif ($card == 4) {
		$pdf->SetXY(143, 111);
		$pdf->SetMargins(143, 111);
	}


	if ("" == ($cages[$cagename]['cardcolorR'])) {
		$cages[$cagename]['cardcolorR'] = 255;
	}

	if ("" == ($cages[$cagename]['cardcolorG'])) {
		$cages[$cagename]['cardcolorG'] = 255;
	}

	if ("" == ($cages[$cagename]['cardcolorB'])) {
		$cages[$cagename]['cardcolorB'] = 255;
	}

	if (strtolower($cages[$cagename]['type']) == 'mating') {
		MatingCard($pdf, $cages, $cagename, $ContactInfo1, $ContactInfo2);
	} else {
		HoldingCard($pdf, $cages, $cagename, $ContactInfo1, $ContactInfo2, $Genoconver);
	}
}
//echo var_dump($cages);
$pdf->Output();
