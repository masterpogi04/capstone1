<?php
session_start(); 
include '../db.php';

// Check if user is logged in as dean
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header("Location: ../login.php");
    exit();
}

function getStudentDetails($connection, $student_id) {
    // Debug output
    error_log("Attempting to get details for student ID: " . $student_id);
    
    // First check in pending_student_violations
    $query = "SELECT 
                student_name,
                student_course,
                student_year_level,
                section_name,
                adviser_name
              FROM pending_student_violations
              WHERE student_id = ?
              ORDER BY created_at DESC
              LIMIT 1";
    
    $stmt = $connection->prepare($query);
    
    if ($stmt === false) {
        error_log("Prepare failed for pending_student_violations: " . $connection->error);
        return null;
    }
    
    $stmt->bind_param("s", $student_id);
    
    if (!$stmt->execute()) {
        error_log("Execute failed for pending_student_violations: " . $stmt->error);
        $stmt->close();
        return null;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Parse the student name to get first and last name
        $name_parts = explode(' ', $student['student_name']);
        if (count($name_parts) > 1) {
            $student['last_name'] = array_pop($name_parts);
            $student['first_name'] = implode(' ', $name_parts);
        } else {
            $student['first_name'] = $student['student_name'];
            $student['last_name'] = '';
        }
        
        $student['course_name'] = $student['student_course'];
        $student['year_level'] = $student['student_year_level'];
        $student['year_and_section'] = (!empty($student['student_year_level']) && !empty($student['section_name'])) ? 
                                      $student['student_year_level'] . " - " . $student['section_name'] : 
                                      "Not specified";
        $stmt->close();
        return $student;
    }
    
    $stmt->close();
    
    // If not found in pending_student_violations, check in pending_incident_witnesses
    $query = "SELECT 
                witness_name,
                witness_course,
                witness_year_level,
                section_name,
                adviser_name
              FROM pending_incident_witnesses
              WHERE witness_id = ? AND witness_type = 'student'
              ORDER BY created_at DESC
              LIMIT 1";
    
    $stmt = $connection->prepare($query);
    
    if ($stmt === false) {
        error_log("Prepare failed for pending_incident_witnesses: " . $connection->error);
        return null;
    }
    
    $stmt->bind_param("s", $student_id);
    
    if (!$stmt->execute()) {
        error_log("Execute failed for pending_incident_witnesses: " . $stmt->error);
        $stmt->close();
        return null;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Parse the witness name to get first and last name
        $name_parts = explode(' ', $student['witness_name']);
        if (count($name_parts) > 1) {
            $student['last_name'] = array_pop($name_parts);
            $student['first_name'] = implode(' ', $name_parts);
        } else {
            $student['first_name'] = $student['witness_name'];
            $student['last_name'] = '';
        }
        
        $student['course_name'] = $student['witness_course'];
        $student['year_level'] = $student['witness_year_level'];
        $student['year_and_section'] = (!empty($student['witness_year_level']) && !empty($student['section_name'])) ? 
                                      $student['witness_year_level'] . " - " . $student['section_name'] : 
                                      "Not specified";
        $stmt->close();
        return $student;
    }
    
    $stmt->close();
    return null;
}

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause for both count and main query
$where_conditions = "WHERE p.status = 'Escalated'";
$params = [];

if (!empty($search)) {
    $search_term = '%' . $connection->real_escape_string($search) . '%';
    $where_conditions .= " AND (
        p.place LIKE ? OR 
        p.description LIKE ? OR 
        CONCAT(g.first_name, ' ', g.last_name) LIKE ? OR
        psv.student_id LIKE ? OR 
        psv.student_name LIKE ?
    )";
    $params = array_fill(0, 5, $search_term);
}

if (!empty($date_from) && !empty($date_to)) {
    $where_conditions .= " AND DATE(p.date_reported) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
}

// Count total records
$count_query = "SELECT COUNT(DISTINCT p.id) as total 
                FROM pending_incident_reports p
                LEFT JOIN tbl_guard g ON p.guard_id = g.id
                LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
                $where_conditions";

