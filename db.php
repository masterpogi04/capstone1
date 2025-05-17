<?php
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "capstone1"; 

// Create connection
$connection = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Function to sanitize user inputs
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        global $connection;
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $connection->real_escape_string($data);
    }
}

// Function to redirect with a message
if (!function_exists('redirect')) {
    function redirect($location, $message = '') {
        if ($message) {
            $_SESSION['message'] = $message;
        }
        header("Location: $location");
        exit();
    }
}

?>
