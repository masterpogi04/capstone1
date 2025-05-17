<?php
session_start();
require_once '../vendor/autoload.php';
include '../db.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    die("Please log in first");
}

// Helper function to generate checkbox HTML
function checkbox($value, $checkedValue) {
    $checked = ($value === $checkedValue) ? '&#x2713;' : ''; // Unicode checkmark
    return '<div class="checkbox">' . $checked . '</div>';
}

// Get current user (facilitator) name
// For facilitator name:
$facilitator_query = "SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?";
$facilitator_stmt = $connection->prepare($facilitator_query);
$facilitator_stmt->bind_param("i", $_SESSION['user_id']);
$facilitator_stmt->execute();
$facilitator_result = $facilitator_stmt->get_result();
$facilitator = $facilitator_result->fetch_assoc();
$facilitator_name = trim($facilitator['first_name'] . ' ' . 
                        ($facilitator['middle_initial'] ? $facilitator['middle_initial'] . '. ' : '') . 
                        $facilitator['last_name']);

// For counselor name:
$counselor_query = "SELECT first_name, middle_initial, last_name FROM tbl_counselor LIMIT 1";
$counselor_result = $connection->query($counselor_query);
$counselor = $counselor_result->fetch_assoc();
$counselor_name = $counselor ? 
    trim($counselor['first_name'] . ' ' . 
         ($counselor['middle_initial'] ? $counselor['middle_initial'] . '. ' : '') . 
         $counselor['last_name']) : 
    'Guidance Counselor';
$referral_id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($referral_id)) {
    die("No referral ID provided");
}

$query = "SELECT * FROM referrals WHERE id = ? OR incident_report_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("ss", $referral_id, $referral_id);
$stmt->execute();
$result = $stmt->get_result();
$referrals = $result->fetch_all(MYSQLI_ASSOC);

if (empty($referrals)) {
    die("Referral not found");
}

