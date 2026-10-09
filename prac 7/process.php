<?php


header("Content-Type: text/html; charset=UTF-8");

$dataFolder = __DIR__ . "/data/";


// Create data folder if it doesn't exist
if (!is_dir($dataFolder)) {
    mkdir($dataFolder, 0777, true);
}


// =====================================================
// HELPER FUNCTIONS
// =====================================================


// Sanitize normal text
function cleanInput($value)
{
    return htmlspecialchars(
        trim($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}


// Load JSON file
function loadJson($filename)
{
    if (!file_exists($filename)) {
        return [];
    }

    $content = file_get_contents($filename);

    if (empty($content)) {
        return [];
    }

    $data = json_decode($content, true);

    if (!is_array($data)) {
        return [];
    }

    return $data;
}


// Save data to JSON
function saveJson($filename, $data)
{
    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE
    );

    return file_put_contents(
        $filename,
        $json,
        LOCK_EX
    );
}


// Display message
function showMessage($title, $message, $success = true)
{
    $color = $success ? "#16a34a" : "#dc2626";

    echo "
    <!DOCTYPE html>

    <html lang='en'>

    <head>

        <meta charset='UTF-8'>

        <meta name='viewport'
              content='width=device-width, initial-scale=1.0'>

        <title>StudentHub</title>

        <link rel='stylesheet'
              href='style.css'>

        <style>

            .php-message {
                max-width: 600px;
                margin: 100px auto;
                padding: 40px;
                background: white;
                border-radius: 20px;
                text-align: center;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            }

            .php-message h2 {
                color: $color;
                margin-bottom: 15px;
            }

            .php-message a {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 25px;
                background: #4f46e5;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }

        </style>

    </head>

    <body>

        <div class='php-message'>

            <h2>$title</h2>

            <p>$message</p>

            <a href='home.html'>
                Back to Home
            </a>

        </div>

    </body>

    </html>
    ";

    exit;
}


// =====================================================
// ONLY ACCEPT POST REQUESTS
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    showMessage(
        "Invalid Request",
        "Please submit the form correctly.",
        false
    );
}


// =====================================================
// FIND WHICH FORM WAS SUBMITTED
// =====================================================

$formType = $_POST["form_type"] ?? "";


// =====================================================
// 1. REGISTRATION
// =====================================================

if ($formType === "register") {

    // Get values

    $fullname =
        cleanInput($_POST["fullname"] ?? "");

    $email =
        cleanInput($_POST["email"] ?? "");

    $mobile =
        cleanInput($_POST["mobile"] ?? "");

    $rollno =
        cleanInput($_POST["rollno"] ?? "");

    $course =
        cleanInput($_POST["course"] ?? "");

    $year =
        cleanInput($_POST["year"] ?? "");

    $gender =
        cleanInput($_POST["gender"] ?? "");

    $password =
        $_POST["password"] ?? "";

    $confirmPassword =
        $_POST["confirmPassword"] ?? "";

    $terms =
        isset($_POST["terms"]);


    // -----------------------------
    // Validation
    // -----------------------------

    if (
        empty($fullname) ||
        empty($email) ||
        empty($mobile) ||
        empty($rollno) ||
        empty($course) ||
        empty($year) ||
        empty($password) ||
        empty($confirmPassword)
    ) {

        showMessage(
            "Registration Error",
            "Please fill in all required fields.",
            false
        );
    }


    // Email validation

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        showMessage(
            "Registration Error",
            "Please enter a valid email address.",
            false
        );
    }


    // Mobile validation

    if (!preg_match("/^[0-9]{10}$/", $mobile)) {

        showMessage(
            "Registration Error",
            "Mobile number must contain exactly 10 digits.",
            false
        );
    }


    // Password length

    if (strlen($password) < 6) {

        showMessage(
            "Registration Error",
            "Password must contain at least 6 characters.",
            false
        );
    }


    // Password confirmation

    if ($password !== $confirmPassword) {

        showMessage(
            "Registration Error",
            "Passwords do not match.",
            false
        );
    }


    // Terms

    if (!$terms) {

        showMessage(
            "Registration Error",
            "Please accept the terms and conditions.",
            false
        );
    }


    // -----------------------------
    // Load existing users
    // -----------------------------

    $file =
        $dataFolder . "users.json";

    $users =
        loadJson($file);


    // Check duplicate email

    foreach ($users as $user) {

        if (
            strtolower($user["email"]) ===
            strtolower($email)
        ) {

            showMessage(
                "Registration Error",
                "An account with this email already exists.",
                false
            );
        }
    }


    // -----------------------------
    // Hash password
    // -----------------------------

    $hashedPassword =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    // -----------------------------
    // Create user record
    // -----------------------------

    $newUser = [

        "id" =>
            count($users) + 1,

        "fullname" =>
            $fullname,

        "email" =>
            $email,

        "mobile" =>
            $mobile,

        "rollno" =>
            $rollno,

        "course" =>
            $course,

        "year" =>
            $year,

        "gender" =>
            $gender,

        "password" =>
            $hashedPassword,

        "created_at" =>
            date("Y-m-d H:i:s")
    ];


    $users[] = $newUser;


    // Save

    if (!saveJson($file, $users)) {

        showMessage(
            "Registration Error",
            "Unable to save your registration.",
            false
        );
    }


    // Success

    showMessage(
        "Registration Successful",
        "Your account has been created successfully. You can now login.",
        true
    );
}


