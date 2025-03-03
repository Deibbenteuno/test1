
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


</body>
</html>




<?php
session_start();

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

// Fetch all receipts from the database
$sql = "SELECT * FROM receipts ORDER BY purchase_date DESC";
$result = $conn->query($sql);

// Check if there are any receipts
if ($result->num_rows > 0) {
    echo "<h2>Purchase History</h2>";

    // Loop through the receipts
    while ($receipt = $result->fetch_assoc()) {
        echo "<div class='receipt'>";
        echo "<p><strong>Receipt ID:</strong> " . $receipt['id'] . "</p>";
        echo "<p><strong>Total Purchase Amount:</strong> $" . number_format($receipt['total_amount'], 2) . "</p>";
        echo "<p><strong>Date of Purchase:</strong> " . $receipt['purchase_date'] . "</p>";
        echo '<a href="report.php?id='. $receipt['id'] . '" target="_blank" class="receipt-box">RECEIPT</a>';

        // Fetch items related to this receipt
        $receipt_id = $receipt['id'];
        $sql_items = "SELECT * FROM receipt_items WHERE receipt_id = $receipt_id";
        $result_items = $conn->query($sql_items);

        if ($result_items->num_rows > 0) {
            echo "<table border='1'>";
            echo "<thead><tr><th>Product Name</th><th>Quantity</th><th>Price per Item</th><th>Total Cost</th></tr></thead>";
            echo "<tbody>";

            // Loop through each item in the receipt
            while ($item = $result_items->fetch_assoc()) {
                // Fetch product name using product_id
                $product_id = $item['product_id'];
                $sql_product = "SELECT name FROM products WHERE id = $product_id";
                $result_product = $conn->query($sql_product);
                $product = $result_product->fetch_assoc();

                // Display each product in the receipt
                echo "<tr>";
                echo "<td>" . $product['name'] . "</td>";
                echo "<td>" . $item['quantity'] . "</td>";
                echo "<td>$" . number_format($item['price'], 2) . "</td>";
                echo "<td>$" . number_format($item['total_cost'], 2) . "</td>";
                echo "</tr>";
            }

            echo "</tbody></table>";
        } else {
            echo "<p>No items found for this receipt.</p>";
        }

        echo "</div><hr>";
    }
} else {
    echo "<p>No purchase history found.</p>";
}

$conn->close();
?>

