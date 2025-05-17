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

// Modified fetchAnalyticsData function to include department and course filters
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
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    $queries = [
        'gender' => "SELECT sp.gender, COUNT(*) as count 
                    FROM student_profiles sp 
                    LEFT JOIN courses c ON sp.course_id = c.id 
                    $whereClause 
                    GROUP BY gender ORDER BY gender",
        'civil_status' => "SELECT sp.civil_status, COUNT(*) as count 
                          FROM student_profiles sp 
                          LEFT JOIN courses c ON sp.course_id = c.id 
                          $whereClause 
                          GROUP BY civil_status ORDER BY civil_status",
        'age' => "SELECT sp.age, COUNT(*) as count 
                 FROM student_profiles sp 
                 LEFT JOIN courses c ON sp.course_id = c.id 
                 $whereClause 
                 GROUP BY age ORDER BY age",
        'year_level' => "SELECT sp.year_level, COUNT(*) as count 
                        FROM student_profiles sp 
                        LEFT JOIN courses c ON sp.course_id = c.id 
                        $whereClause 
                        GROUP BY year_level ORDER BY year_level"
    ];
    
    $data = [];
    foreach ($queries as $key => $query) {
        if (!empty($params)) {
            $stmt = $connection->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $connection->query($query);
        }
        
        $data[$key] = [];
        while ($row = $result->fetch_assoc()) {
            if ($row[$key] !== null) {
                $data[$key][] = $row;
            }
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
            
            $whereConditions = ["sp.$category = ?"];
            $params = [$value];
            $types = 's';
            
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
                c.name as course_name
                FROM student_profiles sp 
                LEFT JOIN courses c ON sp.course_id = c.id 
                WHERE $whereClause
                ORDER BY sp.last_name, sp.first_name";
            
            $stmt = $connection->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = [
                    'profile_id' => $row['profile_id'],
                    'full_name' => $row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] : ''),
                    'course_name' => $row['course_name'] ?? 'Not Assigned',
                    'year_level' => $row['year_level'] ?? 'N/A',
                    'gender' => $row['gender'] ?? 'N/A',
                    'age' => $row['age'] ?? 'N/A',
                    'email' => $row['email'] ?? 'N/A',
                    'contact_number' => $row['contact_number'] ?? 'N/A',
                    'civil_status' => $row['civil_status'] ?? 'N/A',
                    'spouse_name' => $row['spouse_name'] ?? 'N/A',
                    'spouse_occupation' => $row['spouse_occupation'] ?? 'N/A'
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
    <title>Student Profile Analytics</title>
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
            height: 300px;
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
        .card-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
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
            display: inline-flex; /* Changed to inline-flex */
            flex-wrap: nowrap;
            gap: 1rem;
            min-width: min-content; /* Ensures content doesn't wrap */
            padding: 0 4px;
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
        
        <!-- Analytics Navigation Tabs -->
        <ul class="nav nav-analytics">
            <li class="nav-item">
                <a class="nav-link active" href="view_personal_info_analytics.php">Personal Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_family_background_analytics.php">Family Background</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_educational_career_analytics.php">Educational Career</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_medical_history_analytics.php">Medical History</a>
            </li>
        </ul>
        
        <!-- Replace the existing filter-controls div with this updated version -->
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
                <input class="form-check-input" type="checkbox" id="genderCheck" checked>
                <label class="form-check-label" for="genderCheck">Sex Distribution</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="civilStatusCheck" checked>
                <label class="form-check-label" for="civilStatusCheck">Civil Status Distribution</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="ageCheck" checked>
                <label class="form-check-label" for="ageCheck">Age Distribution</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="yearLevelCheck" checked>
                <label class="form-check-label" for="yearLevelCheck">Year Level Distribution</label>
            </div>
            <button id="applyFilters" style="bottom: 20px;" class="btn btn-primary ml-3">Apply Filters</button>
        </div>
    </div>
</div>
        
        <div class="row">
            <div class="col-md-6 graph-container" id="genderContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sex Distribution</h5>
                        <div class="chart-container">
                            <canvas id="genderChart"></canvas>
                        </div>
                        <div id="genderLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 graph-container" id="civilStatusContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Civil Status Distribution</h5>
                        <div class="chart-container">
                            <canvas id="civilStatusChart"></canvas>
                        </div>
                        <div id="civilStatusLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 graph-container" id="ageContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Age Distribution</h5>
                        <div class="chart-container">
                            <canvas id="ageChart"></canvas>
                        </div>
                        <div id="ageLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 graph-container" id="yearLevelContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Year Level Distribution</h5>
                        <div class="chart-container">
                            <canvas id="yearLevelChart"></canvas>
                        </div>
                        <div id="yearLevelLegend" class="chart-legend"></div>
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
    genderChart: null,
    civilStatusChart: null,
    ageChart: null,
    yearLevelChart: null
};

