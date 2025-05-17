<?php
// meeting_minutes_pdf.php
session_start();
require_once '../vendor/autoload.php';
include '../db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die('Report ID not provided');
}

$report_id = $_GET['id'];

// Fetch the report and meeting details
$sql = "SELECT i.*, m.*, 
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        c.name as course_name
        FROM incident_reports i 
        LEFT JOIN meetings m ON i.id = m.incident_report_id
        LEFT JOIN student_violations sv ON i.id = sv.incident_report_id
        LEFT JOIN tbl_student s ON sv.student_id = s.student_id
        LEFT JOIN sections sec ON s.section_id = sec.id
        LEFT JOIN courses c ON sec.course_id = c.id
        WHERE i.id = ? AND i.status = 'settled'";

$stmt = $connection->prepare($sql);
$stmt->bind_param("s", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    
    // Format dates
    $date_reported = date('F d, Y h:i A', strtotime($data['date_reported']));
    $meeting_date = !empty($data['meeting_date']) ? date('F d, Y h:i A', strtotime($data['meeting_date'])) : 'N/A';
    $approval_date = !empty($data['approval_date']) ? date('F d, Y', strtotime($data['approval_date'])) : 'N/A';
    
    // HTML content for the PDF
    $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Meeting Minutes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #333;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #0d693e;
            padding-bottom: 10px;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #0d693e;
            margin: 0;
        }
        .subtitle {
            font-size: 14pt;
            font-weight: bold;
            color: #0d693e;
            margin: 10px 0 8px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #0d693e;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            width: 30%;
            color: #121212;
        }
        .info-table td {
            border: 1px solid #ddd;
            padding: 8px;
            width: 70%;
        }
        .text-block {
            white-space: pre-wrap;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9pt;
            color: #666;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
    </style>
    </head>
    <body>
        <div class="header">
            <div class="title">MEETING MINUTES</div>
        </div>

        <div class="info-section">
            <div class="subtitle">Incident Report Details</div>
            <table class="info-table">
                <tr>
                    <th>Student Name</th>
                    <td>'.htmlspecialchars($data['student_name']).'</td>
                </tr>
                <tr>
                    <th>Course</th>
                    <td>'.htmlspecialchars($data['course_name']).'</td>
                </tr>
                <tr>
                    <th>Date Reported</th>
                    <td>'.$date_reported.'</td>
                </tr>
                <tr>
                    <th>Incident Location</th>
                    <td>'.htmlspecialchars($data['place']).'</td>
                </tr>
                <tr>
                    <th>Incident Description</th>
                    <td class="text-block">'.nl2br(htmlspecialchars($data['description'])).'</td>
                </tr>
            </table>
        </div>

        <div class="info-section">
            <div class="subtitle">Meeting Details</div>
            <table class="info-table">
                <tr>
                    <th>Meeting Date</th>
                    <td>'.$meeting_date.'</td>
                </tr>
                <tr>
                    <th>Venue</th>
                    <td>'.htmlspecialchars($data['venue']).'</td>
                </tr>
                <tr>
                    <th>Persons Present</th>
                    <td>'.nl2br(htmlspecialchars(trim(str_replace('"', ' ', $data['persons_present']), '[]'))).'</td>
                </tr>
                <tr>
                    <th>Meeting Minutes</th>
                    <td class="text-block">'.nl2br(htmlspecialchars($data['meeting_minutes'])).'</td>
                </tr>
            </table>
        </div><br>

        <div class="info-section">
            <table class="info-table">
                <tr>
                    <th>Prepared By</th>
                    <td>'.htmlspecialchars($data['prepared_by']).'</td>
                </tr>
                <tr>
                    <th>Settlement Date</th>
                    <td>'.$approval_date.'</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Generated on '.date('F d, Y h:i A').' | CEIT Guidance Office - Meeting Minutes Report
        </div>
    </body>
    </html>';

    // Configure Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->setChroot(__DIR__);

    // Create and setup Dompdf object
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    
    // Set document information
    $dompdf->addInfo("Title", "Meeting Minutes - Report " . $data['id']);
    $dompdf->addInfo("Author", "CEIT Guidance Facilitator");
    
    // Render and output PDF
    $dompdf->render();
    $dompdf->stream("meeting_minutes_".$data['id'].".pdf", array("Attachment" => 0));
    exit();
} else {
    die('Report not found or access denied');
}
?>