
<?php
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

startSecureSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$rememberMe = isset($_POST["remember_me"]);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
    header("Location: login.html?error=invalid");
    exit;
}

$stmt = $conn->prepare(
    "SELECT student_id, full_name, email, password_hash, role
     FROM students
     WHERE email = ?
     LIMIT 1"
);

$stmt->bind_param("s", $email);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student || !password_verify($password, $student["password_hash"])) {
    header("Location: login.html?error=wrong-details");
    exit;
}

createLoginSession($student);

if ($rememberMe) {
    createRememberToken($conn, (int) $student["student_id"]);
}

$conn->close();

redirectByRole();
?>