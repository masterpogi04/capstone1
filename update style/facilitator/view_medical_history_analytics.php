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

function fetchAnalyticsData($connection, $departmentId = null, $courseId = null) {
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if ($departmentId) {
        $whereConditions[] = "c.department_id = ?";
        $params[] = $departmentId;
        $types .= 'i';
    }
    
    if ($courseId) {
        $whereConditions[] = "sp.course_id = ?";
        $params[] = $courseId;
        $types .= 'i';
    }
    
    $baseWhereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Simplified medical conditions query
$queries = [
'medical_conditions' => "
WITH split_conditions AS (
    SELECT sp.profile_id,
        TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(medical_conditions, ';', numbers.n), ';', -1)) condition_part,
        sp.course_id,
        c.department_id
    FROM student_profiles sp
    LEFT JOIN courses c ON sp.course_id = c.id
    CROSS JOIN (
        SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
        SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL
        SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    ) numbers
    WHERE 
        medical_conditions IS NOT NULL 
        AND medical_conditions != ''
        AND medical_conditions != 'NO MEDICAL CONDITIONS'
        AND numbers.n <= 1 + LENGTH(medical_conditions) - LENGTH(REPLACE(medical_conditions, ';', ''))
),
cleaned_conditions AS (
    SELECT 
        profile_id,
        CASE 
            WHEN condition_part LIKE 'Other:%' THEN 'Other'
            WHEN condition_part LIKE 'Allergy:%' THEN 'Allergy'
            WHEN condition_part LIKE 'Scoliosis/Physical condition:%' THEN 'Scoliosis/Physical condition'
            ELSE TRIM(condition_part)
        END as condition_type,
        course_id,
        department_id
    FROM split_conditions
    WHERE condition_part != '' AND condition_part NOT LIKE '%N/A%'
)
SELECT condition_type, COUNT(DISTINCT profile_id) as count 
FROM (
    SELECT profile_id, condition_type, course_id, department_id
    FROM cleaned_conditions
    WHERE condition_type IS NOT NULL AND condition_type != ''
    
    UNION ALL
    
    SELECT sp.profile_id, 
        'No Medical Conditions' as condition_type,
        sp.course_id,
        c.department_id
    FROM student_profiles sp
    LEFT JOIN courses c ON sp.course_id = c.id
    WHERE medical_conditions IS NULL 
        OR medical_conditions = '' 
        OR medical_conditions = 'NO MEDICAL CONDITIONS'
) all_conditions
" . ($departmentId ? " WHERE department_id = ?" : "") . "
" . ($courseId ? (($departmentId ? " AND" : " WHERE") . " course_id = ?") : "") . "
GROUP BY condition_type
ORDER BY 
    CASE 
        WHEN condition_type = 'No Medical Conditions' THEN 1 
        ELSE 0 
    END,
    count DESC",

    'stress_level' => "SELECT 
            COALESCE(stress_level, 'Not Specified') as level,
            COUNT(*) as count 
            FROM student_profiles sp 
            LEFT JOIN courses c ON sp.course_id = c.id 
            " . ($baseWhereClause ? $baseWhereClause : "") . "
            GROUP BY stress_level
            ORDER BY 
                CASE 
                    WHEN stress_level = 'low' THEN 1
                    WHEN stress_level = 'average' THEN 2
                    WHEN stress_level = 'high' THEN 3
                    ELSE 4
                END",

    'fitness_activity' => "SELECT 
            CASE 
                WHEN fitness_activity = 'NO FITNESS' OR fitness_activity IS NULL OR fitness_activity = '' 
                THEN 'No Fitness Activity'
                ELSE 'Fitness Activity'
            END as activity,
            COUNT(*) as count 
            FROM student_profiles sp 
            LEFT JOIN courses c ON sp.course_id = c.id 
            " . ($baseWhereClause ? $baseWhereClause : "") . "
            GROUP BY 
            CASE 
                WHEN fitness_activity = 'NO FITNESS' OR fitness_activity IS NULL OR fitness_activity = '' 
                THEN 'No Fitness Activity'
                ELSE 'Fitness Activity'
            END",

    'suicide_attempt' => "SELECT 
            CASE 
                WHEN suicide_attempt = 'yes' THEN 'Yes'
                WHEN suicide_attempt = 'no' THEN 'No'
                ELSE 'Not Specified'
            END as response,
            COUNT(*) as count 
            FROM student_profiles sp 
            LEFT JOIN courses c ON sp.course_id = c.id 
            " . ($baseWhereClause ? $baseWhereClause : "") . "
            GROUP BY 
            CASE 
                WHEN suicide_attempt = 'yes' THEN 'Yes'
                WHEN suicide_attempt = 'no' THEN 'No'
                ELSE 'Not Specified'
            END", 
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
        } catch (Exception $e) {
            error_log("Error in query $key: " . $e->getMessage());
            error_log("Query was: " . $queries[$key]);
            $data[$key] = [];
            continue;
        }
    }
    return $data;
}

