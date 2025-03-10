<?php
session_start(); 

if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'user') {
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

// Function to calculate the total bill
function calculateTotalBill() {
    $total = 0;
    
    // Check if cart is set
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        // Loop through each item in the cart
        foreach ($_SESSION['cart'] as $product_id => $cart_item) {
            // Get the price of the product from the session or the database
            $price = $cart_item['price'];
            $quantity = $cart_item['Stock'];

            // Calculate the total price for this product (price * quantity)
            $total += $price * $quantity;
        }
    }

    return $total; // Return the calculated total
}

if (isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();

        if ($product['Stock'] <= 0) {   
            $_SESSION['error_message'] = "Not enough stock available for {$product['name']}";
            header("Location: Barcode_purchase.php");
            exit();
        } else {
            if (!isset($_SESSION['cart'][$product['id']])) {
                $_SESSION['cart'][$product['id']] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'Stock' => 1
                ];
            } else {
                $_SESSION['cart'][$product['id']]['Stock']++;
            }
        }
    } else {
        $_SESSION['error_message'] = "Product not found!";
        header("Location: Barcode_purchase.php");
        exit();
    }

    $_SESSION['total_bill'] = calculateTotalBill();
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

// Handle quantity update in the cart using POST
if (isset($_POST['quantity'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['Stock'] = $quantity;
        }
    }

    // Update total bill after quantity changes
    $_SESSION['total_bill'] = calculateTotalBill();
    header("Location: Barcode_purchase.php");
    exit();
}

