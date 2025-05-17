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
    
    $updateFields = [];
    $types = "";
    $values = [];
    
    $updatableFields = ['username', 'email', 'name'];

    foreach ($updatableFields as $field) {
        if (isset($_POST[$field])) {
            $updateFields[] = "$field = ?";
            $types .= "s";
            $values[] = $_POST[$field];
        }
    }
    
    if (!empty($_POST['password'])) {
        $updateFields[] = "password = ?";
        $types .= "s";
        $values[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($updateFields)) {
        die(json_encode(["success" => false, "error" => "No fields to update"]));
    }
    
    $updateQuery = "UPDATE $table SET " . implode(", ", $updateFields) . " WHERE id = ?";
    
    $stmt = $connection->prepare($updateQuery);
    if ($stmt === false) {
        die(json_encode(["success" => false, "error" => "Prepare failed: " . $connection->error]));
    }
    
    $types .= "i";
    $values[] = $id;
    
    if (!$stmt->bind_param($types, ...$values)) {
        die(json_encode(["success" => false, "error" => "Binding parameters failed: " . $stmt->error]));
    }
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Record updated successfully"]);
        } else {
            echo json_encode(["success" => false, "error" => "No changes were made"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Error updating record: " . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
}

mysqli_close($connection);
?>