const colors = {
    gender: {
        'MALE': '#ED64A6',
        'FEMALE': '#4299E1'
    },
    civil_status: {
        'Single': '#48BB78',
        'Married': '#ECC94B',
        'Divorced': '#ED8936',
        'Separated': '#9F7AEA',
        'Widowed': '#667EEA'
    },
    year_level: {
        'First Year': '#4299E1',
        'Second Year': '#48BB78',
        'Third Year': '#ED64A6',
        'Fourth Year': '#ECC94B',
        'Fifth Year': '#38B2AC',
        'Irregular': '#667EEA'
    },
    default: [
        '#ED64A6', '#4299E1', '#48BB78', '#ECC94B', 
        '#667EEA', '#ED8936', '#9F7AEA', '#38B2AC'
    ]
};

// Function to format large numbers
function formatNumber(num) {
    if (num >= 1000000) {
        return (num/1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num/1000).toFixed(1) + 'K';
    }
    return num;
}

// Enhanced chart creation function with consistent legend and label display
function createChart(elementId, data, key, title) {
        const ctx = document.getElementById(elementId);
        if (!ctx) return null;
        
        // Create custom legend container with wrapper
        let legendWrapper = document.getElementById(`${elementId}LegendWrapper`);
        if (!legendWrapper) {
            legendWrapper = document.createElement('div');
            legendWrapper.id = `${elementId}LegendWrapper`;
            legendWrapper.className = 'chart-legend-wrapper';
            
            let legendContainer = document.createElement('div');
            legendContainer.id = `${elementId}Legend`;
            legendContainer.className = 'chart-legend';
            
            legendWrapper.appendChild(legendContainer);
            ctx.parentNode.appendChild(legendWrapper);
        }
        
        let legendContainer = document.getElementById(`${elementId}Legend`);
    
    // Prepare data
    const labels = data.map(item => item[key]);
    const values = data.map(item => parseInt(item.count));
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
                    display: false // Disable default legend
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
    const chart = new Chart(ctx, config);
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
            
            // Event listeners remain the same
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

        return chart;
    }

// Function to handle chart export
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

    // Prepare CSV data with headers and totals
    const total = chartData.datasets[0].data.reduce((a, b) => a + b, 0);
    const rows = [
        [title.replace(/_/g, ' ') ], // Added distribution title row
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

    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: `Data has been exported to ${filename}`,
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    });
}

// Function to update all charts
function updateCharts(data) {
    const chartContainers = {
        gender: $('#genderContainer'),
        civil_status: $('#civilStatusContainer'),
        age: $('#ageContainer'),
        year_level: $('#yearLevelContainer')
    };

    const titleMap = {
        gender: 'Sex Distribution',
        civil_status: 'Civil Status Distribution',
        age: 'Age Distribution',
        year_level: 'Year Level Distribution'
    };

    Object.entries(chartContainers).forEach(([key, container]) => {
        const chartData = data[key];
        
        if (chartData && chartData.length > 0) {
            container.find('.chart-container').html(`<canvas id="${key}Chart"></canvas>`);
            const chart = createChart(`${key}Chart`, chartData, key, titleMap[key]);


            
            // Add export context menu
            const canvas = document.getElementById(`${key}Chart`);
            if (canvas) {
                canvas.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    
                    Swal.fire({
                        title: 'Export Chart Data',
                        text: 'Do you want to export this data to CSV?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, export!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            handleChartExport(`${key}Chart`, chart.data, titleMap[key]);
                        }
                    });
                });
            }
        } else {
            container.find('.chart-container').html(`
                <div class="text-center p-4">
                    <div class="text-muted">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <p>No data available for ${titleMap[key].toLowerCase()}</p>
                    </div>
                </div>
            `);
        }
    });
}

