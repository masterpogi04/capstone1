<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}


// Fetch archived student profiles
$stmt = $connection->prepare("SELECT asp.*, c.name AS course_name, d.name AS department_name 
                              FROM archive_student_profiles asp 
                              LEFT JOIN courses c ON asp.course_id = c.id
                              LEFT JOIN departments d ON c.department_id = d.id
                              ORDER BY asp.created_at DESC");
if ($stmt === false) {
    die("Error preparing archived profiles query: " . $connection->error);
}
$stmt->execute();
$archived_profiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// Fetch all departments
$stmt = $connection->prepare("SELECT id, name FROM departments");
if ($stmt === false) {
    die("Error preparing departments query: " . $connection->error);
}
$stmt->execute();
$result = $stmt->get_result();
$departments = $result->fetch_all(MYSQLI_ASSOC);

// Fetch facilitator name using the new column structure
$facilitator_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?");
if ($stmt === false) {
    die("Error preparing facilitator query: " . $connection->error);
}
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
$facilitator = $result->fetch_assoc();

// Construct the full name from the separate fields
$facilitator_name = trim($facilitator['first_name'] . ' ' . 
    ($facilitator['middle_initial'] ? $facilitator['middle_initial'] . '. ' : '') . 
    $facilitator['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Profiles - CEIT Guidance Office</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <style>
        :root {
            --primary: #0f6a1a;
            --primary-hover: #218838;
            --header: #ff9042;
            --header-hover: #ff7d1a;
            --background: #f8f9fa;
            --border-color: #e9ecef;
            --dark-yellow: #FFD700;       /* Base gold/yellow */
            --deep-yellow: #FFC000;       /* Darker shade */
            --dark-gold: #D4A017; 
                }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background:  linear-gradient(135deg, #0d693e, #004d4d);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(to right, var(--header), var(--header-hover));
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-title {
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .content-wrapper {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
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
    

        .btn-custom1 {
    background: linear-gradient(to right, var(--deep-yellow), var(--dark-gold));
    color: #2a2a2a;              /* Dark text for contrast */
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;             /* Slightly bolder */
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15); /* Deeper shadow */
}

.btn-custom1:hover {
    background: linear-gradient(to right, var(--dark-gold), var(--deep-yellow));
    color: #000;                  /* Black text on hover */
    transform: translateY(-2px);  /* Subtle lift */
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.2);
}

.btn-custom1:active {
    transform: translateY(0);     /* Reset on click */
    box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
}

        .btn-custom {
            background: linear-gradient(to right, var(--primary), var(--primary-hover));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }


        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(15, 106, 26, 0.15);
        }

        .search-input-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input-container .fas {
            position: absolute;
            left: 10px;
            top: 11px;
            color: #6c757d;
        }

        .search-input {
            padding-left: 35px;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 0 1px #e9ecef;
            margin-top: 1.5rem;
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e9ecef;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-group .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            margin: 0 0.25rem;
        }

        .btn-info {
            background: #17a2b8;
            border: none;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .footer {
            background: var(--header);
            color: white;
            padding: 1rem;
            text-align: center; 
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
        }

        /* No results message */
        .no-results {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }

        /* Loading spinner */
        .spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner-border {
            color: var(--primary);
        }

        /* Divider style */
        .section-divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
            position: relative;
        }

        .divider-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 0 15px;
            color: #6c757d;
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-group .btn {
                width: 100%;
                margin: 0.25rem 0;
            }

            .table {
                display: block;
                overflow-x: auto;
            }
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
        /* Table styling for archived profiles */
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 158, 96, 0.05);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 158, 96, 0.1);
}

.table th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    border-bottom: 2px solid #009E60;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.restore-btn {
    margin-left: 5px;
}

/* Pagination styling */
.pagination {
    justify-content: center;
    margin-top: 20px;
}

.pagination .page-item.active .page-link {
    background-color: #009E60;
    border-color: #009E60;
}

.pagination .page-link {
    color: #009E60;
}

/* For responsive tables on mobile */
@media (max-width: 992px) {
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
    }
}

