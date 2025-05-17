<?php
// generate_minutes_pdf.php
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Check if an incident report ID is provided
if (!isset($_GET['id'])) {
    die("No incident report ID provided.");
}

$incident_report_id = $connection->real_escape_string($_GET['id']);

// Fetch incident report details
$incident_query = "SELECT ir.*, 
                   s.first_name, s.last_name, 
                   sv.status as violation_status, 
                   c.name as course_name,
                   GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses
                   FROM incident_reports ir
                   JOIN student_violations sv ON ir.id = sv.incident_report_id
                   JOIN tbl_student s ON sv.student_id = s.student_id
                   LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
                   LEFT JOIN sections sec ON s.section_id = sec.id
                   LEFT JOIN courses c ON sec.course_id = c.id
                   WHERE ir.id = ?
                   GROUP BY ir.id";

$incident_stmt = $connection->prepare($incident_query);
$incident_stmt->bind_param("s", $incident_report_id);
$incident_stmt->execute();
$incident_result = $incident_stmt->get_result();
$incident = $incident_result->fetch_assoc();

// Updated query to match view_all_minutes.php query style
$meetings_query = "SELECT m.*,
                   ROW_NUMBER() OVER (PARTITION BY m.incident_report_id 
                                    ORDER BY m.meeting_date ASC) as calculated_sequence
                   FROM meetings m
                   WHERE m.incident_report_id = ?
                   AND m.meeting_minutes IS NOT NULL 
                   AND TRIM(m.meeting_minutes) != ''
                   ORDER BY m.meeting_date ASC";

$meetings_stmt = $connection->prepare($meetings_query);
$meetings_stmt->bind_param("s", $incident_report_id);
$meetings_stmt->execute();
$meetings_result = $meetings_stmt->get_result();

// Generate HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { width: 100px; margin-bottom: 10px; }
        .university-name { font-size: 20px; font-weight: bold; margin: 5px 0; }
        .title { font-size: 18px; font-weight: bold; margin: 20px 0; text-align: center; color: #0d693e;}
        .incident-details { margin-bottom: 30px; font-size: 13px;}
        .meeting { margin-bottom: 30px; page-break-inside: avoid; font-size: 15px;}
        .meeting-header { font-weight: bold; margin-bottom: 10px; color: #0d693e;}
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 14px;}
        th { background-color: #f5f5f5; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <div class="university-name">CEIT Guidance Office | Meeting Minutes Record</div>
    </div>

    <div class="incident-details">
        <div class="title">Incident Report Details</div>
        <table>
            <tr>
                <th>Report ID:</th>
                <td>' . htmlspecialchars($incident['id']) . '</td>
                <th>Student Name:</th>
                <td>' . htmlspecialchars($incident['first_name'] . ' ' . $incident['last_name']) . '</td>
            </tr>
            <tr>
                <th>Course:</th>
                <td>' . htmlspecialchars($incident['course_name']) . '</td>
                <th>Date Reported:</th>
                <td>' . htmlspecialchars($incident['date_reported']) . '</td>
            </tr>
            <tr>
                <th>Witnesses:</th>
                <td colspan="3">' . htmlspecialchars($incident['witnesses'] ?? 'No witnesses') . '</td>
            </tr>
            <tr>
                <th>Incident Description:</th>
                <td colspan="3">' . htmlspecialchars($incident['description']) . '</td>
            </tr>
        </table>
    </div>

    <div class="title">Meeting Minutes</div>';

while ($meeting = $meetings_result->fetch_assoc()) {
    $attendees = json_decode($meeting['persons_present'], true) ?? [];
    $html .= '
    <div class="meeting">
        <div class="meeting-header">Meeting #' . htmlspecialchars($meeting['calculated_sequence']) . ' - ' . 
        date('F j, Y, g:i A', strtotime($meeting['meeting_date'])) . '</div>
        <table>
            <tr>
                <th width="20%">Venue:</th>
                <td>' . htmlspecialchars($meeting['venue']) . '</td>
            </tr>
            <tr>
                <th>Persons Present:</th>
                <td>' . htmlspecialchars(is_array($attendees) ? implode(', ', $attendees) : '') . '</td>
            </tr>
            <tr>
                <th>Minutes:</th>
                <td>' . nl2br(htmlspecialchars($meeting['meeting_minutes'])) . '</td>
            </tr>
            <tr>
                <th>Prepared By:</th>
                <td>' . htmlspecialchars($meeting['prepared_by']) . '</td>
            </tr>
        </table>
    </div>';
}

$html .= '
    <div class="footer">
        Generated on ' . date('F j, Y, g:i A') . ' - CEIT Guidance Office
    </div>
</body>
</html>';

// Create Dompdf object
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'Arial');
$options->setChroot(dirname(__FILE__));

$dompdf = new Dompdf($options);

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render PDF
$dompdf->render();

// Output PDF
$filename = "meeting_minutes_report_" . $incident_report_id . ".pdf";
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Content-Length: ' . strlen($dompdf->output()));

echo $dompdf->output();
exit;