function fetchDepartments($connection) {
    $query = "SELECT id, name FROM departments ORDER BY name";
    $result = $connection->query($query);
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    return $departments;
}

function fetchCoursesByDepartment($connection, $departmentId) {
    $query = "SELECT id, name FROM courses WHERE department_id = ? ORDER BY name";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    return $courses;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'getCourses':
            $departmentId = $_POST['department_id'] ?? null;
            $courses = $departmentId ? fetchCoursesByDepartment($connection, $departmentId) : [];
            header('Content-Type: application/json');
            echo json_encode($courses);
            exit();
            
        case 'getFilteredData':
            $departmentId = $_POST['department_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            $filteredData = fetchAnalyticsData($connection, $departmentId, $courseId);
            header('Content-Type: application/json');
            echo json_encode($filteredData);
            exit();
            
        case 'getStudents':
            $category = $_POST['category'];
            $value = $_POST['value'];
            $departmentId = $_POST['department_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            
            $whereConditions = [];
            $params = [];
            $types = '';
            
            switch($category) {
            case 'medical_conditions':
    if ($value === 'No Medical Conditions') {
        $whereConditions[] = "(sp.medical_conditions IS NULL OR sp.medical_conditions = '' OR sp.medical_conditions = 'NO MEDICAL CONDITIONS')";
    } else {
        $searchValue = $value;
        if ($value === 'Scoliosis/Physical condition') {
            $whereConditions[] = "sp.medical_conditions LIKE '%Scoliosis/Physical condition:%'";
        } else if ($value === 'Allergy') {
            $whereConditions[] = "sp.medical_conditions LIKE '%Allergy:%'";
        } else if ($value === 'Other') {
            $whereConditions[] = "sp.medical_conditions LIKE '%Other:%'";
        } else {
            $whereConditions[] = "(
                sp.medical_conditions LIKE ? 
                OR sp.medical_conditions LIKE ? 
                OR sp.medical_conditions LIKE ?
            )";
            $params[] = "%$searchValue;%";  // Middle of list
            $params[] = "$searchValue;%";    // Start of list
            $params[] = "%$searchValue";     // End of list
            $types .= 'sss';
        }
    }
    break;
                case 'stress_level':
                    $whereConditions[] = "sp.stress_level = ?";
                    $params[] = strtolower($value);
                    $types .= 's';
                    break;
                case 'fitness_activity':
                    if ($value === 'No Fitness Activity') {
                        $whereConditions[] = "(sp.fitness_activity = 'NO FITNESS' OR sp.fitness_activity IS NULL OR sp.fitness_activity = '')";
                    } else {
                        $whereConditions[] = "(sp.fitness_activity != 'NO FITNESS' AND sp.fitness_activity IS NOT NULL AND sp.fitness_activity != '')";
                    }
                    break;
                case 'suicide_attempt':
                    $whereConditions[] = "sp.suicide_attempt = ?";
                    $params[] = strtolower($value);
                    $types .= 's';
                    break;
            }
            
            if ($departmentId) {
                $whereConditions[] = "c.department_id = ?";
                $params[] = $departmentId;
                $types .= 'i';
            }
            
            if ($courseId) {
                $whereConditions[] = "sp.course_id = ?";
                $params[] = $courseId;
                $types .= 'i';
            }
            
            $whereClause = implode(" AND ", $whereConditions);
            
            $query = "SELECT 
                sp.*, 
                ts.student_id,
                c.name as course_name,
                d.name as department_name
                FROM student_profiles sp 
                LEFT JOIN tbl_student ts ON sp.student_id = ts.student_id
                LEFT JOIN courses c ON sp.course_id = c.id 
                LEFT JOIN departments d ON c.department_id = d.id
                WHERE $whereClause
                ORDER BY sp.last_name, sp.first_name";
            
            $stmt = $connection->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = [
                    'student_id' => $row['student_id'],
                    'full_name' => $row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] : ''),
                    'department_name' => $row['department_name'] ?? 'Not Assigned',
                    'course_name' => $row['course_name'] ?? 'Not Assigned',
                    'medical_conditions' => $row['medical_conditions'] ?? 'None',
                    'medications' => $row['medications'] ?? 'None',
                    'stress_level' => $row['stress_level'] ?? 'Not Specified',
                    'fitness_activity' => $row['fitness_activity'] ?? 'None',
                    'fitness_frequency' => $row['fitness_frequency'] ?? 'N/A',
                    'suicide_attempt' => $row['suicide_attempt'] ?? 'Not Specified',
                    'suicide_reason' => $row['suicide_reason'] ?? 'Not Specified'
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode($students);
            exit();
    }
}


