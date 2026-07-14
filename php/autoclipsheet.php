<?php
require_once __DIR__ . '/../includes/db.php';
// =============================================================
// php/autoclipsheet.php
//
// Genotyping / ear-clip worksheet for the colony's CURRENT litters.
//
// Track 0 (T0.7): rebuilt from a non-functional prototype (fatal
// parse errors, hard-coded demo data, no DB/auth wiring) into a
// self-contained, session-authenticated FPDF report.
//
// Launched directly from index.php (button next to "Litter Log"),
// so — unlike cagecard_gen5rs.php, which is fed serialized cages by
// cagecard_printer.php — this page authenticates via the session,
// queries the colony DB itself, and streams a PDF.
//
// A "current litter" = a cage whose id starts with L (litter) or F
// (founder) that still holds at least one living animal (dod IS
// NULL). Each litter section lists its pups (id no, sex, ear tag)
// with blank columns for recording the sample tube and resulting
// genotype, plus the genotyping assays (reactions) due for the line.
// =============================================================

// This endpoint emits a PDF: nothing may be echoed to the body before
// FPDF::Output(). mb_session_bootstrap() only sends headers (the
// session cookie), which is safe.

$config = require '../config.php';
mb_debug_init($config);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/session.php';

$mb       = mb_session_bootstrap($config);
$dbname   = $mb['dbname'];
$host     = $mb['host'];
$accessun = $mb['accessun'];
$accesspw = $mb['accesspw'];
$clipper  = ($mb['username'] !== '') ? $mb['username'] : 'unknown';
$clipdate = date('Y-m-d');

/**
 * Emit a single-page PDF notice and stop. Used for the auth/connection
 * failure paths so we never echo HTML into a PDF response.
 */
function mb_clip_fail($msg)
{
    require_once __DIR__ . '/fpdf.php';
    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 12, 'Clip sheet unavailable', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 8, $msg);
    $pdf->Output('I', 'clipsheet.pdf');
    exit;
}

if (!$mb['authenticated'] || $host === null) {
    mb_clip_fail('Please connect to a colony database first, then reopen the clip sheet.');
}

$conn = mb_connect($host, $accessun, $accesspw, $dbname);
if ($conn->connect_error) {
    mb_clip_fail('Could not connect to the colony database (' . $dbname . ').');
}

// -------------------------------------------------------------
// 1) Current litters and their pups.
// -------------------------------------------------------------
$litters = array(); // currentcage => [line,dob,matingcage,parents,pups=>[F,M,U]]

$sql = "SELECT currentcage, matingcage, line, idno, sex, eartag, dob, parents "
     . "FROM table_animals "
     . "WHERE dod IS NULL AND LEFT(currentcage,1) IN ('L','F') "
     . "ORDER BY currentcage ASC, sex ASC, CAST(idno AS UNSIGNED) ASC, idno ASC";

$res = $conn->query($sql);
// Guard (#22/#23 family): a failed query returns false; fetching from
// bool fatals under PHP 8. Only iterate a real result set.
if ($res instanceof mysqli_result) {
    while ($row = $res->fetch_assoc()) {
        $cage = (string)$row['currentcage'];
        if (!isset($litters[$cage])) {
            $litters[$cage] = array(
                'line'       => $row['line'],
                'dob'        => $row['dob'],
                'matingcage' => $row['matingcage'],
                'parents'    => $row['parents'],
                'pups'       => array('F' => array(), 'M' => array(), 'U' => array()),
            );
        }
        $sex = ($row['sex'] === 'F') ? 'F' : (($row['sex'] === 'M') ? 'M' : 'U');
        $litters[$cage]['pups'][$sex][] = array(
            'idno'   => $row['idno'],
            'eartag' => $row['eartag'],
        );
        // Prefer the earliest non-null dob seen for the cage.
        if ($row['dob'] !== null
            && ($litters[$cage]['dob'] === null || $row['dob'] < $litters[$cage]['dob'])) {
            $litters[$cage]['dob'] = $row['dob'];
        }
    }
    $res->free();
}

