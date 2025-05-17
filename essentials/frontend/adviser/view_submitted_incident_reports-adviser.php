<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get search, sort, and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Handle delete action
if (isset($_POST['delete_incident'])) {
    $incident_id = $_POST['incident_id'];
    
    // Start a transaction
    $connection->begin_transaction();
    
    try {
        $deletion_results = array();

        // Function to execute delete query and return results
        function executeDelete($connection, $table, $incident_id, $id_column = 'id') {
            $stmt = $connection->prepare("DELETE FROM $table WHERE $id_column = ?");
            $stmt->bind_param("s", $incident_id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $error = $stmt->error;
            $stmt->close();
            return ['affected_rows' => $affected_rows, 'error' => $error];
        }

        // Delete from child tables first
        $child_tables = [
            ['name' => 'incident_witnesses', 'id_column' => 'incident_report_id'],
            ['name' => 'student_violations', 'id_column' => 'incident_report_id']
        ];

        foreach ($child_tables as $table) {
            $result = executeDelete($connection, $table['name'], $incident_id, $table['id_column']);
            $deletion_results[$table['name']] = $result['affected_rows'];
            if (!empty($result['error'])) {
                throw new Exception("Error deleting from {$table['name']}: " . $result['error']);
            }
        }

        // Now delete from the parent table
        $result = executeDelete($connection, 'incident_reports', $incident_id);
        $deletion_results['incident_reports'] = $result['affected_rows'];
        if (!empty($result['error'])) {
            throw new Exception("Error deleting from incident_reports: " . $result['error']);
        }
        
        // Check if any rows were affected in any table
        if (array_sum($deletion_results) > 0) {
            // Commit the transaction
            $connection->commit();
            $message = "Deletion successful. Incident report and related records have been removed.";
            $alert_type = "success";
        } else {
            throw new Exception("No records found with ID: $incident_id");
        }
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        $connection->rollback();
        $message = "An error occurred: " . $e->getMessage();
        $alert_type = "error";
    }

    // Prepare the SweetAlert script
    $sweet_alert_script = "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '" . ($alert_type == "success" ? "Success!" : "Error!") . "',
            text: '" . addslashes($message) . "',
            icon: '$alert_type',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'view_submitted_incident_reports-adviser.php';
            }
        });
    });
    </script>";

    // Output the SweetAlert script
    echo $sweet_alert_script;
}

// Base query for counting total records
$count_query = "
    SELECT COUNT(DISTINCT ir.id) as total 
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Base query for fetching records
$query = "
    SELECT ir.*, 
           GROUP_CONCAT(DISTINCT sv.student_id) as involved_students,
           GROUP_CONCAT(DISTINCT iw.witness_name) as witnesses,
           GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name)) as student_names
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Add search condition
if (!empty($search)) {
    $search_condition = " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR ir.description LIKE ? OR ir.place LIKE ?)";
    $count_query .= $search_condition;
    $query .= $search_condition;
}

// Add status filter
if (!empty($status_filter)) {
    $status_condition = " AND ir.status = ?";
    $count_query .= $status_condition;
    $query .= $status_condition;
}

// Add involvement filter
if (!empty($involvement_filter)) {
    if ($involvement_filter === 'Involved') {
        $involvement_condition = " AND sv.student_id IS NOT NULL";
    } elseif ($involvement_filter === 'Witness') {
        $involvement_condition = " AND iw.witness_id IS NOT NULL";
    }
    $count_query .= $involvement_condition;
    $query .= $involvement_condition;
}

// Add group by and sorting
$query .= " GROUP BY ir.id ORDER BY ir.date_reported " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
$query .= " LIMIT ? OFFSET ?";

// Prepare and execute count query
$count_stmt = $connection->prepare($count_query);
$count_params = array($user_id, $user_type);
$count_types = "is";

if (!empty($search)) {
    $search_param = "%$search%";
    $count_params = array_merge($count_params, array($search_param, $search_param, $search_param, $search_param));
    $count_types .= "ssss";
}

if (!empty($status_filter)) {
    $count_params[] = $status_filter;
    $count_types .= "s";
}

$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Prepare and execute main query
$stmt = $connection->prepare($query);
$params = array($user_id, $user_type);
$types = "is";

if (!empty($search)) {
    $search_param = "%$search%";
    $params = array_merge($params, array($search_param, $search_param, $search_param, $search_param));
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $params[] = $status_filter;
    $types .= "s";
}

