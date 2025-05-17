<?php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Get facilitator information
$facilitator_id = $_SESSION['user_id'];
$facilitator_name = $_SESSION['name'] ?? 'Facilitator';

// Pagination parameters
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get search and filter parameters
$search = isset($_GET['search']) ? $connection->real_escape_string($_GET['search']) : '';
$filter_course = isset($_GET['filter_course']) ? $connection->real_escape_string($_GET['filter_course']) : '';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Base query
$base_query = "FROM incident_reports ir 
               JOIN student_violations sv ON ir.id = sv.incident_report_id
               JOIN tbl_student s ON sv.student_id = s.student_id
               JOIN sections sec ON s.section_id = sec.id
               JOIN courses c ON sec.course_id = c.id
               WHERE ir.status = 'settled' 
               AND ir.facilitator_id = ?";

// Add search condition
if (!empty($search)) {
    $base_query .= " AND (s.first_name LIKE '%$search%' 
                         OR s.last_name LIKE '%$search%' 
                         OR ir.description LIKE '%$search%'
                         OR ir.id LIKE '%$search%')";
}

// Add course filter
if (!empty($filter_course)) {
    $base_query .= " AND c.name = '$filter_course'";
}

// Count total records for pagination
$count_query = "SELECT COUNT(DISTINCT ir.id) as total " . $base_query;
$stmt = $connection->prepare($count_query);
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query
$query = "SELECT ir.id, ir.place, ir.reported_by, ir.approval_date,
          ir.description, ir.resolution_status, ir.resolution_notes,
          ir.date_reported,  /* Added this line */
          GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name)) as student_names,
          GROUP_CONCAT(DISTINCT c.name) as course_names " . 
          $base_query . 
          " GROUP BY ir.id
            ORDER BY ir.approval_date " . $sort_order . 
          " LIMIT ? OFFSET ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("iii", $facilitator_id, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Get all courses for filter dropdown
