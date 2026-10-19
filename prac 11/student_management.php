
<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/config/db.php";

// Only administrators may manage all student records.
// Your login system must set $_SESSION["role"].
if (
    !isset($_SESSION["student_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    http_response_code(403);
    exit("Access denied. Please log in as an administrator.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

// CSRF protection token
if (empty($_SESSION["student_csrf"])) {
    $_SESSION["student_csrf"] = bin2hex(random_bytes(32));
}

function e(mixed $value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function redirectWithMessage(string $message): never {
    $_SESSION["student_message"] = $message;
    header("Location: student_management.php");
    exit;
}

$courses = ["btech", "bsc", "bba"];
$genders = ["male", "female", "other"];

$message = $_SESSION["student_message"] ?? "";
unset($_SESSION["student_message"]);

$editStudent = null;

// --------------------------------------------------
// ADD, UPDATE AND DELETE: POST REQUESTS ONLY
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["student_csrf"], $token)
    ) {
        http_response_code(403);
        exit("Request rejected. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";

    try {
        // DELETE
        if ($action === "delete") {
            $id = filter_var(
                $_POST["student_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                redirectWithMessage("Failure: Invalid student ID.");
            }

            // Prevent an administrator from deleting their own account.
            if ($id === (int)$_SESSION["student_id"]) {
                redirectWithMessage(
                    "Failure: You cannot delete your own account."
                );
            }

            $stmt = $conn->prepare(
                "DELETE FROM students WHERE student_id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            redirectWithMessage(
                $deleted === 1
                    ? "Success: Student deleted successfully."
                    : "Failure: Student record not found."
            );
        }

        // READ INPUTS FOR ADD / UPDATE
        if (!in_array($action, ["add", "update"], true)) {
            redirectWithMessage("Failure: Invalid action.");
        }

        $id = (int)($_POST["student_id"] ?? 0);
        $username = trim($_POST["username"] ?? "");
        $name = trim($_POST["full_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $mobile = trim($_POST["mobile"] ?? "");
        $roll = trim($_POST["roll_no"] ?? "");
        $course = trim($_POST["course"] ?? "");
        $year = filter_var(
            $_POST["study_year"] ?? null,
            FILTER_VALIDATE_INT
        );
        $gender = trim($_POST["gender"] ?? "");
        $password = $_POST["password"] ?? "";

        if (
            !preg_match('/^[A-Za-z0-9_]{3,30}$/', $username) ||
            mb_strlen($name) < 2 ||
            mb_strlen($name) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !preg_match('/^[0-9]{10}$/', $mobile) ||
            $roll === "" ||
            mb_strlen($roll) > 30 ||
            !in_array($course, $courses, true) ||
            !in_array($year, [1, 2, 3, 4], true) ||
            !in_array($gender, $genders, true)
        ) {
            redirectWithMessage(
                "Failure: Check all fields. Use a valid email, "
                . "10-digit mobile number, valid course and year."
            );
        }

        if ($action === "add") {
            if (strlen($password) < 8) {
                redirectWithMessage(
                    "Failure: Password must contain at least 8 characters."
                );
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO students
                (username, full_name, email, mobile, roll_no,
                 course, study_year, gender, password_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssssiss",
                $username,
                $name,
                $email,
                $mobile,
                $roll,
                $course,
                $year,
                $gender,
                $passwordHash
            );

            $stmt->execute();
            $stmt->close();

            redirectWithMessage(
                "Success: Student added successfully."
            );
        }

        // UPDATE: password is left unchanged.
        if ($id < 1) {
            redirectWithMessage("Failure: Invalid student ID.");
        }

        $stmt = $conn->prepare(
            "UPDATE students
             SET username = ?, full_name = ?, email = ?,
                 mobile = ?, roll_no = ?, course = ?,
                 study_year = ?, gender = ?
             WHERE student_id = ?"
        );

        $stmt->bind_param(
            "ssssssisi",
            $username,
            $name,
            $email,
            $mobile,
            $roll,
            $course,
            $year,
            $gender,
            $id
        );

        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();

        // Zero affected rows can mean unchanged data.
        $check = $conn->prepare(
            "SELECT student_id FROM students WHERE student_id = ?"
        );
        $check->bind_param("i", $id);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows === 1;
        $check->close();

        redirectWithMessage(
            !$exists
                ? "Failure: Student record not found."
                : ($changed > 0
                    ? "Success: Student updated successfully."
                    : "Success: No changes were needed.")
        );

    } catch (mysqli_sql_exception $exception) {
        // Do not expose database errors to website visitors.
        error_log("Student management SQL error: " . $exception->getMessage());

        if ((int)$exception->getCode() === 1062) {
            redirectWithMessage(
                "Failure: Username, email or roll number already exists."
            );
        }

        redirectWithMessage(
            "Failure: Database operation failed. Please try again."
        );
    }
}

// --------------------------------------------------
// LOAD STUDENT FOR EDITING
// --------------------------------------------------

if (isset($_GET["edit"])) {
    $id = filter_var($_GET["edit"], FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        redirectWithMessage("Failure: Invalid student ID.");
    }

    $stmt = $conn->prepare(
        "SELECT student_id, username, full_name, email,
                mobile, roll_no, course, study_year, gender
         FROM students WHERE student_id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editStudent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editStudent) {
        redirectWithMessage("Failure: Student record not found.");
    }
}

// --------------------------------------------------
// SEARCH AND FILTER
// --------------------------------------------------

$search = trim($_GET["search"] ?? "");
$filterCourse = trim($_GET["course"] ?? "");
$filterYear = $_GET["year"] ?? "";

if (!in_array($filterCourse, array_merge([""], $courses), true)) {
    $filterCourse = "";
}

if (!in_array($filterYear, ["", "1", "2", "3", "4"], true)) {
    $filterYear = "";
}

$searchLike = "%" . $search . "%";
$yearNumber = $filterYear === "" ? 0 : (int)$filterYear;

$stmt = $conn->prepare(
    "SELECT student_id, username, full_name, email, mobile,
            roll_no, course, study_year, gender
     FROM students
     WHERE (
         full_name LIKE ?
         OR email LIKE ?
         OR roll_no LIKE ?
         OR username LIKE ?
     )
     AND (? = '' OR course = ?)
     AND (? = 0 OR study_year = ?)
     ORDER BY student_id DESC"
);

$stmt->bind_param(
    "ssssssii",
    $searchLike,
    $searchLike,
    $searchLike,
    $searchLike,
    $filterCourse,
    $filterCourse,
    $yearNumber,
    $yearNumber
);

$stmt->execute();
$students = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Management | StudentHub</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .management { max-width: 1250px; margin: 30px auto; padding: 20px; }
        .management form { margin-bottom: 18px; }
        .management input, .management select {
            padding: 10px; margin: 5px 4px 10px 0;
            max-width: 100%;
        }
        .management table { width: 100%; border-collapse: collapse; }
        .management th, .management td {
            border: 1px solid #ddd; padding: 10px; text-align: left;
        }
        .management th { background: #eef2ff; }
        .management .table-wrap { overflow-x: auto; }
        .management .message { padding: 12px; background: #e8f8ec;
            border-radius: 8px; margin-bottom: 18px; }
        .management .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .management .danger { background: #b91c1c; }
        .management label { display: inline-block; margin-top: 8px; }
    </style>
</head>
<body>

<header>
    <div>
        <h1>StudentHub</h1>
        <p>Student Management Module</p>
    </div>
</header>

<main class="management">

    <p><a href="dashboard.php">← Back to Dashboard</a></p>

    <section>
        <h2><?= $editStudent ? "Edit Student" : "Add Student" ?></h2>

        <?php if ($message !== ""): ?>
            <p class="message" role="status"><?= e($message) ?></p>
        <?php endif; ?>

        <form method="post" action="student_management.php">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["student_csrf"]) ?>">

            <input type="hidden" name="action"
                   value="<?= $editStudent ? "update" : "add" ?>">

            <?php if ($editStudent): ?>
                <input type="hidden" name="student_id"
                       value="<?= (int)$editStudent["student_id"] ?>">
            <?php endif; ?>

            <label for="username">Username</label><br>
            <input id="username" name="username" required minlength="3"
                   maxlength="30" pattern="[A-Za-z0-9_]{3,30}"
                   value="<?= e($editStudent["username"] ?? "") ?>"><br>

            <label for="full_name">Full Name</label><br>
            <input id="full_name" name="full_name" required maxlength="100"
                   value="<?= e($editStudent["full_name"] ?? "") ?>"><br>

            <label for="email">Email</label><br>
            <input type="email" id="email" name="email" required
                   value="<?= e($editStudent["email"] ?? "") ?>"><br>

            <label for="mobile">Mobile (10 digits)</label><br>
            <input id="mobile" name="mobile" required
                   pattern="[0-9]{10}" maxlength="10"
                   value="<?= e($editStudent["mobile"] ?? "") ?>"><br>

            <label for="roll_no">Roll Number</label><br>
            <input id="roll_no" name="roll_no" required maxlength="30"
                   value="<?= e($editStudent["roll_no"] ?? "") ?>"><br>

            <label for="course">Course</label><br>
            <select id="course" name="course" required>
                <option value="">Select Course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>"
                        <?= ($editStudent["course"] ?? "") === $course
                            ? "selected" : "" ?>>
                        <?= e(strtoupper($course)) ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <label for="study_year">Study Year</label><br>
            <select id="study_year" name="study_year" required>
                <option value="">Select Year</option>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <option value="<?= $i ?>"
                        <?= (int)($editStudent["study_year"] ?? 0) === $i
                            ? "selected" : "" ?>>
                        Year <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select><br>

            <label for="gender">Gender</label><br>
            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <?php foreach ($genders as $gender): ?>
                    <option value="<?= e($gender) ?>"
                        <?= ($editStudent["gender"] ?? "") === $gender
                            ? "selected" : "" ?>>
                        <?= e(ucfirst($gender)) ?>
                    </option>
                <?php endforeach; ?>
            </select><br>

            <?php if (!$editStudent): ?>
                <label for="password">Initial Password (minimum 8 characters)</label><br>
                <input type="password" id="password" name="password"
                       required minlength="8" autocomplete="new-password"><br>
            <?php else: ?>
                <p>Password is unchanged by this form.</p>
            <?php endif; ?>

            <button type="submit">
                <?= $editStudent ? "Save Changes" : "Add Student" ?>
            </button>

            <?php if ($editStudent): ?>
                <a href="student_management.php">Cancel</a>
            <?php endif; ?>
        </form>
    </section>

    <section>
        <h2>Student Records</h2>

        <form method="get" action="student_management.php">
            <label for="search">Search</label>
            <input id="search" name="search"
                   placeholder="Name, email, roll number, username"
                   value="<?= e($search) ?>">

            <label for="filter_course">Course</label>
            <select id="filter_course" name="course">
                <option value="">All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>"
                        <?= $filterCourse === $course ? "selected" : "" ?>>
                        <?= e(strtoupper($course)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="filter_year">Year</label>
            <select id="filter_year" name="year">
                <option value="">All Years</option>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <option value="<?= $i ?>"
                        <?= $filterYear === (string)$i ? "selected" : "" ?>>
                        Year <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit">Search / Filter</button>
            <a href="student_management.php">Clear Filters</a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Roll No.</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students->num_rows === 0): ?>
                    <tr>
                        <td colspan="10">No matching students found.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$student["student_id"] ?></td>
                            <td><?= e($student["full_name"]) ?></td>
                            <td><?= e($student["username"]) ?></td>
                            <td><?= e($student["roll_no"]) ?></td>
                            <td><?= e($student["email"]) ?></td>
                            <td><?= e($student["mobile"]) ?></td>
                            <td><?= e(strtoupper($student["course"])) ?></td>
                            <td><?= (int)$student["study_year"] ?></td>
                            <td><?= e(ucfirst($student["gender"])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?edit=<?= (int)$student["student_id"] ?>">
                                        Edit
                                    </a>

                                    <?php if (
                                        (int)$student["student_id"]
                                        !== (int)$_SESSION["student_id"]
                                    ): ?>
                                        <form method="post"
                                              action="student_management.php"
                                              onsubmit="return confirm('Delete this student record?');">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= e($_SESSION["student_csrf"]) ?>">
                                            <input type="hidden" name="action"
                                                value="delete">
                                            <input type="hidden" name="student_id"
                                                value="<?= (int)$student["student_id"] ?>">
                                            <button type="submit" class="danger">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer>
    <p>© 2026 StudentHub College Portal. All Rights Reserved.</p>
</footer>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
