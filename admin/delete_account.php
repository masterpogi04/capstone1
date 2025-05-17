<?php
session_start();
include '../db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['table'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$id = $_POST['id'];
$table = $_POST['table'];

// Verify if table is allowed to be deleted from
$allowed_tables = ['tbl_instructor', 'tbl_dean', 'tbl_guard', 'tbl_adviser'];
if (!in_array($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'error' => 'Invalid table']);
    exit;
}

// Check if account is disabled before allowing deletion
$check_query = "SELECT status FROM $table WHERE id = ?";
$stmt = $connection->prepare($check_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$account = $result->fetch_assoc();

if (!$account || $account['status'] !== 'disabled') {
    echo json_encode(['success' => false, 'error' => 'Account must be disabled before deletion']);
    exit;
}

// Proceed with deletion
$delete_query = "DELETE FROM $table WHERE id = ?";
$stmt = $connection->prepare($delete_query);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Account has been successfully deleted'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to delete account: ' . $connection->error
    ]);
}

$stmt->close();
$connection->close();
?>