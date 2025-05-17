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
        'family_income' => "SELECT sp.family_income, COUNT(*) as count 
                           FROM student_profiles sp 
                           LEFT JOIN courses c ON sp.course_id = c.id 
                           $whereClause 
                           GROUP BY family_income ORDER BY family_income",
        'birth_order' => "SELECT sp.birth_order, COUNT(*) as count 
                         FROM student_profiles sp 
                         LEFT JOIN courses c ON sp.course_id = c.id 
                         $whereClause 
                         GROUP BY birth_order ORDER BY birth_order",
        'siblings' => "SELECT sp.siblings, COUNT(*) as count 
                      FROM student_profiles sp 
                      LEFT JOIN courses c ON sp.course_id = c.id 
                      $whereClause 
                      GROUP BY siblings ORDER BY siblings",
        'parent_status' => "SELECT 
            CASE 
                WHEN father_name = 'N/A' AND mother_name = 'N/A' THEN 'No Parents'
                WHEN father_name = 'N/A' THEN 'Single Parent (Mother)'
                WHEN mother_name = 'N/A' THEN 'Single Parent (Father)'
                ELSE 'Both Parents'
            END as parent_status,
            COUNT(*) as count
            FROM student_profiles sp 
            LEFT JOIN courses c ON sp.course_id = c.id 
            $whereClause
            GROUP BY parent_status"
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
            if (isset($row[$key]) || isset($row['parent_status'])) {
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
            
            $whereConditions = [];
            $params = [];
            $types = '';
            
            if ($category === 'parent_status') {
                $whereConditions[] = "CASE 
                    WHEN sp.father_name = 'N/A' AND sp.mother_name = 'N/A' THEN 'No Parents'
                    WHEN sp.father_name = 'N/A' THEN 'Single Parent (Mother)'
                    WHEN sp.mother_name = 'N/A' THEN 'Single Parent (Father)'
                    ELSE 'Both Parents'
                END = ?";
            } else {
                $whereConditions[] = "sp.$category = ?";
            }
            $params[] = $value;
            $types .= 's';
            
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
                c.name as course_name,
                d.name as department_name
                FROM student_profiles sp 
                LEFT JOIN courses c ON sp.course_id = c.id 
                LEFT JOIN departments d ON c.department_id = d.id
                WHERE $whereClause
                ORDER BY sp.last_name, sp.first_name";
            
            $stmt = $connection->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = [
                    'full_name' => $row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] : ''),
                    'department_name' => $row['department_name'] ?? 'Not Assigned',
                    'course_name' => $row['course_name'] ?? 'Not Assigned',
                    'father_name' => $row['father_name'] ?? 'N/A',
                    'mother_name' => $row['mother_name'] ?? 'N/A',
                    'guardian_name' => $row['guardian_name'] ?? 'N/A',
                    'siblings' => $row['siblings'] ?? 'N/A',
                    'birth_order' => $row['birth_order'] ?? 'N/A',
                    'family_income' => $row['family_income'] ?? 'N/A'
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
    <title>Family Background Analytics</title>
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
        
        <ul class="nav nav-analytics">
            <li class="nav-item">
                <a class="nav-link" href="view_personal_info_analytics.php">Personal Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="view_family_background_analytics.php">Family Background</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_educational_career_analytics.php">Educational Career</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_medical_history_analytics.php">Medical History</a>
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
                        <input class="form-check-input" type="checkbox" id="incomeCheck" checked>
                        <label class="form-check-label" for="incomeCheck">Family Income Distribution</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="parentStatusCheck" checked>
                        <label class="form-check-label" for="parentStatusCheck">Parent Status Distribution</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="birthOrderCheck" checked>
                        <label class="form-check-label" for="birthOrderCheck">Birth Order Distribution</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="siblingsCheck" checked>
                        <label class="form-check-label" for="siblingsCheck">Siblings Distribution</label>
                    </div>
                    <button id="applyFilters" class="btn btn-primary ml-3">Apply Filters</button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 graph-container" id="incomeContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Family Income Distribution</h5>
                        <div class="chart-container">
                            <canvas id="incomeChart"></canvas>
                        </div>
                        <div id="incomeLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 graph-container" id="parentStatusContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Parent Status Distribution</h5>
                        <div class="chart-container">
                            <canvas id="parentStatusChart"></canvas>
                        </div>
                        <div id="parentStatusLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 graph-container" id="birthOrderContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Birth Order Distribution</h5>
                        <div class="chart-container">
                            <canvas id="birthOrderChart"></canvas>
                        </div>
                        <div id="birthOrderLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 graph-container" id="siblingsContainer">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Number of Siblings Distribution</h5>
                        <div class="chart-container">
                            <canvas id="siblingsChart"></canvas>
                        </div>
                        <div id="siblingsLegend" class="chart-legend"></div>
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
        // Initialize global variables
        let charts = {
            incomeChart: null,
            parentStatusChart: null,
            birthOrderChart: null,
            siblingsChart: null
        };

        const colors = {
            family_income: {
                'Below ₱10,000': '#4299E1',
                '₱10,000 - ₱30,000': '#48BB78',
                '₱30,001 - ₱50,000': '#ED64A6',
                '₱50,001 - ₱70,000': '#ECC94B',
                'Above ₱70,000': '#667EEA'
            },
            parent_status: {
                'Both Parents': '#4299E1',
                'Single Parent (Mother)': '#48BB78',
                'Single Parent (Father)': '#ED64A6',
                'No Parents': '#ECC94B'
            },
            default: [
                '#4299E1', '#48BB78', '#ED64A6', '#ECC94B', 
                '#667EEA', '#ED8936', '#9F7AEA', '#38B2AC'
            ]
        };

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
    function createChart(elementId, data, key, title) {
            const ctx = document.getElementById(elementId);
            if (!ctx) return null;

            // Create legend wrapper if it doesn't exist
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
            const labels = data.map(item => item[key] || item['parent_status']);
            const values = data.map(item => parseInt(item.count));
            const total = values.reduce((a, b) => a + b, 0);

            // Get background colors
            const backgroundColor = labels.map(label => {
                if (colors[key] && colors[key][label]) {
                    return colors[key][label];
                }
                return colors.default[labels.indexOf(label) % colors.default.length];
            });

            // Destroy existing chart
            if (charts[elementId]) {
                charts[elementId].destroy();
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
            maxBarThickness: 50
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
                showStudentDetails(key, label);
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
                    ctx.fillText(`(${formatNumber(value)})`, x + 10, y);
                });
            }
        }]
    };

            // Create new chart
            const chart = new Chart(ctx, config);
            charts[elementId] = chart;

            // Create custom legend
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
                
                legendContainer.appendChild(legendItem);
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

    // Format values with thousands separator and ensure proper number formatting
    // Format values with proper number formatting, preserving ranges
