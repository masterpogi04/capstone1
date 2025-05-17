<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die(json_encode(["error" => "Unauthorized access"]));
}

if (isset($_POST['id']) && isset($_POST['table'])) {
    $id = $_POST['id'];
    $table = $_POST['table'];
    
    $allowed_tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_guard'];
    
    if (!in_array($table, $allowed_tables)) {
        die(json_encode(["error" => "Invalid table name"]));
    }

    $query = "SELECT id, username, email, name FROM $table WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode(["success" => true, "data" => $row]);
    } else {
        echo json_encode(["success" => false, "error" => "Record not found"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
}

mysqli_close($connection);
?>