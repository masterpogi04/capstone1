<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Function to fetch departments
function fetchDepartments($connection) {
    $query = "SELECT * FROM departments ORDER BY name";
    $result = $connection->query($query);
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    return $departments;
}

// Function to fetch courses by department
function fetchCoursesByDepartment($connection, $departmentId = null) {
    $query = "SELECT * FROM courses";
    if ($departmentId) {
        $query .= " WHERE department_id = ?";
    }
    $query .= " ORDER BY name";
    
    if ($departmentId) {
        $stmt = $connection->prepare($query);
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connection->query($query);
    }
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    return $courses;
}

function fetchAnalyticsData($connection, $departmentId = null, $courseId = null) {
    $whereConditions = [];
    $params = [];
    $types = '';
    
    $joinClause = "
        LEFT JOIN courses c ON sp.course_id = c.id
        LEFT JOIN departments d ON c.department_id = d.id
    ";
    
    if ($departmentId) {
        $whereConditions[] = "d.id = ?";
        $params[] = $departmentId;
        $types .= 'i';
    }
    
    if ($courseId) {
        $whereConditions[] = "c.id = ?";
        $params[] = $courseId;
        $types .= 'i';
    }
    
    $whereClause = !empty($whereConditions) ? "AND " . implode(" AND ", $whereConditions) : "";
    
    $queries = [
        'course_factors' => "WITH RECURSIVE numbers AS (
                              SELECT 1 AS n
                              UNION ALL
                              SELECT n + 1 FROM numbers WHERE n < 100
                            ),
                            split_factors AS (
                              SELECT 
                                sp.profile_id,
                                TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(sp.course_factors, ';', numbers.n), ';', -1)) AS factor
                              FROM student_profiles sp
                              $joinClause
                              CROSS JOIN numbers
                              WHERE numbers.n <= 1 + (LENGTH(sp.course_factors) - LENGTH(REPLACE(sp.course_factors, ';', '')))
                              AND sp.course_factors IS NOT NULL
                              AND sp.course_factors != ''
                              $whereClause
                            )
                            SELECT 
                                CASE 
                                    WHEN factor LIKE 'I need more information about certain course/s and occupation/s:%' 
                                    THEN 'I need more information about certain course/s and occupation/s'
                                    WHEN LOWER(factor) LIKE 'other:%' OR LOWER(factor) LIKE 'others:%' 
                                    THEN 'Others'
                                    ELSE factor 
                                END AS factor,
                                COUNT(DISTINCT profile_id) as count
                            FROM split_factors
                            WHERE factor != ''
                            AND (
                                (factor LIKE 'I need more information about certain course/s and occupation/s:%')
                                OR 
                                (factor NOT LIKE 'I need more information about certain course/s and occupation/s%')
                            )
                            AND factor NOT IN ('Other', 'Others')
                            GROUP BY 
                                CASE 
                                    WHEN factor LIKE 'I need more information about certain course/s and occupation/s:%' 
                                    THEN 'I need more information about certain course/s and occupation/s'
                                    WHEN LOWER(factor) LIKE 'other:%' OR LOWER(factor) LIKE 'others:%' 
                                    THEN 'Others'
                                    ELSE factor 
                                END
                            ORDER BY 
                                CASE 
                                    WHEN factor = 'Others' THEN 1
                                    ELSE 0 
                                END,
                                count DESC",

        'career_concerns' => "WITH RECURSIVE numbers AS (
                               SELECT 1 AS n
                               UNION ALL
                               SELECT n + 1 FROM numbers WHERE n < 100
                             ),
                             split_concerns AS (
                               SELECT 
                                 sp.profile_id,
                                 TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(sp.career_concerns, ';', numbers.n), ';', -1)) AS concern
                               FROM student_profiles sp
                               $joinClause
                               CROSS JOIN numbers
                               WHERE numbers.n <= 1 + (LENGTH(sp.career_concerns) - LENGTH(REPLACE(sp.career_concerns, ';', '')))
                               AND sp.career_concerns IS NOT NULL
                               AND sp.career_concerns != ''
                               $whereClause
                             )
                             SELECT 
                                CASE 
                                    WHEN concern = 'I need more information about certain course/s and occupation/s'
                                    OR concern LIKE 'I need more information about certain course/s and occupation/s:%'
                                    THEN 'I need more information about certain course/s and occupation/s'
                                    WHEN LOWER(concern) LIKE 'other:%' OR LOWER(concern) LIKE 'others:%' 
                                    THEN 'Others'
                                    ELSE concern 
                                END AS concern,
                                COUNT(DISTINCT profile_id) as count
                             FROM split_concerns
                             WHERE concern != ''
                             AND concern NOT IN ('Other', 'Others')
                             GROUP BY 
                                CASE 
                                    WHEN concern = 'I need more information about certain course/s and occupation/s'
                                    OR concern LIKE 'I need more information about certain course/s and occupation/s:%'
                                    THEN 'I need more information about certain course/s and occupation/s'
                                    WHEN LOWER(concern) LIKE 'other:%' OR LOWER(concern) LIKE 'others:%' 
                                    THEN 'Others'
                                    ELSE concern 
                                END
                             HAVING count > 0
                             ORDER BY 
                                CASE 
                                    WHEN concern = 'Others' THEN 1
                                    ELSE 0 
                                END,
                                count DESC"
    ];
    
    $data = [];
    foreach ($queries as $key => $query) {
        try {
            if (!empty($params)) {
                $stmt = $connection->prepare($query);
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $connection->error);
                }
                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $result = $stmt->get_result();
            } else {
                $result = $connection->query($query);
                if ($result === false) {
                    throw new Exception("Query failed: " . $connection->error);
                }
            }
            
            $data[$key] = [];
            while ($row = $result->fetch_assoc()) {
                $data[$key][] = $row;
            }
            
            if (empty($data[$key])) {
                $data[$key] = [];
            }
            
        } catch (Exception $e) {
            error_log("Error in fetchAnalyticsData: " . $e->getMessage());
            $data[$key] = [];
        }
    }
    
    return $data;
}

