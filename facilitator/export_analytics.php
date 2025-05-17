<?php
session_start();
include '../db.php';
require '../vendor/autoload.php'; // For FPDF

// Function to convert numeric date to word month format
function formatDateWithWordMonth($dateString) {
    if (empty($dateString)) return '';
    
    // Create DateTime object from the input date string
    $date = new DateTime($dateString);
    
    // Format the date with word month (M j, Y format like "May 10, 2025")
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
$reason = $_GET['reason'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';
$format = $_GET['format'] ?? 'pdf';

try {
    // Build the query with all filters
    $query = "SELECT 
                r.*,
                d.name as department_name,
                c.name as course_name
              FROM referrals r
              LEFT JOIN tbl_student s ON r.student_id = s.student_id
              LEFT JOIN sections sec ON s.section_id = sec.id
              LEFT JOIN courses c ON sec.course_id = c.id
              LEFT JOIN departments d ON c.department_id = d.id
              WHERE 1=1";
    
    $params = [];
    $types = "";

    // Add status filter
    if ($status) {
        $query .= " AND r.status = ?";
        $params[] = $status;
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
  if ($academic_year) {
    // For academic year YYYY, get records from June YYYY to May YYYY+1
    $start_academic_year = $academic_year . '-06-01';
    $end_academic_year = ($academic_year + 1) . '-05-31';
    $query .= " AND r.date BETWEEN ? AND ?";
    $params[] = $start_academic_year;
    $params[] = $end_academic_year;
    $types .= "ss";
}

    // Add sorting
    $query .= " ORDER BY r.date DESC";

    $stmt = $connection->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($format === 'csv') {
        // Export as CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="referrals_' . $status . '_' . date('M j, Y') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // Add title and filter information
        fputcsv($output, ['Referral Analytics Report']);
        fputcsv($output, ['Status: ' . $status]);
        if ($start_date || $end_date) {
            fputcsv($output, ['Date Range: ' . 
                ($start_date ? formatDateWithWordMonth($start_date) : 'Start') . 
                ' to ' . 
                ($end_date ? formatDateWithWordMonth($end_date) : 'End')
            ]);
        }
        if ($department || $course || $reason) {
            fputcsv($output, ['Filters Applied: ' . 
                            ($department ? 'Department, ' : '') .
                            ($course ? 'Course, ' : '') .
                            ($reason ? 'Reason' : '')]);
        }
        fputcsv($output, []); // Empty row for spacing
        
        // Add headers
        fputcsv($output, [
            'Date',
            'Student Name',
            'Course/Year',
            'Department',
            'Reason',
            'Details',
            'Status'
        ]);

        // Add data
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                formatDateWithWordMonth($row['date']),
                $row['first_name'] . ' ' . $row['last_name'],
                $row['course_year'],
                $row['department_name'],
                $row['reason_for_referral'],
                $row['violation_details'] ?? $row['other_concerns'] ?? 'N/A',
                $row['status']
            ]);
        }
        fclose($output);
    } else {
        // Export as PDF
        $pdf = new FPDF('L'); // Set to Landscape orientation
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Title and filter information
        $pdf->Cell(0, 10, 'Referral Analytics Report', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Status: ' . $status, 0, 1, 'C');
        if ($start_date || $end_date) {
            $pdf->Cell(0, 10, 'Date Range: ' . 
                ($start_date ? formatDateWithWordMonth($start_date) : 'Start') . 
                ' to ' . 
                ($end_date ? formatDateWithWordMonth($end_date) : 'End'), 
                0, 1, 'C'
            );
        }
        if ($department || $course || $reason) {
            $pdf->Cell(0, 10, 'Filters Applied: ' . 
                            ($department ? 'Department, ' : '') .
                            ($course ? 'Course, ' : '') .
                            ($reason ? 'Reason' : ''), 0, 1, 'C');
        }
        $pdf->Ln(10);

        // Set column widths
        $col1width = 30; // Date
        $col2width = 60; // Student
        $col3width = 70; // Course
        $col4width = 50; // Reason
        $col5width = 60; // Details

        // Headers
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell($col1width, 10, 'Date', 1);
        $pdf->Cell($col2width, 10, 'Student', 1);
        $pdf->Cell($col3width, 10, 'Course', 1);
        $pdf->Cell($col4width, 10, 'Reason', 1);
        $pdf->Cell($col5width, 10, 'Details', 1);
        $pdf->Ln();

        // Data
        $pdf->SetFont('Arial', '', 10);
        while ($row = $result->fetch_assoc()) {
            $formattedDate = formatDateWithWordMonth($row['date']);
            $studentName = $row['first_name'] . ' ' . $row['last_name'];
            $courseYear = $row['course_year'];
            $reason = $row['reason_for_referral'];
            $details = $row['violation_details'] ?? $row['other_concerns'] ?? 'N/A';

            $height = max(
                1,
                ceil($pdf->GetStringWidth($formattedDate) / $col1width),
                ceil($pdf->GetStringWidth($studentName) / $col2width),
                ceil($pdf->GetStringWidth($courseYear) / $col3width),
                ceil($pdf->GetStringWidth($reason) / $col4width),
                ceil($pdf->GetStringWidth($details) / $col5width)
            ) * 7;

            $pdf->Cell($col1width, $height, $formattedDate, 1);
            $pdf->Cell($col2width, $height, $studentName, 1);
            $pdf->Cell($col3width, $height, $courseYear, 1);
            $pdf->Cell($col4width, $height, $reason, 1);
            $pdf->Cell($col5width, $height, $details, 1);
            $pdf->Ln();
        }

        // Output PDF
        $pdf->Output('D', 'referrals_' . $status . '_' . date('M j, Y') . '.pdf');
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>