$logo_path = __DIR__ . '/logo.png';
$logo_data = base64_encode(file_get_contents($logo_path));

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Referral Form</title>
    <style>
        @page {
            margin: 50px;
            padding: 0;
        }
        body { 
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 15px;
            padding: 0;
        }
        letterhead {
            text-align: center;
            margin-bottom: 15px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
         .logo {
            position: absolute;
            left: 100px;  /* Adjust this value to align with text */
            top: 0;
            width: 110px;
            height: 100px;
        }
        
        .letterhead-text {
            text-align: center;
            line-height: 1.2;
        }
        
        .letterhead-text h1 {
            font-size: 18px;
            margin: 0;
            font-family: "Times New Roman", serif;
            font-weight: bold;
        }
        
        .letterhead-text h2 {
            font-size: 14px;
            margin: 2px 0;
        }
        
        .letterhead-text p {
            font-size: 12px;
            margin: 2px 0;
        }
        .form-number {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 12px;
            font-style: italic;
        }
        p {
            font-size: 14px;
        }
        .checkbox-item {
             margin: 1px 0;
            padding-left: 25px;
            position: relative;
            font-size: 14px;
        }
        .checkbox {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            position: absolute;
            left: 0;
            top: 2px;
            text-align: center;
            line-height: 12px;
             font-size: 20px;
            font-family: DejaVu Sans, sans-serif;
        }
        .checkbox-inline {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            line-height: 10px;
            font-size: 15px;
            font-family: DejaVu Sans, sans-serif;
            font-weight: 100;
        }
 
        .signature-section {
            margin-top: 10px;
            width: 100%;
        }
        .signature-name {
            margin-bottom: 5px;
            font-size: 14px;
        }
        .signature-underline {
            border-top: 1px solid black;
            width: 100%; /* Make the underline span the full width */
            margin: 0;
        }
        .signature-caption {
            font-size: 12px;
            margin-top: 5px;
            text-align: center; /* Center the text */
        }
        td .signature-underline {
            margin: 0;
        }
        .version {
            position: fixed;
            margin-top: 3%;
            right: 0;
            font-size: 12px;
        }
        .page-break {
            page-break-after: always;
        }
        .referral-content {
            margin-bottom: 100px;
        }
        hr {
            margin-top: 9%;
        }
    </style>
</head>
<body>';

foreach ($referrals as $index => $referral) {
    $html .= '
    <div class="referral-content">
        <div class="form-number">OSAS-QF-06</div>
        
        <div class="letterhead">
            <img src="data:image/png;base64,' . $logo_data . '" class="logo">
            <div class="letterhead-text">
                <p>Republic of the Philippines</p>
                <h1>CAVITE STATE UNIVERSITY</h1>
                <h2>Don Severino delas Alas Campus</h2>
                <p>Indang, Cavite</p>
            </div>
        </div>

        <h2 style="text-align: center; margin: 20px 0;">REFERRAL FORM</h2>
        
       <p>Date: <span style="text-decoration: underline;">' . date('F d, Y', strtotime($referral['date'])) . '</span></p>
        
        <p>To the <strong>GUIDANCE COUNSELOR:</strong></p>
        
        <p>This is to refer the student, 
            <span style="margin-left: 2px; border-bottom: 1px solid black; padding: 0 20px; font-size: 12px;">
                <strong>' . htmlspecialchars($referral['first_name'] . ' ' . 
                ($referral['middle_name'] ? $referral['middle_name'] . ' ' : '') . 
                $referral['last_name']) . '</strong></span> /
            <span style="border-bottom: 1px solid black; padding: 0 20px;  font-size: 12px;">
                <strong>' . htmlspecialchars($referral['course_year']) . '</strong></span>
        </p>

        <p style="margin-top: -15px;">
            <span style="margin-left: 200px; font-size: 12px;">(Name of student)</span>
            <span style="margin-left: 180px; font-size: 12px;">(Course/year)</span>
        </p>
         <p style="margin-top: -15px;">to your office for counselling.</p>
        
        <p style="margin-bottom: 1px;">Reason for referral, please tick <span class="checkbox-inline">✓</span> one:</p>
        
        <div class="checkbox-item" style="margin-top: 1px;">
            ' . checkbox('Academic concern', $referral['reason_for_referral']) . '
            Academic concern
        </div>
        
        <div class="checkbox-item">
            ' . checkbox('Behavior maladjustment', $referral['reason_for_referral']) . '
            Behaviour maladjustment
        </div>
        
        <div class="checkbox-item">
            ' . checkbox('Violation to school rules', $referral['reason_for_referral']) . '
            <span>Violation to school rules, specifically: </span>
            <span style="display: inline-block; border-bottom: 1px solid black; width: 61%;">
                ' . ($referral['violation_details'] ? htmlspecialchars($referral['violation_details']) : '') . '
            </span>
        </div>

        <div class="checkbox-item">
            ' . checkbox('Other concern', $referral['reason_for_referral']) . '
            <span>Other concern, specify: </span>
            <span style="display: inline-block; border-bottom: 1px solid black; width: 75%;">
                ' . ($referral['other_concerns'] ? htmlspecialchars($referral['other_concerns']) : '') . '
            </span>
        </div>
        
        <p>Thank you.</p>
        
   <div class="signature-section">
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%;">
                <div style="text-align: left;">
                    <div class="signature-name" style="text-align: center;">' . strtoupper(htmlspecialchars($facilitator_name)) . '</div>
                    <div class="signature-underline" style="text-align: center;"></div>
                    <div class="signature-caption" style="text-align: center;">(Signature over printed name of Faculty/Employee)</div>
                </div>
            </td>
            <td style="width: 50%; position: relative;">
                <div style="position: absolute; top: -20px; left: 0;">Acknowledged by:</div>
                <div style="text-align: left;">
                    <div class="signature-name" style="text-align: center;">' . strtoupper(htmlspecialchars($counselor_name)) . '</div>
                    <div class="signature-underline" style="text-align: center;"></div>
                    <div class="signature-caption" style="text-align: center;">(Signature over printed name of Guidance Counselor)</div>
                </div>
            </td>
        </tr>
    </table>
</div>

        
        <div class="version">V01-2018-05-28</div>
         <hr>
    </div>';

    // Add page break if not the last referral
    if ($index < count($referrals) - 1) {
        $html .= '<div class="page-break"></div>';
    }
}

$html .= '</body></html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->setChroot(__DIR__);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("referral_form_" . $referral_id . ".pdf", array("Attachment" => 0));