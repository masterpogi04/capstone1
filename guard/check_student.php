<?php
include '../db.php';

if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    // Modified query to check specifically for CEIT students
    $stmt = $connection->prepare("
        SELECT ts.student_id 
        FROM tbl_student ts
        JOIN sections s ON ts.section_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN departments d ON c.department_id = d.id
        WHERE ts.student_id = ? 
        AND d.name LIKE '%Department of%'  -- This ensures it's a CEIT department
    ");
    
    if (!$stmt) {
        echo json_encode([
            'exists' => false,
            'message' => 'Database error'
        ]);
        exit();
    }
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exists = $result->num_rows > 0;
    
    echo json_encode([
        'exists' => $exists,
        'message' => $exists ? 'Valid CEIT student' : 'Student is not from CEIT or not found'
    ]);
} else {
    echo json_encode([
        'exists' => false,
        'message' => 'No student ID provided'
    ]);
}
?>