$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submitted Incident Reports - Adviser</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
    :root {
        --primary-color: #0d693e;
        --secondary-color: #004d4d;
        --accent-color: #F4A261;
        --hover-color: #094e2e;
        --text-color: #2c3e50;
        
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

    .stats-card {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        transition: transform 0.2s;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stats-card h3 {
        font-size: 2rem;
        margin: 0;
    }

    .stats-card p {
        margin: 5px 0 0;
        opacity: 0.9;
    }

   /* Table Styles */
.table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.table th {
    background-color: #009E60;
    color: white;
    border: none;
}

.table td {
    vertical-align: middle;
}


    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85em;
        font-weight: 500;
    }

    .status-pending { background-color: #ffd700; color: #000; }
    .status-processing { background-color: #87ceeb; color: #000; }
    .status-meeting { background-color: #98fb98; color: #000; }
    .status-resolved { background-color: #90EE90; color: #000; }
    .status-rejected { background-color: #ff6b6b; color: #fff; }

    .btn-custom {
        background-color: var(--primary-color);
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        color: white;
        transition: all 0.3s;
    }

    .btn-custom:hover {
        background-color: var(--hover-color);
        transform: translateY(-1px);
    }

    .filters-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .search-box {
        position: relative;
    }

    .search-box input {
        padding-left: 35px;
        border-radius: 20px;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    .checkbox-custom {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    

    @media (max-width: 768px) {
        .container {
            padding: 15px;
            margin: 20px auto;
        }
        
        .table-responsive {
            border-radius: 8px;
        }

        .stats-card {
            margin-bottom: 15px;
        }
    }
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

</style>
</head>
<body>
    <div class="container mt-5">
        <a href="adviser_homepage.php" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back</span>
    </a>
        <h2>My Submitted Incident Reports</h2>

        <!-- Search and Filter Form -->
        <form class="mb-4" method="GET" action="">
    <div class="row">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="For Meeting" <?php echo $status_filter === 'For Meeting' ? 'selected' : ''; ?>>For Meeting</option>
                <option value="Resolved" <?php echo $status_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="sort_order" class="form-control">
                <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
        </div>
    </div>
</form>

        <form id="deleteForm" method="post">
            <input type="hidden" name="delete_incidents" value="1">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Date Reported</th>
                        <th>Place, Date & Time of Incident</th>
                        <th>Description</th>
                        <th>Students Involved</th>
                        <th>Witnesses</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><input type="checkbox" name="incident_ids[]" value="<?php echo $row['id']; ?>"></td>
                        <td><?php echo htmlspecialchars($row['date_reported']); ?></td>
                        <td><?php echo htmlspecialchars($row['place']); ?></td>
                        <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                        <td><?php echo htmlspecialchars($row['student_names'] ?: $row['involved_students']); ?></td>
                        <td><?php echo htmlspecialchars($row['witnesses']); ?></td>
                        <td>
                            <?php 
                            $status_class = '';
                            switch($row['status']) {
                                case 'Pending':
                                    $status_class = 'text-warning';
                                    break;
                                case 'Processing':
                                    $status_class = 'text-primary';
                                    break;
                                case 'For Meeting':
                                    $status_class = 'text-info';
                                    break;
                                case 'Resolved':
                                    $status_class = 'text-success';
                                    break;
                                case 'Rejected':
                                    $status_class = 'text-danger';
                                    break;
                            }
                            ?>
                            <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                        </td>
                        <td>
                            <a href="view_incident_details-adviser.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="delete_incident" value="1">
                                <input type="hidden" name="incident_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <button type="button" id="deleteSelected" class="btn btn-danger">Delete Selected</button>
        </form>

        <!-- Pagination -->
       <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=1<?php echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>" aria-label="First">
                    <span aria-hidden="true">&laquo;&laquo;</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        <?php endif; ?>

        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);

        for ($i = $start_page; $i <= $end_page; $i++):
        ?>
            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $total_pages; echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>" aria-label="Last">
                    <span aria-hidden="true">&raquo;&raquo;</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

        <div class="text-center mt-3">
            <p>Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries</p>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Check for success parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            Swal.fire({
                title: 'Success!',
                text: 'Selected incident reports have been deleted successfully.',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        }

        $('#selectAll').change(function() {
            $('input[name="incident_ids[]"]').prop('checked', this.checked);
        });

        $('.delete-btn').click(function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this! This will delete the selected incident report and all related records.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        $('#deleteSelected').click(function() {
            var selectedIds = $('input[name="incident_ids[]"]:checked').map(function() {
                return this.value;
            }).get();

            if (selectedIds.length > 0) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this! This will delete the selected incident report(s) and all related records.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#deleteForm').submit();
                    }
                });
            } else {
                Swal.fire('No Selection', 'Please select at least one incident report to delete.', 'info');
            }
        });
    });
    </script>
</body>
</html>