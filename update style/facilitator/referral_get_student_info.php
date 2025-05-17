<?php
include '../db.php';
if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    $stmt = $connection->prepare("
        SELECT ts.first_name, ts.middle_name, ts.last_name, 
               c.name AS course_name, s.year_level, s.section_no,
               CONCAT(ta.first_name, ' ', ta.middle_initial, ' ', ta.last_name) AS adviser_name,
               d.name AS department_name
        FROM tbl_student ts
        JOIN sections s ON ts.section_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN departments d ON c.department_id = d.id
        LEFT JOIN tbl_adviser ta ON s.adviser_id = ta.id
        WHERE ts.student_id = ?
    ");
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
        exit;
    }
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Build the full name
        $fullName = $student['first_name'];
        if (!empty($student['middle_name'])) {
            $fullName .= ' ' . $student['middle_name'];
        }
        $fullName .= ' ' . $student['last_name'];
        
        // Format the course information
        $courseInfo = $student['year_level'];
        if (!empty($student['course_name'])) {
            $courseInfo .= ' ' . $student['course_name'];
        }
        if (!empty($student['section_no'])) {
            $courseInfo .= ' - ' . $student['section_no'];
        }
        
        echo json_encode([
            'success' => true,
            'name' => trim($fullName),
            'first_name' => $student['first_name'],
            'middle_name' => $student['middle_name'],
            'last_name' => $student['last_name'],
            'year_course' => trim($courseInfo),
            'adviser_name' => $student['adviser_name'],
            'department' => $student['department_name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>