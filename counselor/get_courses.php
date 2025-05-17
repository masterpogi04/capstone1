<?php
include '../db.php';

$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';

$query = "SELECT id, name FROM courses WHERE department_id = ? ORDER BY name";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

header('Content-Type: application/json');
echo json_encode($courses);
?>