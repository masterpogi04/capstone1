<?php
//view_approved_reports.php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? $connection->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'meeting_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$filter_schedule = isset($_GET['filter_schedule']) ? $_GET['filter_schedule'] : '';
$filter_course = isset($_GET['filter_course']) ? $connection->real_escape_string($_GET['filter_course']) : '';

// Modified query to only count meetings with actual minutes
$query = "SELECT ir.*, sv.status as violation_status, s.first_name, s.last_name, 
          GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
          ir.description, 
          (SELECT meeting_date FROM meetings WHERE incident_report_id = ir.id ORDER BY meeting_date DESC LIMIT 1) as meeting_date,
          (SELECT COUNT(*) FROM meetings 
           WHERE incident_report_id = ir.id 
           AND meeting_minutes IS NOT NULL 
           AND TRIM(meeting_minutes) != '') as meeting_minutes_count,
          c.name as course_name
          FROM incident_reports ir 
          JOIN student_violations sv ON ir.id = sv.incident_report_id
          JOIN tbl_student s ON sv.student_id = s.student_id
          LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
          LEFT JOIN sections sec ON s.section_id = sec.id
          LEFT JOIN courses c ON sec.course_id = c.id
          WHERE (ir.status = 'For Meeting' OR ir.status = 'Approved' OR ir.status = 'Rescheduled')";

if (!empty($search)) {
    $query .= " AND (s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR ir.description LIKE '%$search%')";
}

if ($filter_schedule === 'scheduled') {
    $query .= " AND EXISTS (SELECT 1 FROM meetings m WHERE m.incident_report_id = ir.id)";
} elseif ($filter_schedule === 'unscheduled') {
    $query .= " AND NOT EXISTS (SELECT 1 FROM meetings m WHERE m.incident_report_id = ir.id)";
}

if (!empty($filter_course)) {
    $query .= " AND c.name = '$filter_course'";
}

$query .= " GROUP BY ir.id ORDER BY $sort $order";

$result = $connection->query($query);

if ($result === false) {
    die("Query failed: " . $connection->error);
}

// Fetch all courses for the filter dropdown
$course_query = "SELECT DISTINCT name FROM courses ORDER BY name";
$course_result = $connection->query($course_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports for Meeting</title>
    <<title>Student Incident Reports</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      :root {
    --primary-color: #0d693e;
    --secondary-color: #004d4d;
    --text-color: #2c3e50;
}

body {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: var(--text-color);
    margin: 0;
    padding: 0;
    justify-content: center;
    align-items: center;
    display: flex;
}

.container {
    background-color: rgba(255, 255, 255, 0.98);
    border-radius: 15px;
    padding: 30px;
    margin: 50px auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

/* Modern Back Button */
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

/* Heading Styles */
h1 {
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 15px 0 30px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    border-bottom: 3px solid #004d4d;
    padding-bottom: 15px;
}

/* Search and Filter Form */
.search-filter-form {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.form-control {
    border-radius: 20px;
    padding: 8px 15px;
    border: 1px solid #ced4da;
}

/* Table Styles */
.table-responsive {
    margin: 20px 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 0.5px;
}

.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

/* Table Header */
thead th {
    background: #009E60;
    color: #ffffff;
    font-weight: 600;
    text-transform: uppercase;
    padding: 15px;
    font-size: 14px;
    letter-spacing: 0.5px;
    white-space: nowrap;
    text-align: center;
}

thead th:first-child {
    border-top-left-radius: 10px;
}

thead th:last-child {
    border-top-right-radius: 10px;
}

/* Table Cells */
td {
    padding: 12px 15px;
    vertical-align: middle;
    border: 0.1px solid #e0e0e0;
    font-size: 14px;
    text-align: center;
}

/* Action Buttons */
.btn {
    border-radius: 15px;
    padding: 8px 15px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    transition: all 0.3s ease;
    margin: 2px;
}

.btn-primary {
    background-color: #3498db;
    border-color: #3498db;
}

.btn-primary:hover {
    background-color: #2980b9;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-success {
    background-color: #2ecc71;
    border-color: #2ecc71;
}

.btn-success:hover {
    background-color: #27ae60;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-warning {
    background-color: #f1c40f;
    border-color: #f1c40f;
    color: #fff;
}

.btn-warning:hover {
    background-color: #f39c12;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-info {
    background-color: #009E60;
    border-color: #009E60;
    color: #fff;
}

.btn-info:hover {
    background-color: #008050;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        margin: 20px;
        padding: 15px;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    td {
        padding: 8px 10px;
    }
}
    </style>
</head>
<body>
    <div class="container mt-5">
    <a href="guidanceservice.html" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
</a>
<div class="style="border-bottom: 3px solid #004d4d;">
        <h2>Incident Reports for Meeting</h2>
       
        
        <form action="" method="GET" class="mb-4 search-filter-form">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search student or violation" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select name="filter_schedule" class="form-control">
                        <option value="">All Schedules</option>
                        <option value="scheduled" <?php echo $filter_schedule === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="unscheduled" <?php echo $filter_schedule === 'unscheduled' ? 'selected' : ''; ?>>Unscheduled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="filter_course" class="form-control">
                        <option value="">All Courses</option>
                        <?php while ($course = $course_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($course['name']); ?>" <?php echo $filter_course === $course['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-control">
                        <option value="meeting_date" <?php echo $sort === 'meeting_date' ? 'selected' : ''; ?>>Meeting Date</option>
                        <option value="date_reported" <?php echo $sort === 'date_reported' ? 'selected' : ''; ?>>Date Reported</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="order" class="form-control">
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </form>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date Reported</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Violation</th>
                    <th>Witnesses</th>
                    <th>Meeting Date</th>
                    <th>Resolution Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['date_reported']); ?></td>
                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['witnesses']); ?></td>
                        <td><?php echo $row['meeting_date'] ? htmlspecialchars(date('F j, Y, g:i A', strtotime($row['meeting_date']))) : 'Not scheduled'; ?></td>
                        <td>
                            <?php if ($row['meeting_minutes_count'] > 0): ?>
                                <button class="btn btn-info btn-sm" onclick="window.location.href='view_all_minutes.php?id=<?php echo $row['id']; ?>'">
                                    <i class="fas fa-eye"></i> View All Minutes (<?php echo $row['meeting_minutes_count']; ?> Meetings)
                                </button>
                            <?php else: ?>
                                <?php if ($row['meeting_date']): ?>
                                    No minutes recorded yet
                                <?php else: ?>
                                    No meeting scheduled
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="schedule_generator.php?report_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                <?php echo $row['meeting_date'] ? 'Reschedule Meeting' : 'Schedule Meeting'; ?>
                            </a>
                            <button class="btn btn-success btn-sm" onclick="window.location.href='add_meeting_minutes.php?id=<?php echo $row['id']; ?>'">
                                <i class="fas fa-plus"></i> Add Minutes
                            </button>
                           <form action="referral_incident_reports.php" method="GET" style="display: inline;">
                                <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    Refer to Counselor
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>


</body>
</html>