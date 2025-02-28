<?php
// Start the session to manage user login status
session_start();

// Database credentials
$servername = "localhost";
$username = "root";  // Replace with your MySQL username
$password = "";      // Replace with your MySQL password
$dbname = "inventory"; // Name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch user info
$sql = "SELECT * FROM user";
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
        <ul>
            <li><a href="ho.php">Home</a></li>
            <li><a href="userinfo.php">User Info</a></li>
            <li><a href="product.php">Product</a></li>
            <li><a href="sales.php">Sales</a></li>
            <li><a href="#">About</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
    
    <br>
    <h2>User Info</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>User Name</th>
                <th>Usertype</th>
                <th>Password</th>
                
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch and display the user info if available
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . $row['username'] . "</td>";
                    echo "<td>" . $row['usertype'] . "</td>";
                    // Don't display the real password, show a placeholder
                    echo "<td>******</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No users found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php
    // Close the database connection
    $conn->close();
    ?>
</body>
</html>
