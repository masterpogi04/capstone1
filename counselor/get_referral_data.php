<?php
session_start();
include '../db.php';

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

try {
    // Base query
    $query = "SELECT 
                r.*,
                s.first_name,
                s.last_name,
                s.middle_name,
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
    if (!empty($start_date)) {
        $query .= " AND r.date >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if (!empty($end_date)) {
        // Add 1 day to end_date to include the end date in results
        $end_date_plus_one = date('Y-m-d', strtotime($end_date . ' +1 day'));
        $query .= " AND r.date < ?";
        $params[] = $end_date_plus_one;
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

    // Add default sorting (newest first)
    $query .= " ORDER BY r.date DESC";

    // Prepare and execute query
    $stmt = $connection->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

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