$analyticsData = fetchAnalyticsData($connection);
$departments = fetchDepartments($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History Analytics</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" type="text/css" href="analytics.css">
    
    <style>

        .chart-legend {
    display: flex;
    flex-wrap: nowrap;
    justify-content: flex-start; /* Changed from center to flex-start */
    gap: 1rem;
    margin-top: 1rem;
    overflow-x: auto;
    padding-bottom: 10px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
    white-space: nowrap;
    padding: 4px; /* Add padding for better appearance */
}

/* Keep these scrollbar styles */
.chart-legend::-webkit-scrollbar {
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
    height: 300px;  /* Increased from 300px */
    margin-bottom: 0.75rem;  /* Keep container spacing tight */
    padding: 0.5rem;  /* Reduced padding to give more space to chart */
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
        .card-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 4rem;
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


        .legend-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8f9fa;
            white-space: nowrap; /* Prevents text wrapping */
            flex-shrink: 0; /* Prevents items from shrinking */
        }

        .legend-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .legend-label {
            margin-right: 8px;
        }

        .legend-value {
            color: #666;

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


        .back-btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #4a5568;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-family: Arial, sans-serif;
    transition: background-color 0.3s;
}

.back-btn:hover {
    background-color: #2d3748;
}

.back-btn::before {
    content: "←";
    margin-right: 8px;
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


    <header><h1 class="mb-4">Student Profile Analytics Dashboard</h1><header>
    <div class="container-fluid mt-4">
        <a href="facilitator_dashboard.php" class="back-btn">Go Back</a>
        
        <ul class="nav nav-analytics">
            <li class="nav-item">
                <a class="nav-link" href="view_personal_info_analytics.php">Personal Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_family_background_analytics.php">Family Background</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_educational_career_analytics.php">Educational Career</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="view_medical_history_analytics.php">Medical History</a>
            </li>
        </ul>

        <div class="filter-controls">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="departmentSelect" class="form-label">Department:</label>
                    <select class="form-control" id="departmentSelect">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="courseSelect" class="form-label">Course:</label>
                    <select class="form-control" id="courseSelect" disabled>
                        <option value="">All Courses</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="conditionsCheck" checked>
                        <label class="form-check-label" for="conditionsCheck">Medical Conditions</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="stressCheck" checked>
                        <label class="form-check-label" for="stressCheck">Stress Levels</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="fitnessCheck" checked>
                        <label class="form-check-label" for="fitnessCheck">Fitness Activities</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="suicideCheck" checked>
                        <label class="form-check-label" for="suicideCheck">Suicidal Thoughts</label>
                    </div>
                    <button id="applyFilters" class="btn btn-primary ml-3">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

        <div class="row">
            <div class="col-md-6 graph-container" id="conditionsContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Medical Conditions Distribution</h5>
                        <div class="chart-container">
                            <canvas id="conditionsChart"></canvas>
                        </div>
                        <div id="conditionsLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 graph-container" id="stressContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Stress Level Distribution</h5>
                        <div class="chart-container">
                            <canvas id="stressChart"></canvas>
                        </div>
                        <div id="stressLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 graph-container" id="fitnessContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Fitness Activities Distribution</h5>
                        <div class="chart-container">
                            <canvas id="fitnessChart"></canvas>
                        </div>
                        <div id="fitnessLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 graph-container" id="suicideContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Suicidal Thoughts Distribution</h5>
                        <div class="chart-container">
                            <canvas id="suicideChart"></canvas>
                        </div>
                        <div id="suicideLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
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
// Global charts object
let charts = {
    conditionsChart: null,
    stressChart: null,
    fitnessChart: null,
    suicideChart: null
};

// Color configuration
const colors = {
    medical_conditions: {
        'Asthma': '#4299E1',
        'Hypertension': '#48BB78',
        'Diabetes': '#ED64A6',
        'Insomnia': '#ECC94B',
        'Vertigo': '#667EEA',
        'No Medical Conditions': '#9F7AEA',
        'Allergy': '#ED8936',
        'Other': '#4299E1',
        'Scoliosis/Physical condition': '#87CEEB'
    },
    stress_level: {
        'low': '#4299E1',
        'average': '#48BB78',
        'high': '#ED64A6',
        'Not Specified': '#ECC94B'
    },
    fitness_activity: {
        'Fitness Activity': '#4299E1',
        'No Fitness Activity': '#ECC94B'
    },
    suicide_attempt: {
        'No': '#4299E1',
        'Yes': '#ED64A6',
        'Not Specified': '#ECC94B'
    },
    default: [
        '#4299E1', '#48BB78', '#ED64A6', '#ECC94B', 
        '#667EEA', '#9F7AEA', '#ED8936', '#38B2AC', '#66CDAA', '#87CEEB'
    ]
};

function formatNumber(num) {
    if (num >= 1000000) return (num/1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num/1000).toFixed(1) + 'K';
    return num;
}


function createChart(elementId, data, key, title) {
    const ctx = document.getElementById(elementId);
    if (!ctx) return null;

    // Create custom legend container with wrapper
    const chartContainer = ctx.closest('.chart-container');
    const cardBody = chartContainer.closest('.card-body');

    let legendWrapper = document.getElementById(`${elementId}LegendWrapper`);
    if (!legendWrapper) {
        legendWrapper = document.createElement('div');
        legendWrapper.id = `${elementId}LegendWrapper`;
        legendWrapper.className = 'chart-legend-wrapper';
        
        let legendContainer = document.createElement('div');
        legendContainer.id = `${elementId}Legend`;
        legendContainer.className = 'chart-legend';
        
        legendWrapper.appendChild(legendContainer);
        cardBody.appendChild(legendWrapper);
    }

    let legendContainer = document.getElementById(`${elementId}Legend`);

    // Check if there's data
    if (!data || data.length === 0) {
        // Clear existing chart and legend if no data
        chartContainer.innerHTML = `
            <div class="text-center p-4">
                <div class="text-muted">
                    <i class="fas fa-chart-bar fa-3x mb-3"></i>
                    <p>No data available</p>
                </div>
            </div>
        `;
        if (legendContainer) {
            legendContainer.innerHTML = '';
        }
        return null;
    }

    // Reset canvas if needed
    const newCanvas = document.createElement('canvas');
    newCanvas.id = elementId;
    if (ctx.tagName.toLowerCase() !== 'canvas') {
        chartContainer.innerHTML = '';
        chartContainer.appendChild(newCanvas);
    } else {
        ctx.parentNode.replaceChild(newCanvas, ctx);
    }

    // Prepare data based on the chart type
    let labels, values;
    if (key === 'medical_conditions') {
        labels = data.map(item => item.condition_type);
        values = data.map(item => parseInt(item.count));
    } else if (key === 'stress_level') {
        labels = data.map(item => item.level);
        values = data.map(item => parseInt(item.count));
    } else if (key === 'fitness_activity') {
        labels = data.map(item => item.activity);
        values = data.map(item => parseInt(item.count));
    } else if (key === 'suicide_attempt') {
        labels = data.map(item => item.response);
        values = data.map(item => parseInt(item.count));
    }

    const total = values.reduce((a, b) => a + b, 0);

    // Get background colors
    const backgroundColor = labels.map(label => {
        if (colors[key] && colors[key][label]) {
            return colors[key][label];
        }
        return colors.default[labels.indexOf(label) % colors.default.length];
    });

    // Destroy existing chart if it exists
    if (charts[elementId]) {
        charts[elementId].destroy();
    }

    // Create chart configuration with consistent styling
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
                maxBarThickness: 50
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
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
                    }
                }
            }
        },
        plugins: [{
            id: 'customCanvasBackgroundColor',
            beforeDraw: (chart) => {
                const ctx = chart.canvas.getContext('2d');
                ctx.save();
                ctx.globalCompositeOperation = 'destination-over';
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, chart.width, chart.height);
                ctx.restore();
            }
        }, {
            id: 'chartEventListener',
            beforeEvent(chart, args, options) {
                const event = args.event;
                if (event.type === 'click') {
                    const elements = chart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, false);
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const value = labels[index];
                        showStudentDetails(key, value);
                    }
                }
            }
        }, {
            id: 'valueLabels',
            afterDatasetsDraw(chart) {
                const ctx = chart.ctx;
                chart.data.datasets[0].data.forEach((value, index) => {
                    const meta = chart.getDatasetMeta(0);
                    const element = meta.data[index];
                    const { x, y } = element.tooltipPosition();
                    
                    const percentage = ((value / total) * 100).toFixed(1);
                    ctx.fillStyle = '#666';
                    ctx.font = '12px Arial';
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(`(${formatNumber(value)}) `, x + 10, y);
                });
            }
        }]
    };

    // Create new chart
    const chart = new Chart(newCanvas, config);
    charts[elementId] = chart;

    // Create custom legend with consistent styling
    legendContainer.innerHTML = '';
    labels.forEach((label, index) => {
        const count = values[index];
        const percentage = ((count / total) * 100).toFixed(1);
        
        const legendItem = document.createElement('div');
        legendItem.className = 'legend-item';
        legendItem.innerHTML = `
            <div class="legend-color" style="background-color: ${backgroundColor[index]};"></div>
            <span class="legend-label">${label}</span>
            <span class="legend-value">(${percentage}%)</span>
        `;
        
        legendItem.addEventListener('click', () => {
            showStudentDetails(key, label);
        });
        
        legendItem.addEventListener('mouseover', () => {
            chart.setActiveElements([{datasetIndex: 0, index: index}]);
            chart.update();
        });
        
        legendItem.addEventListener('mouseout', () => {
            chart.setActiveElements([]);
            chart.update();
        });
        
        legendContainer.appendChild(legendItem);
    });

    // Add export functionality
    newCanvas.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        handleChartExport(elementId, chart.data, title);
    });

    return chart;
}