function fetchStudentDetails($connection, $category, $value, $departmentId = null, $courseId = null) {
    $whereConditions = [];
    $params = [];
    $types = '';
    
    // Main search condition with special handling for "Others" category
    if ($category === 'factor') {
        if ($value === 'I need more information about certain course/s and occupation/s') {
            // Only match entries that have specifications
            $whereConditions[] = "(sp.course_factors LIKE '%I need more information about certain course/s and occupation/s:%')";
        } else if ($value === 'Others') {
            // Only match entries that start with "Other:" or "Others:"
            $whereConditions[] = "(sp.course_factors LIKE ? OR sp.course_factors LIKE ?)";
            $params[] = "%Other:%";
            $params[] = "%Others:%";
            $types .= 'ss';
        } else {
            $whereConditions[] = "sp.course_factors LIKE ?";
            $params[] = "%$value%";
            $types .= 's';
        }
    } elseif ($category === 'concern') {
        if ($value === 'Others') {
            $whereConditions[] = "(sp.career_concerns LIKE ? OR sp.career_concerns LIKE ?)";
            $params[] = "%Other:%";
            $params[] = "%Others:%";
            $types .= 'ss';
        } else {
            $whereConditions[] = "sp.career_concerns LIKE ?";
            $params[] = "%$value%";
            $types .= 's';
        }
    }
    
    // Department and course filters
    if ($departmentId) {
        $whereConditions[] = "d.id = ?";
        $params[] = $departmentId;
        $types .= 'i';
    }
    
    if ($courseId) {
        $whereConditions[] = "c.id = ?";
        $params[] = $courseId;
        $types .= 'i';
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    $query = "SELECT 
        sp.*,
        c.name as course_name,
        d.name as department_name
        FROM student_profiles sp 
        LEFT JOIN courses c ON sp.course_id = c.id 
        LEFT JOIN departments d ON c.department_id = d.id
        $whereClause
        ORDER BY sp.last_name, sp.first_name";
    
    try {
        $stmt = $connection->prepare($query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $connection->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $courseFactors = array_filter(explode(';', $row['course_factors'] ?? ''), function($factor) {
                $factor = trim($factor);
                if ($factor === '') return false;
                if ($factor === 'Other' || $factor === 'Others') return false;
                if ($factor === 'I need more information about certain course/s and occupation/s') return false;
                
                // Include only if it's a specification for course/occupation info
                if (strpos(strtolower($factor), 'i need more information about certain course/s and occupation/s:') === 0) {
                    return true;
                }
                
                // Include other non-empty factors that aren't the base phrase
                return $factor !== '';
            });
            
            $careerConcerns = array_filter(explode(';', $row['career_concerns'] ?? ''), function($concern) {
                $concern = trim($concern);
                if ($concern === '') return false;
                if ($concern === 'Other' || $concern === 'Others') return false;
                return true;
            });
            
            // Process course factors to extract specifications
            $processedCourseFactors = array_map(function($factor) {
                if (strpos($factor, 'I need more information about certain course/s and occupation/s:') === 0) {
                    return str_replace('I need more information about certain course/s and occupation/s:', '', $factor);
                }
                return $factor;
            }, $courseFactors);
            
            $students[] = [
                'full_name' => $row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] : ''),
                'course_name' => $row['course_name'] ?? 'Not Assigned',
                'department_name' => $row['department_name'] ?? 'Not Assigned',
                'course_factors' => !empty($processedCourseFactors) ? implode(';', $processedCourseFactors) : 'N/A',
                'career_concerns' => !empty($careerConcerns) ? implode(';', $careerConcerns) : 'N/A'
            ];
        }
        
        return $students;
    } catch (Exception $e) {
        error_log("Error in fetchStudentDetails: " . $e->getMessage());
        return [];
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch($_GET['action']) {
        case 'getCourses':
            $departmentId = $_POST['department_id'] ?? null;
            echo json_encode(fetchCoursesByDepartment($connection, $departmentId));
            break;
            
        case 'getFilteredData':
            $departmentId = $_POST['department_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            echo json_encode(fetchAnalyticsData($connection, $departmentId, $courseId));
            break;
            
        case 'getStudents':
            $category = $_POST['category'] ?? '';
            $value = $_POST['value'] ?? '';
            $departmentId = $_POST['department_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            echo json_encode(fetchStudentDetails($connection, $category, $value, $departmentId, $courseId));
            break;
    }
    exit();
}

$departments = fetchDepartments($connection);
$analyticsData = fetchAnalyticsData($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educational Career Analytics</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="analytics.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" type="text/css" href="analytics.css"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .header{
    width: 100%;
    padding: clamp(10px, 2vw, 15px);
    background:rgb(255, 255, 255);
    text-align: center;
    color:  #1b651b;
    font-size: clamp(16px, 4vw, 28px);
    font-weight: bold;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
}
.chart-container {
    flex: 1;
    min-height: 400px;
    margin-bottom: 1rem;
}
.chart-legend {
    border-top: 1px solid #e9ecef;
    padding: 1rem;
    margin-top: auto;
    overflow-y: auto;
    max-height: 200px;
    background: #fff;
    border-radius: 0 0 8px 8px;
}

.chart-legend::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.chart-legend::-webkit-scrollbar-track {
    background: #f7fafc;
    border-radius: 3px;
}

.chart-legend::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 3px;
}

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
            padding: 1rem;
        }
        .card {
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
.card-body {
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
    height: 800px;
    overflow: hidden;
}

.card-title {
    margin-bottom: 1rem;
}

.chart-and-legend-container {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

        .loading {
            opacity: 0.5;
            pointer-events: none;
        }
         
        .chart-legend-wrapper {
            position: relative;
            width: 100%;
            overflow-y: auto;
            margin-top: 1rem;
            padding: 4px 0;
            -webkit-overflow-scrolling: touch;
        }

        .chart-legend {
    border-top: 1px solid #e9ecef;
    padding: 1rem;
    margin-top: auto;
    overflow-y: auto;
    max-height: 200px;
    background: #fff;
    border-radius: 0 0 8px 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding-bottom: 80px; /* Added more bottom padding */
}

.legend-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.2s;
    flex: 0 1 calc(20% - 10px); /* Show 5 items per row with gap */
    min-width: 180px;
    margin: 0;
}

.legend-item:hover {
    transform: translateY(-2px);
    background: #f0f0f0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.legend-label {
    margin: 0 8px;
    font-size: 12px;
    flex-grow: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.legend-color {
    min-width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.legend-value {
    color: #666;
    font-size: 12px;
    margin-left: 8px;
    flex-shrink: 0;
}

/* Responsive breakpoints */
@media (max-width: 1400px) {
    .legend-item {
        flex: 0 1 calc(25% - 10px); /* 4 items per row */
    }
}

@media (max-width: 1200px) {
    .legend-item {
        flex: 0 1 calc(33.333% - 10px); /* 3 items per row */
    }
}

@media (max-width: 992px) {
    .legend-item {
        flex: 0 1 calc(50% - 10px); /* 2 items per row */
    }
}

@media (max-width: 576px) {
    .legend-item {
        flex: 0 1 100%; /* 1 item per row */
    }
}

        /* Custom scrollbar styles */
        .chart-legend-wrapper::-webkit-scrollbar {
            height: 6px;
        }

        .chart-legend-wrapper::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 3px;
        }

        .chart-legend-wrapper::-webkit-scrollbar-thumb {
            background-color: #cbd5e0;
            border-radius: 3px;
        }
        .student-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fff;
        }
        .student-card:hover {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }
        .modal-content {
            border-radius: 12px;
        }
        .modal-header {
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }
        .modal-body {
            padding: 1.5rem;
        }
    .filter-controls {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .filter-controls .form-check {
            margin-right: 2rem;
            display: inline-block;
        }
        
        .filter-controls button {
            margin-left: 1rem;
        }
        
        .graph-container {
            transition: all 0.3s ease;
        }
        
        .graph-container.hidden {
            display: none;
        }


    
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
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}
.nav-analytics {
    background: #ffffff;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.5rem 0;
    margin: 0 -15px 2rem;
    border-bottom: 3px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.nav-analytics .nav-item {
    margin-right: 0.5rem;
}

.nav-analytics .nav-link {
    color: #6c757d;
    padding: 1rem 2rem;
    border-radius: 10px 10px 0 0;
    position: relative;
    transition: all 0.3s ease;
    font-weight: 500;
    white-space: nowrap;
    border: 2px solid transparent;
    background: #f8f9fa;
}

.nav-analytics .nav-link:hover:not(.active) {
    color: #2196F3;
    background: #f1f4f6;
    transform: translateY(-2px);
}

.nav-analytics .nav-link.active {
    background: #ffffff;
    color: #2196F3;
    border: 2px solid #e9ecef;
    border-bottom: 3px solid #ffffff;
    margin-bottom: -3px;
    transform: translateY(-3px);
    box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
}

.nav-analytics .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #2196F3;
    border-radius: 3px 3px 0 0;
}

@media (max-width: 768px) {
    .nav-analytics {
        padding: 0.5rem 1rem 0;
    }
    
    .nav-analytics .nav-link {
        padding: 0.75rem 1.25rem;
        font-size: 0.9rem;
    }
}

    </style>
</head>
<body>
<div class="header">Student Profile Analytics Dashboard</div>

<div class="container-fluid mt-4">
    <a href="facilitator_dashboard.php"  class="modern-back-button">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>

        <!-- Analytics Navigation Tabs -->
        <ul class="nav nav-analytics">
            <li class="nav-item">
                <a class="nav-link" href="view_personal_info_analytics.php">Personal Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_family_background_analytics.php">Family Background</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="view_educational_career_analytics.php">Educational Career</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_medical_history_analytics.php">Medical History</a>
            </li>
        </ul>

        <div class="filter-controls">
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="departmentSelect">Department:</label>
            <select class="form-control" id="departmentSelect">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="courseSelect">Course:</label>
            <select class="form-control" id="courseSelect" disabled>
                <option value="">All Courses</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button id="resetFilters" class="btn btn-secondary">Reset Filters</button>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="courseFactorsCheck" checked>
                <label class="form-check-label" for="courseFactorsCheck">Course Selection Factors</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="careerConcernsCheck" checked>
                <label class="form-check-label" for="careerConcernsCheck">Career Concerns</label>
            </div>
        </div>
    </div>
</div>

        <!-- Charts Container -->
        <div class="row">
            <div class="col-12 graph-container" id="courseFactorsContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Course Selection Factors</h5>
                        <div class="chart-and-legend-container">
                            <div class="chart-container">
                                <canvas id="courseFactorsChart"></canvas>
                            </div>
                            <div id="courseFactorsLegend" class="chart-legend"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 graph-container" id="careerConcernsContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Career Concerns Distribution</h5>
                        <div class="chart-and-legend-container">
                            <div class="chart-container">
                                <canvas id="careerConcernsChart"></canvas>
                            </div>
                            <div id="careerConcernsLegend" class="chart-legend"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Student Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" id="studentSearch" class="form-control" placeholder="Search student by name...">
                    </div>
                    <div class="loading-spinner d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div id="studentList"></div>
                </div>
            </div>
        </div>
    </div>

<script>
// Define global variables
let charts = {
    courseFactorsChart: null,
    careerConcernsChart: null
};

const colors = [
    '#2E8B57', '#CD5C5C', '#DAA520', '#6495ED', '#66CDAA', 
    '#DB7093', '#E9AB17', '#87CEEB', '#98FB98', '#FFB6C1', 
    '#F0E68C', '#B0E0E6', '#E0FFC2', '#FFE4E1', '#FFFACD'
];
 
// Function to format numbers
function formatNumber(num) {
    if (num >= 1000000) {
        return (num/1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num/1000).toFixed(1) + 'K';
    }
    return num;
}

// Function to create responsive charts with legends
// Function to create responsive charts with legends
function createChart(elementId, data, key, title) {
    const ctx = document.getElementById(elementId);
    if (!ctx) return null;

    let legendWrapper = document.getElementById(`${elementId}Legend`);
    if (!legendWrapper) {
        legendWrapper = document.createElement('div');
        legendWrapper.id = `${elementId}Legend`;
        legendWrapper.className = 'chart-legend';
        ctx.parentNode.appendChild(legendWrapper);
    }
    
    // Simple mapping for title display in the no-data message
    const titleMap = {
        'factor': 'Course Selection Factors',
        'concern': 'Career Concerns'
    };
    
    // Check if data is empty or undefined
    if (!data || data.length === 0) {
        // Clear any existing chart
        if (charts[elementId]) {
            charts[elementId].destroy();
            charts[elementId] = null;
        }
        
        // Hide the canvas
        ctx.style.display = 'none';
        
        // Create a no-data message div if it doesn't exist
        let noDataMsg = document.getElementById(`${elementId}NoData`);
        if (!noDataMsg) {
            noDataMsg = document.createElement('div');
            noDataMsg.id = `${elementId}NoData`;
            noDataMsg.style.textAlign = 'center';
            noDataMsg.style.padding = '50px 0';
            noDataMsg.innerHTML = `<div class="text-center p-4">
                    <div class="text-muted">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <p>No data available for ${titleMap[key].toLowerCase()}</p>
                    </div>
                </div>`;
            ctx.parentNode.appendChild(noDataMsg);
        } else {
            noDataMsg.style.display = 'block';
        }
        
        // Clear the legend
        legendWrapper.innerHTML = '';
        
        return null;
    } else {
        // Hide any existing no-data message
        let noDataMsg = document.getElementById(`${elementId}NoData`);
        if (noDataMsg) {
            noDataMsg.style.display = 'none';
        }
        
        // Show the canvas
        ctx.style.display = 'block';
    }

    // Prepare data
    const labels = data.map(item => item[key]);
    const values = data.map(item => parseInt(item.count));
    const total = values.reduce((a, b) => a + b, 0);

    // Get background colors
    const backgroundColor = colors.slice(0, labels.length);

    // Destroy existing chart
    if (charts[elementId]) {
        charts[elementId].destroy();
    }

    // Function to wrap text
    function wrapText(str, maxWidth = 30) {
        if (!str) return [''];
        
        const words = str.split(' ');
        const lines = [];
        let currentLine = words[0];

        for (let i = 1; i < words.length; i++) {
            const word = words[i];
            const width = currentLine.length + word.length + 1;
            
            if (width <= maxWidth) {
                currentLine += " " + word;
            } else {
                lines.push(currentLine);
                currentLine = word;
            }
        }
        lines.push(currentLine);
        return lines;
    }

    // Create chart configuration
    const config = {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: backgroundColor,
                borderRadius: 6,
                borderWidth: 1,
                borderColor: backgroundColor.map(color => color.replace('1)', '0.8)')),
                maxBarThickness: 70,
                barPercentage: 0.8,     // Controls the width of the bars
                categoryPercentage: 0.7  // Controls spacing between bar groups
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            onClick: (event, elements) => {
                if (elements && elements.length > 0) {
                    const index = elements[0].index;
                    const label = labels[index];
                    showStudentDetails(key === 'factor' ? 'factor' : 'concern', label);
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `Count: ${formatNumber(value)} (${percentage}%)`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        drawBorder: true,
                    },
                    ticks: {
                        callback: function(value) {
                            return formatNumber(value);
                        }
                    }
                },
                y: {
                    grid: {
                        display: false,
                        drawBorder: false,
                    },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        minRotation: 0,
                        padding: 25,        // Increased padding for y-axis labels
                        font: {
                            size: 12        // You can adjust font size if needed
                        },
                        callback: function(value) {
                            const label = this.getLabelForValue(value);
                            const wrappedText = wrapText(label, 35);
                            return wrappedText;
                        }
                    },
                    afterFit: function(scaleInstance) {
                        // Increase spacing between bars
                        scaleInstance.paddingTop = 40;
                        scaleInstance.paddingBottom = 40;
                    }
                }
            },
            layout: {
                padding: {
                    left: 20,
                    right: 20,
                    top: 30,    // Added top padding
                    bottom: 30  // Added bottom padding
                }
            },
            onResize: function(chart, size) {
                // Dynamically adjust the chart height based on the number of labels
                const minHeight = 400; // Minimum height
                const heightPerLabel = 60; // Increased height per label for more spacing
                const totalHeight = Math.max(minHeight, labels.length * heightPerLabel);
                chart.canvas.parentNode.style.height = `${totalHeight}px`;
            }
        }
    };

    // Create new chart
    const chart = new Chart(ctx, config);
    
    // Trigger initial resize
    config.options.onResize(chart);
    
    charts[elementId] = chart;

    // Create custom legend with wrapped text
    legendWrapper.innerHTML = '';
    labels.forEach((label, index) => {
        const count = values[index];
        const percentage = ((count / total) * 100).toFixed(1);
        const wrappedLabel = wrapText(label, 35).join('<br>');
                const legendItem = document.createElement('div');
        legendItem.className = 'legend-item';
        legendItem.innerHTML = `
            <div class="legend-color" style="background-color: ${backgroundColor[index]};"></div>
            <span class="legend-label">${wrappedLabel}</span>
            <span class="legend-value">(${percentage}%)</span>
        `;
        
        legendItem.addEventListener('click', () => {
            showStudentDetails(key === 'factor' ? 'factor' : 'concern', label);
        });
        
        legendWrapper.appendChild(legendItem);
    });

    // Add context menu for export
    ctx.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Export Data',
            text: 'Do you want to export this data as CSV?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                exportToCSV(chart, title);
            } 
        });
    });

    return chart;
}

