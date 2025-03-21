<?php
// Database configuration
$db_host = '127.0.0.1'; // Use IP address instead of 'localhost'
$db_user = 'root';
$db_password = ''; // Default XAMPP password is empty
$db_name = 'schoolExam';
$db_port = 3306; // Specify the port explicitly

// Create database connection
try {
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
    echo "Connected successfully to the database!";
    
} catch (Exception $e) {
    // Display error message for debugging
    die("Database connection error: " . $e->getMessage());
}