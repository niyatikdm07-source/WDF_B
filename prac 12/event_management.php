
<?php
session_start();

require_once __DIR__ . '/config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

// Only logged-in administrators can manage events.
if (
    !isset($_SESSION['student_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    http_response_code(403);
    exit('Access denied. Please log in as an administrator.');
}

$uploadDir = __DIR__ . '/uploads/event_posters/';
$maxFileSize = 2 * 1024 * 1024; // 2 MB

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    exit('Upload folder is missing or not writable: uploads/event_posters/');
}

// CSRF token
if (empty($_SESSION['event_csrf'])) {
    $_SESSION['event_csrf'] = bin2hex(random_bytes(32));
}

function eventEscape($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function eventRedirect(string $message): void
{
    $_SESSION['event_message'] = $message;
    header('Location: event_management.php');
    exit;
}

/**
 * Validates and stores an uploaded image.
 * Returns its relative path, or null when no file was selected.
 */
function saveEventPoster(
    array $file,
    string $uploadDir,
    int $maxFileSize,
    array $allowedMimeTypes
): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Poster upload failed.');
    }

    if (!isset($file['tmp_name'], $file['size'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    if ($file['size'] < 1 || $file['size'] > $maxFileSize) {
        throw new RuntimeException('Poster must be no larger than 2 MB.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid upload source.');
    }

    // Check actual MIME type rather than trusting the file extension.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowedMimeTypes[$mime])) {
        throw new RuntimeException(
            'Invalid poster type. Upload JPG, PNG, or WEBP only.'
        );
    }

    // Confirm the file contains valid image data.
    $imageInfo = @getimagesize($file['tmp_name']);

    if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    // Never use the original filename.
    $filename = bin2hex(random_bytes(16))
        . '.' . $allowedMimeTypes[$mime];

    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the poster.');
    }

    return 'uploads/event_posters/' . $filename;
}

function removeEventPoster(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }

    // Only remove files inside our event poster directory.
    $prefix = 'uploads/event_posters/';

    if (strpos($relativePath, $prefix) !== 0) {
        return;
    }

    $filename = substr($relativePath, strlen($prefix));

    if (
        $filename !== '' &&
        basename($filename) === $filename
    ) {
        $path = __DIR__ . '/' . $prefix . $filename;

        if (is_file($path)) {
            @unlink($path);
        }
    }
}

$message = $_SESSION['event_message'] ?? '';
unset($_SESSION['event_message']);

$editEvent = null;

