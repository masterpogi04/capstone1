<?php
session_start();
include "../db.php";
include "adviser_sidebar.php";

// Check if the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: login.php");
    exit();
}

// Check if database connection is established
if (!isset($connection) || $connection->connect_error) {
    die("Database connection failed: " . ($connection->connect_error ?? "Unknown error"));
}

$adviser_id = $_SESSION['user_id'];
$message = '';

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['section_id'])) {
        $section_id = $_POST['section_id'];
        
        // Start transaction
        $connection->begin_transaction();

        try {
            // Delete the students associated with this section
            $delete_students_stmt = $connection->prepare("DELETE FROM tbl_student WHERE section_id = ?");
            $delete_students_stmt->bind_param("i", $section_id);
            $delete_students_stmt->execute();
            $affected_students = $delete_students_stmt->affected_rows;
            $delete_students_stmt->close();

            // Now delete the section
            $delete_section_stmt = $connection->prepare("DELETE FROM sections WHERE id = ? AND adviser_id = ?");
            $delete_section_stmt->bind_param("ii", $section_id, $adviser_id);
            $delete_section_stmt->execute();
            
            if ($delete_section_stmt->affected_rows > 0) {
                // Commit the transaction
                $connection->commit();
                $message = "Section successfully deleted. $affected_students student(s) were also removed.";
            } else {
                throw new Exception("No section found or you do not have permission to delete this section.");
            }
            
            $delete_section_stmt->close();
        } catch (Exception $e) {
            // An error occurred, rollback the transaction
            $connection->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch sections added by the logged-in adviser along with student count and department/course names
$sql = "SELECT s.*, d.name AS department_name, c.name AS course_name, COUNT(st.student_id) as student_count 
        FROM sections s
        LEFT JOIN tbl_student st ON s.id = st.section_id
        LEFT JOIN departments d ON d.id = s.department_id
        LEFT JOIN courses c ON c.id = s.course_id
        WHERE s.adviser_id = ?
        GROUP BY s.id";

$stmt = $connection->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $connection->error);
}

$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();
$sections = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>View Sections</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
         .main-content {
                margin-left: 250px;
                padding: 40px;
                padding-top: 60px;
            }

    .container {
                max-width: 1200px;
                margin: 0 auto;
                background-color: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

        
        h2 {
        color: #00674b;
        font-weight: 600;
        font-size: 2rem;
        text-align: center;
        margin-top: 30px;
        margin-bottom: 30px;
        padding-bottom: 10px;
        border-bottom: 2px solid #00674b;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    @media (max-width: 768px) {
        h2 {
            font-size: 1.5rem;
        }
    }
  

        /* Table Styles */
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
        background-color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    th,td {
        padding: 7px 12px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }
    
    th {
        background: #009E60;
        color: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 14px;
    }

    td {
       
        font-size: 16px;
    }

    tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    td:nth-child(6) {
    text-align: center; /* Centers the number */
    }

    /* Also center the header for number of students (column) */
    th:nth-child(6) {
        text-align: center;
    }
    td:nth-child(4) {
    text-align: center; /* Centers the number */
    }

    /* Also center the header for number of students (column) */
    th:nth-child(4) {
        text-align: center;
    }

    tr {
        transition: all 0.3s ease;
    }

    tr:hover {
        background-color: #e9ecef;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .actions-cell {
        white-space: nowrap;
    }
  
    .view-btn, .delete-btn {
        display: inline-block;
            padding: 8px 15px;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            transition: all 0.3s ease;
            margin-right: 10px;
            border: none;
    }

    .view-btn {
        background-color: #3498db; /* Keeping the original blue color */
        color: white;
        margin-right: 5px;
    }

    .view-btn:hover {
        opacity: 0.8;
    }

    .delete-btn {
        background-color: #e74c3c; /* Keeping the original red color */
        color: white;
    }

    .delete-btn:hover {
        opacity: 0.8;
    }

    @media (max-width: 768px) {
        .view-btn, .delete-btn {
            padding: 4px 8px;
            font-size: 0.8em;
            min-width: 50px;
        }
    }
  
    </style>
</head>

<body>
<div class="header">
        <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'adviser_sidebar.php'; ?> 
    
    <div class="main-content">
      
            <h2>Your Sections</h2>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <?php if (count($sections) > 0): ?>
                <table id="sectionsTable">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th style="text-align: center;">Section No</th>
                            <th>Academic Year</th>
                            <th style="text-align: center;">Number of Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $section): ?>
                            <tr id="section-<?php echo $section['id']; ?>">
                                <td><?php echo htmlspecialchars($section['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($section['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($section['year_level']); ?></td>
                                <td><?php echo htmlspecialchars($section['section_no']); ?></td>
                                <td><?php echo htmlspecialchars($section['academic_year']); ?></td>
                                <td><?php echo $section['student_count']; ?></td>
                                <td class="actions-cell">
                                    <a href="section_details.php?section_id=<?php echo $section['id']; ?>" class="view-btn">View</a>
                                     <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this section? This action cannot be undone and will also delete all associated students.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                        <button type="submit" class="delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-sections">No added sections in your account.</p>
            <?php endif; ?>
        </div>
    </div>
    <br>    <br>
    <div class="footer">
        <p>Contact: (123) 456-7890 | Email: info@cvsu.edu.ph | © 2024 Cavite State University. All rights reserved.</p>
    </div>
</body>
</html>