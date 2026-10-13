<?php
session_start();
require_once __DIR__ . "/config/db.php";

function goToRegister(string $message): void {
    $_SESSION["register_message"] = $message;
    header("Location: register.html?error=" . urlencode($message));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    goToRegister("Invalid request.");
}

$username = trim($_POST["username"] ?? "");
$fullName = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$rollNo = trim($_POST["roll_no"] ?? "");
$course = trim($_POST["course"] ?? "");
$studyYear = (int) ($_POST["study_year"] ?? 0);
$gender = trim($_POST["gender"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$acceptedTerms = isset($_POST["terms"]);

$validCourses = ["btech", "bsc", "bba"];
$validGenders = ["male", "female", "other"];

if (
    !preg_match("/^[A-Za-z0-9_]{3,30}$/", $username) ||
    strlen($fullName) < 2 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match("/^[0-9]{10}$/", $mobile) ||
    $rollNo === "" ||
    !in_array($course, $validCourses, true) ||
    $studyYear < 1 || $studyYear > 4 ||
    !in_array($gender, $validGenders, true) ||
    strlen($password) < 8 ||
    $password !== $confirmPassword ||
    !$acceptedTerms
) {
    goToRegister("Please complete every field correctly. Passwords must match and contain at least 8 characters.");
}

try {
    /* Check duplicate username or email before insertion. */
    $check = $conn->prepare(
        "SELECT student_id
         FROM students
         WHERE username = ? OR email = ?
         LIMIT 1"
    );

    $check->bind_param("ss", $username, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        goToRegister("That username or email address is already registered.");
    }

    $check->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insert = $conn->prepare(
        "INSERT INTO students
        (username, full_name, email, mobile, roll_no, course, study_year, gender, password_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $insert->bind_param(
        "ssssssiss",
        $username,
        $fullName,
        $email,
        $mobile,
        $rollNo,
        $course,
        $studyYear,
        $gender,
        $passwordHash
    );

    $insert->execute();
    $insert->close();

    $_SESSION["register_message"] = "Account created successfully. Please log in.";
    header("Location: login.html?registered=1");
    exit;

} catch (mysqli_sql_exception $exception) {
    goToRegister("Unable to create the account. Please try again.");
} finally {
    $conn->close();
}
?>