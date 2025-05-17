<?php
session_start();
include '../db.php';

// Check for admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Get parameters
$department_id = isset($_POST['department_id']) ? $_POST['department_id'] : null;
$start_date = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null;
$status = isset($_POST['status']) && !empty($_POST['status']) ? $_POST['status'] : null;

// Debug logging
error_log("fetch_courses_data.php - Received parameters: " . 
          "department_id=" . ($department_id ?? 'null') . 
          ", start_date=" . ($start_date ?? 'null') . 
          ", end_date=" . ($end_date ?? 'null') . 
          ", status=" . ($status ?? 'null'));

if (!$department_id) {
    echo json_encode(['success' => false, 'error' => 'Department ID is required']);
    exit();
}

// Function to get selected department data
function getDepartmentData($connection, $department_id, $start_date = null, $end_date = null, $status = null) {
    $date_condition = "";
    $status_condition = "";
    $params = [$department_id];
    $types = "i";
    
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
              WHERE d.id = ? AND d.status = 'active' $date_condition $status_condition
              GROUP BY d.id, d.name";
    
    error_log("Department Data SQL Query: " . $query);
    error_log("Parameters: " . print_r($params, true));
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Ensure report_count is an integer
        $row['report_count'] = (int)$row['report_count'];
        return $row;
    }
    
    return null;
}

// Function to get courses with incident report counts for a specific department
function getCoursesWithReportCounts($connection, $department_id, $start_date = null, $end_date = null, $status = null) {
    $date_condition = "";
    $status_condition = "";
    $params = [$department_id];
    $types = "i";
    
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
    
    $query = "SELECT c.id, c.name, COUNT(DISTINCT ir.id) as report_count
              FROM courses c
              LEFT JOIN sections s ON c.id = s.course_id
              LEFT JOIN student_violations sv ON sv.section_id = s.id
              LEFT JOIN incident_reports ir ON sv.incident_report_id = ir.id
              WHERE c.department_id = ? AND c.status = 'active' $date_condition $status_condition
              GROUP BY c.id, c.name
              ORDER BY COUNT(DISTINCT ir.id) DESC";
    
    error_log("Courses SQL Query: " . $query);
    error_log("Parameters: " . print_r($params, true));
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = [];
    
    while ($row = $result->fetch_assoc()) {
        // Ensure report_count is an integer
        $row['report_count'] = (int)$row['report_count'];
        $courses[] = $row;
    }
    
    return $courses;
}

try {
    // Get department data
    $department = getDepartmentData($connection, $department_id, $start_date, $end_date, $status);
    
    if (!$department) {
        echo json_encode(['success' => false, 'error' => 'Department not found']);
        exit();
    }
    
    // Get course data
    $courses = getCoursesWithReportCounts($connection, $department_id, $start_date, $end_date, $status);
    
    // Return success response with data
    echo json_encode([
        'success' => true,
        'department' => $department,
        'courses' => $courses
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching course data: ' . $e->getMessage()
    ]);
}

// Close database connection
mysqli_close($connection);
?>