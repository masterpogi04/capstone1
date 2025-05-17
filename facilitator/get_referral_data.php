<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get parameters
$academic_year = $_GET['academic_year'] ?? '';
$status = $_GET['status'] ?? 'Pending';
$department = $_GET['department'] ?? '';
$course = $_GET['course'] ?? '';
$reason = $_GET['reason'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

try {
    // Base query
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
    if (!empty($department)) {
        $query .= " AND d.id = ?";
        $params[] = $department;
        $types .= "i";
    }

    // Add course filter
    if (!empty($course)) {
        $query .= " AND c.id = ?";
        $params[] = $course;
        $types .= "i";
    }

    // Add reason filter
    if (!empty($reason)) {
        $query .= " AND r.reason_for_referral = ?";
        $params[] = $reason;
        $types .= "s";
    }

    // Simpler academic year filter
    if (!empty($academic_year)) {
        $query .= " AND YEAR(r.date) = ?";
        $params[] = $academic_year;
        $types .= "i";
        
        // Debug logging
        error_log("Academic Year Filter Debug:");
        error_log("Selected Year: " . $academic_year);
        error_log("Full Query: " . $query);
    }

    // Add date range filters
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

    // Add sorting
    $query .= " ORDER BY r.date DESC";

    // Prepare and execute query
    if (!empty($params)) {
        $stmt = $connection->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connection->query($query);
    }

    // Fetch results
    $referrals = [];
    while ($row = $result->fetch_assoc()) {
        $referrals[] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'],
            'last_name' => $row['last_name'],
            'course_year' => $row['course_year'],
            'reason_for_referral' => $row['reason_for_referral'],
            'status' => $row['status'],
            'violation_details' => $row['violation_details'],
            'other_concerns' => $row['other_concerns'],
            'department' => $row['department_name'],
            'course' => $row['course_name']
        ];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($referrals);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>