$stmt = $connection->prepare($count_query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$stmt->close();

// Main query for fetching records
$query = "SELECT p.*, 
          CONCAT(g.first_name, ' ', COALESCE(g.middle_initial, ''), ' ', g.last_name) AS guard_name,
          GROUP_CONCAT(DISTINCT CONCAT(COALESCE(psv.student_id, 'NULL'), ':', 
                                     psv.student_name, ':', 
                                     COALESCE(psv.student_course, ''), ':', 
                                     COALESCE(psv.student_year_level, ''), ':', 
                                     COALESCE(psv.section_name, '')) SEPARATOR '|') AS involved_students,
          GROUP_CONCAT(DISTINCT CONCAT(piw.witness_type, ':', piw.witness_name, ':', 
                                      COALESCE(piw.witness_id, ''), ':', 
                                      COALESCE(piw.witness_email, ''), ':', 
                                      COALESCE(piw.witness_course, ''), ':', 
                                      COALESCE(piw.witness_year_level, ''), ':', 
                                      COALESCE(piw.section_name, '')) SEPARATOR '|') AS witnesses
          FROM pending_incident_reports p
          LEFT JOIN tbl_guard g ON p.guard_id = g.id
          LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
          LEFT JOIN pending_incident_witnesses piw ON p.id = piw.pending_report_id
          $where_conditions
          GROUP BY p.id
          ORDER BY p.date_reported DESC
          LIMIT ?, ?";
// Add pagination parameters
$params[] = $offset;
$params[] = $records_per_page;

$stmt = $connection->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params) - 2) . 'ii'; // Add integer types for LIMIT parameters
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escalated Guard Reports History</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
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

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}


/* Search and Filter Section */
.search-box {
    position: relative;
    margin-bottom: 20px;
}

.search-box input {
    padding-left: 35px;
    border-radius: 20px;
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
}

.filters-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.filters-section .row {
    margin-bottom: 15px;
}

/* Action Buttons */
.btn-edit, .btn-delete {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 15px;
    cursor: pointer;
    text-decoration: none;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    transition: all 0.3s ease;
    margin-right: 10px;
    border: none;
}

.btn-edit {
    background-color: #3498db;
}

.btn-edit:hover {
    background-color: #2980b9;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-delete {
    background-color: #e74c3c;
}

.btn-delete:hover {
    background-color: #c0392b;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Status Badges */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85em;
    font-weight: 500;
}