/* Added styles for pagination info */
.pagination-info {
    text-align: center;
    margin-top: 10px;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Search counter display */
.search-count {
    margin-top: 10px;
    font-size: 0.9rem;
    color: #6c757d;
}
    </style>
</head>
<body> 

    <div class="content-wrapper">
        <div class="content">
        <div class="action-buttons">
            <a href="facilitator_homepage.php" class="modern-back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div> 
        <ul class="nav nav-tabs" id="archiveTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link " href="view_profiles.php">
            <i class="fas fa-check mr-2"></i>Student Profile Inventory
        </a>
    </li>
    <li class="nav-item"> 
        <a class="nav-link active" href="archive_view_profile.php">
            <i class="fas fa-archive mr-2"></i>Archived Student Profile
        </a>
    </li> 
    </ul> 
        <button type="button" class="btn btn-custom1 mb-3" data-toggle="modal" data-target="#csvUploadModal">
        <i class="fas fa-file-upload mr-2"></i> Archive Profiles via CSV
    </button>
    <button type="button" class="btn btn-custom mb-3" data-toggle="modal" data-target="#recoverCsvModal">
    <i class="fas fa-sync-alt mr-2"></i>Recover Profiles via CSV
</button>

    <!-- Archive Upload Modal -->
<div class="modal fade" id="csvUploadModal" tabindex="-1" role="dialog" aria-labelledby="csvUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="csvUploadModalLabel">Archive Students via CSV</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="csvUploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="csvFile">Select CSV File:</label>
                        <input type="file" class="form-control-file" id="csvFile" name="csvFile" accept=".csv" required>
                        <small class="form-text text-muted">
                            CSV should contain student numbers in the first column, one per row.<br>
                            Example:<br>
                            203456789<br>
                            203456790<br>
                        </small>
                    </div>
                </form>
                <div class="alert alert-info mt-3">
                    <strong>Note:</strong> This will archive all student profiles associated with the student numbers in the CSV.
                </div>
                <div id="uploadProgress" class="progress mt-3" style="display: none;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="uploadStatus" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadCsvBtn">Archive Records</button>
            </div>
        </div>
    </div>
</div>

<!-- Recover CSV Upload Modal -->
<div class="modal fade" id="recoverCsvModal" tabindex="-1" role="dialog" aria-labelledby="recoverCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recoverCsvModalLabel">Recover Students via CSV</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="recoverCsvForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="recoverCsvFile">Select CSV File:</label>
                        <input type="file" class="form-control-file" id="recoverCsvFile" name="csvFile" accept=".csv" required>
                        <small class="form-text text-muted">
                            CSV should contain student numbers in the first column, one per row.<br>
                            Example:<br>
                            203456789<br>
                            203456790<br>
                        </small>
                    </div>
                </form>
                <div class="alert alert-info mt-3">
                    <strong>Note:</strong> This will recover all archived student profiles associated with the student numbers in the CSV.
                </div>
                <div id="recoverProgress" class="progress mt-3" style="display: none;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="recoverStatus" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="recoverCsvBtn">Recover Records</button>
            </div>
        </div>
    </div>
</div>

            <h2 class="text-center mb-4">Archived Student Profile Form Inventory</h2>
            
            <!-- Search form - Modified to search only table content -->
            <div class="search-container mb-4">
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control search-input" placeholder="Search student ID, name, department or course...">
                </div>
                <a href="?" class="btn btn-secondary">Reset Filters</a>
                <div class="search-count mt-2">
                    <span id="searchCounter">Showing all records</span>
                </div>
            </div>


           <!-- Divider with text -->
<div class="section-divider">
    <span class="divider-text">Archived Student Profiles</span>
</div>

<!-- Table of archived student profiles -->
<div class="table-responsive">
    <table id="studentTable" class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Course</th>
                <th>Year Level</th>
                <th>Archive Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($archived_profiles)): ?>
                <tr>
                    <td colspan="7" class="text-center">No archived student profiles found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($archived_profiles as $profile): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($profile['student_id']); ?></td>
                        <td>
                            <?php 
                                $fullName = trim(htmlspecialchars($profile['first_name']) . ' ' . 
                                (!empty($profile['middle_name']) ? htmlspecialchars($profile['middle_name']) . ' ' : '') . 
                                htmlspecialchars($profile['last_name']));
                                echo $fullName;
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($profile['department_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($profile['course_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($profile['year_level'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($profile['created_at'])); ?></td>
                        <td> <a href="restore_student_profile.php?student_id=<?php echo $profile['student_id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to Recover this profile? This action cannot be undone.');">Recover</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination controls -->
<nav aria-label="Page navigation">
    <ul id="pagination" class="pagination"></ul>
</nav>
<div class="pagination-info">
    <span id="pageInfo">Page 1 of 1</span>
</div>
            
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Table pagination and search functionality
            const rowsPerPage = 10;
            let currentPage = 1;
            let filteredRows = [];
            
            const table = document.getElementById('studentTable');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            
            // Initial setup
            setupPagination(rows);
            displayRows(currentPage, rows);
            
            // Function to display rows for the current page
function displayRows(page, rowsArray) {
    // Hide all rows
    rows.forEach(row => {
        row.style.display = 'none';
    });
    
    // Check if there are any rows to display
    if (rowsArray.length === 0) {
        // No matching records found - display "No data" message
        const tbody = table.querySelector('tbody');
        
        // Remove any existing "no data" row to avoid duplicates
        const existingNoData = tbody.querySelector('.no-data-row');
        if (existingNoData) {
            existingNoData.remove();
        }
        
        // Create a new row to show "No data found" message
        const noDataRow = document.createElement('tr');
        noDataRow.className = 'no-data-row';
        
        const noDataCell = document.createElement('td');
        noDataCell.colSpan = 6; // Span across all columns
        noDataCell.className = 'text-center py-4';
        noDataCell.innerHTML = '<i class="fas fa-search mr-2"></i> No matching records found';
        
        noDataRow.appendChild(noDataCell);
        tbody.appendChild(noDataRow);
    } else {
        // Remove any existing "no data" row if we now have results
        const existingNoData = table.querySelector('.no-data-row');
        if (existingNoData) {
            existingNoData.remove();
        }
        
        // Calculate start and end indices
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedRows = rowsArray.slice(start, end);
        
        // Display only the rows for current page
        paginatedRows.forEach(row => {
            row.style.display = '';
        });
    }
    
    // Update page info text
    const totalPages = Math.ceil(rowsArray.length / rowsPerPage) || 1;
    $('#pageInfo').text(`Page ${page} of ${totalPages}`);
}

// Function to setup pagination based on filtered rows
function setupPagination(rowsArray) {
    const paginationElement = document.getElementById('pagination');
    paginationElement.innerHTML = '';
    
    const totalPages = Math.ceil(rowsArray.length / rowsPerPage) || 1;
    
    // If no data, hide pagination entirely
    if (rowsArray.length === 0) {
        paginationElement.style.display = 'none';
        $('#pageInfo').parent().hide(); // Hide the page info as well
        return;
    } else {
        paginationElement.style.display = '';
        $('#pageInfo').parent().show();
    }
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.classList.add('page-item');
    if (currentPage === 1) prevLi.classList.add('disabled');
    
    const prevLink = document.createElement('a');
    prevLink.classList.add('page-link');
    prevLink.href = '#';
    prevLink.setAttribute('aria-label', 'Previous');
    prevLink.innerHTML = '<span aria-hidden="true">&laquo;</span>';
    
    prevLink.addEventListener('click', function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            displayRows(currentPage, rowsArray);
            setupPagination(rowsArray);
        }
    });
    
    prevLi.appendChild(prevLink);
    paginationElement.appendChild(prevLi);
    
    // Page number buttons
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    
    if (endPage - startPage < 4 && startPage > 1) {
        startPage = Math.max(1, endPage - 4);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.classList.add('page-item');
        if (i === currentPage) li.classList.add('active');
        
        const link = document.createElement('a');
        link.classList.add('page-link');
        link.href = '#';
        link.textContent = i;
        
        link.addEventListener('click', function(e) {
            e.preventDefault();
            currentPage = i;
            displayRows(currentPage, rowsArray);
            setupPagination(rowsArray);
        });
        
        li.appendChild(link);
        paginationElement.appendChild(li);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.classList.add('page-item');
    if (currentPage === totalPages) nextLi.classList.add('disabled');
    
    const nextLink = document.createElement('a');
    nextLink.classList.add('page-link');
    nextLink.href = '#';
    nextLink.setAttribute('aria-label', 'Next');
    nextLink.innerHTML = '<span aria-hidden="true">&raquo;</span>';
    
    nextLink.addEventListener('click', function(e) {
        e.preventDefault();
        if (currentPage < totalPages) {
            currentPage++;
            displayRows(currentPage, rowsArray);
            setupPagination(rowsArray);
        }
    });
    
    nextLi.appendChild(nextLink);
    paginationElement.appendChild(nextLi);
}

// Update the search functionality for better "no data" handling
$('#searchInput').on('input', function() {
    const searchTerm = $(this).val().toLowerCase().trim();
    
    if (searchTerm === '') {
        // Reset to show all rows
        filteredRows = rows;
        $('#searchCounter').text(`Showing all records`);
    } else {
        // Filter rows based on search term
        filteredRows = rows.filter(row => {
            const text = row.textContent.toLowerCase();
            return text.includes(searchTerm);
        });
        
        if (filteredRows.length === 0) {
            $('#searchCounter').html(`<span class="text-danger">No matching records found</span>`);
        } else {
            $('#searchCounter').html(`Found <span class="text-success">${filteredRows.length}</span> matching records`);
        }
    }
    
    // Reset to first page and update display
    currentPage = 1;
    setupPagination(filteredRows);
    displayRows(currentPage, filteredRows);
});

            // Handle CSV upload for archiving students
            // Handle CSV upload for archiving students
// Handle CSV upload for archiving students
$('#uploadCsvBtn').click(function() {
    const fileInput = $('#csvFile')[0];
    if (!fileInput.files.length) {
        Swal.fire('Error', 'Please select a CSV file first', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('csvFile', fileInput.files[0]);
    formData.append('action', 'archive_students_csv');

    // Show the progress bar and disable the button
    $('#uploadProgress').show();
    $('.progress-bar').css('width', '0%');
    $('#uploadStatus').html('');
    $('#uploadCsvBtn').prop('disabled', true);

    $.ajax({
        url: 'archive_profile.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = (evt.loaded / evt.total) * 100;
                    $('.progress-bar').css('width', percentComplete + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            console.log("Raw response:", response); // For debugging
            
            try {
                // Try to parse the response as JSON
                const result = JSON.parse(response);
                
                if (result.success) {
                    Swal.fire({
                        title: 'Success',
                        html: `Archived ${result.archived_count} student profiles successfully.`,
                        icon: 'success',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        // Force reload with the hardcoded page URL
                        window.location.href = 'archive_view_profile.php';
                    });
                } else {
                    Swal.fire('Error', result.message || 'An error occurred during archiving', 'error');
                }
            } catch (e) {
                console.error("Parse error:", e, "Raw response:", response);
                
                // Check if the response contains HTML or PHP error messages
                if (typeof response === 'string' && 
                (response.includes('<!DOCTYPE html>') || 
                    response.includes('Fatal error') || 
                    response.includes('Warning'))) {
                    Swal.fire('Server Error', 'The server returned an HTML error page. Check console for details.', 'error');
                } else {
                    Swal.fire('Error', 'Invalid response from server: ' + e.message, 'error');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", error);
            console.log("Response Text:", xhr.responseText);
            
            Swal.fire('Error', 'An error occurred during the upload: ' + error, 'error');
        },
        complete: function() {
            $('#uploadProgress').hide();
            $('#uploadCsvBtn').prop('disabled', false);
        }
    });
});

            // Reset form when modal is closed
            $('#csvUploadModal').on('hidden.bs.modal', function() {
                $('#csvUploadForm')[0].reset();
                $('#uploadStatus').html('');
                $('#uploadProgress').hide();
                $('.progress-bar').css('width', '0%');
            });


// Handle CSV upload for recovering students
// Handle CSV upload for recovering students
$('#recoverCsvBtn').click(function() {
    const fileInput = $('#recoverCsvFile')[0];
    if (!fileInput.files.length) {
        Swal.fire('Error', 'Please select a CSV file first', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('csvFile', fileInput.files[0]);
    formData.append('action', 'recover_students_csv');

    // Show the progress bar and disable the button
    $('#recoverProgress').show();
    $('#recoverProgress .progress-bar').css('width', '0%');
    $('#recoverStatus').html('');
    $('#recoverCsvBtn').prop('disabled', true);

    $.ajax({
        url: 'recover_profile.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = (evt.loaded / evt.total) * 100;
                    $('#recoverProgress .progress-bar').css('width', percentComplete + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            console.log("Raw response:", response); // For debugging
            
            try {
                // Try to parse the response as JSON
                const result = JSON.parse(response);
                
                if (result.success) {
                Swal.fire({
                    title: 'Success',
                    html: `Recovered ${result.recovered_count} student profiles successfully.`,
                    icon: 'success',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    // Force reload with the hardcoded page URL
                    window.location.href = 'archive_view_profile.php';
                });
            } else {
                    Swal.fire('Error', result.message || 'An error occurred during recovery', 'error');
                }
            } catch (e) {
                console.error("Parse error:", e, "Raw response:", response);
                
                // Check if the response contains HTML or PHP error messages
                if (typeof response === 'string' && 
                (response.includes('<!DOCTYPE html>') || 
                    response.includes('Fatal error') || 
                    response.includes('Warning'))) {
                    Swal.fire('Server Error', 'The server returned an HTML error page. Check console for details.', 'error');
                } else {
                    Swal.fire('Error', 'Invalid response from server: ' + e.message, 'error');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", error);
            console.log("Response Text:", xhr.responseText);
            
            Swal.fire('Error', 'An error occurred during the recovery: ' + error, 'error');
        },
        complete: function() {
            $('#recoverProgress').hide();
            $('#recoverCsvBtn').prop('disabled', false);
        }
    });
});

// Reset form when recover modal is closed
$('#recoverCsvModal').on('hidden.bs.modal', function() {
    $('#recoverCsvForm')[0].reset();
    $('#recoverStatus').html('');
    $('#recoverProgress').hide();
    $('#recoverProgress .progress-bar').css('width', '0%');
});
        });
    </script>
</body>
</html>