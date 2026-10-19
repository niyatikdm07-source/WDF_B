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
<?php
session_start();
require_once __DIR__ . "/config/db.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: login.html?error=login-required");
    exit;
}

$eventId = filter_input(INPUT_GET, "event_id", FILTER_VALIDATE_INT);

if (!$eventId) {
    die("Invalid event selected.");
}

$stmt = $conn->prepare(
    "SELECT event_id, title, description, event_date, venue
     FROM events
     WHERE event_id = ?"
);

$stmt->bind_param("i", $eventId);
$stmt->execute();

$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    die("Event not found.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Event - StudentHub</title>

    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>

<body>
    <header>
        <h1>StudentHub</h1>
        <p>Your One Stop Student Portal</p>
    </header>

    <nav aria-label="Main Navigation">
        <ul>
            <li><a href="home.html">Home</a></li>
            <li><a href="event.html">Events</a></li>
            <li><a href="dashboard.html">Dashboard</a></li>
        </ul>
    </nav>

    <p class="breadcrumb">
        Home &gt; Events &gt; Event Registration
    </p>

    <main class="auth-container">
        <section class="auth-card">
            <h2>Register for Event</h2>

            <h3><?php echo htmlspecialchars($event["title"]); ?></h3>

            <p>
                <?php echo htmlspecialchars($event["description"]); ?>
            </p>

            <p>
                <strong>Date:</strong>
                <?php echo htmlspecialchars($event["event_date"]); ?>
            </p>

            <p>
                <strong>Venue:</strong>
                <?php echo htmlspecialchars($event["venue"]); ?>
            </p>

            <form action="event_register.php" method="POST">
                <input
                    type="hidden"
                    name="event_id"
                    value="<?php echo (int) $event["event_id"]; ?>"
                >

                <p>
                    You are registering as:
                    <strong>
                        <?php echo htmlspecialchars($_SESSION["student_name"]); ?>
                    </strong>
                </p>

                <button type="submit" class="submit-btn">
                    Confirm Registration
                </button>

                <p class="login-link">
                    <a href="event.html">← Back to Events</a>
                </p>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 StudentHub College Portal. All Rights Reserved.</p>
    </footer>
</body>
</html>