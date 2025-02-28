<?php
session_start();

if (isset($_SESSION["user"])) {
    header("Location: index.php"); 
    exit;
}

require_once "db_conn.php";

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
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO user (username, password, usertype) VALUES (?, ?, ?)";
        $stmt = mysqli_stmt_init($conn);

        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $usertype);
            mysqli_stmt_execute($stmt);
            // Display success message at the top
            $successMessage = "<div class='alert alert-success'>You are registered successfully.</div>";
        } else {
            die("Something went wrong.");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Registration Form</title>
</head>
<body>
    <div class="container">
        <!-- Display success message at the top if set -->
        <?php
        if (isset($successMessage)) {
            echo $successMessage;
        }
        ?>

        <form action="registration.php" method="post">
            <h2>Registration Form</h2>

            <div class="form-group">
                <input type="text" class="form-control" name="user_name" placeholder="Username">
            </div>

            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password">
            </div>

            <select name="usertype">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>

            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Register" name="submit">
            </div>

            <div class="link">
                <a href="index.php">Login Here</a>
            </div>
        </form>
    </div>
</body>
</html>
