<?php
session_start();
include '../db.php';

// Check for admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Get filter parameters
$start_date = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null;
$status = isset($_POST['status']) && !empty($_POST['status']) ? $_POST['status'] : null;

// Debug logging
error_log("fetch_departments_data.php - Received parameters: " . 
          "start_date=" . ($start_date ?? 'null') . 
          ", end_date=" . ($end_date ?? 'null') . 
          ", status=" . ($status ?? 'null'));

// Function to get departments with incident report counts
function getDepartmentsWithReportCounts($connection, $start_date = null, $end_date = null, $status = null) {
    $date_condition = "";
    $status_condition = "";
    $params = [];
    $types = "";
    
    if ($start_date && $end_date) {
        $date_condition = " AND ir.date_reported BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }
    
    if ($status) {
        // Use the exact status value from the request
        $status_condition = " AND sv.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $query = "SELECT d.id, d.name, COUNT(DISTINCT ir.id) as report_count
              FROM departments d
              LEFT JOIN courses c ON d.id = c.department_id
              LEFT JOIN sections s ON c.id = s.course_id
              LEFT JOIN student_violations sv ON sv.section_id = s.id
              LEFT JOIN incident_reports ir ON sv.incident_report_id = ir.id
              WHERE d.status = 'active' $date_condition $status_condition
              GROUP BY d.id, d.name
              ORDER BY COUNT(DISTINCT ir.id) DESC";
    
    error_log("SQL Query: " . $query);
    error_log("Parameters: " . print_r($params, true));
    
    $stmt = $connection->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $departments = [];
    
    while ($row = $result->fetch_assoc()) {
        // Ensure report_count is an integer
        $row['report_count'] = (int)$row['report_count'];
        $departments[] = $row;
    }
    
    return $departments;
}

try {
    // Get department data with filters
    $departments = getDepartmentsWithReportCounts($connection, $start_date, $end_date, $status);
    
    // Return success response with data
    echo json_encode([
        'success' => true,
        'departments' => $departments
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching department data: ' . $e->getMessage()
    ]);
}

// Close database connection
mysqli_close($connection);
?>