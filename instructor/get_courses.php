<?php
include '../db.php';

if (isset($_POST['department_id'])) {
    $dept_id = $connection->real_escape_string($_POST['department_id']);
    
    $query = "SELECT id, name FROM courses WHERE department_id = ? ORDER BY name";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = array();
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    echo json_encode($courses);
}
?>