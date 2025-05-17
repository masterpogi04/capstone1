<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Get all departments
$dept_query = "SELECT * FROM departments ORDER BY name";
$departments = $connection->query($dept_query);

// Get selected department, course, and other filters
$selected_department = isset($_GET['department']) ? $_GET['department'] : '';
$selected_course = isset($_GET['course']) ? $_GET['course'] : '';
$selected_reason = isset($_GET['reason']) ? $_GET['reason'] : '';
$current_status = isset($_GET['status']) ? $_GET['status'] : 'Pending';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Get courses for the selected department
$courses = [];
if ($selected_department) {
    $course_query = "SELECT * FROM courses WHERE department_id = ? ORDER BY name";
    $stmt = $connection->prepare($course_query);
    $stmt->bind_param("i", $selected_department);
    $stmt->execute();
    $courses = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Analytics - CEIT Guidance Office</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #0d693e;
            --secondary-color: #004d4d;
            --accent-color: #F4A261;
            --hover-color: #E76F51;
            --light-bg: #f8f9fa;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --border-radius: 15px;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #2d3436;
        }
        
        .container {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 20px;
            box-shadow: var(--shadow);
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
        h2 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .nav-tabs {
            border-bottom: 2px solid var(--light-bg);
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--primary-color);
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }

        .filter-section {
            background-color: var(--light-bg);
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #dfe6e9;
            padding: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 105, 62, 0.25);
        }

        .btn-success {
            background-color: var(--primary-color);
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .chart-container {
            background-color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-top: 30px;
        }

        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow);
        }

        .modal-content .table {
            margin-top: 20px;
        }

        .table thead th {
            background-color: var(--light-bg);
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
        }

        .table td {
            vertical-align: middle;
        }

        .export-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #dfe6e9;
        }

        .export-section .btn {
            font-weight: 500;
        }

        .export-section .btn i {
            margin-right: 8px;
        }

        #resetFilters {
            background-color: #6c757d;
            border: none;
            padding: 12px;
            height: 100%;
        }

        #resetFilters:hover {
            background-color: #5a6268;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .filter-section {
                padding: 15px;
            }

            .nav-tabs .nav-link {
                padding: 8px 16px;
            }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 1200px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 10px;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="facilitator_dashboard.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <h2 class="mb-4">Referral Analytics</h2>

        <!-- Status Tabs -->
        <ul class="nav nav-tabs mb-4" id="statusTabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_status == 'Pending' ? 'active' : ''; ?>" href="#" data-status="Pending">Pending Referrals</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_status == 'Done' ? 'active' : ''; ?>" href="#" data-status="Done">Done Referrals</a>
            </li>
        </ul>

        <div class="filter-section">
  <form id="filterForm">
    <div class="row">
      <div class="col-md-3 mb-3">
        <select class="form-control" id="departmentFilter" name="department">
          <option value="">All Departments</option>
          <?php while ($dept = $departments->fetch_assoc()): ?>
            <option value="<?php echo $dept['id']; ?>" <?php echo $selected_department == $dept['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($dept['name']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-3 mb-3">
        <select class="form-control" id="courseFilter" name="course" <?php echo empty($selected_department) ? 'disabled' : ''; ?>>
          <option value="">All Courses</option>
          <?php if ($courses): while ($course = $courses->fetch_assoc()): ?>
            <option value="<?php echo $course['id']; ?>" <?php echo $selected_course == $course['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($course['name']); ?>
            </option>
          <?php endwhile; endif; ?>
        </select>
      </div>
      
      <div class="col-md-3 mb-3">
        <select class="form-control" id="reasonFilter" name="reason">
          <option value="">All Referrals</option>
          <option value="Academic concern" <?php echo $selected_reason == 'Academic concern' ? 'selected' : ''; ?>>Academic Concern</option>
          <option value="Behavior maladjustment" <?php echo $selected_reason == 'Behavior maladjustment' ? 'selected' : ''; ?>>Behavior maladjustment</option>
          <option value="Violation to school rules" <?php echo $selected_reason == 'Violation to school rules' ? 'selected' : ''; ?>>Violation to school rules</option>
          <option value="Other concern" <?php echo $selected_reason == 'Other concern' ? 'selected' : ''; ?>>Other Concerns</option>
        </select>
      </div>
      <div class="col-md-1 mb-3">
        <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
      </div>
    </div>
        <div class="row mt-3">
      <div class="col-md-3 mb-3">
        <label for="academicYear">Academic Year:</label>
        <select class="form-control" id="academicYear" name="academic_year">
          <option value="">All Academic Years</option>
          <?php
            // Get unique years from the database
            $year_query = "SELECT DISTINCT YEAR(date) as year 
               FROM referrals 
               WHERE date IS NOT NULL 
               ORDER BY year DESC";
            $years_result = $connection->query($year_query);
            
            while($year = $years_result->fetch_assoc()) {
              $academic_year = $year['year'] . '-' . ($year['year'] + 1);
              echo "<option value='" . $year['year'] . "'>" . $academic_year . "</option>";
            }
          ?>
        </select>
      </div>
          <div class="col-md-3 mb-3">
            <label for="startDate">Start Date:</label>
            <input type="date" class="form-control" id="startDate" name="start_date" max="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label for="endDate">End Date:</label>
            <input type="date" class="form-control" id="endDate" name="end_date" max="<?php echo date('Y-m-d'); ?>">
          </div>
        </div>
          </form>
          <div class="export-section">
            <div class="row">
              <div class="col-md-6 mb-3">
                <button class="btn btn-success btn-block" onclick="exportData('pdf')">
                  <i class="fas fa-file-pdf"></i> Export as PDF
                </button>
              </div>
              <div class="col-md-6 mb-3">
                <button class="btn btn-success btn-block" onclick="exportData('csv')">
                  <i class="fas fa-file-csv"></i> Export as CSV
                </button>
              </div>
            </div>
          </div>
    </div>
        <!-- Main Chart -->
        <div class="chart-container">
            <canvas id="mainChart"></canvas>
        </div>

        <!-- Modal -->
        <div id="detailModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3 id="modalTitle"></h3>
                <div id="modalBody"></div>
            </div>
        </div>
    </div>

    <script>
        let myChart = null;
        let currentStatus = '<?php echo $current_status; ?>';
        let currentReason = '<?php echo $selected_reason; ?>';

        // Initialize chart
        function initChart() {
            const ctx = document.getElementById('mainChart').getContext('2d');
            if (myChart) {
                myChart.destroy();
            }
            myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Number of Referrals',
                        data: [],
                        backgroundColor: '#0d693e',
                        borderColor: '#094e2e',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    onClick: handleChartClick
                }
            });
        }

        // Date validation function
        function validateDates() {
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            
            // Set max date to today for both inputs
            const today = new Date().toISOString().split('T')[0];
            startDate.max = today;
            endDate.max = today;
            
            // Ensure end date isn't before start date
            if (startDate.value && endDate.value) {
                if (startDate.value > endDate.value) {
                    endDate.value = startDate.value;
                }
            }
            
            loadData();
        }

        // Load data and update chart
        async function loadData() {
            const department = document.getElementById('departmentFilter').value;
            const course = document.getElementById('courseFilter').value;
            const academicYear = document.getElementById('academicYear').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            currentReason = document.getElementById('reasonFilter').value;

            try {
                const response = await fetch(`get_referral_data.php?status=${currentStatus}&department=${department}&course=${course}&reason=${currentReason}&academic_year=${academicYear}&start_date=${startDate}&end_date=${endDate}`);
                        const data = await response.json();
                const reasons = [
                    'Academic concern',
                    'Behavior maladjustment',
                    'Violation to school rules',
                    'Other concern'
                ];

                const counts = reasons.map(reason => {
                    return data.filter(item => item.reason_for_referral === reason).length;
                });

                myChart.data.labels = reasons;
                myChart.data.datasets[0].data = counts;
                myChart.update();
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        // Handle chart click
        async function handleChartClick(event, elements) {
            if (!elements.length) return;

            const index = elements[0].index;
            const reason = myChart.data.labels[index];
            
            try {
                const department = document.getElementById('departmentFilter').value;
                const course = document.getElementById('courseFilter').value;
                const academicYear = document.getElementById('academicYear').value;
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;

                const response = await fetch(`get_referral_data.php?status=${currentStatus}&department=${department}&course=${course}&reason=${reason}&academic_year=${academicYear}&start_date=${startDate}&end_date=${endDate}`);
                const data = await response.json();

                let modalContent = `
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Reason</th>
                                <th>Details</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach(item => {
                    modalContent += `
                        <tr>
                            <td>${new Date(item.date).toLocaleDateString()}</td>
                            <td>${item.first_name} ${item.last_name}</td>
                            <td>${item.course_year}</td>
                            <td>${item.reason_for_referral}</td>
                            <td>${item.violation_details || item.other_concerns || 'N/A'}</td>
                            <td>${item.status}</td>
                        </tr>
                    `;
                });

                modalContent += '</tbody></table>';

                document.getElementById('modalTitle').textContent = `${reason} Details`;
                document.getElementById('modalBody').innerHTML = modalContent;
                document.getElementById('detailModal').style.display = 'block';
            } catch (error) {
                console.error('Error loading details:', error);
            }
        }

        // Close modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('detailModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('detailModal')) {
                document.getElementById('detailModal').style.display = 'none';
            }
        }

        // Event listeners
        document.querySelectorAll('#statusTabs .nav-link').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelector('#statusTabs .active').classList.remove('active');
                e.target.classList.add('active');
                currentStatus = e.target.dataset.status;
                loadData();
            });
        });

        document.getElementById('departmentFilter').addEventListener('change', function() {
            loadCourses(this.value);
            loadData();
        });

        document.getElementById('courseFilter').addEventListener('change', loadData);
        document.getElementById('reasonFilter').addEventListener('change', loadData);
        document.getElementById('academicYear').addEventListener('change', loadData);
        document.getElementById('startDate').addEventListener('change', validateDates);
        document.getElementById('endDate').addEventListener('change', validateDates);

        // Function to load courses based on selected department
        function loadCourses(departmentId) {
            fetch(`get_courses.php?department_id=${departmentId}`)
                .then(response => response.json())
                .then(courses => {
                    const courseSelect = document.getElementById('courseFilter');
                    courseSelect.innerHTML = '<option value="">All Courses</option>';
                    courses.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        option.textContent = course.name;
                        courseSelect.appendChild(option);
                    });
                    courseSelect.disabled = !departmentId;
                })
                .catch(error => console.error('Error loading courses:', error));
        }

        // Reset filters
        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('departmentFilter').value = '';
            document.getElementById('courseFilter').value = '';
            document.getElementById('courseFilter').disabled = true;
            document.getElementById('reasonFilter').value = '';
            document.getElementById('academicYear').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            loadData();
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            // Set max date to today for date inputs
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').max = today;
            document.getElementById('endDate').max = today;
            
            initChart();
            loadData();
        });

        function exportData(format) {
            const department = document.getElementById('departmentFilter').value;
            const course = document.getElementById('courseFilter').value;
            const reason = document.getElementById('reasonFilter').value;
            const academicYear = document.getElementById('academicYear').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            window.location.href = 'export_analytics.php?format=' + format + 
                                   '&status=' + encodeURIComponent(currentStatus) +
                                   '&department=' + encodeURIComponent(department) + 
                                   '&course=' + encodeURIComponent(course) + 
                                   '&reason=' + encodeURIComponent(reason) +
                                   '&academic_year=' + encodeURIComponent(academicYear) +
                                   '&start_date=' + encodeURIComponent(startDate) +
                                   '&end_date=' + encodeURIComponent(endDate);
        }
    </script>
</body>
</html>

