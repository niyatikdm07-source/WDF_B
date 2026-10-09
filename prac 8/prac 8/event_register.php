<?php
session_start();
require_once "config/db.php";

// Set this after successful login.
$studentId = $_SESSION["student_id"] ?? 0;
$eventId = (int) ($_POST["event_id"] ?? 0);

if ($studentId <= 0) {
    die("Please log in before registering for an event.");
}

if ($eventId <= 0) {
    die("Invalid event.");
}

$stmt = $conn->prepare(
    "INSERT INTO registrations (student_id, event_id)
     VALUES (?, ?)"
);

$stmt->bind_param("ii", $studentId, $eventId);

if ($stmt->execute()) {
    echo "You are registered for this event.";
} elseif ($conn->errno === 1062) {
    echo "You have already registered for this event.";
} else {
    echo "Registration failed: " . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>