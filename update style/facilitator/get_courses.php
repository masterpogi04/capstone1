<?php
include '../db.php';

if (isset($_GET['department_id'])) {
    $department_id = intval($_GET['department_id']);
    
    $course_query = "SELECT * FROM courses WHERE department_id = ? ORDER BY name";
    $stmt = $connection->prepare($course_query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = array();
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
    }
}
?>