<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pro.css">
    <title>Purchase History</title>
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="users.php">Home</a></li>
            <li><a href="users1_display.php">Cart</a></li>
            <li><a href="ter.php">Receipt</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>

    <div class="purchase-history-container">
        <?php
        session_start();  // Start the session to track the user

        // Ensure the user is logged in before accessing the page
        if (!isset($_SESSION['id'])) {
            echo "<p>You must log in to view your purchase history.</p>";
            exit();
        }

        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "inventory";

        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $user_id = $_SESSION['id'];  // Get the user ID from the session

        // Fetch the user's type (e.g., 'Admin', 'Customer')
        $sql_user = "SELECT usertype FROM user WHERE id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();

        if ($result_user->num_rows > 0) {
            $user = $result_user->fetch_assoc();
            $user_type = $user['usertype']; // Get the user type
        } else {
            echo "<p>User not found.</p>";
            exit();
        }

        // Fetch all receipts for the logged-in user
        $sql_receipts = "SELECT * FROM receipts WHERE user_id = ? ORDER BY purchase_date DESC";
        $stmt_receipts = $conn->prepare($sql_receipts);
        $stmt_receipts->bind_param("i", $user_id); // Only fetch receipts for this user
        $stmt_receipts->execute();
        $result_receipts = $stmt_receipts->get_result();

        // Handle the case where no receipts exist
        if ($result_receipts->num_rows > 0) {
            echo "<h2>Your Purchase History</h2>";
            echo "<p>User Type: " . htmlspecialchars($user_type) . "</p>";  // Display user type

            // Loop through each receipt
            while ($receipt = $result_receipts->fetch_assoc()) {
                $receipt_id = $receipt['id'];

                // Fetch items for this receipt
                $sql_items = "SELECT receipt_items.*, products.name FROM receipt_items 
                              INNER JOIN products ON receipt_items.product_id = products.id 
                              WHERE receipt_items.receipt_id = ?";
                $stmt_items = $conn->prepare($sql_items);
                $stmt_items->bind_param("i", $receipt_id);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();

                // Handle items for this receipt
                if ($result_items->num_rows > 0) {
                    // Display "View Receipt" link
                    echo '<a href="report.php?id=' . $receipt['id'] . '" target="_blank" class="receipt-box">View Receipt</a>';
                    echo "<h4>Items Purchased:</h4>";
                    echo "<table border='1'>";
                    echo "<thead><tr><th>Product Name</th><th>Quantity</th><th>Price</th><th>Total Cost</th></tr></thead>";
                    echo "<tbody>";

                    // Loop through each item in the receipt
                    while ($item = $result_items->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                        echo "<td>₱" . number_format($item['price'], 2) . "</td>";
                        echo "<td>₱" . number_format($item['total_cost'], 2) . "</td>";
                        echo "</tr>";
                    }

                    echo "</tbody></table>";
                }

                echo "<hr>"; // Separate different receipts
            }
        } else {
            echo "<p>No purchase history found.</p>";
        }

        // Close database connection
        $conn->close();
        ?>
    </div>
</body>
</html>
