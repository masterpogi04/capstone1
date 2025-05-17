<?php
session_start();
include '../db.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Function to convert numeric date to word month format
function formatDateWithWordMonth($dateString) {
    if (empty($dateString)) return '';
    $date = new DateTime($dateString);
    return $date->format('M j, Y');
}

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$status = $_GET['status'] ?? 'Pending';
$department = $_GET['department'] ?? '';
$course = $_GET['course'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$format = $_GET['format'] ?? 'pdf';

try {
    // Build the query with proper joins for better data retrieval
    $query = "SELECT DISTINCT 
                ir.id, 
                ir.date_reported, 
                ir.place, 
                ir.description, 
                ir.reported_by, 
                ir.status,
                d.name as department_name, 
                c.name as course_name
              FROM incident_reports ir
              LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
              LEFT JOIN tbl_student s ON sv.student_id = s.student_id
              LEFT JOIN sections sec ON s.section_id = sec.id OR sv.section_id = sec.id
              LEFT JOIN courses c ON sec.course_id = c.id
              LEFT JOIN departments d ON c.department_id = d.id
              WHERE ir.is_archived = 0";
    
    $params = [];
    $types = "";

    // Add filters
    if ($status !== 'all') {
        $query .= " AND ir.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if ($department) {
        $query .= " AND d.id = ?";
        $params[] = $department;
        $types .= "i";
    }

    if ($course) {
        $query .= " AND c.id = ?";
        $params[] = $course;
        $types .= "i";
    }

    if ($academic_year) {
        $start_academic_year = $academic_year . '-06-01';
        $end_academic_year = ($academic_year + 1) . '-05-31';
        $query .= " AND ir.date_reported BETWEEN ? AND ?";
        $params[] = $start_academic_year;
        $params[] = $end_academic_year;
        $types .= "ss";
    }

    if ($start_date) {
        $query .= " AND DATE(ir.date_reported) >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $query .= " AND DATE(ir.date_reported) <= ?";
        $params[] = $end_date;
        $types .= "s";
    }

    // Add grouping and sorting
    $query .= " GROUP BY ir.id ORDER BY ir.date_reported DESC";

    // Execute query
    $stmt = $connection->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Get department and course names for header if filters are applied
    $department_name = '';
    $course_name = '';
    
    if ($department) {
        $dept_query = "SELECT name FROM departments WHERE id = ?";
        $dept_stmt = $connection->prepare($dept_query);
        $dept_stmt->bind_param("i", $department);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        if ($dept_row = $dept_result->fetch_assoc()) {
            $department_name = $dept_row['name'];
        }
    }
    
    if ($course) {
        $course_query = "SELECT name FROM courses WHERE id = ?";
        $course_stmt = $connection->prepare($course_query);
        $course_stmt->bind_param("i", $course);
        $course_stmt->execute();
        $course_result = $course_stmt->get_result();
        if ($course_row = $course_result->fetch_assoc()) {
            $course_name = $course_row['name'];
        }
    }

    if ($format === 'csv') {
        // Export as CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="incidents_' . $status . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add title and filter information
        fputcsv($output, ['Incident Reports Analytics']);
        fputcsv($output, ['Status: ' . $status]);
        if ($department_name) {
            fputcsv($output, ['Department: ' . $department_name]);
        }
        if ($course_name) {
            fputcsv($output, ['Course: ' . $course_name]);
        }
        if ($start_date || $end_date) {
            fputcsv($output, ['Date Range: ' . 
                ($start_date ? formatDateWithWordMonth($start_date) : 'Start') . 
                ' to ' . 
                ($end_date ? formatDateWithWordMonth($end_date) : 'End')
            ]);
        }
        if ($academic_year) {
            fputcsv($output, ['Academic Year: ' . $academic_year . '-' . ($academic_year + 1)]);
        }
        fputcsv($output, []); // Empty row for spacing
        
        // Add headers
        fputcsv($output, [
            'Date Reported',
            'Reference ID',
            'Place',
            'Description',
            'Reported By',
            'Department',
            'Course',
            'Status'
        ]);

        // Add data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                formatDateWithWordMonth($row['date_reported']),
                $row['id'],
                $row['place'],
                $row['description'],
                $row['reported_by'],
                $row['department_name'] ?? 'N/A',
                $row['course_name'] ?? 'N/A',
                $row['status']
            ]);
        }
        fclose($output);
    } else {
        // Export as PDF using Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->setChroot(__DIR__);
        
        $dompdf = new Dompdf($options);
        
        // Start building HTML content with improved styling
        $html = '
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0;
                    padding: 20px;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 20px; 
                    border-bottom: 2px solid #008F57;
                    padding-bottom: 10px;
                }
                .header h2 {
                    color: #008F57;
                    margin: 0 0 10px 0;
                }
                .filters { 
                    margin-bottom: 20px; 
                    text-align: center;
                    background-color: #f5f5f5;
                    padding: 10px;
                    border-radius: 5px;
                }
                .filters p {
                    margin: 5px 0;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 20px;
                    font-size: 12px;
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 8px; 
                    text-align: left; 
                }
                th { 
                    background-color: #008F57;
                    color: white;
                    font-weight: bold;
                }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .description { max-width: 200px; word-wrap: break-word; }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    font-size: 12px;
                    color: #666;
                }
                .status-pending { color: #ffc107; font-weight: bold; }
                .status-meeting { color: #17a2b8; font-weight: bold; }
                .status-settled { color: #28a745; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Incident Reports Analytics</h2>
                <p>CEIT Guidance Office</p>
            </div>
            <div class="filters">
                <p><strong>Status:</strong> ' . htmlspecialchars($status) . '</p>';
        
        if ($department_name) {
            $html .= '<p><strong>Department:</strong> ' . htmlspecialchars($department_name) . '</p>';
        }
        if ($course_name) {
            $html .= '<p><strong>Course:</strong> ' . htmlspecialchars($course_name) . '</p>';
        }
        if ($start_date || $end_date) {
            $html .= '<p><strong>Date Range:</strong> ' . 
                     ($start_date ? formatDateWithWordMonth($start_date) : 'Start') . 
                     ' to ' . 
                     ($end_date ? formatDateWithWordMonth($end_date) : 'End') . '</p>';
        }
        if ($academic_year) {
            $html .= '<p><strong>Academic Year:</strong> ' . $academic_year . '-' . ($academic_year + 1) . '</p>';
        }
        
        $html .= '
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date Reported</th>
                        <th>Reference ID</th>
                        <th>Place</th>
                        <th width="25%">Description</th>
                        <th>Reported By</th>
                        <th>Department</th>
                        <th>Course</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
        
        $rowCount = 0;
        while ($row = $result->fetch_assoc()) {
            $rowCount++;
            
            // Determine status class
            $statusClass = '';
            if ($row['status'] == 'Pending') {
                $statusClass = 'status-pending';
            } else if ($row['status'] == 'For Meeting') {
                $statusClass = 'status-meeting';
            } else if ($row['status'] == 'Settled') {
                $statusClass = 'status-settled';
            }
            
            $html .= '<tr>
                <td>' . formatDateWithWordMonth($row['date_reported']) . '</td>
                <td>' . htmlspecialchars($row['id']) . '</td>
                <td>' . htmlspecialchars($row['place']) . '</td>
                <td class="description">' . htmlspecialchars(substr($row['description'], 0, 200)) . (strlen($row['description']) > 200 ? '...' : '') . '</td>
                <td>' . htmlspecialchars($row['reported_by']) . '</td>
                <td>' . htmlspecialchars($row['department_name'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($row['course_name'] ?? 'N/A') . '</td>
                <td class="' . $statusClass . '">' . htmlspecialchars($row['status']) . '</td>
            </tr>';
        }
        
        if ($rowCount === 0) {
            $html .= '<tr><td colspan="8" style="text-align: center;">No incident reports found with the current filters.</td></tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            <div class="footer">
                <p>Generated on ' . date('F d, Y') . ' | CEIT Guidance Office</p>
                <p>Total Records: ' . $rowCount . '</p>
            </div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        // Add metadata
        $dompdf->addInfo("Title", "Incident Reports Analytics");
        $dompdf->addInfo("Author", "CEIT Guidance Office");
        $dompdf->addInfo("Subject", "Incident Reports " . $status);
        $dompdf->addInfo("CreationDate", date('Y-m-d H:i:s'));
        
        // Output PDF
        $dompdf->stream("incidents_" . $status . "_" . date('Y-m-d') . ".pdf", ["Attachment" => 1]);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>