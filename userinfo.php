<?php

session_start();
// 
// if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] != 'user') {
//    
//     header("Location: login.php"); // Replace with your desired page for non-'user' users
//     exit();
// }
$servername = "localhost";
$username = "root";  
$password = "";      
$dbname = "inventory"; 
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$sql = "SELECT * FROM user WHERE usertype = 'user'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="iba.css">
    <title>User Info</title>
</head>
<body>
    <h1>Inventory</h1>

    <nav class="navbar">
    <?php include_once('header.php') ?>
</nav>
    
    <br>
    <h2>User Info</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>User Name</th>               
                <th>Password</th>

            </tr>
        </thead>
        <tbody>
            <?php
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['username'] . "</td>";                 
                    echo "<td>******</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No users found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php
    
    $conn->close();
    ?>
</body>
</html>