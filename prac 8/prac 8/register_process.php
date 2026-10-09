<?php

require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit;
}

$fullName = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$course = trim($_POST["course"] ?? "");

if ($fullName === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    die("Please enter a valid name, email, and a password of at least 6 characters.");
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO students (full_name, email, password_hash, course)
     VALUES (?, ?, ?, ?)"
);

$stmt->bind_param("ssss", $fullName, $email, $passwordHash, $course);

if ($stmt->execute()) {
    header("Location: login.html?registered=1");
    exit;
} 
elseif ($conn->errno === 1062) {
    echo "This email address is already registered.";
} else {
    echo "Registration failed: " . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>