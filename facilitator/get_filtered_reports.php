<?php
// get_filtered_reports.php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? $connection->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'meeting_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$filter_schedule = isset($_GET['filter_schedule']) ? $_GET['filter_schedule'] : '';
$filter_course = isset($_GET['filter_course']) ? $connection->real_escape_string($_GET['filter_course']) : '';

// Build the query
$query = "SELECT ir.*, sv.status as violation_status, s.first_name, s.last_name, 
          GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
          ir.description, 
          (SELECT meeting_date FROM meetings WHERE incident_report_id = ir.id ORDER BY meeting_date DESC LIMIT 1) as meeting_date,
          (SELECT COUNT(*) FROM meetings 
           WHERE incident_report_id = ir.id 
           AND meeting_minutes IS NOT NULL 
           AND TRIM(meeting_minutes) != '') as meeting_minutes_count,
          c.name as course_name
          FROM incident_reports ir 
          JOIN student_violations sv ON ir.id = sv.incident_report_id
          JOIN tbl_student s ON sv.student_id = s.student_id
          LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
          LEFT JOIN sections sec ON s.section_id = sec.id
          LEFT JOIN courses c ON sec.course_id = c.id
          WHERE (ir.status = 'For Meeting' OR ir.status = 'Approved' OR ir.status = 'Rescheduled')";

if (!empty($search)) {
    $query .= " AND (s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR ir.description LIKE '%$search%')";
}

if ($filter_schedule === 'scheduled') {
    $query .= " AND EXISTS (SELECT 1 FROM meetings m WHERE m.incident_report_id = ir.id)";
} elseif ($filter_schedule === 'unscheduled') {
    $query .= " AND NOT EXISTS (SELECT 1 FROM meetings m WHERE m.incident_report_id = ir.id)";
}

if (!empty($filter_course)) {
    $query .= " AND c.name = '$filter_course'";
}

$query .= " GROUP BY ir.id ORDER BY $sort $order";

$result = $connection->query($query);

if ($result === false) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $connection->error]);
    exit();
}

// Fetch all results
$reports = [];
while ($row = $result->fetch_assoc()) {
    // We don't convert names to proper case here since that will be handled by the JavaScript
    // This ensures consistency in how names are displayed
    $reports[] = $row;
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($reports);
exit();
?>