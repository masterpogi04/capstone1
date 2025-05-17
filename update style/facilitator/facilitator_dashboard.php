<?php
session_start();
include '../db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$facilitator_id = $_SESSION['user_id'];

// Fetch facilitator details
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name, profile_picture FROM tbl_facilitator WHERE id = ?");
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $facilitator = $result->fetch_assoc();
    // Construct full name from components
    $name = trim($facilitator['first_name'] . ' ' . 
            ($facilitator['middle_initial'] ? $facilitator['middle_initial'] . '. ' : '') . 
            $facilitator['last_name']);
    $profile_picture = $facilitator['profile_picture'];
} else {
    die("Facilitator not found.");
}
$stmt->close();

// Fetch departments with error handling
$dept_query = "SELECT DISTINCT d.id, d.name 
               FROM departments d 
               INNER JOIN sections sec ON d.id = sec.department_id 
               INNER JOIN tbl_student s ON sec.id = s.section_id";
               
$dept_result = $connection->query($dept_query);
if (!$dept_result) {
    die("Error fetching departments: " . $connection->error);
}

$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

// Modified query with proper JOIN syntax and error handling
$base_query = "SELECT 
    s.gender,
    d.name as department,
    c.name as course,
    COUNT(d.id) as count
FROM tbl_student s
INNER JOIN sections sec ON s.section_id = sec.id
INNER JOIN departments d ON sec.department_id = d.id
INNER JOIN courses c ON sec.course_id = c.id
GROUP BY s.gender, d.name, c.name
ORDER BY d.name, c.name, s.gender";

$result = $connection->query($base_query);
if (!$result) {
    die("Error executing query: " . $connection->error);
}

$chartData = [];
while ($row = $result->fetch_assoc()) {
    $chartData[] = $row;
}

// Debug information (remove in production)
if (empty($chartData)) {
    error_log("No data returned from query: " . $base_query);
}

$inventory_query = "SELECT 
    d.name as department,
    COUNT(DISTINCT s.student_id) as total_students,
    COUNT(DISTINCT CASE WHEN spi.student_id IS NOT NULL THEN s.student_id END) as filled_inventory
FROM tbl_student s
INNER JOIN sections sec ON s.section_id = sec.id
INNER JOIN departments d ON sec.department_id = d.id
LEFT JOIN student_profiles spi ON s.student_id = spi.student_id
GROUP BY d.name
ORDER BY d.name";

$inventory_result = $connection->query($inventory_query);
if (!$inventory_result) {
    die("Error executing inventory query: " . $connection->error);
}

$inventoryData = [];
while ($row = $inventory_result->fetch_assoc()) {
    $inventoryData[] = $row;
}

// Remove duplicate queries and close connection
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEIT - Guidance Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    <style>

.dropdown {
    position: relative;
    display: inline-block;
}

