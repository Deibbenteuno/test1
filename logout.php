<?php

session_start();



if (isset($_SESSION['username'])) {
    // Destroy all session variables
    session_unset();

    // Destroy the session
    session_destroy();

    // Redirect the user to the login page (index.php)
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}

header('Location: index.php');
exit();
?>
