<?php
session_start();
require_once '../vendor/autoload.php';
include '../db.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Function to format description lines
function formatDescriptionLines($text, $maxLines = 15) {
    // Split text into lines and words
    $words = explode(' ', trim($text));
    $lines = array();
    $currentLine = '';
    
    foreach ($words as $word) {
        if (strlen($currentLine . ' ' . $word) > 80) { // Max chars per line
            $lines[] = trim($currentLine);
            $currentLine = $word;
        } else {
            $currentLine .= ($currentLine ? ' ' : '') . $word;
        }
    }
    if ($currentLine) {
        $lines[] = trim($currentLine);
    }
    
    // Take only up to maxLines
    $formatted = array_slice($lines, 0, $maxLines);
    
    // Pad array with empty strings if less than maxLines
    while (count($formatted) < $maxLines) {
        $formatted[] = "";
    }
    
    return $formatted;
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    die("Please log in first");
}

// Get the facilitator/counselor name who is generating the PDF
$current_user_id = $_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'];

// Get the name of the logged-in facilitator/counselor
$user_query = "SELECT first_name, middle_initial, last_name FROM tbl_" . $current_user_type . " WHERE id = ?";
$user_stmt = $connection->prepare($user_query);
if ($user_stmt === false) {
    die("Error preparing user query: " . $connection->error);
}
$user_stmt->bind_param("i", $current_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Construct full name from components
$current_user_name = 'Unknown User';
if ($user_data) {
    $current_user_name = trim($user_data['first_name'] . ' ' . 
        ($user_data['middle_initial'] ? $user_data['middle_initial'] . '. ' : '') . 
        $user_data['last_name']);
}

// Get report ID from the URL
$report_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($report_id)) {
    die("No report ID provided. Please ensure you're accessing this page with a valid report ID.");
}

// Query for incident report details
$query = "
    SELECT 
        ir.*,
        GROUP_CONCAT(DISTINCT sv.student_name SEPARATOR '\n') as involved_students,
        GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR '\n') as witnesses,
        ir.reported_by,
        ir.date_reported,
        ir.place,
        ir.description,
        ir.status
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    WHERE ir.id = ?
    GROUP BY ir.id
";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    die("Report not found");
}
// THEN get reporter details if it's a student
$student_details = '';
if ($report['reporters_id'] && $report['reported_by_type'] === 'student') {
    $student_query = "
        SELECT 
            ts.*, 
            s.section_no, 
            s.year_level, 
            c.name as course_name
        FROM tbl_student ts
        JOIN sections s ON ts.section_id = s.id
        JOIN courses c ON s.course_id = c.id
        WHERE ts.student_id = ?
    ";
    $student_stmt = $connection->prepare($student_query);
    if ($student_stmt === false) {
        die("Error preparing student query: " . $connection->error);
    }
    
    $student_stmt->bind_param("s", $report['reporters_id']);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows > 0) {
        $student = $student_result->fetch_assoc();
        
        // Convert word year level to numerical
        $year_text = '';
        switch(strtolower(trim($student['year_level']))) {
            case 'first year':
            case 'first':
                $year_text = '1st';
                break;
            case 'second year':
            case 'second':
                $year_text = '2nd';
                break;
            case 'third year':
            case 'third':
                $year_text = '3rd';
                break;
            case 'fourth year':
            case 'fourth':
                $year_text = '4th';
                break;
            case 'fifth year':
            case 'fifth':
                $year_text = '5th';
                break;
            case 'irregular':
                $year_text = 'Irregular';
                break;
            default:
                $year_text = $student['year_level']; // Keep original if none match
        }
        
        // Format the complete student details
        if ($year_text === 'Irregular') {
            $student_details = sprintf(
                "%s - %s",
                $student['course_name'],
                $year_text
            );
        } else {
            $student_details = sprintf(
                "%s %s Year - Section %s",
                $student['course_name'],
                $year_text,
                $student['section_no']
            );
        }
    }
}

// Format description lines
$descriptionLines = formatDescriptionLines($report['description']);

// Get logo
$logo_path = __DIR__ . '/logo.png';
$logo_data = base64_encode(file_get_contents($logo_path));

// Format students and witnesses
$students = array_filter(explode("\n", $report['involved_students']));
$witnesses = array_filter(explode("\n", $report['witnesses']));

