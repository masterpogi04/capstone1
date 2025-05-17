<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Function to get count of students by a specific field
function getStudentCountByField($connection, $field) {
    $query = "SELECT $field, COUNT(*) as count FROM student_profiles GROUP BY $field ORDER BY count DESC";
    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Enhanced queries to get student details
function getStudentsByProblem($connection, $problem) {
    $query = "SELECT sp.student_id, CONCAT(ts.first_name, ' ', ts.middle_name, ' ', ts.last_name) as full_name, 
              c.name as course_name, sp.problems
              FROM student_profiles sp
              JOIN tbl_student ts ON sp.student_id = ts.student_id
              JOIN sections s ON ts.section_id = s.id
              JOIN courses c ON s.course_id = c.id
              WHERE sp.problems LIKE ?";
    $stmt = $connection->prepare($query);
    $problemParam = "%$problem%";
    $stmt->bind_param("s", $problemParam);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getStudentsByStressLevel($connection, $level) {
    $query = "SELECT sp.student_id, CONCAT(ts.first_name, ' ', ts.middle_name, ' ', ts.last_name) as full_name, 
              c.name as course_name, sp.stress_level
              FROM student_profiles sp
              JOIN tbl_student ts ON sp.student_id = ts.student_id
              JOIN sections s ON ts.section_id = s.id
              JOIN courses c ON s.course_id = c.id
              WHERE sp.stress_level = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $level);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle AJAX requests for student details
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'getStudentsByProblem' && isset($_GET['problem'])) {
        $students = getStudentsByProblem($connection, $_GET['problem']);
        echo json_encode($students);
        exit;
    }
    if ($_GET['action'] === 'getStudentsByStress' && isset($_GET['level'])) {
        $students = getStudentsByStressLevel($connection, $_GET['level']);
        echo json_encode($students);
        exit;
    }
}

// Get analytics data
$cityData = getStudentCountByField($connection, 'city');
$genderData = getStudentCountByField($connection, 'gender');
$yearLevelData = getStudentCountByField($connection, 'year_level');
$familyIncomeData = getStudentCountByField($connection, 'family_income');

// Medications
$medicationsQuery = "SELECT 
    CASE WHEN medications = 'NO MEDICATIONS' THEN 'No' ELSE 'Yes' END AS has_medications,
    COUNT(*) as count 
    FROM student_profiles 
    GROUP BY has_medications";
$medicationsData = $connection->query($medicationsQuery)->fetch_all(MYSQLI_ASSOC);

// Medical Conditions
$conditionsQuery = "SELECT medical_conditions, COUNT(*) as count FROM student_profiles GROUP BY medical_conditions ORDER BY count DESC LIMIT 5";
$conditionsData = $connection->query($conditionsQuery)->fetch_all(MYSQLI_ASSOC);

// Problems
$problemsQuery = "SELECT problems, COUNT(*) as count FROM student_profiles GROUP BY problems ORDER BY count DESC LIMIT 5";
$problemsData = $connection->query($problemsQuery)->fetch_all(MYSQLI_ASSOC);

// Fitness Activity
$fitnessData = getStudentCountByField($connection, 'fitness_activity');

// Stress Level
$stressData = getStudentCountByField($connection, 'stress_level');

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Analytics - CEIT Guidance Office</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #FAF3E0;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #F4A261;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 24px;
            font-weight: bold;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1A6E47;
            margin-bottom: 30px;
        }
        .chart-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-title {
            color: #1A6E47;
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
        }
        .btn-back {
            background-color: #F4A261;
            color: black;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
        }

        .btn-back:hover {
            background-color: #e76f51;
            text-decoration: none;
            color: black;
        }
        .student-details-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            max-width: 800px;
            width: 90%;
        }

        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .student-table th, .student-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .student-table th {
            background-color: #f5f5f5;
        }

        .close-modal {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            font-size: 20px;
        }
    </style>


