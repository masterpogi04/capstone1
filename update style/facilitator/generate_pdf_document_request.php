<?php
require 'C:/xampp/htdocs/capstone1/vendor/autoload.php';
include '../db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

$reportType = $_POST['report_type'];
$startDate = $_POST['start_date'];
$endDate = $_POST['end_date'];
$reportData = json_decode($_POST['report_data'], true);

// Initialize DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->setChroot(__DIR__);
$dompdf = new Dompdf($options);

// Generate HTML content
$html = '
<html>
<head>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid black; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
        img { width: 10%; height: 10% }
    </style>
</head>
<body>
    <h1><img src="cvsu.jpg">CEIT Document Request Report</h1>';

if ($reportType === 'all') {
    foreach ($reportData as $type => $data) {
        $html .= generateReportHTML($type, $startDate, $endDate, $data);
    }
} else {
    $html .= generateReportHTML($reportType, $startDate, $endDate, $reportData);
}

$html .='<hr>';



$html .= '</body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->addInfo("Title", "Document Request Report of CEIT");
$dompdf->addInfo("Author", "CEIT Guidance Facilitator");

$dompdf->stream("ceit_request_document_report.pdf", ["Attachment" => 0]);

function generateReportHTML($reportType, $startDate, $endDate, $reportData) {
    $html = '
    <h2>' . getReportTypeName($reportType) . '</h2>
    <p>Period: ' . date('F j, Y', strtotime($startDate)) . ' - ' . date('F j, Y', strtotime($endDate)) . '</p>
    
    <table>
        <tr>';

    foreach (array_keys($reportData[0]) as $header) {
        $html .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
    }

    $html .= '</tr>';

    foreach ($reportData as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . $value . '</td>';
        }
        $html .= '</tr>';
    }

    // Add totals row
    $totals = calculateTotals($reportData);
    $html .= '<tr><td><strong>Total</strong></td>';
    foreach (array_keys($reportData[0]) as $key) {
        if ($key !== array_keys($reportData[0])[0]) { // Skip the first column for totals
            $html .= '<td><strong>' . (isset($totals[$key]) ? $totals[$key] : '-') . '</strong></td>';
        }
    }
    $html .= '</tr></table>';

    return $html;
}

function calculateTotals($reportData) {
    $totals = array();
    foreach ($reportData as $row) {
        foreach ($row as $key => $value) {
            if (is_numeric($value)) {
                if (!isset($totals[$key])) {
                    $totals[$key] = 0;
                }
                $totals[$key] += $value;
            }
        }
    }
    return $totals;
}

function getReportTypeName($reportType) {
    switch ($reportType) {
        case 'monthly_summary':
            return 'Monthly Summary';
        case 'department_wise':
            return 'Department Wise';
        case 'course_wise':
            return 'Course Wise';
        case 'department_course_wise':
            return 'Department and Course Wise';
        case 'document_wise':
            return 'Document Wise';
        default:
            return ucwords(str_replace('_', ' ', $reportType));
    }
}
