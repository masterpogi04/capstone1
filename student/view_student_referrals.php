<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'DESC';

// Build base WHERE clause
$where_clause = "WHERE r.student_id = ?";

// Add status filter
if ($status_filter) {
    $where_clause .= " AND r.status = ?";
}

// Count query
$count_query = "
    SELECT COUNT(*) as total_records 
    FROM referrals r
    $where_clause
";

$count_stmt = $connection->prepare($count_query);
if ($count_stmt === false) {
    die("Prepare failed: " . $connection->error);
}

if ($status_filter) {
    $count_stmt->bind_param("ss", $student_id, $status_filter);
} else {
    $count_stmt->bind_param("s", $student_id);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$row = $count_result->fetch_assoc();
$total_records = $row['total_records'];
$total_pages = ceil($total_records / $records_per_page);

// Main query
$query = "
    SELECT r.id, r.date, r.first_name, r.last_name, r.course_year, 
           r.reason_for_referral, r.status, r.faculty_name, r.acknowledged_by
    FROM referrals r
    $where_clause
    ORDER BY r.date " . ($sort_order === 'ASC' ? 'ASC' : 'DESC') . "
    LIMIT ? OFFSET ?
";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

if ($status_filter) {
    $stmt->bind_param("ssii", $student_id, $status_filter, $records_per_page, $offset);
} else {
    $stmt->bind_param("sii", $student_id, $records_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

// Calculate showing range for display
$showing_start = min($total_records, $offset + 1);
$showing_end = min($total_records, $offset + $records_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Referrals</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
:root {
    --primary-color: #0d693e;
    --secondary-color: #004d4d;
    --accent-color: #F4A261;
    --hover-color: #094e2e;
    --text-color: #2c3e50;
    --btn-color: #0d693e;
    --btn-hover-color: #094e2e;
} 

body {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: var(--text-color);
    margin: 0;
    padding: 0;
}

.container {
    background-color: rgba(255, 255, 255, 0.98);
    border-radius: 15px;
    padding: 30px;
    margin: 50px auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

h2 {
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 15px 0 30px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

.form-control {
    padding: 5px 15px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    width: 100%;
    transition: all 0.3s ease;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
}

.mb-4 {
    margin-bottom: 1.5rem !important;
}

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

td {
    padding: 12px 15px;
    vertical-align: middle;
    border: 0.1px solid #e0e0e0;
    font-size: 14px;
    text-align: center;
    background-color: transparent;
}

tbody tr:last-child td:first-child {
    border-bottom-left-radius: 10px;
}

tbody tr:last-child td:last-child {
    border-bottom-right-radius: 10px;
}

tbody tr {
    background-color: white;
    transition: background-color 0.2s ease;
}

.table th,
.table td {
    padding: 12px 15px;
    vertical-align: middle;
    font-size: 14px;
    text-align: center;
}

.table th:nth-child(1),
.table td:nth-child(1) {
    width: 12%;
    padding: 20px;
    text-align: left;
}

.table th:nth-child(2),
.table td:nth-child(2) {
    width: 20%;
    text-align: left;
}

.table th:nth-child(3),
.table td:nth-child(3) {
    width: 25%;
    text-align: left;
}

.table th:nth-child(4),
.table td:nth-child(4) {
    width: 15%;
    text-align: left;
}

.table th:nth-child(5),
.table td:nth-child(5) {
    width: 15%;
}

.actions-cell {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.no-records-container {
    text-align: center;
    padding: 40px 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
    border: 1px dashed #dee2e6;
}

.no-records-icon {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 15px;
}

.no-records-text {
    font-size: 18px;
    color: #495057;
    font-weight: 500;
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

.btn-primary {
    background-color: var(--btn-color) !important;
    border-color: var(--btn-color) !important;
    color: white;
}

.btn-primary:hover, 
.btn-primary:focus, 
.btn-primary:active {
    background-color: var(--btn-hover-color) !important;
    border-color: var(--btn-hover-color) !important;
}

.btn-primary.btn-sm {
    background-color: var(--btn-color) !important;
    border-color: var(--btn-color) !important;
}

.btn-primary.btn-sm:hover {
    background-color: var(--btn-hover-color) !important;
    border-color: var(--btn-hover-color) !important;
}

.page-item.active .page-link {
    background-color: var(--btn-color) !important;
    border-color: var(--btn-color) !important;
}

.page-link {
    color: var(--btn-color) !important;
}

.page-link:hover {
    color: var(--btn-hover-color) !important;
}

@media screen and (max-width: 992px) {
    .container {
        width: 95%;
        padding: 20px;
    }

    .col-md-4, .col-md-3, .col-md-2 {
        flex: 0 0 100%;
        max-width: 100%;
        padding: 0 15px;
        margin-bottom: 10px;
    }

    .btn-primary {
        width: 100%;
    }
}

@media screen and (max-width: 768px) {
    .container {
        padding: 15px;
        margin: 20px auto;
    }

    h2 {
        font-size: 1.5rem;
    }

    .table thead {
        display: none;
    }

    .table tr {
        display: block;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .table td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        min-height: 50px;
        line-height: 1.5;
        width: 100% !important;
    }

    .table td::before {
        content: attr(data-label);
        font-weight: 600;
        font-size: 14px;
        color: #444;
        padding-right: 15px;
        flex: 1;
        white-space: nowrap;
    }

    .table td:last-child {
        border-bottom: none;
    }

    td[data-label="Date"] {
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
        font-weight: 500;
    }

    td[data-label="Status"] {
        background: #f8f9fa;
    }

    td[data-label="Actions"] {
        border-radius: 0 0 8px 8px;
        justify-content: flex-end;
    }

    .btn-primary.btn-sm {
        width: 100%;
        padding: 10px;
        text-align: center;
        border-radius: 6px;
    }
    
    .no-records-container {
        padding: 30px 15px;
    }
    
    .no-records-icon {
        font-size: 36px;
    }
    
    .no-records-text {
        font-size: 16px;
    }
}

@media screen and (max-width: 576px) {
    .container {
        padding: 10px;
        margin: 10px;
    }

    h2 {
        font-size: 1.25rem;
    }

    .table td {
        padding: 10px 12px;
        font-size: 13px;
    }

    .modern-back-button {
        padding: 6px 12px;
        font-size: 0.85rem;
    }
    
    .row {
        margin-right: -5px;
        margin-left: -5px;
    }
    
    .col-md-2 {
        padding: 0 5px;
        margin-bottom: 8px;
    }
    
    .form-control {
        font-size: 13px;
    }
}

@media (hover: none) {
    .form-control, 
    .btn-primary,
    .btn-primary.btn-sm {
        min-height: 44px;
    }

    .table td {
        padding: 12px 15px;
    }
}
/* Referral Button */
.modern-referral-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #00A36C;
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    margin-left: 15px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0, 163, 108, 0.15);
}

.modern-referral-button:hover {
    background-color: #008C5C;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(0, 163, 108, 0.25);
    color: white;
    text-decoration: none;
}

.modern-referral-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}

/* Responsive adjustments for the buttons */
@media screen and (max-width: 576px) {
    .modern-referral-button {
        padding: 6px 12px;
        font-size: 0.85rem;
        margin-left: 10px;
    }
    
    .d-flex.justify-content-start {
        flex-wrap: wrap;
    }
}
.no-records-container {
    text-align: center;
    padding: 40px 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
    border: 1px dashed #dee2e6;
}

.no-records-icon {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 15px;
}

.no-records-icon i {
    opacity: 0.8;
}

.no-records-text {
    font-size: 18px;
    color: #495057;
    font-weight: 500;
}

@media screen and (max-width: 768px) {
    .no-records-container {
        padding: 30px 15px;
    }
    
    .no-records-icon {
        font-size: 36px;
    }
    
    .no-records-text {
        font-size: 16px;
    }
}

@media screen and (max-width: 576px) {
    .no-records-container {
        padding: 20px 10px;
    }
    
    .no-records-icon {
        font-size: 32px;
    }
    
    .no-records-text {
        font-size: 14px;
    }
}
.records-info {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
    text-align: right;
}

.pagination {
    margin-top: 20px;
}

@media screen and (max-width: 768px) {
    .records-info {
        text-align: center;
        margin-top: 10px;
        margin-bottom: 10px;
    }
}
</style>
</head>
<body>
<div class="container mt-5">
        <div class="d-flex justify-content-start align-items-center mb-4">
            <a href="student_homepage.php" class="modern-back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back</span>
            </a>
            <a href="view_student_incident_reports.php" class="modern-referral-button">
                <i class="fas fa-clipboard-list"></i>
                <span>View My Violation Records</span>
            </a>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4"style="border-bottom: 3px solid #004d4d;">
        <h2>MY Referrals</h2>

        <div class="col-md-4">
        <div class="search-container">
            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
        </div>
    </div>
</div>
        <!-- Filter Form -->
        <form class="mb-4" method="GET" action="">
            <div class="row">
                <div class="col-md-2">
                    <select name="status" id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Done" <?php echo $status_filter === 'Done' ? 'selected' : ''; ?>>Done</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" id="sortOrder" class="form-control">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="?" class="btn btn-secondary">Reset Filters</a>
                </div>
            </div>
        </form>


       <!-- Table with responsive wrapper -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reason for Referral</th>
                        <th>Faculty Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if ($result->num_rows == 0): ?>
                    <tr id="noRecordsRow">
                        <td colspan="5">
                            <div class="no-records-container">
                                <div class="no-records-icon">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div class="no-records-text">No data available</div>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                           <td data-label="Date"><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                           <td data-label="Reason"><?php echo htmlspecialchars($row['reason_for_referral']); ?></td>
                           <td data-label="Faculty"><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                           <td data-label="Status"><?php echo htmlspecialchars($row['status']); ?></td>
                            <td data-label="Actions">
                                <a href="view_student_referral_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Hidden No Results Template (for search function) -->
        <div id="noSearchResults" style="display: none;">
            <div class="no-records-container">
                <div class="no-records-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="no-records-text">No data available</div>
            </div>
        </div>

      <!-- Records Info -->
        <div class="records-info">
            <?php if ($total_records > 0): ?>
                Showing <?php echo $showing_start; ?> - <?php echo $showing_end; ?> of <?php echo $total_records; ?> records
            <?php else: ?>
                No records found
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_records > 0): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>">Last</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

<script>
$(document).ready(function() {
    // Auto-submit form when dropdown selection changes
    $('#statusFilter, #sortOrder').change(function() {
        $(this).closest('form').submit();
    });
    
    // Search functionality (keep your existing search code)
    $("#searchInput").keyup(function() {
        var searchText = $(this).val().toLowerCase();
        var visibleRows = 0;
        
        $(".table tbody tr").each(function() {
            if (!$(this).is("#noRecordsRow") && !$(this).is("#noSearchResultsRow")) {
                var row = $(this);
                
                // Get text from all cells
                var date = row.find('td[data-label="Date"]').text().toLowerCase();
                var reason = row.find('td[data-label="Reason"]').text().toLowerCase();
                var faculty = row.find('td[data-label="Faculty"]').text().toLowerCase();
                var status = row.find('td[data-label="Status"]').text().toLowerCase();
                
                // Combine all searchable content
                var rowContent = date + ' ' + reason + ' ' + faculty + ' ' + status;
                
                // Remove extra spaces and format text
                var formattedContent = rowContent.replace(/\s+/g, ' ').trim();
                var searchPattern = searchText.replace(/\s+/g, ' ').trim();
                
                // Show/hide row based on search match
                if (formattedContent.includes(searchPattern)) {
                    row.show();
                    visibleRows++;
                } else {
                    row.hide();
                }
            }
        });
        
        // Show no results message if nothing matches
        if (visibleRows === 0 && searchText !== '') {
            $("#noSearchResultsRow").remove(); // Remove any existing no results row
            $("#tableBody").append('<tr id="noSearchResultsRow"><td colspan="5">' + 
                $("#noSearchResults").html() + '</td></tr>');
        } else if (visibleRows > 0 || searchText === '') {
            $("#noSearchResultsRow").remove();
        }
    });

    // Add touch device detection
    function isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints;
    }

    // Adjust for touch devices
    if (isTouchDevice()) {
        $('.btn').addClass('touch-device');
    }
});
</script>
</body>
</html>