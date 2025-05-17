<?php
session_start();
require '../vendor/autoload.php';
include '../db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Check if report data is available in session
if (!isset($_SESSION['report_data']) || !isset($_SESSION['report_filters'])) {
    header("Location: facilitator_generate_reports.php");
    exit();
}

$reportData = $_SESSION['report_data'];
$filters = $_SESSION['report_filters'];

// Initialize DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->setChroot(__DIR__);
$dompdf = new Dompdf($options);

// Function to calculate totals
function calculateTotals($reportData) {
    $totals = [
        'total_requests' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    
    foreach ($reportData as $row) {
        $totals['total_requests'] += $row['total_requests'];
        $totals['pending'] += $row['pending'];
        $totals['approved'] += $row['approved'];
        $totals['rejected'] += $row['rejected'];
    }
    
    return $totals;
}

// Generate HTML content for the PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Document Request Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 80px;
            height: auto;
        }
        h1, h2, h3 {
            text-align: center;
            color: #0d693e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #0d693e;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }
        .report-info {
            margin-bottom: 15px;
        }
        .report-info p {
            margin: 5px 0;
        }
        .totals-row {
            font-weight: bold;
            background-color: #e6e6e6;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="cvsu.jpg" alt="CvSU Logo">
        <h1>CAVITE STATE UNIVERSITY</h1>
        <h2>College of Engineering and Information Technology</h2>
        <h3>Document Request Report</h3>
    </div>
    
    <div class="report-info">
        <p><strong>Report Period:</strong> ' . date('F j, Y', strtotime($filters['start_date'])) . ' to ' . date('F j, Y', strtotime($filters['end_date'])) . '</p>
        <p><strong>Generated On:</strong> ' . date('F j, Y h:i A') . '</p>
    </div>
    
    <table>
        <thead>
            <tr>';

// Add table headers based on selected grouping options
if ($filters['group_by_department'] === 'yes') {
    $html .= '<th>Department</th>';
}
if ($filters['group_by_course'] === 'yes') {
    $html .= '<th>Course</th>';
}
if ($filters['group_by_document'] === 'yes') {
    $html .= '<th>Document Type</th>';
}
if ($filters['group_by_purpose'] === 'yes') {
    $html .= '<th>Purpose</th>';
}

$html .= '
                <th>Total Requests</th>
                <th>Pending</th>
                <th>Approved</th>
                <th>Rejected</th>
            </tr>
        </thead>
        <tbody>';

// Add table rows for each data entry
foreach ($reportData as $row) {
    $html .= '<tr>';
    
    if ($filters['group_by_department'] === 'yes') {
        $html .= '<td>' . htmlspecialchars($row['department']) . '</td>';
    }
    if ($filters['group_by_course'] === 'yes') {
        $html .= '<td>' . htmlspecialchars($row['course']) . '</td>';
    }
    if ($filters['group_by_document'] === 'yes') {
        $html .= '<td>' . htmlspecialchars($row['document_request']) . '</td>';
    }
    if ($filters['group_by_purpose'] === 'yes') {
        $html .= '<td>' . htmlspecialchars($row['purpose']) . '</td>';
    }
    
    $html .= '
        <td>' . $row['total_requests'] . '</td>
        <td>' . $row['pending'] . '</td>
        <td>' . $row['approved'] . '</td>
        <td>' . $row['rejected'] . '</td>
    </tr>';
}

// Calculate and add totals row
$totals = calculateTotals($reportData);
$html .= '<tr class="totals-row">';

// Add colspan based on number of grouping columns
$colspan = 0;
if ($filters['group_by_department'] === 'yes') $colspan++;
if ($filters['group_by_course'] === 'yes') $colspan++;
if ($filters['group_by_document'] === 'yes') $colspan++;
if ($filters['group_by_purpose'] === 'yes') $colspan++;

$html .= '<td colspan="' . $colspan . '"><strong>TOTAL</strong></td>';
$html .= '
    <td><strong>' . $totals['total_requests'] . '</strong></td>
    <td><strong>' . $totals['pending'] . '</strong></td>
    <td><strong>' . $totals['approved'] . '</strong></td>
    <td><strong>' . $totals['rejected'] . '</strong></td>
</tr>';

$html .= '
        </tbody>
    </table>
    
    <div class="footer">
        <p>This report was generated by the CEIT Guidance Office.</p>
    </div>
</body>
</html>';

// Load HTML content into DOMPDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Add document metadata
$dompdf->addInfo('Title', 'Document Request Report');
$dompdf->addInfo('Author', 'CEIT Guidance Office');
$dompdf->addInfo('Subject', 'Document Request Statistics');
$dompdf->addInfo('Keywords', 'document, request, report, statistics');
$dompdf->addInfo('Creator', 'CEIT Guidance Information System');

// Output the generated PDF
$dompdf->stream('document_request_report_' . date('Y-m-d') . '.pdf', ['Attachment' => 0]);

// Clear session data after generating PDF
unset($_SESSION['report_data']);
unset($_SESSION['report_filters']);