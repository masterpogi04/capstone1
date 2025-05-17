<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    die("Unauthorized access");
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_courses':
        getCourses();
        break;
    case 'get_year_levels':
        getYearLevels();
        break;
    case 'get_sections':
        getSections();
        break;
    case 'get_students':
        getStudents();
        break;
    case 'search_students':
        searchStudents();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getCourses() {
    global $connection;
    $department_id = $_GET['department_id'] ?? null;

    if ($department_id) {
        $stmt = $connection->prepare("SELECT id, name FROM courses WHERE department_id = ?");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = $result->fetch_all(MYSQLI_ASSOC);

        $output = '<option value="">Select a course</option>';
        foreach ($courses as $course) {
            $output .= '<option value="' . $course['id'] . '">' . htmlspecialchars($course['name']) . '</option>';
        }
        echo $output;
    } else {
        echo '<option value="">Select a course</option>';
    }
}

function getYearLevels() {
    global $connection;
    $department_id = $_GET['department_id'] ?? null;
    $course_id = $_GET['course_id'] ?? null;

    if ($department_id && $course_id) {
        $stmt = $connection->prepare("SELECT DISTINCT year_level FROM sections WHERE department_id = ? AND course_id = ? ORDER BY year_level");
        $stmt->bind_param("ii", $department_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $year_levels = $result->fetch_all(MYSQLI_ASSOC);

        $output = '<option value="">Select a year level</option>';
        foreach ($year_levels as $year) {
            $output .= '<option value="' . $year['year_level'] . '">' . htmlspecialchars($year['year_level']) . '</option>';
        }
        echo $output;
    } else {
        echo '<option value="">Select a year level</option>';
    }
}

function getSections() {
    global $connection;
    $department_id = $_GET['department_id'] ?? null;
    $course_id = $_GET['course_id'] ?? null;
    $year_level = $_GET['year_level'] ?? null;

    if ($department_id && $course_id && $year_level) {
        $stmt = $connection->prepare("SELECT id, section_no FROM sections WHERE department_id = ? AND course_id = ? AND year_level = ?");
        if ($stmt === false) {
            error_log("Prepare failed: " . $connection->error);
            echo '<option value="">Error preparing statement</option>';
            return;
        }
        $stmt->bind_param("iis", $department_id, $course_id, $year_level);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            echo '<option value="">Error executing query</option>';
            return;
        }
        $result = $stmt->get_result();
        $sections = $result->fetch_all(MYSQLI_ASSOC);

        $output = '<option value="">Select a section</option>';
        foreach ($sections as $section) {
            $output .= '<option value="' . $section['id'] . '">' . htmlspecialchars($section['section_no']) . '</option>';
        }
        echo $output;
    } else {
        echo '<option value="">Select a section</option>';
    }
}

function getStudents() {
    global $connection;
    $section_id = $_GET['section_id'] ?? null;
    $department_id = $_GET['department_id'] ?? null;
    $course_id = $_GET['course_id'] ?? null;
    $year_level = $_GET['year_level'] ?? null;

    if ($section_id) {
        $stmt = $connection->prepare("SELECT student_id, first_name, last_name FROM tbl_student WHERE section_id = ?");
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);

        if (count($students) > 0) {
            $output = '<h3>Students in Selected Section</h3>';
            $output .= '<table class="table table-striped">';
            $output .= '<thead><tr><th>Student ID</th><th>Name</th><th>Action</th></tr></thead>';
            $output .= '<tbody>';
            foreach ($students as $student) {
                $output .= '<tr>';
                $output .= '<td>' . htmlspecialchars($student['student_id']) . '</td>';
                $output .= '<td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>';
                $output .= '<td><a href="view_student_profile.php?student_id=' . $student['student_id'] . 
                           '&department_id=' . $department_id . 
                           '&course_id=' . $course_id . 
                           '&year_level=' . urlencode($year_level) . 
                           '&section_id=' . $section_id . 
                           '" class="btn btn-primary btn-sm">View Profile</a></td>';
                $output .= '</tr>';
            }
            $output .= '</tbody></table>';
        } else {
            $output = '<p>No students found in this section.</p>';
        }
        echo $output;
    } else {
        echo '<p>Please select a section to view students.</p>';
    }
}

// function for searching students in view_profiles
function searchStudents() {
    global $connection;
    $search_term = '%' . ($_GET['search'] ?? '') . '%';

    $stmt = $connection->prepare("
        SELECT ts.student_id, ts.first_name, ts.last_name, 
               d.name AS department_name, c.name AS course_name, 
               s.year_level, s.section_no, s.id AS section_id,
               d.id AS department_id, c.id AS course_id
        FROM tbl_student ts
        JOIN sections s ON ts.section_id = s.id
        JOIN departments d ON s.department_id = d.id
        JOIN courses c ON s.course_id = c.id
        WHERE ts.student_id LIKE ? OR ts.first_name LIKE ? OR ts.last_name LIKE ?
        LIMIT 50
    ");
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);

    if (count($students) > 0) {
        $output = '<h3>Search Results</h3>';
        $output .= '<table class="table table-striped">';
        $output .= '<thead><tr><th>Student ID</th><th>Name</th><th>Department</th><th>Course</th><th>Year Level</th><th>Section</th><th style="width: 250px;">Action</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($students as $student) {
            $output .= '<tr>';
            $output .= '<td>' . htmlspecialchars($student['student_id']) . '</td>';
            $output .= '<td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>';
            $output .= '<td>' . htmlspecialchars($student['department_name']) . '</td>';
            $output .= '<td>' . htmlspecialchars($student['course_name']) . '</td>';
            $output .= '<td>' . htmlspecialchars($student['year_level']) . '</td>';
            $output .= '<td>' . htmlspecialchars($student['section_no']) . '</td>';
            $output .= '<td><div class="btn-group" role="group">';
            $output .= '<a href="view_student_profile.php?student_id=' . urlencode($student['student_id']) . 
                       '&department_id=' . urlencode($student['department_id']) . 
                       '&course_id=' . urlencode($student['course_id']) . 
                       '&year_level=' . urlencode($student['year_level']) . 
                       '&section_id=' . urlencode($student['section_id']) . 
                       '" class="btn btn-primary btn-sm"><i class="fas fa-user"></i> View Profile</a>';
            $output .= '<a href="view_student_incident_history.php?student_id=' . urlencode($student['student_id']) . 
                       '" class="btn btn-info btn-sm ml-1"><i class="fas fa-history"></i> View History</a>';
            $output .= '</div></td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
    } else {
        $output = '<p>No students found matching your search. Kindly check your input.</p>';
    }
    echo $output;
}
?>