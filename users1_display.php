<?php
session_start(); // Start session for cart management

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
        // Check if stock is zero
        if ($product['Stock'] <= 0) {   
            $_SESSION['error_message'] = "Not enough stock available for {$product['name']}";
            header("Location: users1_display.php");
            exit();
                
        } else {
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

// Handle AJAX request for updating cart quantity
if (isset($_POST['update_cart'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = $_POST['new_quantity'];

    // Update the cart with the new quantity
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['Stock'] = $new_quantity;
    }

    // Recalculate total bill
    $_SESSION['total_bill'] = calculateTotalBill();

    // Return updated total bill as JSON response
    echo json_encode(['total_bill' => number_format($_SESSION['total_bill'], 2)]);
    exit();
}

// Handle completing the purchase and clearing the cart
if (isset($_POST['purchase_product'])) {
    // Clear the cart
    unset($_SESSION['cart']);
    
    // Clear the total bill
    unset($_SESSION['total_bill']);

    // Redirect to a purchase confirmation page or home
    header("Location: ter.php");  // Redirect to purchase complete page
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
    <script>
        // Update the cart when the quantity changes
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    let productId = this.getAttribute('data-product-id');
                    let newQuantity = this.value;

                    // Make sure quantity is within valid range
                    if (newQuantity < 1 || newQuantity > this.max) {
                        // alert("Quantity must be between 1 and " + this.max);
                        // return;
                    }

                    // Update the cart with the new quantity via AJAX
                    let formData = new FormData();
                    formData.append('product_id', productId);
                    formData.append('new_quantity', newQuantity);
                    formData.append('update_cart', true);

                    fetch('users1_display.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Update the total bill on the page
                        document.getElementById('total-bill').innerHTML = 'Your total bill is: ' + data.total_bill;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            });
        });
    </script>
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


<?php
if (isset($_SESSION['error_message'])) {
    echo "<div class='error-message'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); 
}
?>
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
    // Complete purchase button
    echo "<form action='users1.php' method='POST'>
            <button type='submit' name='purchase_product'>Complete Purchase</button>
          </form>";
} else {
    echo "<p>Your cart is empty.</p>";
}
?>

<p id="total-bill">
    <?php
    if (isset($_SESSION['total_bill']) && $_SESSION['total_bill'] != 0) {
        echo '<p class="total-bill">Your Total Bill is: ₱' . number_format($_SESSION['total_bill'], 0) . '</p>';
    }
    ?>
</p>

<?php
// Close database connection
$conn->close();
?>
</body>
</html>