// Function to export chart data to CSV
function exportToCSV(chart, title) {
    const labels = chart.data.labels;
    const values = chart.data.datasets[0].data;
    const total = values.reduce((a, b) => a + b, 0);
    
    const departmentText = $('#departmentSelect option:selected').text();
    const courseText = $('#courseSelect option:selected').text();
    const timestamp = new Date().toISOString().split('T')[0];
    
    const filterInfo = [];
    if (departmentText !== 'All Departments') {
        filterInfo.push(departmentText);
        if (courseText !== 'All Courses') {
            filterInfo.push(courseText);
        }
    }
    
    const filterSuffix = filterInfo.length > 0 ? ` - ${filterInfo.join(' - ')}` : '';
    const filename = `${title.toLowerCase().replace(/\s+/g, '_')}${filterSuffix}_${timestamp}.csv`;

    const formattedRows = labels.map((label, index) => {
        const value = values[index];
        const percentage = ((value / total) * 100).toFixed(2);
        const formattedLabel = label.replace(/[–—]/g, '-');
        
        return [
            formattedLabel,
            value.toString(),
            percentage + '%'
        ];
    });

    const rows = [
        [title.replace(/_/g, ' ') + ' Distribution '],
        [], // Empty row for spacing
        ['Category', 'Count', 'Percentage'],
        ...formattedRows,
        [],
        ['Total', total.toString(), '100%'],
        [],
        ['Report Generated on:', new Date().toLocaleString()],
        ['Department:', departmentText],
        ['Course:', courseText]
    ];

    const csvContent = rows.map(row => 
        row.map(cell => {
            if (cell && (cell.includes(',') || cell.includes('"') || cell.includes('\n'))) {
                return `"${cell.replace(/"/g, '""')}"`;
            }
            return cell;
        }).join(',')
    ).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: `Data has been exported to ${filename}`,
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    });
}

