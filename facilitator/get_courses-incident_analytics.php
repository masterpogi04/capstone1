<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$department_id = $_GET['department_id'] ?? '';

try {
    $query = "SELECT id, name FROM courses WHERE department_id = ? AND status = 'active' ORDER BY name";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($courses);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>