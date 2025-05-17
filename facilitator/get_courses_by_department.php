<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if department_id is set in the POST request
if (!isset($_POST['department_id']) || empty($_POST['department_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Department ID is required']);
    exit();
}

$departmentId = $_POST['department_id'];

// Query to get courses by department
$query = "SELECT id, name FROM courses WHERE department_id = ? AND status = 'active' ORDER BY name";
$stmt = $connection->prepare($query);

if (!$stmt) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $connection->error]);
    exit();
}

$stmt->bind_param("i", $departmentId);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Return courses as JSON
header('Content-Type: application/json');
echo json_encode($courses);
exit();