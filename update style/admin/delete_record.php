<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die(json_encode(["success" => false, "error" => "Unauthorized access"]));
}

if (isset($_POST['id']) && isset($_POST['table'])) {
    $id = $_POST['id'];
    $table = $_POST['table'];
    
    $allowed_tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_guard'];
    
    if (!in_array($table, $allowed_tables)) {
        die(json_encode(["success" => false, "error" => "Invalid table name"]));
    }

    $query = "DELETE FROM $table WHERE id = ?";
    $stmt = $connection->prepare($query);
    if ($stmt === false) {
        die(json_encode(["success" => false, "error" => "Prepare failed: " . $connection->error]));
    }

    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Record deleted successfully"]);
        } else {
            echo json_encode(["success" => false, "error" => "No record found with the given ID"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Error deleting record: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
}

mysqli_close($connection);
?>