</head>
<body>
    <div class="header">
        CEIT - GUIDANCE OFFICE
    </div>

    <div class="container">
    <a href="view_profiles.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to Student Profiles
    </a>
    
    <h1 class="text-center">Student Analytics</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <h3>Gender Distribution</h3>
                <canvas id="genderChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h3>Year Level Distribution</h3>
                <canvas id="yearLevelChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h3>Top 5 Cities</h3>
                <canvas id="cityChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h3>Family Income Distribution</h3>
                <canvas id="incomeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h3>Students with Medications</h3>
                <canvas id="medicationsChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h3>Top 5 Medical Conditions</h3>
                <canvas id="conditionsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h3>Top 5 Student Problems</h3>
                <canvas id="problemsChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h3>Fitness Activity</h3>
                <canvas id="fitnessChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6 offset-md-3">
            <div class="chart-container">
                <h3>Student Stress Levels</h3>
                <canvas id="stressChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Modal for Student Details -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="studentTableContainer"></div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        // Create chart functions
        function createPieChart(elementId, labels, data, title) {
            return new Chart(document.getElementById(elementId), {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                            '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: title
                        }
                    }
                }
            });
        }

        function createBarChart(elementId, labels, data, title) {
            return new Chart(document.getElementById(elementId), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Students',
                        data: data,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: title
                        }
                    },
                    onClick: function(evt, elements) {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const label = this.data.labels[index];
                            if (elementId === 'problemsChart') {
                                fetchAndDisplayStudents('problem', label);
                            } else if (elementId === 'stressChart') {
                                fetchAndDisplayStudents('stress', label);
                            }
                        }
                    }
                }
            });
        }

        /// Initialize all charts
        const genderChart = createPieChart('genderChart', 
            <?php echo json_encode(array_column($genderData, 'gender')); ?>,
            <?php echo json_encode(array_column($genderData, 'count')); ?>,
            'Gender Distribution'
        );

        const yearLevelChart = createBarChart('yearLevelChart',
            <?php echo json_encode(array_column($yearLevelData, 'year_level')); ?>,
            <?php echo json_encode(array_column($yearLevelData, 'count')); ?>,
            'Year Level Distribution'
        );

        const cityChart = createBarChart('cityChart',
            <?php echo json_encode(array_slice(array_column($cityData, 'city'), 0, 5)); ?>,
            <?php echo json_encode(array_slice(array_column($cityData, 'count'), 0, 5)); ?>,
            'Top 5 Cities'
        );

        const incomeChart = createPieChart('incomeChart', 
            <?php echo json_encode(array_column($familyIncomeData, 'family_income')); ?>,
            <?php echo json_encode(array_column($familyIncomeData, 'count')); ?>,
            'Family Income Distribution'
        );

        const medicationsChart = createPieChart('medicationsChart', 
            <?php echo json_encode(array_column($medicationsData, 'has_medications')); ?>,
            <?php echo json_encode(array_column($medicationsData, 'count')); ?>,
            'Students with Medications'
        );

        const conditionsChart = createBarChart('conditionsChart', 
            <?php echo json_encode(array_column($conditionsData, 'medical_conditions')); ?>,
            <?php echo json_encode(array_column($conditionsData, 'count')); ?>,
            'Top 5 Medical Conditions'
        );

        const problemsChart = createBarChart('problemsChart', 
            <?php echo json_encode(array_column($problemsData, 'problems')); ?>,
            <?php echo json_encode(array_column($problemsData, 'count')); ?>,
            'Top 5 Student Problems'
        );

        const fitnessChart = createPieChart('fitnessChart', 
            <?php echo json_encode(array_column($fitnessData, 'fitness_activity')); ?>,
            <?php echo json_encode(array_column($fitnessData, 'count')); ?>,
            'Fitness Activity Distribution'
        );

        const stressChart = createBarChart('stressChart', 
            <?php echo json_encode(array_column($stressData, 'stress_level')); ?>,
            <?php echo json_encode(array_column($stressData, 'count')); ?>,
            'Student Stress Levels'
        );

        // Function to fetch and display student details
        function fetchAndDisplayStudents(type, value) {
            const action = type === 'problem' ? 'getStudentsByProblem' : 'getStudentsByStress';
            const param = type === 'problem' ? 'problem' : 'level';
            
            fetch(`view_profiles_analytics.php?action=${action}&${param}=${encodeURIComponent(value)}`)
                .then(response => response.json())
                .then(students => displayStudentModal(students, value, type));
        }

        function displayStudentModal(students, value, type) {
            const modalTitle = document.getElementById('modalTitle');
            const tableContainer = document.getElementById('studentTableContainer');
            
            modalTitle.textContent = `Students with ${type === 'problem' ? 'Problem: ' : 'Stress Level: '} ${value}`;

            let tableHTML = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            students.forEach(student => {
                tableHTML += `
                    <tr>
                        <td>${student.student_id}</td>
                        <td>${student.full_name}</td>
                        <td>${student.course_name}</td>
                        <td>
                            <a href="Referral_analytics.php?student_id=${student.student_id}" 
                               class="btn btn-primary btn-sm">Refer to Counselor</a>
                        </td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            tableContainer.innerHTML = tableHTML;

            $('#studentModal').modal('show');
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>