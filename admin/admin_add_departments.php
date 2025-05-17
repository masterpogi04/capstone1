<?php
session_start();
include '../db.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Function to get all departments with their courses
function getDepartmentsWithCourses($includeDisabled = false) {
    global $connection;
    $whereClause = $includeDisabled ? 
        "WHERE (d.status = 'disabled' OR c.status = 'disabled')" : 
        "WHERE d.status = 'active'";
        
    $stmt = $connection->prepare("
        SELECT d.id AS dept_id, d.name AS dept_name, d.status AS dept_status,
               c.id AS course_id, c.name AS course_name, c.status AS course_status
        FROM departments d
        LEFT JOIN courses c ON d.id = c.department_id
        $whereClause
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
                'status' => $row['dept_status'],
                'courses' => []
            ];
        }
        if ($row['course_id']) {
            $departments[$row['dept_id']]['courses'][] = [
                'id' => $row['course_id'],
                'name' => $row['course_name'],
                'status' => $row['course_status']
            ];
        }
    }
    return $departments;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_department'])) {
        $dept_name = trim($_POST['department_name']);
        $stmt = $connection->prepare("INSERT INTO departments (name, status) VALUES (?, 'active')");
        $stmt->bind_param("s", $dept_name);
        $stmt->execute();
    } 
    elseif (isset($_POST['add_course'])) {
        $course_name = trim($_POST['course_name']);
        $dept_id = $_POST['department_id'];
        $stmt = $connection->prepare("INSERT INTO courses (name, department_id, status) VALUES (?, ?, 'active')");
        $stmt->bind_param("si", $course_name, $dept_id);
        $stmt->execute();
    } 
    elseif (isset($_POST['edit_department'])) {
        $dept_id = $_POST['dept_id'];
        $dept_name = trim($_POST['dept_name']);
        $stmt = $connection->prepare("UPDATE departments SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $dept_name, $dept_id);
        $stmt->execute();
    } 
    elseif (isset($_POST['edit_course'])) {
        $course_id = $_POST['course_id'];
        $course_name = trim($_POST['course_name']);
        $stmt = $connection->prepare("UPDATE courses SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $course_name, $course_id);
        $stmt->execute();
    }
    elseif (isset($_POST['disable_department'])) {
        $dept_id = $_POST['dept_id'];
        // Start transaction
        $connection->begin_transaction();
        try {
            // Disable department
            $stmt = $connection->prepare("UPDATE departments SET status = 'disabled' WHERE id = ?");
            $stmt->bind_param("i", $dept_id);
            $stmt->execute();
            
            // Disable all courses in the department
            $stmt = $connection->prepare("UPDATE courses SET status = 'disabled' WHERE department_id = ?");
            $stmt->bind_param("i", $dept_id);
            $stmt->execute();
            
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollback();
            error_log("Error disabling department: " . $e->getMessage());
        }
    }
    elseif (isset($_POST['enable_department'])) {
        $dept_id = $_POST['dept_id'];
        $stmt = $connection->prepare("UPDATE departments SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
    }
    elseif (isset($_POST['disable_course'])) {
        $course_id = $_POST['course_id'];
        $stmt = $connection->prepare("UPDATE courses SET status = 'disabled' WHERE id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
    }
    elseif (isset($_POST['enable_course'])) {
        $course_id = $_POST['course_id'];
        // Check if parent department is active
        $stmt = $connection->prepare("
            SELECT d.status 
            FROM departments d
            JOIN courses c ON d.id = c.department_id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row && $row['status'] === 'active') {
            $stmt = $connection->prepare("UPDATE courses SET status = 'active' WHERE id = ?");
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get both active and disabled departments
$departments = getDepartmentsWithCourses();
$disabledDepartments = getDepartmentsWithCourses(true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Departments and Courses</title>

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css/" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<style>
           
            .table th {
                font-weight: 600;
            }
            .btn {
                border-radius: 10px;
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
            cursor: pointer;
        }
        .theadstyle {
            background: #008F57;
            color: white;
        }

            .dashboard-header {
                border-bottom: 2px solid #008F57;
                margin-bottom: 2rem;
                padding-bottom: 1rem;
            }

            .department-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                margin-bottom: 1.5rem;
                transition: box-shadow 0.3s ease;
            }

            .department-card:hover {
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .department-header {
                padding: 1.5rem;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .department-name {
                font-size: 1.25rem;
                font-weight: 600;
                color: #1f2937;
            }

            .course-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .course-item {
                padding: 1rem 1.5rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #e5e7eb;
                transition: background-color 0.2s ease;
            }

            .course-item:hover {
                background-color: #f9fafb;
            }

            .course-name {
                color: #4b5563;
            }

            .edit-btn {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: none;
                background: transparent;
                color: #6b7280;
                transition: all 0.2s ease;
            }

            .edit-btn:hover {
                background: #f3f4f6;
                color: #008F57;
            }

            .action-buttons {
                display: flex;
                gap: 1rem;
            }

            .search-container {
            position: relative;
            max-width: 400px;
            margin-bottom: 0.8rem;
            margin-left: auto; 
            padding-right: 5x;
            width: 100%;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

            .search-input {
                flex:1;
                width: 100%;
                padding: 0.75rem 1rem 0.75rem 2.5rem;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                outline: none;
                transition: border-color 0.2s ease;
            }

            .search-input:focus {
                border-color: #008F57;
            }

            .search-icon {
                position: absolute;
                left: 0.75rem;
                top: 50%;
                transform: translateY(-50%);
                color: #9ca3af;
            }

            .btn-primary {
            
            } 

            .btn-primary:hover {
                border-color: #007346;
            }

            h2{
            font-weight: 700;
            font-size: 2rem;
            text-align: center;
            margin: 15px 0 30px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
</style>
<body>
<div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
    <div class="dashboard-container">
        <div class="container p-6 mb-6">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="d-flex justify-content-center align-items-center">
                    <h2 id="college-title">College of Engineering and Information Technology</h2>
                    <button class="btn btn-link ml-2" onclick="editCollege()" title="Edit College Name">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>

            <!-- Add this before your other modals -->
            <div class="modal fade" id="editCollegeModal" tabindex="-1" role="dialog" aria-labelledby="editCollegeModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCollegeModalLabel">Edit College Name</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <input type="text" class="form-control" id="edit_college_name" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveCollegeName()">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Actions -->
            <div class="flex-col justify-content-between align-items-center mb-4">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search departments or courses...">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addDepartmentModal">
                        <i class="fas fa-plus-circle mr-2"></i>Add Department
                    </button>
                    <button class="btn btn-success" data-toggle="modal" data-target="#addCourseModal">
                        <i class="fas fa-plus-circle mr-2"></i>Add Course
                    </button>
                    <button class="btn btn-warning" data-toggle="modal"data-target="#disabledItemsModal">
                        <i class="fas fa-archive mr-2"></i>Disabled Items
                    </button>
                </div>
                
            </div>

            <!-- Departments Grid -->
            <div class="row">
                <?php foreach ($departments as $dept): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="department-card">
                        <div class="department-header">
                            <span class="department-name"><?php echo htmlspecialchars($dept['name']); ?></span>
                            <div class="btn-group">
                                <button class="btn btn-link p-0" onclick="editDepartment(<?php echo $dept['id']; ?>, '<?php echo addslashes($dept['name']); ?>')">
                                    <i class="fas fa-edit text-muted"></i>
                                </button>
                            </div>
                        </div>
                        
                        <ul class="course-list">
                            <?php if (!empty($dept['courses'])): ?>
                                <?php foreach ($dept['courses'] as $course): ?>
                                    <?php if ($course['status'] === 'active'): ?>
                                        <li class="course-item">
                                            <span class="course-name"><?php echo htmlspecialchars($course['name']); ?></span>
                                            <div class="btn-group">
                                                <button class="edit-btn" 
                                                        onclick="editCourse(<?php echo $course['id']; ?>, '<?php echo addslashes($course['name']); ?>')" 
                                                        data-toggle="tooltip" 
                                                        title="Edit Course">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                </form>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="course-item text-muted">No courses added yet</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="disabledItemsModal" tabindex="-1" role="dialog" aria-labelledby="disabledItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="disabledItemsModalLabel">Disabled Departments and Courses</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="disabled-departments">
                    <?php
                    $hasDisabledItems = false;
                    foreach ($disabledDepartments as $dept):
                        // Check if department or any of its courses are disabled
                        $hasDisabledCourses = false;
                        $hasDisabledStatus = false;
                        
                        if ($dept['status'] === 'disabled') {
                            $hasDisabledStatus = true;
                            $hasDisabledItems = true;
                        }
                        
                        foreach ($dept['courses'] as $course) {
                            if ($course['status'] === 'disabled') {
                                $hasDisabledCourses = true;
                                $hasDisabledItems = true;
                            }
                        }
                        
                        // Only show departments that are either disabled or have disabled courses
                        if ($hasDisabledStatus || $hasDisabledCourses):
                    ?>
                        <div class="card mb-4 p-3">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        Department: <?php echo htmlspecialchars($dept['name']); ?>
                                        <span class="badge badge-<?php echo $dept['status'] === 'active' ? 'success' : 'warning'; ?> ml-2">
                                            <?php echo ucfirst($dept['status']); ?>
                                        </span>
                                    </h6>
                                    <?php if ($dept['status'] === 'disabled'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                                            <button type="submit" name="enable_department" class="btn btn-success btn-sm">
                                                <i class="fas fa-toggle-on mr-1"></i>Enable Department
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($dept['courses']) && $dept['status'] === 'active'): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($dept['courses'] as $course): ?>
                                        <?php if ($course['status'] === 'disabled'): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($course['name']); ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                    <button type="submit" name="enable_course" class="btn btn-success btn-sm">
                                                        <i class="fas fa-toggle-on mr-1"></i>Enable Course
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php elseif ($dept['status'] === 'disabled' && !empty($dept['courses'])): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($dept['courses'] as $course): ?>
                                        <?php if ($course['status'] === 'disabled'): ?>
                                            <li class="list-group-item">
                                                <?php echo htmlspecialchars($course['name']); ?>
                                                <small class="text-muted d-block">Enable department first to enable courses</small>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php 
                        endif;
                    endforeach;
                    
                    if (!$hasDisabledItems) {
                        echo '<div class="text-center py-5">
                                <i class="fas fa-check-circle text-success mb-6" style="font-size: 48px;"></i>
                                <h5 class="text-muted">No disabled items found</h5>
                                <p class="text-muted mb-0">All departments and courses are currently active</p>
                            </div>';
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
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
                        <button type="submit" name="add_course" class="btn btn-primary">Save Changes</button>
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
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">  <!-- This was wrong -->
                            <input type="hidden" name="action" value="disable">
                            <button type="submit" name="disable_department" class="btn btn-warning">Disable Department</button>
                        </form>
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
                        <button type="button" class="btn btn-warning" onclick="deleteCourse($('#edit_course_id').val(), $('#edit_course_name').val())">Disable Course</button>
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

        // Add this to your existing script section
document.addEventListener('DOMContentLoaded', function() {
    // Load saved college name if it exists
    const savedName = localStorage.getItem('collegeName');
    if (savedName) {
        document.getElementById('college-title').textContent = savedName;
    }
});

function editCollege() {
    const currentName = document.getElementById('college-title').textContent;
    document.getElementById('edit_college_name').value = currentName;
    $('#editCollegeModal').modal('show');
}

function saveCollegeName() {
    const newName = document.getElementById('edit_college_name').value.trim();
    if (newName) {
        document.getElementById('college-title').textContent = newName;
        localStorage.setItem('collegeName', newName);
        $('#editCollegeModal').modal('hide');
    }
}

document.querySelector('.search-input').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.department-card');
    let hasResults = false;
    
    cards.forEach(card => {
        const departmentName = card.querySelector('.department-name').textContent.toLowerCase();
        const courseElements = card.querySelectorAll('.course-name');
        const courseItems = card.querySelectorAll('.course-item');
        
        if (searchTerm === '') {
            // Show everything when search is empty
            card.closest('.col-12').style.display = '';
            courseItems.forEach(item => item.style.display = '');
            hasResults = true;
            return;
        }

        let departmentMatch = departmentName.includes(searchTerm);
        let courseMatches = false;
        
        // Check courses and highlight matches
        courseElements.forEach((courseEl, index) => {
            const courseName = courseEl.textContent.toLowerCase();
            const courseMatch = courseName.includes(searchTerm);
            courseItems[index].style.display = courseMatch || departmentMatch ? '' : 'none';
            if (courseMatch) {
                courseMatches = true;
            }
        });

        // Show/hide the entire department card
        const shouldShowCard = departmentMatch || courseMatches;
        card.closest('.col-12').style.display = shouldShowCard ? '' : 'none';
        if (shouldShowCard) hasResults = true;
    });

    // Show/hide no results message
    let noResultsMsg = document.getElementById('no-results-message');
    if (!hasResults && searchTerm !== '') {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'no-results-message';
            noResultsMsg.className = 'col-12 text-center py-4 text-muted';
            noResultsMsg.innerHTML = 'No matching departments or courses found';
            document.querySelector('.row').appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = '';
    } else if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
});
        
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
    
function deleteDepartment(id, name) {
    if (confirm(`Are you sure you want to disable the department "${name}"? This will also disable all courses in this department.`)) {
        fetch('delete_department.php', {  // Update this to match your PHP file name
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `dept_id=${id}&action=disable`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#editDepartmentModal').modal('hide');
                location.reload();
            } else {
                alert('Failed to disable department');
            }
        })
        .catch(error => {
            alert('Failed to disable department');
            console.error('Error:', error);
        });
    }
}

function deleteCourse(id, name) {
    if (confirm(`Are you sure you want to disable the course "${name}"?`)) {
        fetch('delete_course.php', {  // Update this to match your PHP file name
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `course_id=${id}&action=disable`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#editCourseModal').modal('hide');
                location.reload();
            } else {
                alert('Failed to disable course');
            }
        })
        .catch(error => {
            alert('Failed to disable course');
            console.error('Error:', error);
        });
    }
}
    </script>
</body>
</html>