.analytics-btn {
    background-color: #1A6E47;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

.analytics-btn:hover {
    background-color: #145536;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 200px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1001;
    border-radius: 5px;
}

.dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.dropdown-content a:hover {
    background-color: #f1f1f1;
    border-radius: 5px;
}

.dropdown:hover .dropdown-content {
    display: block;
}

        .footer {
        background-color: #ff9042;
        color: #ecf0f1;
        text-align: center;
        padding: 15px;
        position: fixed;
        bottom: 0;
        left: 255px;
        right: 0;
        height: 50px;
        z-index: 1000;
        }

        

        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            height: 400px; /* Fixed height for consistency */
            display: flex;
            flex-direction: column;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .dashboard-card h3 {
            color: #1A6E47;
            margin-bottom: 20px;
            font-family: 'Georgia', serif;
            flex-shrink: 0; /* Prevents header from shrinking */
        }

        .chart-container {
            position: relative;
            flex: 1; /* Takes remaining space */
            min-height: 0; /* Allows container to shrink */
            width: 100%;
        }

        .engagement-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 20px;
        }

        .metric-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s ease;
        }

        .metric-card:hover {
            background-color: #e9ecef;
        }

        .metric-label {
            font-size: 1.1rem;
            color: #1A6E47;
            font-weight: 500;
        }

        .metric-value {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ff9042;
            background-color: #fff;
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .main-content {
            margin-left: 250px;
            padding: 80px 20px 70px; /* Adjusted padding to account for header and footer */
            flex: 1;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Chart-specific styling */
        canvas {
            max-height: 100%;
            width: 100% !important;
        }

        @media (max-width: 768px) {
            .dashboard-card {
                height: 350px; /* Slightly smaller height for mobile */
            }
            
            .metric-card {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }

        .profile-section img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 3px solid #ecf0f1;
    object-fit: cover;
    backface-visibility: hidden;  /* Prevents flickering */
    transform: translateZ(0);     /* Forces GPU acceleration */
    -webkit-transform: translateZ(0);
    will-change: transform;       /* Optimizes for animations */
}   


    

    .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            height: 450px;
            display: flex;
            flex-direction: column;
        }

        .dashboard-card h3 {
            color: #1A6E47;
            margin-bottom: 10px;
            font-family: 'Georgia', serif;
            text-align: center;
        }

        .chart-container {
            flex: 1;
            position: relative;
            width: 100%;
            min-height: 0;
            padding-top: 20px;
        }

        .student-engagement {
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
            padding: 20px;
        }

        .engagement-metric {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .engagement-metric .label {
            font-size: 1.1rem;
            color: #1A6E47;
            font-weight: 500;
        }

        .engagement-metric .value {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ff9042;
            background-color: white;
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }



        .filter-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #1A6E47;
            font-weight: 500;
        }
        
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
        }

        .charts-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    
    @media (max-width: 1200px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
    }

    .analytics-btn {
    display: inline-block;
    padding: 12px 24px;
    background: linear-gradient(to right, #4a90e2, #357abd);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-family: Arial, sans-serif;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.analytics-btn:before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        120deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: 0.5s;
}

.analytics-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    background: linear-gradient(to right, #357abd, #2868a0);
}

.analytics-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.analytics-btn:hover:before {
    left: 100%;
}
    </style>
</head>
<body>

    <div class="header">
        <h1>CAVITE STATE UNIVERSITY-MAIN<h1>
    </div>
    <?php include 'facilitator_sidebar.php'; ?> <!-- Include sidebar with admin-specific links -->
    <main class="main-content">
        <div class="dropdown">
    <a href="view_personal_info_analytics.php" class="analytics-btn">
        Student Profile Analytics
    </a>
</div>
    </div>
</header>

    <div class="filter-container">
        <div class="filter-row">
            <div class="filter-group">
                <label for="departmentFilter">Department:</label>
                <select id="departmentFilter">
                    <option value="all">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['name']); ?>">
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="courseFilter">Course:</label>
                <select id="courseFilter">
                    <option value="all">All Courses</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Student Distribution Card -->
    <div class="dashboard-card">
        <h3>Student Distribution</h3>
        <div class="chart-container">
            <canvas id="studentChart"></canvas>
        </div>
        <div class="totals-container" style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
            <div style="display: flex; justify-content: space-around; text-align: center;">
                <div class="total-box">
                    <h4 style="color: #1A6E47; margin-bottom: 5px;">Total Students</h4>
                    <span id="totalCount" style="font-size: 1.5rem; font-weight: bold;">0</span>
                </div>
                <div class="total-box">
                    <h4 style="color: #2E7D32; margin-bottom: 5px;">Male Students</h4>
                    <span id="maleCount" style="font-size: 1.5rem; font-weight: bold;">0</span>
                </div>
                <div class="total-box">
                    <h4 style="color: #C2185B; margin-bottom: 5px;">Female Students</h4>
                    <span id="femaleCount" style="font-size: 1.5rem; font-weight: bold;">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Profile Inventory Card -->
    <div class="dashboard-card" style="height: auto;">
        <h3>Student Profile Inventory Completion</h3>
        <div class="inventory-stats-container">
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; padding: 20px;">
                <?php foreach ($inventoryData as $data): ?>
                    <div class="stat-card" style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div class="stat-header" style="font-size: 1.2rem; color: #1A6E47; font-weight: bold; margin-bottom: 15px;">
                            <?php echo htmlspecialchars($data['department']); ?>
                        </div>
                        <div class="stat-body" style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="stat-item" style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="stat-label" style="color: #1A6E47;">Total Students:</span>
                                <span class="stat-value" style="font-weight: bold; color: #ff9042;">
                                    <?php echo number_format($data['total_students']); ?>
                                </span>
                            </div>
                            <div class="stat-item" style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="stat-label" style="color: #1A6E47;">Completed Inventory:</span>
                                <span class="stat-value" style="font-weight: bold; color: #ff9042;">
                                    <?php echo number_format($data['filled_inventory']); ?>
                                </span>
                            </div>
                            <div class="stat-item" style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="stat-label" style="color: #1A6E47;">Completion Rate:</span>
                                <span class="stat-value" style="font-weight: bold; color: #ff9042;">
                                    <?php 
                                        $rate = $data['total_students'] > 0 
                                            ? round(($data['filled_inventory'] / $data['total_students']) * 100, 1)
                                            : 0;
                                        echo $rate . '%';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 All Rights Reserved</p>
    </footer>
