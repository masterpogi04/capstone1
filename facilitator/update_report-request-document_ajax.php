<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Function to generate report based on filters
function generateReport($connection, $filters) {
    $query = "SELECT 
                d.name as department,
                c.name as course,
                dr.document_request,
                dr.purpose,
                COUNT(*) as total_requests,
                SUM(CASE WHEN dr.status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN dr.status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN dr.status = 'Rejected' THEN 1 ELSE 0 END) as rejected
              FROM document_requests dr
              JOIN tbl_student s ON dr.student_number = s.student_id
              JOIN sections sec ON s.section_id = sec.id
              JOIN departments d ON sec.department_id = d.id
              JOIN courses c ON sec.course_id = c.id
              WHERE dr.request_time BETWEEN ? AND ?";
    
    $params = [$filters['start_date'], $filters['end_date']];
    $types = "ss";
    
    // Add department filter if selected
    if (!empty($filters['department']) && $filters['department'] !== 'all') {
        $query .= " AND d.id = ?";
        $params[] = $filters['department'];
        $types .= "i";
    }
    
    // Add course filter if selected
    if (!empty($filters['course']) && $filters['course'] !== 'all') {
        $query .= " AND c.id = ?";
        $params[] = $filters['course'];
        $types .= "i";
    }
    
    // Add document filter if selected
    if (!empty($filters['document_type']) && $filters['document_type'] !== 'all') {
        if ($filters['document_type'] === 'good_moral') {
            $query .= " AND dr.document_request = 'Good Moral'";
        } else if ($filters['document_type'] === 'others') {
            $query .= " AND dr.document_request != 'Good Moral'";
        } else {
            $query .= " AND dr.document_request = ?";
            $params[] = $filters['document_type'];
            $types .= "s";
        }
    }
    
    // Add purpose filter if selected
    if (!empty($filters['purpose']) && $filters['purpose'] !== 'all') {
        $query .= " AND dr.purpose = ?";
        $params[] = $filters['purpose'];
        $types .= "s";
    }
    
    // Group by selected parameters
    $groupBy = [];
    
    if ($filters['group_by_department'] === 'yes') {
        $groupBy[] = "d.name";
    }
    
    if ($filters['group_by_course'] === 'yes') {
        $groupBy[] = "c.name";
    }
    
    if ($filters['group_by_document'] === 'yes') {
        $groupBy[] = "dr.document_request";
    }
    
    if ($filters['group_by_purpose'] === 'yes') {
        $groupBy[] = "dr.purpose";
    }
    
    if (!empty($groupBy)) {
        $query .= " GROUP BY " . implode(', ', $groupBy);
    }
    
    $stmt = $connection->prepare($query);
    if ($stmt === false) {
        return ['error' => "Error preparing statement: " . $connection->error];
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

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

// Check if required form fields are submitted
if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    echo json_encode(['error' => 'Required date fields are missing']);
    exit();
}

// Get filter data from AJAX request
$filters = [
    'department' => $_POST['department'] ?? 'all',
    'course' => $_POST['course'] ?? 'all',
    'document_type' => $_POST['document_type'] ?? 'all',
    'purpose' => $_POST['purpose'] ?? 'all',
    'start_date' => $_POST['start_date'] . ' 00:00:00',
    'end_date' => $_POST['end_date'] . ' 23:59:59',
    'group_by_department' => $_POST['group_by_department'] ?? 'no',
    'group_by_course' => $_POST['group_by_course'] ?? 'no',
    'group_by_document' => $_POST['group_by_document'] ?? 'no',
    'group_by_purpose' => $_POST['group_by_purpose'] ?? 'no'
];

// Store in session for PDF generation
$_SESSION['report_filters'] = $filters;

// Generate report
$reportData = generateReport($connection, $filters);
$_SESSION['report_data'] = $reportData;

// Generate HTML for the table
$html = '';

if (empty($reportData)) {
    $html = '
    <div class="no-data-container text-center py-5">
        <i class="fas fa-file-alt no-data-icon fa-5x text-muted mb-3"></i>
        <h4 class="text-muted">No data available for the selected criteria</h4>
        <p class="text-muted">Try adjusting your filters or date range</p>
    </div>';
} else {
    $html .= '
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>';
    
    // Add table headers based on grouping options
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
    
    // Add table rows
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
    
    if ($colspan > 0) {
        $html .= '<td colspan="' . $colspan . '"><strong>Total</strong></td>';
    }
    
    $html .= '
        <td><strong>' . $totals['total_requests'] . '</strong></td>
        <td><strong>' . $totals['pending'] . '</strong></td>
        <td><strong>' . $totals['approved'] . '</strong></td>
        <td><strong>' . $totals['rejected'] . '</strong></td>
    </tr>';
    
    $html .= '
            </tbody>
        </table>
    </div>';
}

// Return HTML and period as JSON
header('Content-Type: application/json');
echo json_encode([
    'html' => $html,
    'period' => date('F j, Y', strtotime($filters['start_date'])) . ' - ' . date('F j, Y', strtotime($filters['end_date']))
]);