// Handle add, update, and delete requests.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['event_csrf'], $token)
    ) {
        http_response_code(403);
        exit('Invalid security token. Refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';
    $newPoster = null;
    $oldPoster = null;

    try {
        // DELETE EVENT
        if ($action === 'delete') {
            $id = filter_var(
                $_POST['event_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                eventRedirect('Failure: Invalid event ID.');
            }

            $stmt = $conn->prepare(
                'SELECT poster_path FROM events WHERE event_id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $event = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$event) {
                eventRedirect('Failure: Event not found.');
            }

            $oldPoster = $event['poster_path'];

            $stmt = $conn->prepare(
                'DELETE FROM events WHERE event_id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            // Delete the file only after the database deletion succeeds.
            removeEventPoster($oldPoster);

            eventRedirect('Success: Event deleted successfully.');
        }

        if (!in_array($action, ['add', 'update'], true)) {
            eventRedirect('Failure: Invalid action.');
        }

        $id = (int)($_POST['event_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $venue = trim($_POST['venue'] ?? '');

        if (
            $title === '' ||
            mb_strlen($title) > 150 ||
            $description === '' ||
            $venue === '' ||
            mb_strlen($venue) > 200
        ) {
            eventRedirect(
                'Failure: Enter a title, description, and venue. '
                . 'Title and venue must fit their field limits.'
            );
        }

        // Validate and normalize datetime-local input.
        $dateObject = DateTime::createFromFormat(
            '!Y-m-d\TH:i',
            $eventDate
        );

        if (
            !$dateObject ||
            $dateObject->format('Y-m-d\TH:i') !== $eventDate
        ) {
            eventRedirect('Failure: Enter a valid event date and time.');
        }

        $sqlDate = $dateObject->format('Y-m-d H:i:s');

        if ($action === 'add') {
            $newPoster = saveEventPoster(
                $_FILES['poster'] ?? [],
                $uploadDir,
                $maxFileSize,
                $allowedMimeTypes
            );

            $stmt = $conn->prepare(
                'INSERT INTO events
                    (title, description, event_date, venue, poster_path)
                 VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'sssss',
                $title,
                $description,
                $sqlDate,
                $venue,
                $newPoster
            );

            $stmt->execute();
            $stmt->close();

            eventRedirect('Success: Event added successfully.');
        }

        // UPDATE EVENT
        if ($id < 1) {
            eventRedirect('Failure: Invalid event ID.');
        }

        $stmt = $conn->prepare(
            'SELECT poster_path FROM events WHERE event_id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existing) {
            eventRedirect('Failure: Event not found.');
        }

        $oldPoster = $existing['poster_path'];

        $newPoster = saveEventPoster(
            $_FILES['poster'] ?? [],
            $uploadDir,
            $maxFileSize,
            $allowedMimeTypes
        );

        // Keep the old poster if no replacement was uploaded.
        $posterToSave = $newPoster ?? $oldPoster;

        $stmt = $conn->prepare(
            'UPDATE events
             SET title = ?, description = ?, event_date = ?,
                 venue = ?, poster_path = ?
             WHERE event_id = ?'
        );

        $stmt->bind_param(
            'sssssi',
            $title,
            $description,
            $sqlDate,
            $venue,
            $posterToSave,
            $id
        );

        $stmt->execute();
        $stmt->close();

        // Remove the old poster only after the update succeeds.
        if ($newPoster !== null && $oldPoster !== $newPoster) {
            removeEventPoster($oldPoster);
        }

        eventRedirect('Success: Event updated successfully.');

    } catch (Throwable $exception) {
        // Clean up a newly uploaded file if the database operation failed.
        if ($newPoster !== null) {
            removeEventPoster($newPoster);
        }

        error_log('Event management error: ' . $exception->getMessage());

        eventRedirect(
            'Failure: The operation could not be completed. '
            . 'Check the form and try again.'
        );
    }
}

// Load event details into the edit form.
if (isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        eventRedirect('Failure: Invalid event ID.');
    }

    $stmt = $conn->prepare(
        'SELECT event_id, title, description, event_date,
                venue, poster_path
         FROM events
         WHERE event_id = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editEvent) {
        eventRedirect('Failure: Event not found.');
    }
}

// View all events, newest event date first.
$stmt = $conn->prepare(
    'SELECT event_id, title, description, event_date,
            venue, poster_path
     FROM events
     ORDER BY event_date DESC, event_id DESC'
);
$stmt->execute();
$events = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Management | StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .events-page {
            max-width: 1150px;
            margin: 30px auto;
            padding: 20px;
        }
        .events-page input,
        .events-page textarea {
            width: 100%;
            max-width: 650px;
            box-sizing: border-box;
            padding: 10px;
            margin: 6px 0 14px;
        }
        .events-page button {
            padding: 10px 16px;
            cursor: pointer;
        }
        .events-page .notice {
            padding: 12px;
            background: #e8f8ec;
            border-radius: 6px;
        }
        .events-page .table-wrap { overflow-x: auto; }
        .events-page table {
            width: 100%;
            border-collapse: collapse;
        }
        .events-page th,
        .events-page td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        .events-page th { background: #eef2ff; }
        .event-poster {
            width: 110px;
            max-height: 120px;
            object-fit: contain;
        }
        .delete-button { background: #b91c1c; color: white; border: 0; }
        .event-form { margin-bottom: 32px; }
    </style>
</head>
<body>
<header>
    <h1>StudentHub</h1>
    <p>Admin Event Management</p>
</header>

<main class="events-page">
    <p><a href="dashboard.php">← Back to Dashboard</a></p>

    <?php if ($message !== ''): ?>
        <p class="notice" role="status"><?= eventEscape($message) ?></p>
    <?php endif; ?>

    <section class="event-form">
        <h2><?= $editEvent ? 'Update Event' : 'Add New Event' ?></h2>

        <form method="post"
              action="event_management.php"
              enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"
                   value="<?= eventEscape($_SESSION['event_csrf']) ?>">

            <input type="hidden" name="action"
                   value="<?= $editEvent ? 'update' : 'add' ?>">

            <?php if ($editEvent): ?>
                <input type="hidden" name="event_id"
                       value="<?= (int)$editEvent['event_id'] ?>">
            <?php endif; ?>

            <label for="title">Event Title</label>
            <input id="title" name="title" required maxlength="150"
                   value="<?= eventEscape($editEvent['title'] ?? '') ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description"
                      rows="5" required><?= eventEscape(
                          $editEvent['description'] ?? ''
                      ) ?></textarea>

            <label for="event_date">Event Date and Time</label>
            <input type="datetime-local" id="event_date"
                   name="event_date" required
                   value="<?= $editEvent
                       ? eventEscape(date(
                           'Y-m-d\TH:i',
                           strtotime($editEvent['event_date'])
                       ))
                       : '' ?>">

            <label for="venue">Venue</label>
            <input id="venue" name="venue" required maxlength="200"
                   value="<?= eventEscape($editEvent['venue'] ?? '') ?>">

            <?php if (!empty($editEvent['poster_path'])): ?>
                <p>Current poster:</p>
                <img class="event-poster"
                     src="<?= eventEscape($editEvent['poster_path']) ?>"
                     alt="Current event poster">
            <?php endif; ?>

            <p>
                <label for="poster">Event Poster (JPG, PNG, or WEBP; max 2 MB)</label>
                <input type="file" id="poster" name="poster"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                       <?= $editEvent ? '' : '' ?>>
                <?php if ($editEvent): ?>
                    Leave the file field empty to keep the current poster.
                <?php else: ?>
                    A poster is optional.
                <?php endif; ?>
            </p>

            <button type="submit">
                <?= $editEvent ? 'Save Changes' : 'Add Event' ?>
            </button>

            <?php if ($editEvent): ?>
                <a href="event_management.php">Cancel</a>
            <?php endif; ?>
        </form>
    </section>

    <section>
        <h2>All Events</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Poster</th>
                        <th>Title and Description</th>
                        <th>Date and Time</th>
                        <th>Venue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($events->num_rows === 0): ?>
                    <tr>
                        <td colspan="6">No events have been added yet.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($event = $events->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$event['event_id'] ?></td>
                            <td>
                                <?php if ($event['poster_path']): ?>
                                    <img class="event-poster"
                                         src="<?= eventEscape(
                                             $event['poster_path']
                                         ) ?>"
                                         alt="Poster for <?= eventEscape(
                                             $event['title']
                                         ) ?>">
                                <?php else: ?>
                                    No poster
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= eventEscape(
                                    $event['title']
                                ) ?></strong>
                                <p><?= nl2br(eventEscape(
                                    $event['description']
                                )) ?></p>
                            </td>
                            <td><?= eventEscape(
                                date('d M Y, h:i A', strtotime(
                                    $event['event_date']
                                ))
                            ) ?></td>
                            <td><?= eventEscape($event['venue']) ?></td>
                            <td>
                                <p>
                                    <a href="?edit=<?= (int)$event['event_id'] ?>">
                                        Edit
                                    </a>
                                </p>
                                <form method="post"
                                      action="event_management.php"
                                      onsubmit="return confirm('Delete this event and its poster?');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= eventEscape(
                                               $_SESSION['event_csrf']
                                           ) ?>">
                                    <input type="hidden" name="action"
                                           value="delete">
                                    <input type="hidden" name="event_id"
                                           value="<?= (int)$event['event_id'] ?>">
                                    <button class="delete-button" type="submit">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
