<?php
session_start();
include '../db.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Function to get all departments with their courses
function getDepartmentsWithCourses() {
    global $connection;
    $stmt = $connection->prepare("
        SELECT d.id AS dept_id, d.name AS dept_name, c.id AS course_id, c.name AS course_name
        FROM departments d
        LEFT JOIN courses c ON d.id = c.department_id
        ORDER BY d.name, c.name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($departments[$row['dept_id']])) {
            $departments[$row['dept_id']] = [
                'id' => $row['dept_id'],
                'name' => $row['dept_name'],
                'courses' => []
            ];
        }
        if ($row['course_id']) {
            $departments[$row['dept_id']]['courses'][] = [
                'id' => $row['course_id'],
                'name' => $row['course_name']
            ];
        }
    }
    return $departments;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_department'])) {
        $dept_name = trim($_POST['department_name']);
        $stmt = $connection->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->bind_param("s", $dept_name);
        $stmt->execute();
    } elseif (isset($_POST['add_course'])) {
        $course_name = trim($_POST['course_name']);
        $dept_id = $_POST['department_id'];
        $stmt = $connection->prepare("INSERT INTO courses (name, department_id) VALUES (?, ?)");
        $stmt->bind_param("si", $course_name, $dept_id);
        $stmt->execute();
    } elseif (isset($_POST['edit_department'])) {
        $dept_id = $_POST['dept_id'];
        $dept_name = trim($_POST['dept_name']);
        $stmt = $connection->prepare("UPDATE departments SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $dept_name, $dept_id);
        $stmt->execute();
    } elseif (isset($_POST['edit_course'])) {
        $course_id = $_POST['course_id'];
        $course_name = trim($_POST['course_name']);
        $stmt = $connection->prepare("UPDATE courses SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $course_name, $course_id);
        $stmt->execute();
    }
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$departments = getDepartmentsWithCourses();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Departments and Courses</title>
    <link rel="stylesheet" type="text/css" href="admin_styles.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css/" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<style>
    .table th {
        font-weight: 600;
    }
    .btn {
        border-radius: 20px;
    }
    .card {
        border-radius: 15px;
    }
    .modal-content {
        border-radius: 10px;
    }
   .edit-btn {
    width: 35px;
    height: 35px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0px;  
    color:  #FFC107;;
    cursor: pointer;
}
.theadstyle {
    background: #008F57;
    color: white;
}


</style>
<body>
    <div class="header">
        CAVITE STATE UNIVERSITY-MAIN
    </div>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content bg-light">
    <div class="dashboard-container py-5">
            <div class="container">
                <h1>Manage Departments and Courses of the <b>College of Engineering and Information Technology</b></h1>
                <br><br>
                <div class="d-flex justify-content-end mb-4">
                <button class="btn btn-primary mr-2" data-toggle="modal" data-target="#addDepartmentModal">
                    <i class="fas fa-plus-circle"></i> Add Department</button>
                <button class="btn btn-info" data-toggle="modal" data-target="#addCourseModal">
                <i class="fas fa-plus-circle"></i> Add Course</button>
                </div>

                <table class="table table-bordered">
                <thead class="theadstyle">
                        <tr>
                            <th>Department</th>
                            <th>Courses</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                        <td class="align-middle font-weight-bold"><?php echo htmlspecialchars($dept['name']); ?></td>
                            <td>
                                <?php if (!empty($dept['courses'])): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($dept['courses'] as $course): ?>
                                            <li class=" d-flex justify-content-between align-items-center  px-0 py-2">
                                                <?php echo htmlspecialchars($course['name']); ?>
                                                <button class="edit-btn" 
                                                onclick="editCourse(<?php echo $course['id']; ?>, '<?php echo addslashes($course['name']); ?>')" 
                                                data-toggle="tooltip"
                                                title="Edit Programs">
                                                <i class="fas fa-edit"></i></button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    No courses
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-warning" onclick="editDepartment(<?php echo $dept['id']; ?>, '<?php echo addslashes($dept['name']); ?>')">Edit Department</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Department Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentModalLabel">Add Department</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <input type="text" class="form-control" name="department_name" required placeholder="Department Name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_department" class="btn btn-primary">Add Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" role="dialog" aria-labelledby="addCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCourseModalLabel">Add Course</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <select name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="course_name" required placeholder="Course Name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_course" class="btn btn-primary">Add Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="dept_id" id="edit_dept_id">
                        <div class="form-group">
                            <input type="text" class="form-control" name="dept_name" id="edit_dept_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="edit_department" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div class="modal fade" id="editCourseModal" tabindex="-1" role="dialog" aria-labelledby="editCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCourseModalLabel">Edit Course</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="course_id" id="edit_course_id">
                        <div class="form-group">
                            <input type="text" class="form-control" name="course_name" id="edit_course_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="edit_course" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Contact number | Email | Copyright</p>
    </div>

    <script>
        
    function editDepartment(id, name) {
        $('#edit_dept_id').val(id);
        $('#edit_dept_name').val(name);
        $('#editDepartmentModal').modal('show');
    }

    function editCourse(id, name) {
        $('#edit_course_id').val(id);
        $('#edit_course_name').val(name);
        $('#editCourseModal').modal('show');
    }
    

    </script>
</body>
</html>