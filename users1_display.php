<?php
session_start(); // Start session for cart management

if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'admin') {
    # code...
    header("location:index.php");
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

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    
    // Check if the cart is already initialized
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Get product details from the database
    $sql = "SELECT * FROM products WHERE id = $product_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();

        // Check if the product is already in the cart
        if (isset($_SESSION['cart'][$product_id])) {
            // Increase quantity if already in cart
            $_SESSION['cart'][$product_id]['Stock']++;
        } else {
            // Add new product to the cart
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'Stock' => 1 // Default quantity 1 when adding to cart
            ];
        }
    }

    // Update total bill
    $_SESSION['total_bill'] = calculateTotalBill();

    // Redirect to avoid form resubmission on refresh
    header("Location: users1_display.php");
    exit();
}

// Handle removal from cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];

    // Remove product from session cart
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }

    // Optionally update the total bill after removal
    $_SESSION['total_bill'] = calculateTotalBill();

    // Redirect to avoid form resubmission on refresh
    header("Location: users1_display.php");
    exit();
}

// Function to calculate the total bill
function calculateTotalBill() {
    global $conn;
    $total = 0;

    // Loop through the cart to calculate the total
    foreach ($_SESSION['cart'] as $product_id => $cart_item) {
        // Get product price from the database
        $sql = "SELECT price FROM products WHERE id = $product_id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $total += $product['price'] * $cart_item['Stock'];
        }
    }

    return $total;
}

// Fetch products for displaying on the page
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
                        <form action='users1_display.php' method='POST'>
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
                    <form action='users1_display.php' method='POST'>
                        <input type='hidden' name='product_id' value='" . $product['id'] . "'>
                        <button type='submit' name='remove_from_cart'>Remove</button>
                    </form>
                  </td>";
            echo "</tr>";
        }
    }

    echo "</tbody></table>";
    echo "<form action='users1_display.php' method='POST'><button type='submit' name='purchase_product'>Complete Purchase</button></form>";
} else {
    echo "<p>Your cart is empty.</p>";
}
?>

<?php
if (isset($_SESSION['total_bill']) && $_SESSION['total_bill'] != 0) {
    echo '<p class="total-bill">Your total bill is: ' . number_format(isset($_SESSION['total_bill']) ? $_SESSION['total_bill'] : 0, 0) . '</p>';
}
?>



<?php
// Close database connection
$conn->close();
?>
</body>
</html>