// Handle Purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_product'])) {
    if (!empty($_SESSION['cart'])) {
        $purchase_success = true;
        $total_amount = 0;

        // Start a transaction for database consistency
        $conn->begin_transaction();

        // Insert the new receipt into the receipts table
        $sql_receipt = "INSERT INTO receipts (total_amount) VALUES (0)"; // Initially 0, to be updated later
        if ($conn->query($sql_receipt) === TRUE) {
            $receipt_id = $conn->insert_id; // Get the ID of the new receipt

            foreach ($_SESSION['cart'] as $product_id => $cart_item) {
                $purchase_Stock = $cart_item['Stock']; // Use the quantity from the cart

                // SQL query to fetch the current stock for the product
                $sql = "SELECT id, name, price, Stock FROM products WHERE id = $product_id";
                $result = $conn->query($sql);
                $product = $result->fetch_assoc();

                if ($product['Stock'] >= $purchase_Stock) {
                    // Enough stock, reduce stock by the purchase amount
                    $new_Stock = $product['Stock'] - $purchase_Stock;
                    $total_cost = $product['price'] * $purchase_Stock; // Calculate total cost for this product
                    $total_amount += $total_cost; // Accumulate total cost

                    // SQL query to update the stock
                    $sql_update = "UPDATE products SET Stock = $new_Stock WHERE id = $product_id";
                    if ($conn->query($sql_update) === TRUE) {
                        // Insert the purchase item into the receipt_items table
                        $sql_receipt_item = "INSERT INTO receipt_items (receipt_id, product_id, quantity, price, total_cost) 
                                             VALUES ($receipt_id, $product_id, $purchase_Stock, {$product['price']}, $total_cost)";
                        if (!$conn->query($sql_receipt_item)) {
                            $purchase_success = false;
                            echo "Error logging the purchase item: " . $conn->error;
                            break;
                        }
                    } else {
                        $purchase_success = false;
                        echo "Error purchasing product: " . $conn->error;
                        break;
                    }
                } else {
                    $purchase_success = false;
                    echo "Not enough Stock available for product: " . $product['name'] . ". Only " . $product['Stock'] . " items are available.";
                    break;
                }
            }

            // If purchase is successful, commit the transaction and clear cart
            if ($purchase_success) {
                // Update the total amount for the receipt
                $sql_update_receipt = "UPDATE receipts SET total_amount = $total_amount WHERE id = $receipt_id";
                $conn->query($sql_update_receipt);

                // Commit the transaction
                $conn->commit();

                // Clear cart after purchase
                unset($_SESSION['cart']);
                unset($_SESSION['total_bill']); // Reset total bill after purchase

                // Redirect to the receipt page (ter.php) after purchase
                header("Location: ter.php");
                exit(); // Ensure no further code execution after redirect
            } else {
                $conn->rollback(); // Rollback the transaction if any error occurs
                echo "There was an error completing the purchase. Please try again.";
            }
        } else {
            echo "Error creating receipt: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <h1>Product List</h1>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pro.css">
    <title>Barcode purchase</title>
    <script>
        // Update cart when quantity is changed
        function updateQuantity(product_id) {
            let quantity = document.getElementById('quantity_' + product_id).value;

            // Create a hidden form element to submit the quantity change
            let form = document.createElement('form');
            form.method = 'POST';
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'quantity[' + product_id + ']';
            input.value = quantity;
            form.appendChild(input);

            // Submit the form to update the cart
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</head>
<body>
<nav class="navbar">
    <ul>
        <li><a href="ho.php">Home</a></li>
        <li><a href="userinfo.php">User Info</a></li>
        <li><a href="product_display.php">Product</a></li>
        <li><a href="price_checking.php">Price_Checking</a></li>
        <li><a href="Barcode_purchase.php">Barcode_Purchase</a></li>
        <?php
if ($_SESSION['usertype'] == 'admin') {
    echo '<a href="ter.php?view_receipts=1" class="view-receipts-button">
            <button type="button">View User Receipts</button>
          </a>';
}
?>
        <li><a href="sales.php">Sales</a></li>
        <li><a href="logout.php">Log Out</a></li>
    </ul>
</nav>

<?php
if (isset($_SESSION['error_message'])) {
    echo "<div class='error-message'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Clear the error message after displaying it
}
?>



<form action="" method="POST">
    <label for="barcode">Scan your barcode:</label>
    <input type="text" name="barcode" id="barcode" required>
    <button type="submit">Submit</button> 
</form>

<!-- Your Cart Section -->
<h2>Your Cart</h2>
<?php
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<form action='' method='POST'>";
    echo "<table border='1'>";
    echo "<thead><tr><th>Product Name</th><th>Stock</th><th>Quantity</th><th>Action</th></tr></thead>";
    echo "<tbody>";

    foreach ($_SESSION['cart'] as $product_id => $cart_item) {
        // Get product details
        $sql = "SELECT id, name, price, Stock FROM products WHERE id = $product_id";
        $result = $conn->query($sql);

        if (!$result) {
            die("Query failed: " . $conn->error); // Display error message if the query fails
        }

        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc(); // Fetch the product details from the database

            echo "<tr>";
            echo "<td>" . $product['name'] . "</td>";
            echo "<td>" . $product['Stock'] . "</td>"; // Display stock available for the product
            echo "<td>
                    <input type='number' id='quantity_" . $product_id . "' value='" . $cart_item['Stock'] . "' min='1' max='" . $product['Stock'] . "' onchange='updateQuantity(" . $product_id . ")'>
                  </td>";
            echo "<td>
                    <form action='' method='POST'>
                        <input type='hidden' name='product_id' value='" . $product['id'] . "'>
                        <button type='submit' name='remove_from_cart'>Remove</button>
                    </form>
                  </td>";
            echo "</tr>";
        }
    }

    echo "</tbody></table>";
}

if (isset($_SESSION['total_bill']) && $_SESSION['total_bill'] != 0) {
    echo '<p class="total-bill">Your total bill is: ₱ ' . number_format($_SESSION['total_bill'], 0) . '</p>';
}
?>

<form action="" method="POST">
    <button type="submit" name="purchase_product">Complete Purchase</button>
</form>

</body>
</html>

<?php
// Close database connection
$conn->close();
?>