// Function to show student details
function showStudentDetails(category, value) {
    const modal = $('#studentModal');
    const studentList = $('#studentList');
    const loadingSpinner = $('.loading-spinner');
    const departmentId = $('#departmentSelect').val();
    const courseId = $('#courseSelect').val();

    // Add this to your showStudentDetails function just before the modal.modal('show') line
$('#studentSearch').val(''); // Clear search when opening modal

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
            if (students.length === 0) {
                loadingSpinner.addClass('d-none');
                studentList
                    .html('<div class="alert alert-info">No students found for this category.</div>')
                    .removeClass('d-none');
                return;
            }
            
            const studentCards = students.map(student => `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">${student.full_name}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Course:</strong> ${student.course_name}</p>
                                <p><strong>Year Level:</strong> ${student.year_level}</p>
                                <p><strong>Gender:</strong> ${student.gender}</p>
                                <p><strong>Civil Status:</strong> ${student.civil_status}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Age:</strong> ${student.age}</p>
                                <p><strong>Email:</strong> ${student.email || 'N/A'}</p>
                                <p><strong>Contact:</strong> ${student.contact_number || 'N/A'}</p>
                            </div>
                        </div>
                        ${student.civil_status.toLowerCase() === 'married' ? `
                        <div class="mt-3">
                            <h6 class="font-weight-bold">Spouse Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> ${student.spouse_name || 'N/A'}</p>
                                    <p><strong>Occupation:</strong> ${student.spouse_occupation || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
            
            loadingSpinner.addClass('d-none');
            studentList
                .html(studentCards)
                .removeClass('d-none');
        },
        error: function(xhr, status, error) {
            loadingSpinner.addClass('d-none');
            studentList
                .html('<div class="alert alert-danger">Error loading student data. Please try again.</div>')
                .removeClass('d-none');
            console.error('Error fetching student details:', error);
        }
    });
}

// Initialize everything when document is ready
$(document).ready(function() {
    // Initialize charts with initial data
    const initialData = <?php echo json_encode($analyticsData); ?>;
    updateCharts(initialData);

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
                    if (courses && courses.length > 0) {
                        courses.forEach(function(course) {
                            courseSelect.append(`<option value="${course.id}">${course.name}</option>`);
                        });
                    } else {
                        courseSelect.html('<option value="">No courses available</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching courses:', error);
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
            applyFilters(); // Automatically refresh charts
        }
    });



    // Modified search event handler
    $('#studentSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#studentList .card').each(function() {
            const studentName = $(this).find('.card-title').text().toLowerCase();
            $(this).toggle(studentName.includes(searchTerm));
        });
    });



    // Handle filter application
    $('#applyFilters').click(function() {
        const departmentId = $('#departmentSelect').val();
        const courseId = $('#courseSelect').val();
        const showGender = $('#genderCheck').prop('checked');
        const showCivilStatus = $('#civilStatusCheck').prop('checked');
        const showAge = $('#ageCheck').prop('checked');
        const showYearLevel = $('#yearLevelCheck').prop('checked');

        // Show loading state
        $('.chart-container').addClass('loading');

        $.ajax({
            url: '?action=getFilteredData',
            method: 'POST',
            data: {
                department_id: departmentId,
                course_id: courseId
            },
            success: function(data) {
                updateCharts(data);
                
                // Toggle chart visibility
                $('#genderContainer').toggleClass('hidden', !showGender);
                $('#civilStatusContainer').toggleClass('hidden', !showCivilStatus);
                $('#ageContainer').toggleClass('hidden', !showAge);
                $('#yearLevelContainer').toggleClass('hidden', !showYearLevel);
                
                $('.chart-container').removeClass('loading');
            },
            error: function(xhr, status, error) {
                console.error('Error updating charts:', error);
                $('.chart-container').removeClass('loading');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update charts. Please try again.'
                });
            }
        });
    });

    // Handle checkbox changes
    $('.form-check-input').change(function() {
        const chartId = $(this).attr('id').replace('Check', '');
        $(`#${chartId}Container`).toggleClass('hidden', !$(this).prop('checked'));
    });
});

</script>
</body>
</html>