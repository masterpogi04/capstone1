<?php
include '../db.php';

if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    $stmt = $connection->prepare("
        SELECT 
            ts.first_name,
            ts.middle_name,
            ts.last_name,
            c.name AS course_name,
            s.year_level,
            s.section_no,
            CONCAT(ta.first_name, ' ', CASE 
                WHEN ta.middle_initial IS NOT NULL AND ta.middle_initial != '' 
                THEN CONCAT(ta.middle_initial, '. ') 
                ELSE '' 
            END, ta.last_name) AS adviser_name
        FROM tbl_student ts
        JOIN sections s ON ts.section_id = s.id
        JOIN courses c ON s.course_id = c.id
        LEFT JOIN tbl_adviser ta ON s.adviser_id = ta.id
        WHERE ts.student_id = ? AND ts.status = 'active'
    ");
    
    if (!$stmt) {
        die("Error preparing statement: " . $connection->error);
    }
    
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Format student's full name
        $student_name = $student['first_name'];
        if (!empty($student['middle_name'])) {
            $student_name .= ' ' . substr($student['middle_name'], 0, 1) . '.';
        }
        $student_name .= ' ' . $student['last_name'];
        
        echo json_encode([
            'success' => true,
            'name' => $student_name,
            'year_course' => $student['year_level'] . ' ' . $student['course_name'] . ' - ' . $student['section_no'],
            'adviser' => $student['adviser_name'] ?? 'Not Assigned'
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>