<?php
session_start(); // Start session for cart management

// Initialize the cart if not already initialized
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check user type and redirect if admin
if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'admin') {
    header("location:index.php");
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

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    
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

// Insert a new receipt into the database
if (isset($_POST['purchase_product'])) {
    $user_id = $_SESSION['id']; // Assuming user_id is stored in the session
    $total_amount = $_SESSION['total_bill'];
    $purchase_date = date('Y-m-d H:i:s'); // Current timestamp

    // Insert receipt
    $sql_receipt = "INSERT INTO receipts (user_id, total_amount, purchase_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_receipt);
    if ($stmt === false) {
        die('Error preparing the receipt insert query: ' . $conn->error);
    }
    $stmt->bind_param("ids", $user_id, $total_amount, $purchase_date);
    $stmt->execute();

    // Get the last inserted receipt ID
    $receipt_id = $stmt->insert_id;

    // Insert cart items into the receipt_items table and update product stock
    foreach ($_SESSION['cart'] as $product_id => $cart_item) {
        $quantity = $cart_item['Stock'];
        $price = $cart_item['price'];
        $total_cost = $quantity * $price;

        // First, check if enough stock is available
        $sql_check_stock = "SELECT Stock FROM products WHERE id = ?";
        $stmt_check_stock = $conn->prepare($sql_check_stock);
        $stmt_check_stock->bind_param("i", $product_id);
        $stmt_check_stock->execute();
        $result = $stmt_check_stock->get_result();
        $product = $result->fetch_assoc();

        if ($product['Stock'] < $quantity) {
            // If there's not enough stock, we should stop the purchase and show an error
            echo "<p>Sorry, not enough stock for product: " . $cart_item['name'] . "</p>";
            exit();
        }

        // Insert the product into receipt_items table
        $sql_items = "INSERT INTO receipt_items (receipt_id, product_id, quantity, price, total_cost) VALUES (?, ?, ?, ?, ?)";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("iiidd", $receipt_id, $product_id, $quantity, $price, $total_cost);
        $stmt_items->execute();

        // Reduce product stock in the products table
        $sql_update_stock = "UPDATE products SET Stock = Stock - ? WHERE id = ?";
        $stmt_update_stock = $conn->prepare($sql_update_stock);
        $stmt_update_stock->bind_param("ii", $quantity, $product_id);
        $stmt_update_stock->execute();
    }

    // Clear the cart and reset total bill
    unset($_SESSION['cart']);
    $_SESSION['total_bill'] = 0;

    // Redirect to view the receipt on ter.php
    header("Location: ter.php");
    exit();
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

if (isset($_SESSION['total_bill']) && $_SESSION['total_bill'] != 0) {
    echo '<p class="total-bill">Your Total Bill is: ₱' . number_format($_SESSION['total_bill'], 0) . '</p>';
}
?>

<?php
// Close database connection
$conn->close();
?>
</body>
</html>
