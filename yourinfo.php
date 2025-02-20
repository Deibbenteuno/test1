<?php
session_start();

if (isset($_SESSION['id']) && isset($_SESSION['user_name'])) {
    // Store user name in a variable
    $user_name = $_SESSION['user_name'];
    $user_id = $_SESSION['id']; // Get the user ID from session

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "inventory";  // Make sure this is your inventory database

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch user info from the database
    $user_query = "SELECT * FROM users WHERE id = $user_id LIMIT 1";
    $user_result = $conn->query($user_query);

    if ($user_result->num_rows > 0) {
        $user_info = $user_result->fetch_assoc();
    } else {
        die("User not found.");
    }
    ?>

    <!DOCTYPE html>
    <html>
        <head>
            <title>Your Information</title>
            <link rel="stylesheet" type="text/css" href="home.css">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h1 {
                    color: #333;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th, td {
                    padding: 8px;
                    text-align: center;
                }
                .greeting {
                    position: absolute;
                    top: 10px;
                    left: 10px;
                    font-size: 18px;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="greeting">Hi, <?php echo htmlspecialchars($user_name); ?>!</div>

            <h1>Your Information</h1>

            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($user_info['id']); ?></td>
                        <td><?php echo htmlspecialchars($user_info['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($user_info['email']); ?></td>
                        <td><?php echo htmlspecialchars($user_info['phone']); ?></td>
                        <td><?php echo htmlspecialchars($user_info['address']); ?></td>
                    </tr>
                </tbody>
            </table>
        </body>
    </html>

    <?php
} else {
    header("Location: index.php");
    exit();
}
?>
