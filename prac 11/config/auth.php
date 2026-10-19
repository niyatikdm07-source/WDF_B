<?php

const SESSION_TIMEOUT = 1800; // 30 minutes

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            "lifetime" => 0,
            "path" => "/",
            "httponly" => true,
            "secure" => !empty($_SERVER["HTTPS"]),
            "samesite" => "Lax"
        ]);

        session_start();
    }
}

function createLoginSession(array $student): void {
    session_regenerate_id(true);

    $_SESSION["student_id"] = $student["student_id"];
    $_SESSION["student_name"] = $student["full_name"];
    $_SESSION["student_role"] = $student["role"];
    $_SESSION["last_activity"] = time();
}

function redirectByRole(): void {
    if ($_SESSION["student_role"] === "admin") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

function requireLogin(mysqli $conn): void {
    startSecureSession();

    if (isset($_SESSION["student_id"])) {
        if (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();

            header("Location: login.html?error=session-expired");
            exit;
        }

        $_SESSION["last_activity"] = time();
        return;
    }

    header("Location: login.html?error=login-required");
    exit;
}

function createRememberToken(mysqli $conn, int $studentId): void {
    $selector = bin2hex(random_bytes(9));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);
    $expiry = date("Y-m-d H:i:s", time() + (60 * 60 * 24 * 30));

    $stmt = $conn->prepare(
        "INSERT INTO remember_tokens
        (student_id, selector, token_hash, expires_at)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param("isss", $studentId, $selector, $tokenHash, $expiry);
    $stmt->execute();
    $stmt->close();

    setcookie("studenthub_remember", $selector . ":" . $token, [
        "expires" => time() + (60 * 60 * 24 * 30),
        "path" => "/",
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]),
        "samesite" => "Lax"
    ]);
}
?>
<?php

const SESSION_TIMEOUT = 1800; // 30 minutes

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            "lifetime" => 0,
            "path" => "/",
            "httponly" => true,
            "secure" => !empty($_SERVER["HTTPS"]),
            "samesite" => "Lax"
        ]);

        session_start();
    }
}

function createLoginSession(array $student): void {
    session_regenerate_id(true);

    $_SESSION["student_id"] = $student["student_id"];
    $_SESSION["student_name"] = $student["full_name"];
    $_SESSION["student_role"] = $student["role"];
    $_SESSION["last_activity"] = time();
}

function redirectByRole(): void {
    if ($_SESSION["student_role"] === "admin") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

function requireLogin(mysqli $conn): void {
    startSecureSession();

    if (isset($_SESSION["student_id"])) {
        if (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();

            header("Location: login.html?error=session-expired");
            exit;
        }

        $_SESSION["last_activity"] = time();
        return;
    }

    header("Location: login.html?error=login-required");
    exit;
}

function createRememberToken(mysqli $conn, int $studentId): void {
    $selector = bin2hex(random_bytes(9));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);
    $expiry = date("Y-m-d H:i:s", time() + (60 * 60 * 24 * 30));

    $stmt = $conn->prepare(
        "INSERT INTO remember_tokens
        (student_id, selector, token_hash, expires_at)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param("isss", $studentId, $selector, $tokenHash, $expiry);
    $stmt->execute();
    $stmt->close();

    setcookie("studenthub_remember", $selector . ":" . $token, [
        "expires" => time() + (60 * 60 * 24 * 30),
        "path" => "/",
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]),
        "samesite" => "Lax"
    ]);
}
?>