.status-pending { background-color: #ffd700; color: #000; }
.status-processing { background-color: #87ceeb; color: #000; }
.status-meeting { background-color: #98fb98; color: #000; }
.status-rejected { background-color: #ff6b6b; color: #fff; }

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

/* Pagination */
.pagination {
    margin-top: 20px;
}

.page-link {
    color: #009E60;
    border: 1px solid #dee2e6;
}

.page-item.active .page-link {
    background-color: #009E60;
    border-color: #009E60;
}

.page-link:hover {
    color: #006E42;
    background-color: #e9ecef;
}


h2{
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 15px 0 30px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}


* Search and Filter Form Styles */
.mb-4 {
    margin-bottom: 1.5rem !important;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
}


/* Refined table styles with hover effect */
.table-responsive {
    margin: 20px 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
    padding: 0.5px;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

/* Header styles */
th:first-child {
    border-top-left-radius: 10px;
}

th:last-child {
    border-top-right-radius: 10px;
}

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

thead th:last-child {
    border-right: none;
}

/* Cell styles */
td {
    padding: 12px 15px;
    vertical-align: middle;
    border: 0.1px solid #e0e0e0;
    font-size: 14px;
    text-align: center;
    background-color: transparent; /* Changed from white to transparent */
}

td:last-child {
    
}

/* Bottom rounded corners for last row */
tbody tr:last-child td:first-child {
    border-bottom-left-radius: 10px;
}

tbody tr:last-child td:last-child {
    border-bottom-right-radius: 10px;
}

/* Row hover effect */
tbody tr {
    background-color: white; /* Base background color for rows */
    transition: background-color 0.2s ease; /* Smooth transition for hover */
}



.table th,
    .table td {
        padding: 12px 15px;
        vertical-align: middle;
        font-size: 14px;
        text-align: center;
    } 

    /* Set specific widths for each column */
    .table th:nth-child(1), /* Student Name */
    .table td:nth-child(1) {
        width: 10%;
        padding:20px;
    }

    .table th:nth-child(2), /* Date Reported */
    .table td:nth-child(2) {
        width: 12%;
        padding:20px;
    }

    .table th:nth-child(3), /* Place, Date & Time */
    .table td:nth-child(3) {
        width: 20%;
        text-align: center;
        white-space: normal;
        min-width: 250px;
    }

   /* Description - making it wider */
    .table td:nth-child(4) {
        width: 10%;
        padding:20px;
        
    }

    .table th:nth-child(4){
         width: 10%;
        padding:20px;
    }

    .table th:nth-child(5), /* Involvement */
    .table td:nth-child(5) {
        width: 20%;
        padding:20px;
    }

    .table th:nth-child(6), /* Status */
    .table td:nth-child(6) {
        width: 10%;
        padding:20px;
    }

    .table th:nth-child(7), /* Action */
    .table td:nth-child(7) {
        width: 8%;
        padding:20px;
    }


/* Actions cell specific styling */
.actions-cell {
    display: flex;
    justify-content: center;
    gap: 8px;
}

/* Add this to your existing style section */
.form-label {
    font-weight: 500;
    color: var(--text-color);
    margin-bottom: 5px;
}

#resetButton {
    height: 38px;
    padding: 0 20px;
    background-color: #6c757d;
    border: none;
    color: white;
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

#resetButton:hover {
    background-color: #5a6268;
}

    </style>

</head>
<body>
    <div class="container mt-5">
    <a href="dean_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        
        <h2>Escalated Guard Reports History</h2>

        <!-- Search Form -->
<!-- Search Form -->
<form action="" method="GET" class="mb-4" id="searchForm">
    <div class="row">
        <div class="col-md-4">
            <label for="dateFrom" class="form-label">Search:</label>
            <input type="text" 
                   name="search" 
                   id="searchInput" 
                   class="form-control" 
                   placeholder="Search reports..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   autocomplete="off">
        </div>
        <div class="col-md-3">
            <label for="dateFrom" class="form-label">From Date:</label>
            <input type="date" 
                   name="date_from" 
                   id="dateFrom"
                   class="form-control" 
                   value="<?php echo htmlspecialchars($date_from); ?>"
                   max="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-3">
            <label for="dateTo" class="form-label">To Date:</label>
            <input type="date" 
                   name="date_to" 
                   id="dateTo"
                   class="form-control" 
                   value="<?php echo htmlspecialchars($date_to); ?>"
                   max="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" id="resetButton" class="btn btn-secondary">Reset</button>
        </div>
    </div>
</form>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date Reported</th>
                            <th>Incident Place,<br> Date & Time</th>
                            <th>Description</th>
                            <th>Students Involved</th>
                            <th>Witnesses</th>
                            <th>Reported By</th>
                        </tr>
                    </thead>
<tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php 
                $date_time = new DateTime($row['date_reported']);
                echo $date_time->format('F j, Y') . ' at ' . $date_time->format('g:i A'); 
                // This will output like: December 15, 2024 at 2:30 PM
                ?></td>
            <td>
                <?php 
                $place_data = $row['place']; // Using $row instead of $incident
                $place_parts = explode(' - ', $place_data);
                if (count($place_parts) > 1) {
                    echo htmlspecialchars($place_parts[0]) . ',<br>' . 
                         str_replace(' at ', '<br>at ', htmlspecialchars($place_parts[1]));
                } else {
                    echo htmlspecialchars($place_data);
                }
                ?>
            </td>
            <td><?php echo htmlspecialchars($row['description']); ?></td>
<td>
    <?php
    if (!empty($row['involved_students'])) {
        $students = explode('|', $row['involved_students']);
        $total_students = count(array_filter($students));
        $current_count = 0;

        foreach ($students as $student) {
            if (empty(trim($student))) continue;
            
            $current_count++;
            $parts = explode(':', trim($student));
            $student_id = $parts[0] ?? '';
            $student_name = $parts[1] ?? '';
            $student_course = $parts[2] ?? '';  // You already have course info
            $student_year = $parts[3] ?? '';    // You already have year level info
            $section_name = $parts[4] ?? '';    // You already have section info
            
            // Check for any form of NULL or empty student_id
            if ($student_id === 'NULL' || $student_id === 'null' || empty($student_id)) {
                echo ucwords(strtolower($student_name)) . " (Non-CEIT Student)";
            } else {
                // Instead of calling getStudentDetails(), use the data already retrieved
                echo ucwords(strtolower($student_name));
                if (!empty($student_course) && !empty($student_year)) {
                    echo " (" . $student_course . " - " . $student_year . ")";
                }
            }

            if ($current_count < $total_students) {
                echo ", <br><br>";
            } else {
                echo "<br>";
            }
        }
    } else {
        echo "No students involved";
    }
    ?>
</td>
<td>
    <?php
    if (!empty($row['witnesses'])) {
        $witnesses = explode('|', $row['witnesses']);
        $total_witnesses = count(array_filter($witnesses));
        $current_count = 0;

        foreach ($witnesses as $witness) {
            if (empty(trim($witness))) continue;
            
            $current_count++;
            $parts = explode(':', trim($witness));
            $witness_type = $parts[0] ?? '';
            $witness_name = $parts[1] ?? '';
            $witness_id = $parts[2] ?? '';
            
            if ($witness_type === 'student') {
                if (!empty($witness_id) && $witness_id !== 'null') {
                    $student_details = getStudentDetails($connection, $witness_id);
                    if ($student_details) {
                        echo ucwords(strtolower($student_details['first_name'] . ' ' . $student_details['last_name']));
                        echo " (" . $student_details['course_name'] . " - " . $student_details['year_level'] . ")";
                    } else {
                        echo ucwords(strtolower($witness_name)) . " (Non-CEIT Student)";
                    }
                } else {
                    echo ucwords(strtolower($witness_name)) . " (Non-CEIT Student)";
                }
            } else if ($witness_type === 'staff') {
    $witness_email = $parts[3] ?? ''; // Get the email from the parts array
    echo ucwords(strtolower($witness_name)) . " (Staff)";
    if (!empty($witness_email)) {
        echo " - " . $witness_email;
    }
}

            if ($current_count < $total_witnesses) {
                echo ", <br><br>";
            } else {
                echo "<br>";
            }
        }
    } else {
        echo "No witnesses recorded";
    }
    ?>
</td>
            <td><?php echo htmlspecialchars($row['guard_name']); ?></td>
        </tr>
    <?php endwhile; ?>
</tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    <?php echo $i; ?>
                                </a> 
                            </li>
                        <?php endfor; ?>
                    </ul> 
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info" role="alert">
                No incident reports found.
            </div>
        <?php endif; ?>
    </div>


<script>
$(document).ready(function() {
    // Set max date for date inputs to today
    const today = new Date().toISOString().split('T')[0];
    $('#dateFrom, #dateTo').attr('max', today);

    function updateTable() {
        const searchText = $("#searchInput").val().toLowerCase();
        const dateFrom = $("#dateFrom").val();
        const dateTo = $("#dateTo").val();

        $(".table tbody tr").each(function() {
            const row = $(this);
            // Fix date parsing by getting the date part before "at"
            const rowDate = row.find("td:first").text().split(' at ')[0]; // Gets "December 15, 2024"
            const dateReported = new Date(rowDate);
            let showRow = true;

            // Text search
            if (searchText) {
                const content = row.text().toLowerCase();
                showRow = content.includes(searchText);
            }

            // Date range filter
            if (showRow && (dateFrom || dateTo)) {
                const fromDate = dateFrom ? new Date(dateFrom) : null;
                const toDate = dateTo ? new Date(dateTo) : null;

                if (fromDate) {
                    fromDate.setHours(0, 0, 0, 0);
                    showRow = dateReported >= fromDate;
                }
                if (toDate && showRow) {
                    toDate.setHours(23, 59, 59, 999);
                    showRow = dateReported <= toDate;
                }
            }

            row.toggle(showRow);
        });

        // Update no results message
        const visibleRows = $(".table tbody tr:visible").length;
        $("#no-results-message").remove();
        if (visibleRows === 0) {
            $(".table").after(
                '<div id="no-results-message" class="alert alert-info text-center mt-3">' +
                'No incident reports found matching your criteria.</div>'
            );
        }
    }

    // Event handlers
    $("#searchInput").on("keyup", updateTable);
    $("#dateFrom, #dateTo").on("change", function() {
        const fromDate = new Date($("#dateFrom").val());
        const toDate = new Date($("#dateTo").val());
        
        // Validate date range
        if (fromDate > toDate) {
            alert("'From Date' cannot be later than 'To Date'");
            $(this).val('');
        }
        updateTable();
    });

    // Reset button handler
    $("#resetButton").click(function() {
        $("#searchInput").val('');
        $("#dateFrom").val('');
        $("#dateTo").val('');
        $(".table tbody tr").show();
        $("#no-results-message").remove();
    });
});
</script>

</body>
</html>