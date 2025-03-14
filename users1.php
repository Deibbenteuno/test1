<?php
session_start(); // Start session for cart management

// Reset total bill on page reload or after purchase
if (!isset($_SESSION['total_bill'])) {
    $_SESSION['total_bill'] = 0; // Initialize total bill if not already set
}

if (isset($_SESSION['usertype'])) {
    # code...
    header("location:users1_display.php");
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

// Add new product if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];

    $sql = "INSERT INTO products (name, Stock, price) VALUES ('$name', $quantity, $price)";
    
    if ($conn->query($sql) === TRUE) {
        echo "New product added successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Add product to cart (simplified, with automatic quantity update)
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];

    // Query to get the price of the product from the database
    $sql = "SELECT price FROM products WHERE id = $product_id";
    $result = $conn->query($sql);
    $product = $result->fetch_assoc();

    // Check if the product is already in the cart
    if (isset($_SESSION['cart'][$product_id])) {
        // If the product is already in the cart, just increase the quantity by 1
        $_SESSION['cart'][$product_id]['Stock'] += 1;
    } else {
        // Add new product to the cart with a quantity of 1
        $_SESSION['cart'][$product_id] = ['Stock' => 1];
    }

    // Update the total bill (total_bill = total_bill + product price)
    $_SESSION['total_bill'] += $product['price'];

    // Optionally provide feedback to the user
    echo "<p>Product added to cart!</p>";

    // Redirect to the users1_display.php page
    header("Location: users1_display.php");
    exit(); // Ensure no further code is executed after the redirection
}

// Handle purchase (simplified)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_product'])) {
    if (!empty($_SESSION['cart'])) {
        $purchase_success = true;
        $total_amount = 0;

        // Start a transaction for database consistency
        $conn->begin_transaction();

        // Insert the new receipt into the receipts table
        $sql_receipt = "INSERT INTO receipts (total_amount) VALUES ($total_amount)";
        if ($conn->query($sql_receipt) === TRUE) {
            $receipt_id = $conn->insert_id; // Get the ID of the new receipt

            foreach ($_SESSION['cart'] as $product_id => $cart_item) {
                $purchase_Stock = $cart_item['Stock']; // Use the quantity from the cart

                // SQL query to fetch the current stock for the product
                $sql = "SELECT * FROM products WHERE id = $product_id";
                $result = $conn->query($sql);
                $product = $result->fetch_assoc();

                if ($product['Stock'] >= $purchase_Stock) {
                    // Enough stock, reduce stock by the purchase amount
                    $new_Stock = $product['Stock'] - $purchase_Stock;
                    $total_cost = $product['price'] * $purchase_Stock; // Calculate total cost
                    $total_amount += $total_cost;

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
                    echo "Not enough stock available for product: " . $product['name'] . ". Only " . $product['Stock'] . " items are available.";
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
    } else {
        echo "<p>Your cart is empty.</p>";
    }
}

// Handle item removal from cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];

    // Get product price from the database
    $sql = "SELECT price FROM products WHERE id = $product_id";
    $result = $conn->query($sql);
    $product = $result->fetch_assoc();

    // Remove the product from the cart
    unset($_SESSION['cart'][$product_id]);

    // Reset total bill to 0 if cart is empty
    if (empty($_SESSION['cart'])) {
        $_SESSION['total_bill'] = 0;
    } else {
        // Update the total bill after removal (subtract the price of the removed item)
        $_SESSION['total_bill'] -= $product['price'];
    }

    // Provide feedback to the user
    echo "<p>Product removed from your cart!</p>";

    // Optionally, redirect the user to refresh the page (optional but good for UX)
    header("Location: users1.php");
    exit();
}

// Handle quantity update in the cart
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = $_POST['quantity'];

    // Ensure quantity is within the available stock
    $sql = "SELECT Stock FROM products WHERE id = $product_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        if ($new_quantity <= $product['Stock'] && $new_quantity > 0) {
            // Update the quantity in the session cart
            $old_quantity = $_SESSION['cart'][$product_id]['Stock'];
            $_SESSION['cart'][$product_id]['Stock'] = $new_quantity;

            // Update total bill based on the change in quantity
            $difference_in_quantity = $new_quantity - $old_quantity;
            $_SESSION['total_bill'] += $difference_in_quantity * $product['price'];

            echo "<p>Quantity updated!</p>";
        } else {
            echo "<p>Invalid quantity. Please check the stock availability.</p>";
        }
    }
}

// Barcode scanning logic (from yourinfo.php)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['barcode'])) {
    $barcode = trim($_POST['barcode']);

    if ($barcode == "endcode") {
        echo "<p>Shopping done</p>";
    } else {
        // Prepare SQL query to fetch product details from the database
        $sql = "SELECT id, name, price, Stock FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);

        // Check if the statement preparation was successful
        if ($stmt === false) {
            echo "<p>Error preparing query: " . $conn->error . "</p>";
        } else {
            // Bind the barcode (now using 'id') to the query
            $stmt->bind_param("i", $barcode); // 'i' for integer, assuming 'id' is an integer
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($id, $name, $price, $stock);

            if ($stmt->num_rows > 0) {
                // Item found, display details
                $stmt->fetch();
                echo "<p>This is: $name, price is: $price</p>";

                // Check if stock is available to add to the cart
                if ($stock > 0) {
                    // Add the product to the cart (session)
                    if (isset($_SESSION['cart'][$id])) {
                        // If already in the cart, increase the quantity
                        $_SESSION['cart'][$id]['Stock'] += 1;
                    } else {
                        // Add new product to the cart
                        $_SESSION['cart'][$id] = ['Stock' => 1];
                    }

                    // Reduce the stock in the database
                    $new_stock = $stock - 1;
                    $sql_update = "UPDATE products SET Stock = $new_stock WHERE id = $id";
                    if ($conn->query($sql_update) === TRUE) {
                        echo "<p>Product added to cart, stock decreased by 1.</p>";
                    } else {
                        echo "<p>Error updating stock: " . $conn->error . "</p>";
                    }
                } else {
                    echo "<p>Sorry, the product is out of stock.</p>";
                }

                // Update the total bill stored in the session
                $_SESSION['total_bill'] += $price;
            } else {
                echo "<p>Item not found with barcode: $barcode</p>";
            }

            $stmt->close();
        }
    }
}

// Fetch products from the database
$sql = "SELECT * FROM products";
$result = $conn->query($sql);
?>

<?php
// Close database connection
$conn->close();
?>