function handleChartExport(chartId, chartData, title) {
    const timestamp = new Date().toISOString().split('T')[0];
    const departmentText = $('#departmentSelect option:selected').text();
    const courseText = $('#courseSelect option:selected').text();
    
    let filterInfo = [];
    if (departmentText !== 'All Departments') {
        filterInfo.push(departmentText);
        if (courseText !== 'All Courses') {
            filterInfo.push(courseText);
        }
    }
    
    const filterSuffix = filterInfo.length > 0 ? ` - ${filterInfo.join(' - ')}` : '';
    const filename = `${title.toLowerCase().replace(/\s+/g, '_')}${filterSuffix}_${timestamp}.csv`;

    // Show confirmation dialog before exporting
    Swal.fire({
        title: 'Export Chart Data',
        text: 'Do you want to export this data to CSV?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Export!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Prepare CSV data
            const total = chartData.datasets[0].data.reduce((a, b) => a + b, 0);
            const rows = [
                [title.replace(/_/g, ' ') + ' Distribution '], // Added distribution title row
                [], // Empty row for spacing
                ['Category', 'Count', 'Percentage'],
                ...chartData.labels.map((label, index) => [
                    label,
                    chartData.datasets[0].data[index],
                    ((chartData.datasets[0].data[index] / total) * 100).toFixed(2) + '%'
                ]),
                [], // Empty row
                ['Total', total, '100%'],
                [], // Empty row
                ['Report Generated on:', new Date().toLocaleString()],
                ['Department:', departmentText],
                ['Course:', courseText]
            ];

            const csvContent = rows.map(row => row.join(',')).join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Export Successful',
                text: `Data has been exported to ${filename}`,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        }
    });
}

