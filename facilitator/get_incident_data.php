<?php
// Prevent any output before headers
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
include '../db.php';

header('Content-Type: application/json');

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $status = $_GET['status'] ?? 'Pending';
    $department = $_GET['department'] ?? '';
    $course = $_GET['course'] ?? '';
    $academic_year = $_GET['academic_year'] ?? '';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    // Prepare query with improved joins
    $query = "SELECT DISTINCT 
                ir.id,
                ir.date_reported,
                ir.status,
                ir.description,
                ir.place,
                ir.reported_by,
                d.id as department_id, 
                d.name as department_name,
                c.id as course_id,
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

    // Add status filter
    if ($status !== 'all') {
        $query .= " AND ir.status = ?";
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

    // Add academic year filter
    if ($academic_year) {
        $start_academic_year = $academic_year . '-06-01';
        $end_academic_year = ($academic_year + 1) . '-05-31';
        $query .= " AND ir.date_reported BETWEEN ? AND ?";
        $params[] = $start_academic_year;
        $params[] = $end_academic_year;
        $types .= "ss";
    }

    // Add date range filters
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
    $query .= " GROUP BY ir.id 
                ORDER BY ir.date_reported DESC";

    // Log the query for debugging
    error_log("Query: " . $query);
    error_log("Parameters: " . print_r($params, true));

    // Prepare and execute the statement
    $stmt = $connection->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $connection->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    // Process results
    $incidents = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Only add to incidents if there's a department (for the chart)
            if (!empty($row['department_name'])) {
                $incidents[] = [
                    'id' => $row['id'],
                    'date_reported' => $row['date_reported'],
                    'status' => $row['status'],
                    'description' => $row['description'],
                    'place' => $row['place'],
                    'reported_by' => $row['reported_by'],
                    'department_id' => $row['department_id'],
                    'department_name' => $row['department_name'],
                    'course_id' => $row['course_id'],
                    'course_name' => $row['course_name']
                ];
            }
        }
    }

    // Log the results
    error_log("Found " . count($incidents) . " incidents");
    
    // Return the results
    echo json_encode($incidents);
    
} catch (Exception $e) {
    error_log("Error in get_incident_data.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'An error occurred while fetching data',
        'details' => $e->getMessage()
    ]);
}

// Clear any output buffers
ob_end_flush();
?>