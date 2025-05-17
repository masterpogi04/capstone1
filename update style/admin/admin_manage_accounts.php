<?php
session_start();
include '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$admin_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT name, profile_picture FROM tbl_admin WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $name = $admin['name'];
    $profile_picture = $admin['profile_picture'];
} else {
    die("Admin not found.");
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Manage Accounts</title>
    <link rel="stylesheet" type="text/css" href="admin_styles.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Global Styles */
body {
    margin: 0;
    font-family: 'Arial', sans-serif;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background-color: #f0f2f5;
    background-image: linear-gradient(to bottom right, #f0f2f5, #e6e9f0);
}

.main-content {
                margin-left: 250px;
                padding: 40px;
                padding-top: 60px;
            }

    .container {
                max-width: 1000px;
                margin: 0 auto;
                background-color: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

        
        h2 {
        color: #00674b;
        font-weight: 600;
        font-size: 2rem;
        text-align: center;
        margin-top: 30px;
        margin-bottom: 30px;
        padding-bottom: 10px;
        border-bottom: 2px solid #00674b;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    @media (max-width: 768px) {
        h2 {
            font-size: 1.5rem;
        }
    }
  
/* Table Styles */
.table-container {
    display: flex;
    justify-content: center;
    
   
}
table {
        width: 80%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 10px;
        background-color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    th,td {
        padding: 8px 10px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }
    
    th {
        background: #009E60;
        color: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 14px;
    }

    td {
       
        font-size: 16px;
    }

    tr {
        transition: all 0.3s ease;
    }

    tr:hover {
        background-color: #e9ecef;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
th:last-child,
td:last-child {
    width: 20%; /* Set a fixed width for the action column */
    white-space: nowrap; /* Prevent button text from wrapping */
    text-align: center; /* Center the content */
}

/* Adjust the view button to fit the narrower column */
.view_button {
    display: inline-block;
    padding: 6px 10px; /* Slightly reduced padding */
    border-radius: 15px;
    cursor: pointer;
    text-decoration: none;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px; /* Slightly smaller font */
    transition: all 0.3s ease;
    margin-right: 0; /* Remove right margin */
    border: none;
    width: auto; /* Allow button to size to content */
}


    .view_button {
        background-color: #3498db; /* Keeping the original blue color */
        color: white;
        margin-right: 5px;
    }

    .view_button:hover {
        opacity: 0.8;
    }

    @media (max-width: 768px) {
        .view-button {
            padding: 4px 8px;
            font-size: 0.8em;
            min-width: 50px;
        }
    }
/* Status indicator */
.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}

.status-active {
    background-color: #4CAF50;
}

.status-inactive {
    background-color: #F44336;
}

/* Button Styles */
.btn {
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: 500;
    transition: all 0.3s ease;
    margin-right: 8px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
}

.edit_button {
    background-color: #3498db;
    color: white;
    border: none;
}

.edit_button:hover {
    background-color: #2980b9;
    box-shadow: 0 2px 5px rgba(52, 152, 219, 0.3);
}

.delete-btn {
    background-color: #e74c3c;
    color: white;
    border: none;
}

.delete-btn:hover {
    background-color: #c0392b;
    box-shadow: 0 2px 5px rgba(231, 76, 60, 0.3);
}

/* Icon styles (if you're using icons) */
.btn i {
    margin-right: 5px;
    font-size: 16px;
}

/* Action column styles */
.action-column {
    white-space: nowrap;
    text-align: right;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn {
        padding: 6px 10px;
        font-size: 12px;
        min-width: 40px;
    }
    
    .action-column {
        display: flex;
        justify-content: flex-end;
    }
    
    .action-column .btn {
        margin-bottom: 5px;
    }
}

/* Modal Styles */
.modal-body {
    max-height: 400px;
    overflow-y: auto;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .table-responsive {
        overflow-x: auto;
    }
    
    .btn {
        padding: 6px 10px;
        font-size: 0.9rem;
    }
}

/* Additional Enhancements */
.profile-picture {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.counselor-name {
    font-weight: 600;
    color: #2c3e50;
}

.counselor-email {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.date-column {
    font-size: 0.9rem;
    color: #34495e;
}
    </style>
</head>
<body>
    <div class="header">
        CAVITE STATE UNIVERSITY-MAIN
    </div>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="dashboard-container">
            <h2 class="mb-4">CEIT Guidance Office - Users</h2>
            <div class="table-container">
                <table class=" table-bordered">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_guard'];
                        foreach ($tables as $table) {
                            echo "<tr>
                                    <td>$table</td>
                                    <td>
                                        <button class='btn btn-primary btn-sm view_button' data-table='$table'>
                                            <i class='fas fa-eye'></i> View
                                        </button>
                                    </td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="footer">
        <p class="mb-0">Contact number | Email | Copyright</p>
    </div>

    <!-- Modal for displaying table content -->
    <div class="modal fade" id="tableContentModal" tabindex="-1" aria-labelledby="tableContentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tableContentModalLabel">Table Content</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="tableContentBody">
                    <!-- Table content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing record -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" aria-labelledby="editRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRecordModalLabel">Edit Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editRecordForm">
                        <!-- Form fields will be dynamically added here -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
                </div>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    $('.view_button').click(function() {
        var tableName = $(this).data('table');
        $.ajax({
            url: 'fetch_table_content.php',
            method: 'POST',
            data: { table: tableName },
            success: function(response) {
                $('#tableContentBody').html(response);
                $('#tableContentModalLabel').text(tableName + ' Content');
                $('#tableContentModal').modal('show');
            },
            error: function() {
                Swal.fire('Error', 'Failed to fetch table content', 'error');
            }
        });
    });

    $(document).on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        var table = $(this).data('table');
        $.ajax({
            url: 'fetch_record.php',
            method: 'POST',
            data: { id: id, table: table },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var form = $('#editRecordForm');
                    form.empty();
                    form.append('<input type="hidden" name="id" value="' + response.data.id + '">');
                    form.append('<input type="hidden" name="table" value="' + table + '">');
                    
                    // Only show id, username, email, and name fields
                    var fields = ['id', 'username', 'email', 'name'];
                    fields.forEach(function(field) {
                        if (response.data.hasOwnProperty(field)) {
                            form.append('<div class="form-group">' +
                                '<label for="' + field + '">' + field + '</label>' +
                                '<input type="text" class="form-control" id="' + field + '" name="' + field + '" value="' + response.data[field] + '"' + (field === 'id' ? ' readonly' : '') + '>' +
                                '</div>');
                        }
                    });
                    
                    // Add password field (empty for security reasons)
                    form.append('<div class="form-group">' +
                        '<label for="password">Password </label>' +
                        '<input type="text" class="form-control" id="password" name="password">' +
                        '</div>');
                    
                    $('#editRecordModal').modal('show');
                } else {
                    Swal.fire('Error', response.error, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to fetch record data', 'error');
            }
        });
    });

    $('#saveChanges').click(function() {
        var formData = $('#editRecordForm').serialize();
        Swal.fire({
            title: 'Confirm Update',
            text: "Are you sure you want to save these changes?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'update_record.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success', response.message, 'success').then(() => {
                                $('#editRecordModal').modal('hide');
                                $('.view_button[data-table="' + $('input[name="table"]').val() + '"]').click();
                            });
                        } else {
                            Swal.fire('Error', response.error, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to update record', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var table = $(this).data('table');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_record.php',
                    method: 'POST',
                    data: { id: id, table: table },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                $('.view_button[data-table="' + table + '"]').click();
                            });
                        } else {
                            Swal.fire('Error', response.error, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to delete record', 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>