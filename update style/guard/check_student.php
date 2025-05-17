<?php
include '../db.php';

if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    $stmt = $connection->prepare("SELECT student_id FROM tbl_student WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
} else {
    echo json_encode(['exists' => false]);
}
?>