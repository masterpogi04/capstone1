<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Fetch all departments
$stmt = $connection->prepare("SELECT id, name FROM departments");
if ($stmt === false) {
    die("Error preparing departments query: " . $connection->error);
}
$stmt->execute();
$result = $stmt->get_result();
$departments = $result->fetch_all(MYSQLI_ASSOC);

// Fetch facilitator name using the new column structure
$facilitator_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?");
if ($stmt === false) {
    die("Error preparing facilitator query: " . $connection->error);
}
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
$facilitator = $result->fetch_assoc();

// Construct the full name from the separate fields
$facilitator_name = trim($facilitator['first_name'] . ' ' . 
    ($facilitator['middle_initial'] ? $facilitator['middle_initial'] . '. ' : '') . 
    $facilitator['last_name']);

// Handle search
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $stmt = $connection->prepare("
        SELECT ts.student_id, ts.first_name, ts.last_name, 
               d.id AS department_id, d.name AS department_name, 
               c.id AS course_id, c.name AS course_name, 
               s.id AS section_id, s.year_level, s.section_no
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
    $search_results = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Profiles - CEIT Guidance Office</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0f6a1a;
            --primary-hover: #218838;
            --header: #ff9042;
            --header-hover: #ff7d1a;
            --background: #f8f9fa;
            --border-color: #e9ecef;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background:  linear-gradient(135deg, #0d693e, #004d4d);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(to right, var(--header), var(--header-hover));
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-title {
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .content-wrapper {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }

       /* Back Button*/
.modern-back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #2EDAA8;
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(46, 218, 168, 0.15);
    letter-spacing: 0.3px;
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(46, 218, 168, 0.15);
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}


        .btn-custom {
            background: linear-gradient(to right, var(--primary), var(--primary-hover));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(15, 106, 26, 0.15);
        }

        .search-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .search-input-group {
            display: flex;
            gap: 1rem;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 0 1px #e9ecef;
            margin-top: 1.5rem;
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e9ecef;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-group .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            margin: 0 0.25rem;
        }

        .btn-info {
            background: #17a2b8;
            border: none;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .footer {
            background: var(--header);
            color: white;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .search-input-group {
                flex-direction: column;
            }

            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-group .btn {
                width: 100%;
                margin: 0.25rem 0;
            }

            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

    <div class="content-wrapper">
       

        <div class="content">
        <div class="action-buttons">
            <a href="facilitator_homepage.php" class="modern-back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
            <h2 class="text-center mb-4">View Student Profile Form Inventory</h2>
            
            <!-- Search form -->
            <div class="search-container">
                <form id="searchForm" action="" method="GET" class="form-inline justify-content-center">
                    <input type="text" name="search" id="searchInput" class="form-control mr-sm-2" placeholder="Search by ID, First Name, or Last Name" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <!-- Display search results -->
            <div id="searchResults">
                <?php if (!empty($search_results)): ?>
                    <h3>Search Results</h3>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Section</th>
                                <th style="width: 250px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                                    <td><?php echo htmlspecialchars($student['section_no']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_student_incident_history.php?student_id=<?php echo urlencode($student['student_id']); ?>" 
                                               class="btn btn-info btn-sm ml-1">
                                                <i class="fas fa-history"></i> View History
                                            </a>
                                            <a href="view_student_profile.php?student_id=<?php echo urlencode($student['student_id']); ?>&department_id=<?php echo urlencode($student['department_id']); ?>&course_id=<?php echo urlencode($student['course_id']); ?>&year_level=<?php echo urlencode($student['year_level']); ?>&section_id=<?php echo urlencode($student['section_id']); ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-user"></i> View Profile
                                            </a>
                                            
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif (isset($_GET['search'])): ?>
                    <p class="text-center">No results found.</p>
                <?php endif; ?>
            </div>

            <!-- Existing form for department, course, and year level selection -->
            <form id="profileSelector" action="view_select_sections.php" method="GET">
                <div class="form-group">
                    <label for="department">Department:</label>
                    <select class="form-control" id="department" name="department_id" required>
                        <option value="">Select a department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>">
                                <?php echo htmlspecialchars($department['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course">Course:</label>
                    <select class="form-control" id="course" name="course_id" required disabled>
                        <option value="">Select a course</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level:</label>
                    <select class="form-control" id="year_level" name="year_level" required disabled>
                        <option value="">Select a year level</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-custom btn-block">Next</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Existing JavaScript for handling department, course, and year level selection
            $('#department').change(function() {
                var departmentId = $(this).val();
                if(departmentId) {
                    $.ajax({
                        url: 'get_data.php',
                        type: 'GET',
                        data: {action: 'get_courses', department_id: departmentId},
                        success: function(data) {
                            $('#course').html(data);
                            $('#course').prop('disabled', false);
                            $('#year_level').html('<option value="">Select a year level</option>');
                            $('#year_level').prop('disabled', true);
                        }
                    });
                } else {
                    $('#course').html('<option value="">Select a course</option>');
                    $('#course').prop('disabled', true);
                    $('#year_level').html('<option value="">Select a year level</option>');
                    $('#year_level').prop('disabled', true);
                }
            });

            $('#course').change(function() {
                var courseId = $(this).val();
                var departmentId = $('#department').val();
                if(courseId) {
                    $.ajax({
                        url: 'get_data.php',
                        type: 'GET',
                        data: {action: 'get_year_levels', department_id: departmentId, course_id: courseId},
                        success: function(data) {
                            $('#year_level').html(data);
                            $('#year_level').prop('disabled', false);
                        }
                    });
                } else {
                    $('#year_level').html('<option value="">Select a year level</option>');
                    $('#year_level').prop('disabled', true);
                }
            });

            // New JavaScript for handling search with AJAX
            $('#searchForm').submit(function(e) {
                e.preventDefault();
                var searchTerm = $('#searchInput').val();
                $.ajax({
                    url: 'get_data.php',
                    type: 'GET',
                    data: {action: 'search_students', search: searchTerm},
                    success: function(data) {
                        $('#searchResults').html(data);
                    }
                });
            });
        });
    </script>
</body>
</html>