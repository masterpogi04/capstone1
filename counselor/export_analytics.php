<?php
session_start();
include '../db.php';
require '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Check if user is logged in as counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get parameters
$status = $_GET['status'] ?? 'Pending';
$department = $_GET['department'] ?? '';
$course = $_GET['course'] ?? '';
$reason = $_GET['reason'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$format = $_GET['format'] ?? 'pdf'; // pdf or csv

// Function to convert name to proper case
function toProperCase($name) {
    // Split by spaces, hyphens, and apostrophes to handle multi-part names
    $parts = preg_split('/(\s+|-|\')/', $name, -1, PREG_SPLIT_DELIM_CAPTURE);
    $result = '';
    
    foreach ($parts as $part) {
        if ($part === ' ' || $part === '-' || $part === '\'') {
            $result .= $part;
        } else {
            // For common name prefixes like Mc, Mac, etc. handle special capitalization
            if (preg_match('/^(mc|mac)/i', $part)) {
                $result .= ucfirst(substr($part, 0, 2)) . ucfirst(substr($part, 2));
            } else {
                $result .= ucfirst(strtolower($part));
            }
        }
    }
    
    return $result;
}

// Generate a date range string for display
function getDateRangeString($start_date, $end_date) {
    if ($start_date && $end_date) {
        return 'Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date));
    } elseif ($start_date) {
        return 'From: ' . date('M d, Y', strtotime($start_date));
    } elseif ($end_date) {
        return 'Until: ' . date('M d, Y', strtotime($end_date));
    }
    return 'All Time';
}

try {
    // Get referral data
    $query = "SELECT 
                r.*,
                s.first_name,
                s.last_name,
                d.name as department_name,
                c.name as course_name
              FROM referrals r
              LEFT JOIN tbl_student s ON r.student_id = s.student_id
              LEFT JOIN sections sec ON s.section_id = sec.id
              LEFT JOIN courses c ON sec.course_id = c.id
              LEFT JOIN departments d ON c.department_id = d.id
              WHERE r.status = ?";
    
    $params = [$status];
    $types = "s";

    // Add date filters
    if ($start_date) {
        $query .= " AND r.date >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $query .= " AND r.date <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    
    // Add department filter
    if ($department) {
        $query .= " AND d.id = ?";
        $params[] = $department;
        $types .= "i";
    }
    
    // Add course filter
    if ($course) {
        $query .= " AND c.id = ?";
        $params[] = $course;
        $types .= "i";
    }
    
    // Add reason filter
    if ($reason) {
        $query .= " AND r.reason_for_referral = ?";
        $params[] = $reason;
        $types .= "s";
    }

    $query .= " ORDER BY r.date DESC";

    $stmt = $connection->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get the date range string for display
    $date_range_string = getDateRangeString($start_date, $end_date);

    if ($format === 'csv') {
        // Export as CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="referrals_' . $status . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // Add title row with report information
        $title = 'Referral Analytics Report - Status: ' . $status;
        if ($date_range_string !== 'All Time') {
            $title .= ' (' . $date_range_string . ')';
        }
        
        // Write title as a single cell spanning the whole row
        fputcsv($output, [$title]);
        
        // Add an empty row for spacing
        fputcsv($output, []);
        
        // Add column headers
        fputcsv($output, [
            'Date',
            'Student Name',
            'Course/Year',
            'Department',
            'Reason',
            'Details',
            'Status'
        ]);

        // Add data with proper case for names
        while ($row = $result->fetch_assoc()) {
            $firstName = toProperCase($row['first_name'] ?? '');
            $lastName = toProperCase($row['last_name'] ?? '');
            $fullName = trim($firstName . ' ' . $lastName);
            
            fputcsv($output, [
                $row['date'],
                $fullName,
                $row['course_year'],
                $row['department_name'],
                $row['reason_for_referral'],
                $row['violation_details'] ?? $row['other_concerns'] ?? 'N/A',
                $row['status']
            ]);
        }

        fclose($output);
    } else {
        // Export as PDF using DOMPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('dpi', 120); // Higher DPI for better quality
        
        $dompdf = new Dompdf($options);
        
        // Collect data for HTML generation
        $referrals = [];
        while ($row = $result->fetch_assoc()) {
            $firstName = toProperCase($row['first_name'] ?? '');
            $lastName = toProperCase($row['last_name'] ?? '');
            $fullName = trim($firstName . ' ' . $lastName);
            
            $referrals[] = [
                'date' => $row['date'],
                'student_name' => $fullName,
                'course_year' => $row['course_year'] ?? 'N/A',
                'department' => $row['department_name'] ?? 'N/A',
                'reason' => $row['reason_for_referral'] ?? 'N/A',
                'details' => $row['violation_details'] ?? $row['other_concerns'] ?? 'N/A',
                'status' => $row['status'] ?? 'N/A'
            ];
        }
        
        // Get logo
        $logo_path = __DIR__ . '/logo.png';
        $logo_data = base64_encode(file_get_contents($logo_path));
        
        // Generate HTML content
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Referral Analytics Report</title>
            <style>
                @page {
                    size: landscape;
                    margin: 15mm;
                }
                body {
                    font-family: "DejaVu Sans", sans-serif;
                    font-size: 10pt;
                    line-height: 1.3;
                    margin: 0;
                    padding: 15px;
                }
                h1 {
                    text-align: center;
                    font-size: 16pt;
                    margin-bottom: 5px;
                    margin-top: 25px;
                }
                h2 {
                    text-align: center;
                    font-size: 12pt;
                    margin-top: 0;
                    margin-bottom: 15px;
                }
                h3 {
                    text-align: center;
                    font-size: 10pt;
                    margin-top: 0;
                    margin-bottom: 15px;
                    color: #555;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th {
                    background-color: #f2f2f2;
                    text-align: left;
                    padding: 6px;
                    font-weight: bold;
                    border: 1px solid #ddd;
                    font-size: 10pt;
                }
                td {
                    border: 1px solid #ddd;
                    padding: 5px;
                    font-size: 9pt;
                    vertical-align: top;
                    word-wrap: break-word;
                    max-width: 300px;
                }
                .date-col {
                    width: 10%;
                }
                .student-col {
                    width: 15%;
                }
                .course-col {
                    width: 15%;
                }
                .dept-col {
                    width: 18%;
                }
                .reason-col {
                    width: 15%;
                }
                .details-col {
                    width: 25%;
                }
                .status-col {
                    width: 10%;
                }
                .footer {
                    position: fixed;
                    bottom: 0;
                    width: 100%;
                    text-align: center;
                    font-size: 9pt;
                    padding: 5px;
                }
                .form-number {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    font-size: 11px;
                    font-style: italic;
                }
                
                .letterhead {
                    text-align: center;
                    margin-bottom: 10px;
                    position: relative;
                }
                
                .logo {
                    position: absolute;
                    left: 30%;
                    top: 10px;
                    width: 90px;
                    height: 80px;
                }
                
                .letterhead-text {
                    text-align: center;
                    line-height: 1.3;
                    margin-bottom: 20px;
                }
                
                .letterhead-text p:first-child {
                    font-size: 12px;
                    margin: 5px 0;
                }
                
                .letterhead-text h1 {
                    font-size: 20px;
                    margin: 5px 0;
                    font-family: "Times New Roman", serif;
                    font-weight: bold;
                }
                
                .letterhead-text h2 {
                    font-size: 14px;
                    margin: 5px 0;
                }
                
                .letterhead-text p:last-child {
                    font-size: 12px;
                    margin: 5px 0;
                }
            </style>
        </head>
        <body>
            
            
            <div class="letterhead">
                <div class="letterhead-text">
                    <p>Republic of the Philippines</p>
                    <img src="data:image/png;base64,' . $logo_data . '" class="logo">
                    <h1>CAVITE STATE UNIVERSITY</h1>
                    <h2>Don Severino delas Alas Campus</h2>
                    <p>Indang, Cavite</p>
                </div>
            </div>
            
            <h1>Referral Analytics Report</h1>
            <h2>Status: ' . htmlspecialchars($status) . '</h2>';
        
        if ($date_range_string !== 'All Time') {
            $html .= '<h3>' . htmlspecialchars($date_range_string) . '</h3>';
        }
            
        $html .= '
            <table>
                <thead>
                    <tr>
                        <th class="date-col">Date</th>
                        <th class="student-col">Student</th>
                        <th class="course-col">Course/Year</th>
                        <th class="dept-col">Department</th>
                        <th class="reason-col">Reason</th>
                        <th class="details-col">Details</th>
                        <th class="status-col">Status</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($referrals as $referral) {
            $html .= '
                    <tr>
                        <td class="date-col">' . date('Y-m-d', strtotime($referral['date'])) . '</td>
                        <td class="student-col">' . htmlspecialchars($referral['student_name']) . '</td>
                        <td class="course-col">' . htmlspecialchars($referral['course_year']) . '</td>
                        <td class="dept-col">' . htmlspecialchars($referral['department']) . '</td>
                        <td class="reason-col">' . htmlspecialchars($referral['reason']) . '</td>
                        <td class="details-col">' . htmlspecialchars($referral['details']) . '</td>
                        <td class="status-col">' . htmlspecialchars($referral['status']) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                Generated by University Guidance Counselor - ' . date('Y-m-d') . '
            </div>
        </body>
        </html>';
        
        // Load HTML content into DOMPDF
        $dompdf->loadHtml($html);
        
        // Set paper size to landscape A4
        $dompdf->setPaper('A4', 'landscape');
        
        // Add document information
        $dompdf->addInfo("Title", "Referral-Analytics-Report");
        $dompdf->addInfo("Author", "University Guidance Counselor");
        
        // Render the PDF
        $dompdf->render();
        
        // Output PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="referrals_' . $status . '_' . date('Y-m-d') . '.pdf"');
        echo $dompdf->output();
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>