// Create HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Incident Report Form</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            line-height: 1.2;
            padding: 15px;
            margin: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .form-title {
            text-align: center;
            font-weight: bold;
            margin-top: 5%;
            font-size: 12px;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        
        td {
            border: 1px solid black;
            padding: 5px 8px;
            font-size: 12px;
            vertical-align: top;
        }
        
        .section-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .section-subtitle {
            font-style: italic;
            text-align: center;
            margin-bottom: 8px;
            font-size: 12px;
            color: #333;
        }
        
        .description-cell {
            padding: 10px;
            font-size: 12px;
        }
        
        .description-content {
            margin-bottom: 15px;
        }
        
        .description-line {
            margin-bottom: 12px;
            min-height: 18px;
        }
        
        .signature-row {
            page-break-inside: avoid;
        }
        
        .form-number {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 11px;
            font-style: italic;
        }
        .version-number {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 12px;
        }
        
        .letterhead {
            text-align: center;
            margin-bottom: 10px;
            position: relative;
        }
        
        .logo {
            position: absolute;
            left: 100px;
            top: 0;
            width: 100px;
            height: 90px;
        }
        
        .letterhead-text {
            text-align: center;
            line-height: 1.2;
        }
        
        .letterhead-text h1 {
            font-size: 16px;
            margin: 0;
            font-family: "Times New Roman", serif;
            font-weight: bold;
        }
        
        .letterhead-text h2 {
            font-size: 12px;
            margin: 2px 0;
        }
        
        .letterhead-text p {
            font-size: 11px;
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="form-number">OSAS-QF-44</div>
    
    <div class="letterhead">
        <div class="letterhead-text">
            <p>Republic of the Philippines</p>
            <img src="data:image/png;base64,' . $logo_data . '" class="logo">
            <h1>CAVITE STATE UNIVERSITY</h1>
            <h2>Don Severino delas Alas Campus</h2>
            <p>Indang, Cavite</p>
        </div>
    </div>

    <div class="form-title">INCIDENT REPORT FORM</div>

    <table>
        <tr>
            <td width="50%">
                <div class="section-title">Date & Time Reported:</div>
                <div class="section-subtitle">(Petsa at oras ng ini-ulat)</div>
                <div style="width: 100%; margin-top: 5px;">
                    <div style="width: 100%; border-bottom: 1px solid black; margin-top: 5%;">' . htmlspecialchars($report['date_reported']) . '</div>
                </div>
            </td>
            <td width="50%">
                <div class="section-title">Place, Date & Time of Incident:</div>
                <div class="section-subtitle">(Lugar, petsa, at oras ng pangyayari)</div>
                <div style="width: 100%; margin-top: 5px;">
                    <div style="width: 100%; border-bottom: 1px solid black; margin-top: 5%;">' . htmlspecialchars($report['place']) . '</div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="section-title">Person/s Involved:</div>
                <div class="section-subtitle">(Mga may kinalaman)</div>
                <div style="display: table; width: 100%; margin-top: 40px;">
                    <div style="display: table-row; text-align:center;">
                        <div style="display: table-cell; width: 60%;">Name</div>
                        <div style="display: table-cell; width: 40%;">Signature</div>
                    </div>
                </div>';
                foreach ($students as $student) {
                    $html .= '
                    <div style="display: table; width: 100%; margin-top: 10px;">
                        <div style="display: table-row;">
                            <div style="display: table-cell; width: 60%; border-bottom: 1px solid black;">' . htmlspecialchars(trim($student)) . '</div>
                            <div style="display: table-cell; width: 40%; border-bottom: 1px solid black;"></div>
                        </div>
                    </div>';
                }
            $html .= '
            </td>
            <td>
                <div class="section-title">Witness/es:</div>
                <div class="section-subtitle">(Saksi/Mga nakakita ng pangyayari)</div>
                <div style="display: table; width: 100%; margin-top: 40px;">
                    <div style="display: table-row; text-align:center;">
                        <div style="display: table-cell; width: 60%;">Name</div>
                        <div style="display: table-cell; width: 40%;">Signature</div>
                    </div>
                </div>';
                foreach ($witnesses as $witness) {
                    $html .= '
                    <div style="display: table; width: 100%; margin-top: 10px;">
                        <div style="display: table-row;">
                            <div style="display: table-cell; width: 60%; border-bottom: 1px solid black;">' . htmlspecialchars(trim($witness)) . '</div>
                            <div style="display: table-cell; width: 40%; border-bottom: 1px solid black;"></div>
                        </div>
                    </div>';
                }
            $html .= '
            </td>
        </tr>
        <tr>
            <td colspan="2" class="description-cell">
                <div style="text-align: center; font-weight: bold;">Brief Description of the Incident/Offense:</div>
                <div style="text-align: center; font-style: italic; margin-bottom: 50px;">(Maikling salaysay tungkol sa pangyayari)</div>';
                
                // Add each line of description with an underline
                foreach ($descriptionLines as $line) {
                    $html .= '
                    <div style="margin-bottom: 10px;">
                        <div style="margin-bottom: 9px;">' . htmlspecialchars($line) . '</div>
                        <div style="width: 100%; border-bottom: 1px solid black;"></div>
                    </div>';
                }
                
            $html .= '
            </td>
        </tr>
        <tr class="signature-row">
            <td>
                <div style="text-align: center;font-weight:bold;">Reported by:</div>
                <div style="text-align: center; font-style: italic;">(Isinalaysay ni)</div>
                <div style="width: 100%; margin-top: 20px;">
                    <div style="width: 100%; border-bottom: 1px solid black; text-align:center;">
                        ' . htmlspecialchars($report['reported_by']) . 
                        ($student_details ? '<br><span style="font-size: 10px;">' . htmlspecialchars($student_details) . '</span>' : '') . '
                    </div>
                    <div style="text-align: center; font-size: 12px; margin-top: 5px;">(Name, Course, Yr. & Sec.)</div>
                </div>
            </td>
            <td>
                <div style="text-align: center; font-weight:bold;">Noted by:</div>
                <div style="width: 100%; margin-top: 50px;">
                    <div style="width: 100%; border-bottom: 1px solid black;"></div>
                    <div style="text-align: center; margin-top: 5px;">Guidance Counselor</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="version-number">V01-2018-05-28</div>
</body>
</html>';

// Create Dompdf object with options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->setChroot('C:/xampp/htdocs/capstone1');

$dompdf = new Dompdf($options);

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Add document information
$dompdf->addInfo("Title", "OSAS-QF-44-Incident-Report-Form");
$dompdf->addInfo("Author", "CEIT Guidance Facilitator");

// Render PDF
$dompdf->render();

// Output PDF
$dompdf->stream("incident_report_" . $report_id . ".pdf", array("Attachment" => 0));
?>