<?php
session_start();

// Check if user is logged in and the usertype exists
if (!isset($_SESSION['username']) || !isset($_SESSION['usertype'])) {
    header("Location: index.php?error=You must log in first");
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

// Function to calculate the total bill
function calculateTotalBill() {
    $total = 0;

    // Check if cart is set
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        // Loop through each item in the cart
        foreach ($_SESSION['cart'] as $product_id => $cart_item) {
            // Get the price of the product
            $price = $cart_item['price'];
            $quantity = $cart_item['stock'];

            // Calculate the total price for this product (price * quantity)
            $total += $price * $quantity;
        }
    }

    return $total; // Return the calculated total
}

// Handle Barcode submission
if (isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];

    // Prepare and execute a query to fetch the product based on the barcode (assuming column name is `id`)
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Barcode exists, add to cart
        $product = $result->fetch_assoc();

        // Check if product already exists in the cart
        if (!isset($_SESSION['cart'][$product['id']])) {
            // Add to cart
            $_SESSION['cart'][$product['id']] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'stock' => 1 // Default quantity
            ];
        } else {
            // Update existing product quantity if needed
            $_SESSION['cart'][$product['id']]['stock']++;
        }
    } else {
        echo "<p>Product not found!</p>";
    }

    // Update total bill
    $_SESSION['total_bill'] = calculateTotalBill();

    // Redirect to avoid form resubmission on refresh
    header("Location: Barcode_purchase.php");
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
    header("Location: Barcode_purchase.php");
    exit();
}

// Handle the purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_product'])) {
    if (!empty($_SESSION['cart'])) {
        // Start a transaction for safety
        $conn->begin_transaction();

        try {
            // Insert a new receipt into the database
            $user_id = $_SESSION['id']; // Assuming user_id is stored in the session
            $total_amount = $_SESSION['total_bill'];
            $purchase_date = date('Y-m-d H:i:s'); // Current timestamp

            $sql_receipt = "INSERT INTO receipts (user_id, total_amount, purchase_date) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql_receipt);
            $stmt->bind_param("ids", $user_id, $total_amount, $purchase_date);
            $stmt->execute();

            // Get the last inserted receipt ID
            $receipt_id = $stmt->insert_id;

            // Insert cart items into the receipt_items table and update product stock
            foreach ($_SESSION['cart'] as $product_id => $cart_item) {
                $quantity = $cart_item['stock'];
                $price = $cart_item['price'];
                $total_cost = $quantity * $price;

                // First, check if enough stock is available
                $sql_check_stock = "SELECT stock FROM products WHERE id = ?";
                $stmt_check_stock = $conn->prepare($sql_check_stock);
                $stmt_check_stock->bind_param("i", $product_id);
                $stmt_check_stock->execute();
                $result = $stmt_check_stock->get_result();
                $product = $result->fetch_assoc();

                if ($product['stock'] < $quantity) {
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
                $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stmt_update_stock = $conn->prepare($sql_update_stock);
                $stmt_update_stock->bind_param("ii", $quantity, $product_id);
                $stmt_update_stock->execute();
            }

            // Commit the transaction if everything is successful
            $conn->commit();

            // Clear the cart after successful purchase
            unset($_SESSION['cart']);
            unset($_SESSION['total_bill']);

            echo "<p>Purchase successful!</p>";

            // After successful purchase, redirect to `ter.php` if the user is an admin
            $sql_user = "SELECT usertype FROM user WHERE id = ?";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();

            if ($result_user->num_rows > 0) {
                $user = $result_user->fetch_assoc();
                if ($user['usertype'] == 'admin') {
                    // Redirect to `ter.php` if the user is an admin
                    header("Location: ter.php");
                    exit();
                } else {
                    // Optionally redirect to a different page if the user is not an admin
                    header("Location: user_dashboard.php"); // Change to your desired page
                    exit();
                }
            }

        } catch (Exception $e) {
            // Rollback the transaction in case of an error
            $conn->rollback();
            echo "<p>Error: " . $e->getMessage() . "</p>";
        }
    }
}

// HTML content goes here
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pro.css">
    <title>Barcode Purchase</title>
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="ho.php">Home</a></li>
        <li><a href="userinfo.php">User Info</a></li>
        <li><a href="product_display.php">Product</a></li>
        <li><a href="price_checking.php">Price Checking</a></li>
        <li><a href="Barcode_purchase.php">Barcode Purchase</a></li>
        <li><a href="sales.php">Sales</a></li>
        <li><a href="#">About</a></li>
        <li><a href="logout.php">Log Out</a></li>
    </ul>
</nav>

<form action="" method="POST">
    <label for="barcode">Scan your barcode:</label>
    <input type="text" name="barcode" id="barcode" required>
    <button type="submit">Submit</button>
</form>

<h2>Your Cart</h2>
<?php
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<table border='1'>";
    echo "<thead><tr><th>Product Name</th><th>Stock</th><th>Quantity</th><th>Action</th></tr></thead>";
    echo "<tbody>";

    foreach ($_SESSION['cart'] as $product_id => $cart_item) {
        $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();

            echo "<tr>";
            echo "<td>" . $product['name'] . "</td>";
            echo "<td>" . $product['stock'] . "</td>";
            echo "<td>
                    <input type='number' class='quantity-input' data-product-id='" . $product['id'] . "' value='" . $cart_item['stock'] . "' min='1' max='" . $product['stock'] . "'>
                  </td>";
            echo "<td>
                    <form action='Barcode_purchase.php' method='POST'>
                        <input type='hidden' name='product_id' value='" . $product['id'] . "'>
                        <button type='submit' name='remove_from_cart'>Remove</button>
                    </form>
                  </td>";
            echo "</tr>";
        }
    }

    echo "</tbody></table>";
    echo "<form action='Barcode_purchase.php' method='POST'><button type='submit' name='purchase_product'>Complete Purchase</button></form>";
}

if (isset($_SESSION['total_bill']) && $_SESSION['total_bill'] != 0) {
    echo '<p class="total-bill">Your total bill is: ₱ ' . number_format($_SESSION['total_bill'], 0) . '</p>';
}
?>

</body>
</html>

<?php
// Close the database connection after all actions are complete
$conn->close();
?>