$course_query = "SELECT DISTINCT c.name FROM courses c ORDER BY c.name";
$course_result = $connection->query($course_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settled Incident Reports - Facilitator Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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

        /* Modern back button */
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

        /* Table styles */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }

        thead th {
            background: #009E60;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            padding: 15px;
            font-size: 14px;
            letter-spacing: 0.5px;
            text-align: center;
        }

        td {
            padding: 12px 15px;
            vertical-align: middle;
            border: 0.1px solid #e0e0e0;
            font-size: 14px;
            text-align: center;
        }

        /* Search and filter styles */
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-container input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid #ced4da;
        }

        .form-control {
            border-radius: 20px;
            padding: 8px 15px;
        }

        /* Modal styles */
        .modal-content {
            border-radius: 15px;
        }

        .modal-header {
            background: #009E60;
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .info-header {
            font-weight: 600;
            color: #009E60;
            margin-bottom: 10px;
            border-bottom: 2px solid #009E60;
            padding-bottom: 5px;
        }

        /* Pagination styles */
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

        /* Title styles */
        h2 {
            font-weight: 700;
            font-size: 2rem;
            text-align: center;
            margin: 15px 0 30px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* Action buttons */
        .btn-success {
            background-color: #009E60;
            border-color: #009E60;
        }

        .btn-success:hover {
            background-color: #008050;
            border-color: #008050;
        }

        .btn-export {
            background-color: #2EDAA8;
            color: white;
        }

        .btn-export:hover {
            background-color: #28C498;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
    <a href="guidanceservice.html" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
</a>
</a>

                <h2 class="mb-4">Settled Incident Reports</h2>
                <div style="border-top: 3px solid #004d4d;">
                    <br>
                    
 <!-- Search and Filter Form -->
 <div class="row">
 <div class="col-md-4">
 <div class="search-container">
                <input type="text" class="form-control" id="search" name="search" placeholder="Search reports..." 
                       value="<?php echo htmlspecialchars($search); ?>" onchange="updateFilters()">
            </div>
    </div>
       
        <form class="mb-4" method="GET" action="">
        <div class="row">
        <div class="col-md-3">
                <select class="form-control" id="filter_course" name="filter_course" onchange="updateFilters()">
                    <option value="">All Courses</option>
                    <?php while ($course = $course_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($course['name']); ?>" 
                                <?php echo $filter_course === $course['name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-control" id="sort_order" name="sort_order" onchange="updateFilters()">
                    <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Latest Settlement First</option>
                    <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Earliest Settlement First</option>
                </select>
            </div>
        </div>
                    </form>
        
                
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Description</th>
                                <th>Place</th>
                                <th>Reported By</th>
                                <th>Settlement Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_names']); ?></td>
                                        <td><?php echo htmlspecialchars($row['course_names']); ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo htmlspecialchars($row['place']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reported_by']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['approval_date'])); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-success btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#minutesModal<?php echo $row['id']; ?>">
                                                <i class="fas fa-file-alt me-1"></i> View Minutes
                                            </button>
                                        </td>
                                    </tr>

                                   <!-- Modal for Meeting Minutes -->
                                    <div class="modal fade" id="minutesModal<?php echo $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-file-alt me-2"></i>Meeting Minutes
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="info-section">
                                                    <div class="info-header">Incident Report Information</div>
                                                    <div class="info-content">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="info-row">
                                                                    <div class="info-label">Date Reported:</div>
                                                                    <div><?php echo date('F d, Y H:i:s', strtotime($row['date_reported'])); ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="info-row">
                                                                    <div class="info-label">Incident Location:</div>
                                                                    <div><?php echo htmlspecialchars($row['place']); ?></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-3">
                                                            <div class="col-md-12">
                                                                <div class="info-row">
                                                                    <div class="info-label">Incident Description:</div>
                                                                    <div><?php echo nl2br(htmlspecialchars($row['description'])); ?></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-3">
                                                            <div class="col-md-6">
                                                                <div class="info-row">
                                                                    <div class="info-label">Reported By:</div>
                                                                    <div><?php echo htmlspecialchars($row['reported_by']); ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="info-row">
                                                                    <div class="info-label">Settlement Date:</div>
                                                                    <div><?php echo date('F d, Y', strtotime($row['approval_date'])); ?></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php
                                                $minutes_sql = "SELECT * FROM meetings WHERE incident_report_id = ? ORDER BY meeting_sequence ASC";
                                                $minutes_stmt = $connection->prepare($minutes_sql);
                                                $minutes_stmt->bind_param("s", $row['id']);
                                                $minutes_stmt->execute();
                                                $minutes_result = $minutes_stmt->get_result();
                                                
                                                $meeting_count = 1;
                                                while($minute = $minutes_result->fetch_assoc()) {
                                                    ?>
                                                    <div class="info-section">
                                                        <div class="minutes-header d-flex justify-content-between align-items-center">
                                                            <span>Meeting #<?php echo $meeting_count; ?></span>
                                                            <span class="meeting-date"><?php echo date('F d, Y', strtotime($minute['meeting_date'])); ?></span>
                                                        </div>
                                                        <div class="info-content">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="info-row">
                                                                        <div class="info-label">Meeting Time:</div>
                                                                        <div><?php echo date('h:i A', strtotime($minute['meeting_date'])); ?></div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="info-row">
                                                                        <div class="info-label">Venue:</div>
                                                                        <div><?php echo htmlspecialchars($minute['venue']); ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row mt-3">
                                                                <div class="col-12">
                                                                    <div class="info-row">
                                                                        <div class="info-label">Persons Present:</div>
                                                                        <div><?php echo htmlspecialchars($minute['persons_present']); ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row mt-3">
                                                                <div class="col-12">
                                                                    <div class="info-row">
                                                                        <div class="info-label">Meeting Minutes:</div>
                                                                        <div><?php echo nl2br(htmlspecialchars($minute['meeting_minutes'])); ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row mt-3">
                                                                <div class="col-12">
                                                                    <div class="info-row">
                                                                        <div class="info-label">Prepared By:</div>
                                                                        <div><?php echo htmlspecialchars($minute['prepared_by']); ?></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php
                                                    $meeting_count++;
                                                }
                                                ?>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="generate_minutes_pdf.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-export" 
                                                   target="_blank">
                                                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                                                </a>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No settled incident reports found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($total_pages > 1): ?>
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort_order=<?php echo $sort_order; ?>">First</a>
                            </li>
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort_order=<?php echo $sort_order; ?>">Previous</a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort_order=<?php echo $sort_order; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort_order=<?php echo $sort_order; ?>">Next</a>
                            </li>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort_order=<?php echo $sort_order; ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateFilters() {
            const search = document.getElementById('search').value;
            const filterCourse = document.getElementById('filter_course').value;
            const sortOrder = document.getElementById('sort_order').value;
            
            window.location.href = `?page=1&search=${encodeURIComponent(search)}&filter_course=${encodeURIComponent(filterCourse)}&sort_order=${sortOrder}`;
        }
    </script>
</body>
</html>