// =====================================================
// 2. LOGIN
// =====================================================

elseif ($formType === "login") {

    $username =
        cleanInput($_POST["username"] ?? "");

    $role =
        cleanInput($_POST["role"] ?? "");

    $course =
        cleanInput($_POST["course"] ?? "");

    $password =
        $_POST["password"] ?? "";


    // Validation

    if (
        empty($username) ||
        empty($role) ||
        empty($course) ||
        empty($password)
    ) {

        showMessage(
            "Login Error",
            "Please fill in all login fields.",
            false
        );
    }


    // Load users

    $usersFile =
        $dataFolder . "users.json";

    $users =
        loadJson($usersFile);


    $loggedInUser = null;


    // Find user

    foreach ($users as $user) {

        $emailMatches =
            strtolower($user["email"]) ===
            strtolower($username);

        $nameMatches =
            strtolower($user["fullname"]) ===
            strtolower($username);

        if (
            ($emailMatches || $nameMatches) &&
            $user["course"] === $course &&
            password_verify(
                $password,
                $user["password"]
            )
        ) {

            $loggedInUser = $user;

            break;
        }
    }


    // Login failed

    if ($loggedInUser === null) {

        // Store failed login attempt

        $loginFile =
            $dataFolder . "login_records.json";

        $loginRecords =
            loadJson($loginFile);


        $loginRecords[] = [

            "username" =>
                $username,

            "status" =>
                "failed",

            "time" =>
                date("Y-m-d H:i:s")
        ];


        saveJson(
            $loginFile,
            $loginRecords
        );


        showMessage(
            "Login Failed",
            "Invalid username, course, or password.",
            false
        );
    }


    // Store successful login

    $loginFile =
        $dataFolder . "login_records.json";

    $loginRecords =
        loadJson($loginFile);


    $loginRecords[] = [

        "username" =>
            $username,

        "status" =>
            "success",

        "time" =>
            date("Y-m-d H:i:s")
    ];


    saveJson(
        $loginFile,
        $loginRecords
    );


    // Success

    showMessage(
        "Login Successful",
        "Welcome back, " .
        htmlspecialchars(
            $loggedInUser["fullname"]
        ) .
        "! Login was successful.",
        true
    );
}


// =====================================================
// 3. CONTACT US
// =====================================================

elseif ($formType === "contact") {

    $name =
        cleanInput($_POST["name"] ?? "");

    $email =
        cleanInput($_POST["email"] ?? "");

    $message =
        cleanInput($_POST["message"] ?? "");


    // Validation

    if (
        empty($name) ||
        empty($email) ||
        empty($message)
    ) {

        showMessage(
            "Contact Error",
            "Please fill in all fields.",
            false
        );
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        showMessage(
            "Contact Error",
            "Please enter a valid email address.",
            false
        );
    }


    if (strlen($message) < 5) {

        showMessage(
            "Contact Error",
            "Message is too short.",
            false
        );
    }


    // Load contacts

    $file =
        $dataFolder . "contacts.json";

    $contacts =
        loadJson($file);


    // New contact

    $contacts[] = [

        "id" =>
            count($contacts) + 1,

        "name" =>
            $name,

        "email" =>
            $email,

        "message" =>
            $message,

        "created_at" =>
            date("Y-m-d H:i:s")
    ];


    // Save

    if (!saveJson($file, $contacts)) {

        showMessage(
            "Contact Error",
            "Unable to save your message.",
            false
        );
    }


    showMessage(
        "Message Sent Successfully",
        "Thank you, $name. Your message has been received.",
        true
    );
}


// =====================================================
// 4. FEEDBACK
// =====================================================

elseif ($formType === "feedback") {

    $name =
        cleanInput($_POST["fname"] ?? "");

    $rating =
        cleanInput($_POST["rating"] ?? "");

    $comments =
        cleanInput($_POST["comments"] ?? "");


    // Validation

    if (
        empty($name) ||
        empty($rating)
    ) {

        showMessage(
            "Feedback Error",
            "Please provide your name and rating.",
            false
        );
    }


    // Allowed ratings

    $allowedRatings = [

        "excellent",
        "good",
        "average",
        "poor"
    ];


    if (!in_array(
        strtolower($rating),
        $allowedRatings,
        true
    )) {

        showMessage(
            "Feedback Error",
            "Invalid rating selected.",
            false
        );
    }


    // Load feedback

    $file =
        $dataFolder . "feedback.json";

    $feedback =
        loadJson($file);


    // Add record

    $feedback[] = [

        "id" =>
            count($feedback) + 1,

        "name" =>
            $name,

        "rating" =>
            $rating,

        "comments" =>
            $comments,

        "created_at" =>
            date("Y-m-d H:i:s")
    ];


    // Save

    if (!saveJson($file, $feedback)) {

        showMessage(
            "Feedback Error",
            "Unable to save your feedback.",
            false
        );
    }


    showMessage(
        "Feedback Submitted",
        "Thank you for your valuable feedback.",
        true
    );
}


// =====================================================
// UNKNOWN FORM
// =====================================================

else {

    showMessage(
        "Invalid Form",
        "The submitted form type is not recognized.",
        false
    );
}

?>