function showStudentDetails(category, value) {
    const modal = $('#studentModal');
    const studentList = $('#studentList');
    const loadingSpinner = $('.loading-spinner');
    const filters = {
        category: category,
        value: value,
        department_id: $('#departmentSelect').val(),
        course_id: $('#courseSelect').val()
    };

    $('#studentSearch').val('');
    modal.modal('show');
    studentList.addClass('d-none');
    loadingSpinner.removeClass('d-none');
    
    $.ajax({
        url: '?action=getStudents',
        method: 'POST',
        data: filters,
        success: function(students) {
            loadingSpinner.addClass('d-none');
            
            if (!students || students.length === 0) {
                studentList.html(`
                    <div class="alert alert-info">No students found matching the selected criteria.</div>
                `).removeClass('d-none');
                return;
            }
            
            const studentCards = students.map(student => {
    let referralButton = '';
if ((category === 'stress_level' && student.stress_level?.toLowerCase() === 'high') || 
    (category === 'suicide_attempt' && student.suicide_attempt?.toLowerCase() === 'yes')) {
    referralButton = `
        <button class="btn btn-warning" onclick="referToCounselor('${student.full_name}', '${category}', '${student.student_id}')">
            Refer to Counselor
        </button>`;
}

    const formatMedicalConditions = (conditions) => {
    if (!conditions || 
        conditions === 'None' || 
        conditions === 'NO MEDICAL CONDITIONS') {
        return 'None';
    }
    return conditions.split(';')
        .map(condition => condition.trim())
        .filter(condition => {
            // Filter out any condition that contains N/A
            return condition && 
                   !condition.includes('N/A') && 
                   !condition.endsWith('N/A') && 
                   condition !== 'N/A';
        })
        .map(condition => {
            // Preserve the details after the colon for special conditions
            if (condition.startsWith('Allergy:')) {
                const details = condition.split(':')[1].trim();
                return `Allergy: ${details}`;
            } else if (condition.startsWith('Other:')) {
                const details = condition.split(':')[1].trim();
                return `Other: ${details}`;
            } else if (condition.startsWith('Scoliosis/Physical condition:')) {
                const details = condition.split(':')[1].trim();
                return `Physical Condition: ${details}`;
            }
            return condition;
        })
        .join('<br>• ');
};

                return `
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="card-title mb-0">${student.full_name}</h5>
                    <small class="text-muted">Student ID: ${student.student_id}</small>
                </div>
                ${referralButton}
            </div>
                    
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Department:</strong> ${student.department_name}</p>
                    <p><strong>Course:</strong> ${student.course_name}</p>
                    <p><strong>Student ID:</strong> ${student.student_id}</p>
                </div>


                    <div class="col-md-6">
                        ${category === 'medical_conditions' ? `
                            <p><strong>Medical Conditions:</strong><br>• ${formatMedicalConditions(student.medical_conditions)}</p>
                            <p><strong>Medications:</strong> ${student.medications}</p>
                        ` : ''}
                        ${category === 'stress_level' ? `
                            <p><strong>Stress Level:</strong> ${student.stress_level}</p>
                        ` : ''}
                        ${category === 'fitness_activity' ? `
                            <p><strong>Fitness Activity:</strong> ${student.fitness_activity}</p>
                            <p><strong>Frequency:</strong> ${student.fitness_frequency}</p>
                        ` : ''}
                        ${category === 'suicide_attempt' ? `
                            <p><strong>Suicidal Thoughts:</strong> ${student.suicide_attempt}</p>
                            ${student.suicide_attempt?.toLowerCase() === 'yes' ? 
                                `<p><strong>Reason:</strong> ${student.suicide_reason || 'Not specified'}</p>` 
                            : ''}
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
}).join('');
            
            studentList.html(studentCards).removeClass('d-none');
        },
        error: function(xhr, status, error) {
            loadingSpinner.addClass('d-none');
            studentList.html(`
                <div class="alert alert-danger">Error loading student data. Please try again.</div>
            `).removeClass('d-none');
            console.error('Error:', error);
        }
    });
}

