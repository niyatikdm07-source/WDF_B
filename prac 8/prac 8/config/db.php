<?php
// config/db.php

$host = "localhost";
$username = "root";
$password = "";          // Default XAMPP MySQL password is usually blank
$database = "studenthub_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>