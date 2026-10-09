<?php
session_start();
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$fullName = trim($_POST["fullname"] ?? "");
$email = trim($_POST["email"] ?? "");
$course = trim($_POST["course"] ?? "");
$password = $_POST["password"] ?? "";

if (
    $fullName === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $course === "" ||
    $password === ""
) {
    header("Location: login.html?error=wrong-details");
    exit;
}

$stmt = $conn->prepare(
    "SELECT student_id, full_name, course, password_hash
     FROM students
     WHERE email = ?"
);

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

$nameMatches = $student &&
    strtolower(trim($student["full_name"])) === strtolower($fullName);

$courseMatches = $student &&
    $student["course"] === $course;

$passwordMatches = $student &&
    password_verify($password, $student["password_hash"]);

if ($nameMatches && $courseMatches && $passwordMatches) {
    session_regenerate_id(true);

    $_SESSION["student_id"] = $student["student_id"];
    $_SESSION["student_name"] = $student["full_name"];

    header("Location: dashboard.html");
    exit;
}

header("Location: login.html?error=wrong-details");
exit;
?>