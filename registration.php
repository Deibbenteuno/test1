<?php
session_start();

if (isset($_SESSION["user"])) {
    header("Location: index.php"); 
    exit;
}

require_once "db_conn.php";

$successMessage = ""; // Initialize success message variable

if (isset($_POST["submit"])) {
    $username = $_POST["user_name"];
    $password = $_POST["password"];
    $usertype = $_POST["usertype"];
    $errors = array();

    if (empty($username) || empty($password)) {
        array_push($errors, "Username and Password cannot be empty.");
    }

    $sql = "SELECT * FROM user WHERE username = ?";
    $stmt = mysqli_stmt_init($conn);

    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rowCount = mysqli_num_rows($result);

        if ($rowCount > 0) {
            array_push($errors, "Username already exists.");
        }
    }

    if (count($errors) > 0) {
        $_SESSION['errors'] = $errors;
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO user (username, password, usertype) VALUES (?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);

        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $usertype);
            mysqli_stmt_execute($stmt);
            $successMessage = "You are registered successfully.";
            $_SESSION['successMessage'] = $successMessage;
        } else {
            die("Something went wrong.");
        }
    }
}

// Redirect to the registration form page after processing
header("Location: registration_display.php");
exit;
?>