const formattedRows = labels.map((label, index) => {
    const value = values[index];
    const percentage = ((value / total) * 100).toFixed(2);
    
    // Replace any en dash or em dash with regular hyphen
    const formattedLabel = label.replace(/[–—]/g, '-');
    
    return [
        formattedLabel,
        value.toString(),
        percentage + '%'
    ];
});

    const rows = [
        [title.replace(/_/g, ' ') ], // Added distribution title row
                [], // Empty row for spacing
        ['Category', 'Count', 'Percentage'],
        ...formattedRows,
        [], // Empty row for spacing
        ['Total', total.toString(), '100%'],
        [], // Empty row for spacing
        ['Report Generated:', new Date().toLocaleString()],
        ['Department:', departmentText],
        ['Course:', courseText]
    ];

    // Convert rows to CSV format, ensuring proper handling of commas and quotes
    const csvContent = rows.map(row => 
        row.map(cell => {
            // If cell contains commas, quotes, or newlines, wrap in quotes
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

            // Modified search event handler
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
                    
                    const studentCards = students.map(student => `
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">${student.full_name}</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Course:</strong> ${student.course_name}</p>
                                        <p><strong>Father's Name:</strong> ${student.father_name}</p>
                                        <p><strong>Mother's Name:</strong> ${student.mother_name}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Guardian:</strong> ${student.guardian_name}</p>
                                        <p><strong>Birth Order:</strong> ${student.birth_order}</p>
                                        <p><strong>Number of Siblings:</strong> ${student.siblings}</p>
                                        <p><strong>Family Income:</strong> ${student.family_income}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    
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
                }
            });
            
// Single search handler with specific selector
    $(document).on('input', '#studentSearch', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#studentModal #studentList .card').each(function() {
            const studentName = $(this).find('.card-title').text().toLowerCase();
            $(this).toggle(studentName.includes(searchTerm));
        });
    });
            // Handle filter application
            $('#applyFilters').click(function() {
                const departmentId = $('#departmentSelect').val();
                const courseId = $('#courseSelect').val();
                const showIncome = $('#incomeCheck').prop('checked');
                const showParentStatus = $('#parentStatusCheck').prop('checked');
                const showBirthOrder = $('#birthOrderCheck').prop('checked');
                const showSiblings = $('#siblingsCheck').prop('checked');

                $('.chart-container').addClass('loading');

                $.ajax({
                    url: '?action=getFilteredData',
                    method: 'POST',
                    data: {
                        department_id: departmentId,
                        course_id: courseId
                    },
                    success: function(data) {
                        if (showIncome) {
                            createChart('incomeChart', data.family_income, 'family_income', 'Family Income Distribution');
                        }
                        if (showParentStatus) {
                            createChart('parentStatusChart', data.parent_status, 'parent_status', 'Parent Status Distribution');
                        }
                        if (showBirthOrder) {
                            createChart('birthOrderChart', data.birth_order, 'birth_order', 'Birth Order Distribution');
                        }
                        if (showSiblings) {
                            createChart('siblingsChart', data.siblings, 'siblings', 'Siblings Distribution');
                        }

                        $('#incomeContainer').toggleClass('hidden', !showIncome);
                        $('#parentStatusContainer').toggleClass('hidden', !showParentStatus);
                        $('#birthOrderContainer').toggleClass('hidden', !showBirthOrder);
                        $('#siblingsContainer').toggleClass('hidden', !showSiblings);

                        $('.chart-container').removeClass('loading');
                    },
                    error: function() {
                        $('.chart-container').removeClass('loading');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update charts. Please try again.'
                        });
                    }
                });
            });

        // Initial chart creation
        const initialData = <?php echo json_encode($analyticsData); ?>;
        createChart('incomeChart', initialData.family_income, 'family_income', 'Family Income Distribution');
        createChart('parentStatusChart', initialData.parent_status, 'parent_status', 'Parent Status Distribution');
        createChart('birthOrderChart', initialData.birth_order, 'birth_order', 'Birth Order Distribution');
        createChart('siblingsChart', initialData.siblings, 'siblings', 'Siblings Distribution');

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