function referToCounselor(studentName, category, studentId) {
    const reason = category === 'stress_level' ? 'High Stress Level' : 'Mental Health Concern';
    
    // Get course from the student card
    const studentCard = event.target.closest('.card-body');
    const courseParagraph = Array.from(studentCard.querySelectorAll('p')).find(p => p.textContent.includes('Course:'));
    const courseText = courseParagraph ? courseParagraph.textContent.split('Course:')[1].trim() : '';
    
    // Split the student name into components
    const nameParts = studentName.split(', ');
    const lastName = nameParts[0];
    let firstName = '', middleName = '';
    
    if (nameParts[1]) {
        const remainingNames = nameParts[1].trim().split(' ');
        firstName = remainingNames[0];
        middleName = remainingNames.slice(1).join(' ');
    }

    // Redirect to referral page with parameters including student_id
    const params = new URLSearchParams({
        firstName: firstName,
        middleName: middleName || '',
        lastName: lastName,
        reason: reason,
        course: courseText,
        referralCategory: category,
        student_id: studentId  // Add this line
    }).toString();

    window.location.href = 'Referral_analytics.php?' + params;
}


$(document).ready(function() {
// Department change handler
$('#departmentSelect').change(function() {
    const departmentId = $(this).val();
    const courseSelect = $('#courseSelect');
    
    if (departmentId) {
        courseSelect.prop('disabled', true).html('<option value="">Loading...</option>');
        
        $.ajax({
            url: '?action=getCourses',
            method: 'POST',
            data: { department_id: departmentId },
            success: function(courses) {
                courseSelect.html('<option value="">All Courses</option>');
                if (courses && courses.length > 0) {
                    courses.forEach(course => {
                        courseSelect.append(`<option value="${course.id}">${course.name}</option>`);
                    });
                }
                courseSelect.prop('disabled', false);
            },
            error: function() {
                courseSelect.html('<option value="">Error loading courses</option>');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load courses. Please try again.'
                });
            }
        });
    } else {
        courseSelect.prop('disabled', true).html('<option value="">All Courses</option>');
        $('#applyFilters').click(); // Trigger filter update when department is cleared
    }
});


