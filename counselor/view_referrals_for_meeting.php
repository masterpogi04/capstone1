<?php
session_start();
include '../db.php';

// Check if user is logged in and is a counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header("Location: ../login.php");
    exit();
}

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of referrals with 'for meeting' status
$total_query = "SELECT COUNT(*) as total FROM referrals WHERE status = 'For Meeting'";
$total_result = $connection->query($total_query);
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get referrals for current page
$query = "SELECT 
            r.*, 
            DATE_FORMAT(r.date, '%Y-%m-%d') as formatted_date,
            CASE 
                WHEN r.reason_for_referral = 'Other concern' THEN CONCAT('Other concern: ', r.other_concerns)
                WHEN r.reason_for_referral = 'Violation to school rules' THEN CONCAT('Violation: ', r.violation_details)
                ELSE r.reason_for_referral
            END as detailed_reason
          FROM referrals r
          WHERE r.status = 'For Meeting'
          ORDER BY r.date DESC
          LIMIT $offset, $records_per_page";
$result = $connection->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referrals For Meeting</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #0d693e, #004d4d);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        
        .header {
            background-color: #ff9f1c;
            padding: 10px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            position: absolute;
            right: 0;
            top: 0;
            width: 100%;
            color: white;
            z-index: 1000;
        }
        
        .content-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 60px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .table {
            margin-top: 20px;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .back-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #5a6268;
            color: white;
        }
        
        .btn-view {
            background-color: #0d693e;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 6px 12px;
        }
        
        .btn-view:hover {
            background-color: #095030;
            color: white;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #0d693e;
            border-color: #0d693e;
        }
        
        .pagination .page-link {
            color: #0d693e;
        }

        .reason-cell {
            max-width: 250px;
            white-space: normal;
            word-wrap: break-word;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">REFERRALS FOR MEETING</div>
    
    <div class="container content-container">
        <a href="view_referrals_page.php" class="btn back-btn">Back</a>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student Name</th>
                            <th>Course/Year</th>
                            <th>Reason for Referral</th>
                            <th>Faculty Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['formatted_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_year']); ?></td>
                                <td class="reason-cell"><?php echo htmlspecialchars($row['detailed_reason']); ?></td>
                                <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                                <td>
                                    <a href="view_referral_details.php?id=<?php echo $row['id']; ?>" class="btn btn-view btn-sm">View Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">&laquo; First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>">&lsaquo; Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, min($page - 2, $total_pages - 4));
                        $end_page = min($total_pages, max(5, $page + 2));

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>">Next &rsaquo;</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>">Last &raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-message">
                <h4>No Referrals for Meeting</h4>
                <p>There are currently no referrals scheduled for meeting.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>