// Function to show student details
function showStudentDetails(category, value) {
    const modal = $('#studentModal');
    const studentList = $('#studentList');
    const loadingSpinner = $('.loading-spinner');
    const departmentId = $('#departmentSelect').val();
    const courseId = $('#courseSelect').val();

    $('#studentSearch').val('');

    modal.modal('show');
    studentList.addClass('d-none');
    loadingSpinner.removeClass('d-none');
    
    $.ajax({
        url: '?action=getStudents',
        method: 'POST',
        data: { 
            category, 
            value,
            department_id: departmentId,
            course_id: courseId
        },
        success: function(students) {
            loadingSpinner.addClass('d-none');
            studentList.removeClass('d-none');

            if (students.length === 0) {
                studentList.html('<div class="alert alert-info">No students found for this category.</div>');
                return;
            }
            
            const studentCards = students.map(student => {
                // Function to format delimited strings into bullet points
                const formatDelimitedString = (str) => {
                    if (!str || str === 'N/A') return 'N/A';
                    return str.split(';')
                        .map(item => item.trim())
                        .filter(item => item)
                        .map(item => `• ${item}`)
                        .join('<br>');
                };

                return `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">${student.full_name}</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Department:</strong> ${student.department_name}</p>
                                    <p><strong>Course:</strong> ${student.course_name}</p>
                                    <p>
                                        <strong>Course Factors:</strong>
                                        <div style="margin-top: 5px; margin-left: 5px;">
                                            ${formatDelimitedString(student.course_factors)}
                                        </div>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <div>
                                        <p>
                                            <strong>Career Concerns:</strong>
                                            <div style="margin-top: 5px; margin-left: 5px;">
                                                ${formatDelimitedString(student.career_concerns)}
                                            </div>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            studentList.html(studentCards);
        },
        error: function() {
            loadingSpinner.addClass('d-none');
            studentList.removeClass('d-none')
                .html('<div class="alert alert-danger">Error loading student data. Please try again.</div>');
        }
    });
}

// Initialize everything when document is ready
$(document).ready(function() {
    // Handle department selection
    $('#departmentSelect').change(function() {
        const departmentId = $(this).val();
        const courseSelect = $('#courseSelect');
        
        if (departmentId) {
            courseSelect.prop('disabled', false).html('<option value="">Loading...</option>');
            
            $.ajax({
                url: '?action=getCourses',
                method: 'POST',
                data: { department_id: departmentId },
                success: function(courses) {
                    courseSelect.html('<option value="">All Courses</option>');
                    courses.forEach(function(course) {
                        courseSelect.append(`<option value="${course.id}">${course.name}</option>`);
                    });
                    // Trigger filtering after courses load
                    applyFilters();
                },
                error: function() {
                    courseSelect.html('<option value="">Error loading courses</option>');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch courses. Please try again.'
                    });
                }
            });
        } else {
            courseSelect.prop('disabled', true).html('<option value="">All Courses</option>');
            applyFilters();
        }
    });

    // Handle search functionality
    $(document).on('input', '#studentSearch', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#studentList .card').each(function() {
            const studentName = $(this).find('.card-title').text().toLowerCase();
            $(this).toggle(studentName.includes(searchTerm));
        });
    });

    // Handle filter application
    function applyFilters() {
        const departmentId = $('#departmentSelect').val();
        const courseId = $('#courseSelect').val();
        const showCourseFactors = $('#courseFactorsCheck').prop('checked');
        const showCareerConcerns = $('#careerConcernsCheck').prop('checked');

        $('.card').addClass('loading');

        $.ajax({
            url: '?action=getFilteredData',
            method: 'POST',
            data: {
                department_id: departmentId,
                course_id: courseId
            },
            success: function(data) {
                // Only update charts that are visible
                if (showCourseFactors) {
                    createChart('courseFactorsChart', data.course_factors, 'factor', 'Course Selection Factors');
                }
                if (showCareerConcerns) {
                    createChart('careerConcernsChart', data.career_concerns, 'concern', 'Career Concerns');
                }
                
                // Toggle container visibility
                $('#courseFactorsContainer').toggleClass('hidden', !showCourseFactors);
                $('#careerConcernsContainer').toggleClass('hidden', !showCareerConcerns);
                
                $('.card').removeClass('loading');
            },
            error: function() {
                $('.card').removeClass('loading');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update charts. Please try again.'
                });
            }
        });
    }

    $('#departmentSelect, #courseSelect').change(applyFilters);

    // Reset filters handler
    $('#resetFilters').click(function() {
        // Reset selects
        $('#departmentSelect').val('');
        $('#courseSelect').val('').prop('disabled', true);
        
        // Reset checkboxes (ensure both are checked)
        $('#courseFactorsCheck').prop('checked', true);
        $('#careerConcernsCheck').prop('checked', true);
        
        // Show both containers
        $('#courseFactorsContainer, #careerConcernsContainer').removeClass('hidden');
        
        // Apply filters
        applyFilters();

        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Filters Reset',
            text: 'All filters have been reset to default values.',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // Handle checkbox changes
    $('#courseFactorsCheck, #careerConcernsCheck').change(function() {
        const targetId = $(this).attr('id').replace('Check', '');
        $(`#${targetId}Container`).toggleClass('hidden', !$(this).prop('checked'));
        window.dispatchEvent(new Event('resize'));
    });

    // Initial charts creation
    const initialData = <?php echo json_encode($analyticsData); ?>;
    createChart('courseFactorsChart', initialData.course_factors, 'factor', 'Course Selection Factors');
    createChart('careerConcernsChart', initialData.career_concerns, 'concern', 'Career Concerns');

    // Handle responsive behavior
    $(window).on('resize', function() {
        Object.values(charts).forEach(chart => {
            if (chart) {
                chart.resize();
            }
        });
    });
});
</script>
</body>
</html>