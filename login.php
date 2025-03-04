<?php
session_start();
include "db_conn.php"; 
if (isset($_SESSION['username'])) {
    # code...
    header("location:ho.php");
}

// Function to sanitize input data
function validate($data) {
    $data = trim($data); // Remove extra spaces
    $data = stripslashes($data); // Remove backslashes
    $data = htmlspecialchars($data); // Convert special characters to HTML entities
    return $data;
}

// Check if form is submitted and process the login
if (isset($_POST['uname']) && isset($_POST['password'])) {

    // Sanitize the input data
    $uname = validate($_POST['uname']);
    
    $pass = validate($_POST['password']);

    // Check if inputs are empty
    if (empty($uname)) {
        header("Location: index.php?error=User Name is required");
        exit();
    } else if (empty($pass)) {
        header("Location: index.php?error=Password is required");
        exit();
    }

    // Ensure a successful database connection
    if ($conn === false) {
        die("ERROR: Could not connect. " . mysqli_connect_error());
    }

    // SQL query to check if the username exists
    $sql = "SELECT * FROM user WHERE username='$uname'";

    // Debugging: Print the SQL query to check if it's correct
    echo "SQL: " . $sql . "<br>";

    // Execute the query
    $result = mysqli_query($conn, $sql);

    // Debugging: Check if the query was successful
    if (!$result) {
        die("ERROR: Could not execute query. " . mysqli_error($conn));
    }

    // If the username exists, check the password
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        // Debugging: Print user data and usertype to verify it
        echo "User: " . $row['username'] . "<br>";
        echo "Usertype: " . $row['usertype'] . "<br>";

        // Verify the password with the hashed one stored in the database
        if ($row['username'] === $uname && password_verify($pass, $row['password'])) {

            // Debugging: If password is correct
            echo "Password Verified!<br>";

            // Set session variables upon successful login
            $_SESSION['username'] = $row['username'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['usertype'] = $row['usertype'];

            // Debugging: Check session values
            var_dump($_SESSION); 

            // Redirect based on user type
            if ($row['usertype'] == 'admin') {
                header("Location: ho.php");  
                exit();
            } else if ($row['usertype'] == 'user') {
                header("Location: users.php");  
                exit();
            }
        } else {
            // Invalid password
            header("Location: index.php?error=Invalid password");
            exit();
        }
    } else {
        // Invalid username
        header("Location: index.php?error=Invalid username");
        exit();
    }
}
?>
