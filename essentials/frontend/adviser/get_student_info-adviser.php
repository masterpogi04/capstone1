<?php
include '../db.php';

if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    $stmt = $connection->prepare("
        SELECT ts.first_name, ts.last_name, ts.email, c.name AS course_name, 
               s.year_level, s.section_no, ta.name AS adviser_name, 
               d.name AS department_name
        FROM tbl_student ts
        JOIN sections s ON ts.section_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN departments d ON c.department_id = d.id
        LEFT JOIN tbl_adviser ta ON s.adviser_id = ta.id
        WHERE ts.student_id = ?
    ");
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'name' => $student['first_name'] . ' ' . $student['last_name'],
            'email' => $student['email'],
            'year_course' => $student['year_level'] . ' ' . $student['course_name'] . ' - ' . $student['section_no'],
            'department' => $student['department_name'],
            'adviser' => $student['adviser_name'] ?? 'Not Assigned',
            'full_info' => $student['first_name'] . ' ' . $student['last_name'] . ' - ' . 
                           $student['year_level'] . ' ' . $student['course_name'] . ' - ' . 
                           $student['section_no'] . ' (' . $student['department_name'] . ')'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>