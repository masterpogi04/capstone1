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

        .search-input-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input-container .fas {
            position: absolute;
            left: 10px;
            top: 11px;
            color: #6c757d;
        }

        .search-input {
            padding-left: 35px;
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

        /* No results message */
        .no-results {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }

        /* Loading spinner */
        .spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner-border {
            color: var(--primary);
        }

        /* Divider style */
        .section-divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
            position: relative;
        }

        .divider-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 0 15px;
            color: #6c757d;
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
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

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 600;
            padding: 12px 20px;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            background-color: #f8f9fa;
            color: #009E60;
        }

        .nav-tabs .nav-link.active {
            color: #009E60;
            background-color: white;
            border-bottom: 3px solid #009E60;
            font-weight: 700;
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
        <ul class="nav nav-tabs" id="archiveTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" href="view_profiles.php">
            <i class="fas fa-check mr-2"></i>Student Profile Inventory
        </a>
    </li>
    <li class="nav-item"> 
        <a class="nav-link " href="archive_view_profile.php">
            <i class="fas fa-archive mr-2"></i>Archived Student Profile
        </a>
    </li> 
    </ul>
            <h2 class="text-center mb-4">View Student Profile Form Inventory</h2>
            
            <!-- Search form - Modified to be intuitive and real-time -->
            <div class="search-container mb-4">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control search-input" placeholder="Start typing student ID, first name, or last name...">
                </div>
                <div>
                    <a href="?" class="btn btn-secondary">Reset Filters</a>
                </div>
                <div id="spinner" class="spinner">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <span class="ml-2">Searching...</span>
                </div>
            </div>

            <!-- Display search results -->
            <div id="searchResults">
                <!-- Results will be loaded here dynamically -->
            </div>

            <!-- Divider with text -->
            <div class="section-divider">
                <span class="divider-text">OR SELECT BY DEPARTMENT</span>
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
            // Variables for search debouncing
            let searchTimeout = null;
            const debounceDelay = 300; // milliseconds to wait after typing stops

            // Handle dynamic search as you type
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val().trim();
                
                // Clear any pending timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // If search field is empty, clear results
                if (searchTerm === '') {
                    $('#searchResults').empty();
                    return;
                }
                
                // Show spinner while waiting
                $('#spinner').show();
                
                // Set a timeout to wait for user to stop typing
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: 'get_data.php',
                        type: 'GET',
                        data: {action: 'search_students', search: searchTerm},
                        success: function(data) {
                            $('#searchResults').html(data);
                            $('#spinner').hide();
                        },
                        error: function() {
                            $('#searchResults').html('<p class="no-results">An error occurred. Please try again.</p>');
                            $('#spinner').hide();
                        }
                    });
                }, debounceDelay);
            });

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
        });
    </script>
</body>
</html>