// -------------------------------------------------------------
// 2) Genotyping assays (reactions) per line.
//    line -> allelegroups (key_allelebyline)
//         -> reactions   (key_allelegroupbygenotypingrxn)
// -------------------------------------------------------------
$assays = array(); // line => "rxnA / rxnB / ..."

$sqlA = "SELECT DISTINCT k.line AS line, g.genotypingrxn AS rxn "
      . "FROM key_allelebyline k "
      . "JOIN key_allelegroupbygenotypingrxn g ON k.allelegroup = g.allelegroup "
      . "ORDER BY k.line ASC, g.genotypingrxn ASC";

$resA = $conn->query($sqlA);
if ($resA instanceof mysqli_result) {
    $acc = array();
    while ($row = $resA->fetch_assoc()) {
        $acc[(string)$row['line']][] = $row['rxn'];
    }
    foreach ($acc as $ln => $rxns) {
        $assays[$ln] = implode(' / ', $rxns);
    }
    $resA->free();
}
$conn->close();

// -------------------------------------------------------------
// 3) Render the PDF.
// -------------------------------------------------------------
require_once __DIR__ . '/fpdf.php';

$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'Genotyping / Ear-Clip Worksheet', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Colony: ' . $dbname . '     Clipper: ' . $clipper . '     Date: ' . $clipdate, 0, 1, 'C');
$pdf->Ln(2);

if (count($litters) === 0) {
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->Cell(0, 8, 'No current litters (no living animals in L* / F* cages).', 0, 1, 'C');
} else {
    $order = array('F' => 'Female', 'M' => 'Male', 'U' => 'Unknown');
    foreach ($litters as $cage => $L) {
        // Section header for the litter.
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(225, 225, 225);
        $pdf->Cell(0, 7, 'Cage: ' . $cage . '     Line: ' . $L['line'], 0, 1, 'L', true);

        $pdf->SetFont('Arial', '', 9);
        $dobShort = ($L['dob'] !== null) ? substr((string)$L['dob'], 0, 10) : '';
        $clip = ($dobShort !== '') ? date('Y-m-d', strtotime($dobShort . ' +14 days')) : '';
        $wean = ($dobShort !== '') ? date('Y-m-d', strtotime($dobShort . ' +21 days')) : '';
        $pdf->Cell(0, 5, 'DOB: ' . $dobShort . '    Clip due: ' . $clip
            . '    Wean: ' . $wean . '    Mating cage: ' . (string)$L['matingcage'], 0, 1, 'L');

        $assayTxt = isset($assays[$L['line']]) ? $assays[$L['line']] : '(no assays defined for this line)';
        $pdf->MultiCell(0, 5, 'Assays: ' . $assayTxt);

        if (trim((string)$L['parents']) !== '') {
            $pdf->MultiCell(0, 5, 'Parents: ' . (string)$L['parents']);
        }
        $pdf->Ln(1);

        // Pup table header.
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(20, 6, 'Sex', 1, 0, 'C');
        $pdf->Cell(35, 6, 'ID no', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Ear tag', 1, 0, 'C');
        $pdf->Cell(55, 6, 'Sample / tube #', 1, 0, 'C');
        $pdf->Cell(50, 6, 'Genotype', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        $any = false;
        foreach ($order as $sk => $slabel) {
            foreach ($L['pups'][$sk] as $pup) {
                $any = true;
                $pdf->Cell(20, 6, $slabel, 1, 0, 'C');
                $pdf->Cell(35, 6, (string)$pup['idno'], 1, 0, 'C');
                $pdf->Cell(30, 6, (string)$pup['eartag'], 1, 0, 'C');
                $pdf->Cell(55, 6, '', 1, 0, 'C');
                $pdf->Cell(50, 6, '', 1, 1, 'C');
            }
        }
        if (!$any) {
            $pdf->Cell(190, 6, '(no pups)', 1, 1, 'C');
        }
        $pdf->Ln(4);
    }
}

$pdf->Output('I', 'clipsheet_' . $clipdate . '.pdf');
