<?php
session_start();
include '../db.php'; // Ensure database connection is established
// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
// Get the admin's ID from the session
$admin_id = $_SESSION['user_id'];
// Fetch the admin's details from the database
$stmt = $connection->prepare("SELECT first_name, last_name, profile_picture FROM tbl_admin WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error); // Output the error message
}
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $name = $admin['first_name'] . ' ' . $admin['last_name'];  // Concatenate first and last name
    $profile_picture = $admin['profile_picture'];
} else {
    die("Admin not found.");
}
// Fetch user counts for each table
$user_tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_student', 'tbl_guard'];
$user_counts = [];

foreach ($user_tables as $table) {
    $count_query = "SELECT COUNT(*) as count FROM $table";
    $count_result = mysqli_query($connection, $count_query);
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $user_counts[$table] = $count_row['count'];
    } else {
        $user_counts[$table] = 0;
    }
}

mysqli_close($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css/" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<style>
   /* Analytics Dashboard Styling */
:root {
    --primary-light: #b2fba5;
    --primary: #a6e999;
    --primary-medium: #99d88e;
    --primary-dark: #8dc682;
    --accent: #80b577;
    --deep: #74a36b;
    --text-dark: #2c3e50;
    --text-light: #ffffff;
    --shadow: 0 8px 24px rgba(116, 163, 107, 0.2);
}

/* Analytics Cards Container */
.analytics-section {
    margin-bottom: 2rem;
}

.analytics-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}

.analytics-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(149, 157, 165, 0.3);
}

/* Card Header Styling */
.analytics-card .card-header {
    background: #008F57;
    color: white;
    padding: 1.5rem;
    border: none;
}

.analytics-card .card-title {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.analytics-card .card-title i {
    font-size: 1.5rem;
}

/* Table Styling */
.analytics-table {
    margin: 0;
    width: 100%;
}

.analytics-table thead th {
    background: #cbf5dd;
    color: var(--text-dark);
    font-weight: 600;
    padding: 1.25rem;
    border: none;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
}

.analytics-table tbody tr {
    transition: background-color 0.2s ease;
}

.analytics-table tbody tr:nth-child(even) {
    background-color: rgba(67, 97, 238, 0.05);
}

.analytics-table tbody tr:hover {
    background-color: rgba(67, 97, 238, 0.1);
}

.analytics-table td {
    padding: 1.25rem;
    font-size: 1rem;
    color: var(--text-dark);
    border-bottom: 1px solid var(--grid-line);
}


.analytics-table td:last-child {
    font-weight: 600;
    color: var(--chart-primary);
}

/* Chart Styling */
.chart-container {
    padding: 1.5rem;
    position: relative;
}

canvas#userChart {
    max-height: 350px !important;
    margin: 1rem 0;
}

/* Custom Legend Styling */
.chart-legend {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(67, 97, 238, 0.05);
    border-radius: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #2c3e50;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.count-badge {
    color: black;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}


/* Responsive Design */
@media (max-width: 768px) {
    .analytics-card {
        margin-bottom: 1.5rem;
    }

    .analytics-table td, 
    .analytics-table th {
        padding: 1rem;
    }

    canvas#userChart {
        max-height: 300px !important;
    }

    .chart-legend {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
}

/* Chart Tooltip Customization */
.chart-tooltip {
    background: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(4px);
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    padding: 0.75rem !important;
    border: 1px solid rgba(67, 97, 238, 0.1) !important;
}
</style>
<body>
    <div class="header">
        <h1>CAVITE STATE UNIVERSITY-MAIN<h1>
    </div>
    <?php include 'admin_sidebar.php'; ?> <!-- Include sidebar with admin-specific links -->
    <main class="main-content">
    <div class="row analytics-section">
    <div class="col-md-6">
        <div class="analytics-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-people-fill"></i>
                    User Analytics
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>User Type</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php foreach ($user_counts as $table => $count): ?>
                                    <tr>
                                        <td><?php echo ucwords(str_replace('tbl_', '', $table)); ?></td>
                                        <td> <span class="count-badge"><?php echo $count; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
        <div class="analytics-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-bar-chart-fill"></i>
                    User Distribution
                </h3>
            </div>
                <div class="card-body">
                <div class="chart-container">
                    <canvas id="userChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
    <footer class="footer">
        <p>Contact number | Email | Copyright</p>
    </footer>

    <script>
        var ctx = document.getElementById('userChart').getContext('2d');
var userChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($table) { return ucwords(str_replace('tbl_', '', $table)); }, array_keys($user_counts))); ?>,
        datasets: [{
            label: 'Number of Users',
            data: <?php echo json_encode(array_values($user_counts)); ?>,
            backgroundColor: [
                'rgba(178, 251, 165, 0.7)',
                'rgba(166, 233, 153, 0.7)',
                'rgba(153, 216, 142, 0.7)',
                'rgba(141, 198, 130, 0.7)',
                'rgba(128, 181, 119, 0.7)',
                'rgba(116, 163, 107, 0.7)'
            ],
            borderColor: [
                'rgb(178, 251, 165)',
                'rgb(166, 233, 153)',
                'rgb(153, 216, 142)',
                'rgb(141, 198, 130)',
                'rgb(128, 181, 119)',
                'rgb(116, 163, 107)'
            ],
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    display: true,
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    stepSize: 1,
                    font: {
                        family: "'Inter', sans-serif",
                        size: 12
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        family: "'Inter', sans-serif",
                        size: 12
                    }
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: "'Inter', sans-serif",
                        size: 12,
                        weight: '600'
                    },
                    padding: 20
                }
            },
            title: {
                display: true,
                text: 'CEIT User Distribution',
                font: {
                    family: "'Inter', sans-serif",
                    size: 16,
                    weight: '600'
                },
                padding: {
                    top: 10,
                    bottom: 30
                }
            }
        }
    }
});
    </script>
</b>
</html>