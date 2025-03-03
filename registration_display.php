<?php
session_start();
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
        if (isset($_SESSION['successMessage'])) {
            echo "<div class='alert alert-success'>" . $_SESSION['successMessage'] . "</div>";
            unset($_SESSION['successMessage']); // Clear the message after showing it
        }

        // Display errors if any
        if (isset($_SESSION['errors'])) {
            foreach ($_SESSION['errors'] as $error) {
                echo "<div class='alert alert-danger'>$error</div>";
            }
            unset($_SESSION['errors']); // Clear errors after displaying
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
