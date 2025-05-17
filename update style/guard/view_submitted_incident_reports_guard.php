<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guard') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Handle delete action
if (isset($_POST['delete_incident'])) {
    $incident_id = $_POST['incident_id'];
    
    $connection->begin_transaction();
    
    try {
        // Delete from pending_incident_witnesses
        $stmt = $connection->prepare("DELETE FROM pending_incident_witnesses WHERE pending_report_id = ?");
        $stmt->bind_param("s", $incident_id);
        $stmt->execute();

        // Delete from pending_student_violations
        $stmt = $connection->prepare("DELETE FROM pending_student_violations WHERE pending_report_id = ?");
        $stmt->bind_param("s", $incident_id);
        $stmt->execute();

        // Delete from pending_incident_reports
        $stmt = $connection->prepare("DELETE FROM pending_incident_reports WHERE id = ?");
        $stmt->bind_param("s", $incident_id);
        $stmt->execute();

        if ($connection->affected_rows > 0) {
            $connection->commit();
            $response = [
                'status' => 'success',
                'message' => 'Deletion successful. Pending incident report and related records have been removed.'
            ];
        } else {
            throw new Exception("No records found to delete.");
        }
    } catch (Exception $e) {
        $connection->rollback();
        $response = [
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Count total records for pagination
$count_query = "SELECT COUNT(DISTINCT pir.id) as total 
                FROM pending_incident_reports pir
                LEFT JOIN pending_student_violations psv ON pir.id = psv.pending_report_id
                WHERE pir.guard_id = ?";
$count_stmt = $connection->prepare($count_query);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Modified query to show all involved students
$query = "
    SELECT 
        pir.*,
        GROUP_CONCAT(
            DISTINCT CONCAT(
                'ID: ', psv.student_id, 
                ' - ', psv.student_name
            ) 
            ORDER BY psv.student_name 
            SEPARATOR '\n'
        ) as student_details,
        GROUP_CONCAT(DISTINCT piw.witness_name ORDER BY piw.witness_name SEPARATOR '<br>') as witnesses
    FROM pending_incident_reports pir
    LEFT JOIN pending_student_violations psv ON pir.id = psv.pending_report_id
    LEFT JOIN pending_incident_witnesses piw ON pir.id = piw.pending_report_id
    WHERE pir.guard_id = ?
    GROUP BY pir.id
    ORDER BY pir.date_reported DESC
    LIMIT ? OFFSET ?
";

$stmt = $connection->prepare($query);
$stmt->bind_param("iii", $user_id, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submitted Pending Incident Reports - Guard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
     <style>
        body {
            background: linear-gradient(135deg, #0d693e, #004d4d);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            margin: 0;
            color: #333;
        }
        .container {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 30px;
            margin-top: 50px;
            margin-bottom: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #0d693e;
            border-bottom: 2px solid #0d693e;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #0d693e;
            border-color: #0d693e;
        }
        .btn-primary:hover {
            background-color: #094e2e;
            border-color: #094e2e;
        }
        .btn-secondary {
            background-color: #F4A261;
            border-color: #F4A261;
            color: #fff;
            padding: 10px 20px;
        }
        .btn-secondary:hover {
            background-color: #E76F51;
            border-color: #E76F51;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .page-item.active .page-link {
            background-color: #0d693e;
            border-color: #0d693e;
        }
        .page-link {
            color: #0d693e;
        }
        .page-link:hover {
            color: #094e2e;
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
    <a href="guard_homepage.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
        </a>
        <h2>My Submitted Pending Incident Reports</h2>
        
        <table class="table table-striped">
            <thead>
                <tr>
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
                <td><?php echo htmlspecialchars($row['date_reported']); ?></td>
                <td><?php echo htmlspecialchars($row['place']); ?></td>
                <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                <td>
                    <?php 
                    if (!empty($row['student_details'])) {
                        echo '<div class="student-list" style="white-space: pre-line;">';
                        echo htmlspecialchars($row['student_details']);
                        echo '</div>';
                    } else {
                        echo 'No students involved';
                    }
                    ?>
                </td>
                <td>
                    <?php 
                    if (!empty($row['witnesses'])) {
                        echo '<div style="white-space: pre-line;">';
                        echo htmlspecialchars($row['witnesses']);
                        echo '</div>';
                    } else {
                        echo 'No witnesses';
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <a href="view_incident_details_guard.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                    <form method="post" action="view_submitted_incident_reports_guard.php" style="display: inline;">
                        <input type="hidden" name="delete_incident" value="1">
                        <input type="hidden" name="incident_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1">&laquo;&laquo;</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">&laquo;</a>
                    </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">&raquo;</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>">&raquo;&raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <script>
    $(document).ready(function() {
        $('.delete-btn').click(function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this! This will delete the selected pending incident report and all related records.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: form.attr('method'),
                        url: form.attr('action'),
                        data: form.serialize(),
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire(
                                'Error!',
                                'An error occurred while deleting the report.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
    </script>
</body>
</html>