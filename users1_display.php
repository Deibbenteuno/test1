<?php
session_start(); // Start session for cart management

// Reset total bill on page reload or after purchase
if (!isset($_SESSION['total_bill'])) {
    $_SESSION['total_bill'] = 0; // Initialize total bill if not already set
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

$sql = "SELECT * FROM products";
$result = $conn->query($sql);

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pro.css">
    <title>Inventory Management</title>
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


<script>
    window.onload = function() {
        document.getElementById('barcode').focus();
    }
</script>
<!-- Barcode Scanning Form -->
<form action="" method="POST">
    <label for="barcode">Scan your barcode:</label>
    <input type="text" name="barcode" id="barcode" required>
    <button type="submit">Submit</button>
</form>

<!-- Product List and Cart Section -->
<h1>Product List</h1>
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Cart</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . number_format($row['price'], 2) . "</td>";
                echo "<td>" . $row['Stock'] . "</td>";
                echo "<td>
                        <form action='users1.php' method='POST'>
                            <input type='hidden' name='product_id' value='" . $row['id'] . "'>
                            <button type='submit' name='add_to_cart'>Add to Cart</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No products found</td></tr>";
        }
        ?>
    </tbody>
</table>



<!-- Your Cart Section -->
<h2>Your Cart</h2>
<?php
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<table border='1'>";
    echo "<thead><tr><th>Product Name</th><th>Stock</th><th>Quantity</th><th>Action</th></tr></thead>";
    echo "<tbody>";

    foreach ($_SESSION['cart'] as $product_id => $cart_item) {
        // Get product details
        $sql = "SELECT * FROM products WHERE id = $product_id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc(); // Fetch the product details from the database

            echo "<tr>";
            echo "<td>" . $product['name'] . "</td>";
            echo "<td>" . $product['Stock'] . "</td>"; // Display stock available for the product
            echo "<td>
                    <input type='number' class='quantity-input' data-product-id='" . $product['id'] . "' value='" . $cart_item['Stock'] . "' min='1' max='" . $product['Stock'] . "'>
                  </td>";
            echo "<td>
                    <form action='users1.php' method='POST'>
                        <input type='hidden' name='product_id' value='" . $product['id'] . "'>
                        <button type='submit' name='remove_from_cart'>Remove</button>
                    </form>
                  </td>";
            echo "</tr>";
        }
    }
    
    

    echo "</tbody></table>";
    echo "<form action='users1.php' method='POST'><button type='submit' name='purchase_product'>Complete Purchase</button></form>";
} else {
    echo "<p>Your cart is empty.</p>";
}

?>



</body>
</html>
<p>Your total bill is: <?php echo $_SESSION['total_bill']; ?></p>
<?php
// Close database connection
$conn->close();
?>