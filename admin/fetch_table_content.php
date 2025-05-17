<?php
session_start();
include '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Unauthorized access");
}
if (isset($_POST['table'])) {
    $table = $_POST['table'];
    $allowed_tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_guard'];
    
    // Modify your existing query to handle status parameter
if (isset($_POST['status'])) {
    $status = $_POST['status'];
    $query = "SELECT id, username, email, first_name, middle_initial, last_name, created_at, status 
              FROM $table WHERE status = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $status);
} else {
    // Default to showing active accounts
    $status = 'active';
    $query = "SELECT id, username, email, first_name, middle_initial, last_name, created_at, status 
              FROM $table WHERE status = 'active'";
    $stmt = $connection->prepare($query);
}
$stmt->execute();
$result = $stmt->get_result();

    echo "
<div class='search-container'>
    <div class='search-wrapper'>
        <i class='fas fa-search search-icon'></i>
        <input type='text' id='searchInput' class='search-input' placeholder='Search for users...' />
    </div>
</div>";
    
    // Add CSS styles
    echo "<style>
    .table-container {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin: 10px;
    }
    
    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }
    
    .table th {
        background: #008F57;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        padding: 15px 20px;
        font-size: 14px;
        letter-spacing: 0.5px;
        border-bottom: 2px solid rgba(0, 0, 0, 0.1);
    }
    
    .table td {
        padding: 15px 20px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        background: #e5e7eb;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #374151;
        font-size: 16px;
    }
    
    .user-details {
        display: flex;
        flex-direction: column;
    }
    
    .user-name {
        font-weight: 600;
        color: #111827;
        font-size: 14px;
    }
    
    .user-email {
        color: #6b7280;
        font-size: 13px;
    }
    
    .date-cell {
        color: #6b7280;
        font-size: 13px;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    
    .btn {
        padding: 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary:hover {
        background-color: #007346;
    }
    
    .btn-danger {
        background-color: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #dc2626;
    }
    
    .table tbody tr:hover {
        background-color: #f9fafb;
    }
    
    .fas {
        font-size: 14px;
    }

    .search-container {
        margin: 10px;
        margin-bottom: 20px;
    }

    .search-wrapper {
        position: relative;
        max-width: 300px;
    }

    .search-input {
        width: 100%;
        padding: 12px 20px 12px 40px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .search-input:focus {
        outline: none;
        border-color: #008F57;
        box-shadow: 0 0 0 3px rgba(0, 143, 87, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 16px;
    }

    /* Add a no-results message style */
    .no-results {
        text-align: center;
        padding: 20px;
        color: #6b7280;
        font-style: italic;
        background: #f9fafb;
        border-radius: 8px;
        margin: 10px;
    }
</style>";

if ($result) {
    echo "<div class='status-tabs mb-4'>
        <button class='btn btn-primary active' data-status='active'>Active Accounts</button>
        <button class='btn btn-secondary' data-status='disabled'>Disabled Accounts</button>
      </div>";
    echo "<div class='table-container'>";
    echo "<table class='table'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th style='width: 30%'>User</th>";
    echo "<th style='width: 25%'>Username</th>";
    echo "<th style='width: 20%'>Created At</th>";
    echo "<th style='width: 15%'>Actions</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $fullName = trim($row['first_name'] . ' ' . $row['middle_initial'] . ' ' . $row['last_name']);
        $initials = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
        
        echo "<tr>";
        // User column with avatar and details
        echo "<td>
                <div class='user-info'>
                    <div class='user-avatar'>{$initials}</div>
                    <div class='user-details'>
                        <span class='user-name'>" . htmlspecialchars($fullName) . "</span>
                        <span class='user-email'>" . htmlspecialchars($row['email']) . "</span>
                    </div>
                </div>
              </td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td class='date-cell'>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
echo "<td>
    <div class='action-buttons'>";
        echo "<button class='btn btn-primary edit-btn p-3' data-id='" . $row['id'] . "' data-table='" . $table . "' title='Edit'>
            <i class='fas fa-edit'></i>
        </button>";
    
    echo "<button class='btn " . ($row['status'] == 'active' ? 'btn-secondary' : 'btn-success') . " disable-btn p-3' 
            data-id='" . $row['id'] . "' 
            data-table='" . $table . "' 
            data-status='" . $row['status'] . "' 
            title='" . ($row['status'] == 'active' ? 'Disable' : 'Enable') . " Account'>
        <i class='fas " . ($row['status'] == 'active' ? 'fa-ban' : 'fa-check-circle') . "'></i>
    </button>";

echo "</div></td>";        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";

    } else {
        echo "Error fetching data: " . mysqli_error($connection);
    }
}
mysqli_close($connection);

// Add this before closing your PHP tags
echo "<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('.table tbody tr');
    let hasResults = false;

    tableRows.forEach(row => {
        const userName = row.querySelector('.user-name').textContent.toLowerCase();
        const userEmail = row.querySelector('.user-email').textContent.toLowerCase();
        const username = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const date = row.querySelector('.date-cell').textContent.toLowerCase();

        if (userName.includes(searchTerm) || 
            userEmail.includes(searchTerm) || 
            username.includes(searchTerm) || 
            date.includes(searchTerm)) {
            row.style.display = '';
            hasResults = true;
        } else {
            row.style.display = 'none';
        }
    });

    // Handle no results
    let noResultsMsg = document.querySelector('.no-results');
    if (!hasResults) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'no-results';
            noResultsMsg.textContent = 'No matching records found';
            document.querySelector('.table-container').appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = 'block';
    } else if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
});

$(document).on('click', '.delete-btn', function() {
    const id = $(this).data('id');
    const table = $(this).data('table');

    Swal.fire({
        title: 'Are you sure?',
        text: 'This account will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'delete_account.php',
                method: 'POST',
                data: { id: id, table: table },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success')
                        .then(() => {
                            // Refresh the table content
                            loadTableContent(table, 'disabled');
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
    });
});
</script>";
?>