</div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
// Store the full dataset
const fullData = <?php echo json_encode($chartData); ?>;
let studentChart;

// Define colors for gender
const genderColors = {
    'MALE': '#2E7D32',
    'FEMALE': '#C2185B'
};

// Initialize the chart with improved data handling
function initializeChart(data) {
    const ctx = document.getElementById('studentChart').getContext('2d');
    if (studentChart) {
        studentChart.destroy();
    }

    studentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Male',
                    data: [],
                    backgroundColor: genderColors.MALE,
                    borderWidth: 0
                },
                {
                    label: 'Female',
                    data: [],
                    backgroundColor: genderColors.FEMALE,
                    borderWidth: 0
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                datalabels: {
                    color: '#000',
                    anchor: 'end',
                    align: 'right',
                    offset: 4,
                    formatter: function(value) {
                        return value > 0 ? value.toLocaleString() : '';
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true
                },
                y: {
                    stacked: true
                }
            }
        }
    });
    
    updateChart();
}

// Improved chart update function with proper data aggregation
function updateChart() {
    const departmentFilter = document.getElementById('departmentFilter').value;
    const courseFilter = document.getElementById('courseFilter').value;

    let filteredData = fullData;

    // Apply department and course filters
    if (departmentFilter !== 'all') {
        filteredData = filteredData.filter(item => item.department === departmentFilter);
    }
    if (courseFilter !== 'all') {
        filteredData = filteredData.filter(item => item.course === courseFilter);
    }

    // Calculate totals
    let totalMale = 0;
    let totalFemale = 0;
    
    // Group data by department/course and gender
    const groupedData = {};
    filteredData.forEach(item => {
        let key;
        if (courseFilter !== 'all') {
            key = item.course;
        } else if (departmentFilter !== 'all') {
            key = item.course;
        } else {
            key = item.department;
        }

        if (!groupedData[key]) {
            groupedData[key] = {
                MALE: 0,
                FEMALE: 0
            };
        }
        groupedData[key][item.gender] += parseInt(item.count);
        
        // Update totals
        if (item.gender === 'MALE') {
            totalMale += parseInt(item.count);
        } else if (item.gender === 'FEMALE') {
            totalFemale += parseInt(item.count);
        }
    });

    // Update totals display
    document.getElementById('maleCount').textContent = totalMale.toLocaleString();
    document.getElementById('femaleCount').textContent = totalFemale.toLocaleString();
    document.getElementById('totalCount').textContent = (totalMale + totalFemale).toLocaleString();

    // Sort data by total count (male + female) in descending order
    const sortedEntries = Object.entries(groupedData)
        .sort(([,a], [,b]) => (b.MALE + b.FEMALE) - (a.MALE + a.FEMALE));

    const labels = sortedEntries.map(([key]) => key);
    const maleData = sortedEntries.map(([,value]) => value.MALE);
    const femaleData = sortedEntries.map(([,value]) => value.FEMALE);

    // Update chart
    studentChart.data.labels = labels;
    studentChart.data.datasets[0].data = maleData;
    studentChart.data.datasets[1].data = femaleData;

    studentChart.update();
}

// Improved course options update function
function updateCourseOptions() {
    const departmentFilter = document.getElementById('departmentFilter').value;
    const courseSelect = document.getElementById('courseFilter');
    
    const courses = new Set(fullData
        .filter(item => departmentFilter === 'all' || item.department === departmentFilter)
        .map(item => item.course)
        .sort());
    
    courseSelect.innerHTML = '<option value="all">All Courses</option>';
    courses.forEach(course => {
        const option = document.createElement('option');
        option.value = course;
        option.textContent = course;
        courseSelect.appendChild(option);
    });
}

// Event listeners
document.getElementById('departmentFilter').addEventListener('change', () => {
    updateCourseOptions();
    updateChart();
});
document.getElementById('courseFilter').addEventListener('change', updateChart);

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initializeChart();
    updateCourseOptions();
});
</script>
</body>
</html>