<?php
session_start();
include '../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}  

// Fetch admin details
$admin_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name, profile_picture FROM tbl_admin WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $admin_name = $admin['first_name'] . ' ' . $admin['middle_initial'] . ' ' . $admin['last_name'];
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

            .swal2-title {
    border-bottom: none !important;  /* Remove the bottom border */
    padding-bottom: 0 !important;    /* Remove any bottom padding that might create space */
    margin-bottom: 0.5em !important; /* Maintain proper spacing */
}

/* If the line still persists, you might need to override any inherited styles */
.swal2-popup h2.swal2-title::after,
.swal2-popup h2.swal2-title::before {
    display: none !important;
}
            .main-content {
                        padding: 40px;
                        padding-top: 60px;
                    }

            .container {
            max-width: calc(100% - 280px);
            margin-left: 265px;
            margin-right: 15px;
            margin-top: 70px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        /* Role cards responsive grid */
        .role-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 20px 0;
        }

        /* Responsive breakpoints */
        @media (max-width: 1200px) {
            .role-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .container {
                max-width: calc(100% - 100px);
                margin-left: 85px;
            }
            
            .modal-dialog {
                max-width: 90%;
            }
        }

        @media (max-width: 768px) {
            .container {
                max-width: 95%;
                margin: 70px auto 20px;
                padding: 15px;
            }
            
            .role-cards {
                grid-template-columns: 1fr;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .role-card {
                padding: 15px;
            }

            table {
                width: 100%;
                font-size: 14px;
            }

            th, td {
                padding: 8px 5px;
            }
            
            thead th {
                font-size: 14px;
            }

            .btn {
                padding: 5px 10px;
                font-size: 12px;
            }
        }

        @media (max-width: 576px) {
            .container {
                margin-top: 70px;
                padding: 10px;
            }
            
            .role-info {
                flex-direction: column;
                text-align: center;
            }
            
            .role-icon {
                margin: 0 auto 10px;
            }
            
            .modal-dialog {
                max-width: 95%;
                margin: 10px auto;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .form-group {
                margin-bottom: 0.75rem;
            }
        }

        /* Table responsive styles */
        @media (max-width: 480px) {
            .table-container {
                margin-right: 0;
            }
            
            th, td {
                font-size: 12px;
                padding: 6px 3px;
            }
            
            .action-column .btn {
                padding: 3px 6px;
                font-size: 11px;
                margin: 2px;
            }
        }
                
                h2 {
                font-weight: 600;
                font-size: 2rem;
                text-align: center;
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
                            justify-content: center;
                            margin-right: 30px;
                            
                           
                        }
                        table {
                                width: 50%;
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
                                text-align: center;
                                border-bottom: 1px solid #e0e0e0;
                            }
                            
                            thead th {
                                background: #009E60;
                                color: #ffffff;
                                font-weight: bold;
                                text-transform: uppercase;
                                padding: 15px;
                                font-size: 18px;
                                letter-spacing: 0.5px;
                                white-space: nowrap;
                                text-align: center;
                            }

                            td {
                                padding: 12px 15px;
                                vertical-align: middle;
                                border: 0.1px solid #e0e0e0;
                                font-size: 17px;
                                text-align: center;
                                background-color: transparent; /* Changed from white to transparent */
                            }


                            tr {
                                transition: all 0.3s ease;
                            }

                            
                        th:last-child,
                        td:last-child {
                            width: 20%; /* Set a fixed width for the action column */
                            white-space: nowrap; /* Prevent button text from wrapping */
                            text-align: center; /* Center the content */
                        }


                            .view_button {
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

                        .modal-content {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-header {
            border-bottom: 1px solid #e5e7eb;
            background-color: #f9fafb;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .modal-title {
            font-weight: 600;
            color: #111827;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #e5e7eb;
            background-color: #f9fafb;
            border-radius: 0 0 0.5rem 0.5rem;
        }

        /* Add this to your existing CSS */
        .role-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 20px 0;
        }

        .role-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .role-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .role-info {
            display: flex;
            align-items: center;
        }

        .role-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .role-icon i {
            color: white;
            font-size: 18px;
        }

        .role-name {
            color: #333;
            font-size: 16px;
            font-weight: 500;
        }

        .role-count {
            background: #f8f9fa;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        /* Color variations for different roles */
        .counselor-icon { background-color: #4285f4; }
        .facilitator-icon { background-color: #00C851; }
        .adviser-icon { background-color: #aa66cc; }
        .instructor-icon { background-color: #ff8800; }
        .dean-icon { background-color: #ff4444; }
        .guard-icon { background-color: #778899; }


        .status-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .status-tabs .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .status-tabs .btn.active {
            background-color: #008F57;
            color: white;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #000;
        }

        .btn-success {
            background-color: #28a745;
            color: #fff;
        }

        .status-tabs {
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .status-tabs .btn {
            min-width: 120px;
        }

        .loading-spinner {
            text-align: center;
            padding: 40px 20px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #008F57;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: #666;
            font-size: 16px;
            margin-top: 10px;
        }
</style>
</head>

<body>
<div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'admin_sidebar.php'; ?>
    <div class="container">
    <div class="bg-white p-6 mb-6">
        <h2 class="text-center mb-4">CEIT Guidance Office - Users</h2>
    </div>
    
    <div class="role-cards">
        <?php
        $roles = [
            'tbl_counselor' => ['name' => 'Counselors', 'icon' => 'fas fa-user-tie', 'class' => 'counselor-icon'],
            'tbl_facilitator' => ['name' => 'Facilitators', 'icon' => 'fas fa-chalkboard-teacher', 'class' => 'facilitator-icon'],
            'tbl_adviser' => ['name' => 'Advisers', 'icon' => 'fas fa-user-friends', 'class' => 'adviser-icon'],
            'tbl_instructor' => ['name' => 'Instructors', 'icon' => 'fas fa-user-graduate', 'class' => 'instructor-icon'],
            'tbl_dean' => ['name' => 'Deans', 'icon' => 'fas fa-user-shield', 'class' => 'dean-icon'],
            'tbl_guard' => ['name' => 'Guards', 'icon' => 'fas fa-user-shield', 'class' => 'guard-icon']
        ];

        foreach ($roles as $table => $role) {
            echo "
            <div class='role-card' onclick='viewTableContent(this)' data-table='$table'>
                <div class='role-info'>
                    <div class='role-icon {$role['class']}'>
                        <i class='{$role['icon']}'></i>
                    </div>
                    <span class='role-name'>{$role['name']}</span>
                </div>
            </div>";
        }
        ?>
    </div>
</div>    <!-- Modal for displaying table content -->
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
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="table" id="edit_table">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="middle_initial">Middle Initial</label>
                            <input type="text" class="form-control" id="middle_initial" name="middle_initial" maxlength="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Your existing script imports here -->
    
    <script>
// Global variable for current table
let currentTable = '';

// Document ready handler
$(document).ready(function() {
    // Initialize event handlers
    initializeEventHandlers();
    
    // Search functionality
    initializeSearch();
});

function initializeEventHandlers() {
    // Status tabs click handler
    $(document).on('click', '.status-tabs .btn', function() {
        const status = $(this).data('status');
        $('.status-tabs .btn').removeClass('active');
        $(this).addClass('active');
        loadTableContent(currentTable, status);
    });

    // View table content click handler
    $('.role-card').click(function() {
        currentTable = $(this).data('table');
        viewTableContent(currentTable);
    });

    // Edit button click handler
    $(document).on('click', '.edit-btn', handleEditClick);

    // Save changes click handler
    $('#saveChanges').click(handleSaveChanges);

    // Disable/Enable button click handler
    $(document).on('click', '.disable-btn', handleDisableClick);


    $(document).on('click', '.delete-btn', handleDeleteClick);
}

function getLoadingHTML() {
    return `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div class="loading-text">Loading content...</div>
        </div>
    `;
}

function viewTableContent(tableName) {
    // Show modal with loading state first
    $('#tableContentBody').html(getLoadingHTML());
    $('#tableContentModalLabel').text(
        tableName.replace('tbl_', '').charAt(0).toUpperCase() + 
        tableName.replace('tbl_', '').slice(1) + ' Account/s'
    );
    $('#tableContentModal').modal('show');
    
    // Fetch the actual content
    $.ajax({
        url: 'fetch_table_content.php',
        method: 'POST',
        data: { table: tableName },
        success: function(response) {
            // Small delay to prevent flickering on fast connections
            setTimeout(() => {
                $('#tableContentBody').html(response);
            }, 300);
        },
        error: function() {
            $('#tableContentBody').html(`
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 2em; margin-bottom: 10px;"></i>
                    <p style="color: #666;">Failed to load content. Please try again.</p>
                </div>
            `);
        }
    });
}

function loadTableContent(table, status) {
    if (!table) return;
    
    // Show loading state
    $('#tableContentBody').html(getLoadingHTML());
    
    $.ajax({
        url: 'fetch_table_content.php',
        method: 'POST',
        data: { 
            table: table,
            status: status
        },
        success: function(response) {
            setTimeout(() => {
                $('#tableContentBody').html(response);
            }, 300);
        },
        error: function() {
            $('#tableContentBody').html(`
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 2em; margin-bottom: 10px;"></i>
                    <p style="color: #666;">Failed to load content. Please try again.</p>
                </div>
            `);
        }
    });
}

function handleEditClick() {
    const id = $(this).data('id');
    const table = $(this).data('table');
    
    $.ajax({
        url: 'fetch_record.php',
        method: 'POST',
        data: { id: id, table: table },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                populateEditForm(response.data, table);
                $('#editRecordModal').modal('show');
            } else {
                Swal.fire('Error', response.error, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to fetch record data', 'error');
        }
    });
}

function populateEditForm(data, table) {
    $('#edit_id').val(data.id);
    $('#edit_table').val(table);
    $('#username').val(data.username);
    $('#email').val(data.email);
    $('#first_name').val(data.first_name);
    $('#middle_initial').val(data.middle_initial);
    $('#last_name').val(data.last_name);
    $('#password').val(''); // Clear password field for security
}

function handleSaveChanges() {
    const formData = $('#editRecordForm').serialize();
    
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
            updateRecord(formData);
        }
    });
}

function updateRecord(formData) {
    $.ajax({
        url: 'update_record.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success').then(() => {
                    $('#editRecordModal').modal('hide');
                    loadTableContent(
                        $('#edit_table').val(),
                        $('.status-tabs .btn.active').data('status')
                    );
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

function handleDisableClick() {
    const id = $(this).data('id');
    const table = $(this).data('table');
    const currentStatus = $(this).data('status');
    const newStatus = currentStatus === 'active' ? 'disabled' : 'active';
    const actionText = currentStatus === 'active' ? 'disable' : 'enable';

    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to ${actionText} this account?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: `Yes, ${actionText} it!`
    }).then((result) => {
        if (result.isConfirmed) {
            toggleAccountStatus(id, table, newStatus);
        }
    });
}

function toggleAccountStatus(id, table, newStatus) {
    $.ajax({
        url: 'toggle_account_status.php',
        method: 'POST',
        data: { 
            id: id, 
            table: table, 
            status: newStatus 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('Updated!', response.message, 'success').then(() => {
                    loadTableContent(table, $('.status-tabs .btn.active').data('status'));
                });
            } else {
                Swal.fire('Error', response.error, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to update account status', 'error');
        }
    });
}

function initializeSearch() {
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        const tableRows = $('.table tbody tr');
        let hasResults = false;

        tableRows.each(function() {
            const $row = $(this);
            const userName = $row.find('.user-name').text().toLowerCase();
            const userEmail = $row.find('.user-email').text().toLowerCase();
            const username = $row.find('td:nth-child(2)').text().toLowerCase();
            const date = $row.find('.date-cell').text().toLowerCase();

            if (userName.includes(searchTerm) || 
                userEmail.includes(searchTerm) || 
                username.includes(searchTerm) || 
                date.includes(searchTerm)) {
                $row.show();
                hasResults = true;
            } else {
                $row.hide();
            }
        });

        handleNoResults(hasResults);
    });
}

function handleNoResults(hasResults) {
    let noResultsMsg = $('.no-results');
    if (!hasResults) {
        if (noResultsMsg.length === 0) {
            noResultsMsg = $('<div>', {
                class: 'no-results',
                text: 'No matching records found'
            }).appendTo('.table-container');
        }
        noResultsMsg.show();
    } else {
        noResultsMsg.hide();
    }
}

function handleDeleteClick() {
    const id = $(this).data('id');
    const table = $(this).data('table');

    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteAccount(id, table);
        }
    });
}

function deleteAccount(id, table) {
    $.ajax({
        url: 'delete_account.php',
        method: 'POST',
        data: { 
            id: id, 
            table: table
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('Deleted!', response.message, 'success').then(() => {
                    loadTableContent(table, $('.status-tabs .btn.active').data('status'));
                });
            } else {
                Swal.fire('Error', response.error, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to delete account', 'error');
        }
    });
}
</script>
</body>
</html>