$('#applyFilters').click(function() {
    const filters = {
        department_id: $('#departmentSelect').val(),
        course_id: $('#courseSelect').val()
    };

    // Show loading state
    $('.chart-container').addClass('loading');
    
    // Disable only the apply filters button during loading
    $('#applyFilters').prop('disabled', true);

    $.ajax({
        url: '?action=getFilteredData',
        method: 'POST',
        data: filters,
        success: function(data) {
            // Process each chart section
            const chartConfigs = [
                {
                    checkId: '#conditionsCheck',
                    containerId: '#conditionsContainer',
                    chartId: 'conditionsChart',
                    dataKey: 'medical_conditions',
                    title: 'Medical_Conditions'
                },
                {
                    checkId: '#stressCheck',
                    containerId: '#stressContainer',
                    chartId: 'stressChart',
                    dataKey: 'stress_level',
                    title: 'Stress_Levels'
                },
                {
                    checkId: '#fitnessCheck',
                    containerId: '#fitnessContainer',
                    chartId: 'fitnessChart',
                    dataKey: 'fitness_activity',
                    title: 'Fitness_Activities'
                },
                {
                    checkId: '#suicideCheck',
                    containerId: '#suicideContainer',
                    chartId: 'suicideChart',
                    dataKey: 'suicide_attempt',
                    title: 'Mental_Health_Concerns'
                }
            ];

            chartConfigs.forEach(config => {
                const isVisible = $(`${config.checkId}`).prop('checked');
                $(config.containerId).toggleClass('hidden', !isVisible);

                if (isVisible) {
                    const chartData = data[config.dataKey];
                    const container = $(config.containerId).find('.chart-container');
                    
                    if (chartData && chartData.length > 0) {
                        // Clear existing chart container and create new canvas
                        container.html(`<canvas id="${config.chartId}"></canvas>`);
                        
                        // Create new chart
                        const chart = createChart(
                            config.chartId, 
                            chartData, 
                            config.dataKey, 
                            config.title
                        );
                    } else {
                        // Show no data message
                        container.html(`
                            <div class="text-center p-4">
                                <div class="text-muted">
                                    <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                    <p>No data available for ${config.title.replace(/_/g, ' ').toLowerCase()}</p>
                                </div>
                            </div>
                        `);
                    }
                }
            });
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update charts. Please try again.'
            });
            
            // Show error message for all visible charts
            $('.chart-container:not(.hidden)').each(function() {
                $(this).html(`
                    <div class="text-center p-4">
                        <div class="text-danger">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <p>Error loading chart data. Please try again.</p>
                        </div>
                    </div>
                `);
            });
        },
        complete: function() {
            // Remove loading state and re-enable the apply filters button
            $('.chart-container').removeClass('loading');
            $('#applyFilters').prop('disabled', false);
            
            // Keep department select always enabled
            $('#departmentSelect').prop('disabled', false);
            
            // Enable/disable course select based on department selection only
            $('#courseSelect').prop('disabled', !$('#departmentSelect').val());
        }
    });
});


    // Student search handler
    $('#studentSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#studentList .card').each(function() {
            const studentName = $(this).find('.card-title').text().toLowerCase();
            $(this).toggle(studentName.includes(searchTerm));
        });
    });

    // Initialize charts with initial data
const initialData = <?php echo json_encode($analyticsData); ?>;
createChart('conditionsChart', initialData.medical_conditions, 'medical_conditions', 'Medical_Conditions');
createChart('stressChart', initialData.stress_level, 'stress_level', 'Stress_Levels');
createChart('fitnessChart', initialData.fitness_activity, 'fitness_activity', 'Fitness_Activities');
createChart('suicideChart', initialData.suicide_attempt, 'suicide_attempt', 'Mental_Health_Concerns');

    // Handle window resize
    $(window).resize(function() {
        Object.values(charts).forEach(chart => {
            if (chart) chart.resize();
        });
    });

    // Handle checkbox changes for chart visibility
    $('.form-check-input').change(function() {
        const chartId = $(this).attr('id').replace('Check', '');
        $(`#${chartId}Container`).toggleClass('hidden', !$(this).prop('checked'));
    });
});
</script>
</body>
</html>