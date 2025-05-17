<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Unauthorized access");
}

if (isset($_POST['table'])) {
    $table = $_POST['table'];
    $allowed_tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_guard'];
    
    if (!in_array($table, $allowed_tables)) {
        die("Invalid table name");
    }

    $query = "SELECT id, username, email, name FROM $table";
    $result = mysqli_query($connection, $query);
    if ($result) {
        echo "<table class='table table-bordered table-striped'>";
        echo "<thead class='thead-dark'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Username</th>";
        echo "<th>Email</th>";
        echo "<th>Name</th>";
        echo "<th>Actions</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td class='action-buttons'>";
            echo "<button class='btn btn-sm btn-primary edit-btn' data-id='" . $row['id'] . "' data-table='" . $table . "'>Edit</button> ";
            echo "<button class='btn btn-sm btn-danger delete-btn' data-id='" . $row['id'] . "' data-table='" . $table . "'>Delete</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "Error fetching data: " . mysqli_error($connection);
    }
}

mysqli_close($connection);
?>