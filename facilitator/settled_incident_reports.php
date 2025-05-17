<?php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Function to convert text to proper case
function toProperCase($name) {
    // Split the name into parts
    $parts = explode(' ', $name);
    $properName = [];
    
    foreach ($parts as $part) {
        // Check for middle initial with period (like "C.")
        if (strlen($part) === 2 && substr($part, -1) === '.') {
            $properName[] = strtoupper($part);
        } else {
            $properName[] = ucfirst(strtolower($part));
        }
    }
    
    return implode(' ', $properName);
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

// Calculate the record range being displayed
$start_record = ($page - 1) * $records_per_page + 1;
$end_record = min($start_record + $records_per_page - 1, $total_records);

// Adjust start_record when there are no records
if ($total_records == 0) {
    $start_record = 0;
}

// Force at least 1 page even if no records
if ($total_pages < 1) {
    $total_pages = 1;
}

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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            padding: 20px;
            margin: 20px auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px;
                width: 95%;
            }
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


        td {
            padding: 12px 15px;
            vertical-align: middle;
            border: 0.1px solid #e0e0e0;
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 768px) {
            thead th {
                padding: 10px 5px;
                font-size: 12px;
            }

            td {
                padding: 8px 5px;
                font-size: 12px;
            }

           
        }

        @media (max-width: 576px) {
            
        }

        /* Search and filter styles */
        .filters-section {
            margin-bottom: 20px;
        }

        .search-container {
            position: relative;
            margin-bottom: 15px;
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
            margin-bottom: 10px;
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
            flex-wrap: wrap;
            justify-content: center;
        }

        .page-link {
            color: #009E60;
            border: 1px solid #dee2e6;
            margin: 2px;
            padding: 6px 12px;
        }

        .page-item.active .page-link {
            background-color: #009E60;
            border-color: #009E60;
        }

        @media (max-width: 576px) {
            .page-link {
                padding: 4px 8px;
                font-size: 14px;
            }
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

        @media (max-width: 768px) {
            h2 {
                font-size: 1.5rem;
                margin: 10px 0 20px;
            }
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

        @media (max-width: 576px) {
            .btn {
                padding: 4px 8px;
                font-size: 12px;
            }
        }

        /* Modal responsive styles */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
            }

            .modal-body {
                padding: 10px;
            }

            .info-section {
                padding: 10px;
            }
        }
        .pagination-info {
            text-align: center;
            margin-top: 15px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }

        .pagination-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .page-item {
            margin: 0 2px;
        }

        .page-item .page-link {
            display: flex;
            justify-content: center;
            align-items: center;
            min-width: 40px;
            height: 40px;
            color: #333;
            text-decoration: none;
            background-color: #fff;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }

        .page-item.active .page-link {
            background-color: #0d693e;
            color: white;
            border-color: #0d693e;
        }

        .page-item .page-link:hover:not(.active) {
            background-color: #f5f5f5;
        }

        .page-item.disabled .page-link {
            color: #ccc;
            pointer-events: none;
            cursor: default;
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 600;
            padding: 12px 20px;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            background-color: #f8f9fa;
            color: #009E60;
        }

        .nav-tabs .nav-link.active {
            color: #009E60;
            background-color: white;
            border-bottom: 3px solid #009E60;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="guidanceservice.html" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Guidance Services</span>
        </a>

        <ul class="nav nav-tabs" id="archiveTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" href="settled_incident_reports.php">
            <i class="fas fa-check mr-2"></i>Settled Reports
        </a>
    </li>
    <li class="nav-item"> 
        <a class="nav-link " href="archive_reports.php">
            <i class="fas fa-archive mr-2"></i>Archived Settled Reports
        </a>
    </li> 
    </ul>


        <h2 class="mb-4">Settled Incident Reports</h2>
        <div style="border-top: 3px solid #004d4d;">
            <br>
            
            <!-- Search and Filter Form -->
            <div class="filters-section">
                <div class="row">
                    <div class="col-md-3 col-12">
                        <div class="search-container">
                            <input type="text" class="form-control" id="searchInput" name="search" placeholder="Search reports..." 
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 col-12">
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
                    
                    <div class="col-md-3 col-sm-6 col-12">
                        <select class="form-control" id="sort_order" name="sort_order" onchange="updateFilters()">
                            <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Latest</option>
                            <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 col-12">
                            <a href="?" class="btn btn-secondary">Reset Filters</a>
                    </div>
                </div>
            </div>
            
            <!-- Responsive Table -->
            <div class="table-responsive">
                <table class="table table-striped table-mobile-view">
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
                    <tbody id="reportTableBody">
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            // Process student names with proper case
                            $student_names = "";
                            if (!empty($row['student_names'])) {
                                $students_array = explode(',', $row['student_names']);
                                foreach ($students_array as $index => $student) {
                                    $students_array[$index] = toProperCase($student);
                                }
                                $student_names = implode(', ', $students_array);
                            } else {
                                $student_names = $row['student_names'];
                            }
                            
                            // Also apply proper case to the reported_by field
                            $reported_by = toProperCase($row['reported_by']);
                            ?>
                            <tr class="data-row">
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($student_names); ?></td>
                                <td><?php echo htmlspecialchars($row['course_names']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['description'], 0, 30)) . (strlen($row['description']) > 30 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars($row['place']); ?></td>
                                <td><?php echo htmlspecialchars($reported_by); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['approval_date'])); ?></td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-success btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#minutesModal<?php echo $row['id']; ?>">
                                        <i class="fas fa-file-alt"></i> <span class="d-none d-md-inline">View Minutes</span>
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
                                                                <div><?php echo htmlspecialchars($reported_by); ?></div>
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
                                                // Apply proper case to persons_present and prepared_by fields
                                                $persons_present = toProperCase($minute['persons_present']);
                                                $prepared_by = toProperCase($minute['prepared_by']);
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
                                                                    <div><?php echo htmlspecialchars($persons_present); ?></div>
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
                                                                    <div><?php echo htmlspecialchars($prepared_by); ?></div>
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
                        echo "<tr id='no-data-row'><td colspan='8' class='text-center'>No settled incident reports found.</td></tr>";
                    }
                    ?>
                    <tr id="no-results-message" style="display: none;">
                        <td colspan="8" class="text-center">No settled incident reports found.</td>
                    </tr>
                </tbody>
                </table>
            </div>

            <!-- Pagination info and navigation -->
                <div class="pagination-container">
                    <div class="pagination-info">
                        <?php if ($total_records > 0): ?>
                            Showing <?php echo $start_record; ?> - <?php echo $end_record; ?> out of <?php echo $total_records; ?> records
                        <?php else: ?>
                            No records found
                        <?php endif; ?>
                    </div>
                    
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php 
                            // Maximum pages to show (not counting next/last)
                            $max_visible_pages = 3;
                            
                            // Calculate starting page based on current page
                            $start_page = max(1, min($page - floor($max_visible_pages/2), $total_pages - $max_visible_pages + 1));
                            $end_page = min($start_page + $max_visible_pages - 1, $total_pages);
                            
                            // Adjust if we're showing fewer than max pages
                            if ($end_page - $start_page + 1 < $max_visible_pages) {
                                $start_page = max(1, $end_page - $max_visible_pages + 1);
                            }
                            
                            // Display numbered pages
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort_order=<?php echo $sort_order; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next page (») -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort_order=<?php echo $sort_order; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Last page (»») -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort_order=<?php echo $sort_order; ?>" aria-label="Last">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Function to perform live search
        function performSearch(searchTerm) {
            searchTerm = searchTerm.toLowerCase();
            const dataRows = $('.data-row');
            let visibleRows = 0;
            
            dataRows.each(function() {
                const row = $(this);
                const text = row.text().toLowerCase();
                
                if (text.includes(searchTerm)) {
                    row.show();
                    visibleRows++;
                } else {
                    row.hide();
                }
            });
            
            // Show or hide the no results message
            if (visibleRows === 0 && dataRows.length > 0) {
                $('#no-results-message').show();
            } else {
                $('#no-results-message').hide();
            }
            
            // Update pagination info for client-side filtering
            if (visibleRows > 0) {
                $('.pagination-info').text(`Showing 1 - ${visibleRows} out of ${visibleRows} filtered records`);
            } else {
                $('.pagination-info').text('No records found');
            }
            
            // Hide pagination for filtered results
            if (searchTerm.length > 0) {
                $('.pagination').hide();
            } else {
                $('.pagination').show();
            }
        }

        // Handle search input for client-side filtering
        $('#searchInput').on('input', function() {
            const searchTerm = $(this).val();
            performSearch(searchTerm);
        });
        
        // Handle reset filters button
        $('#resetFilters').on('click', function() {
            // Clear the client-side filter first
            $('#searchInput').val('');
            $('.data-row').show();
            $('#no-results-message').hide();
            $('.pagination').show();
            
            // Update pagination info
            const totalRows = $('.data-row').length;
            if (totalRows > 0) {
                $('.pagination-info').text(`Showing 1 - ${totalRows} out of ${totalRows} records`);
            } else {
                $('.pagination-info').text('No records found');
            }
            
            // Navigate to the page without any filters
            window.location.href = window.location.pathname;
        });
    });

    function updateFilters() {
        const search = document.getElementById('searchInput').value;
        const filterCourse = document.getElementById('filter_course').value;
        const sortOrder = document.getElementById('sort_order').value;
        
        window.location.href = `?page=1&search=${encodeURIComponent(search)}&filter_course=${encodeURIComponent(filterCourse)}&sort_order=${sortOrder}`;
    }

    // Add responsive handling for table columns
    function adjustTableForScreenSize() {
        const width = window.innerWidth;
        const table = document.querySelector('.table-mobile-view');
        
        if (width <= 576) {
            // Very small screens - show minimal columns
            table.classList.add('very-small-screen');
        } else {
            table.classList.remove('very-small-screen');
        }
    }

    // Run on page load and window resize
    window.addEventListener('load', adjustTableForScreenSize);
    window.addEventListener('resize', adjustTableForScreenSize);
</script>
</body>
</html>