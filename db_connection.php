<?php
// Database configuration
$host = 'bgcdb.mysql.database.azure.com'; // Azure database host
$username = 'judymalahay'; // Azure database username
$password = 'Malahayj123'; // Your Azure database password
$database = 'bgc_database'; // Your Azure database name

// Create a connection
$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>