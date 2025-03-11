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

// Function to calculate the total bill
function calculateTotalBill() {
    $total = 0;
    foreach ($_SESSION['cart'] as $product_id => $cart_item) {
        $total += $cart_item['price'] * $cart_item['quantity'];
    }
    return $total;
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    
    // Get product details from the database
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Check stock availability
        if ($product['Stock'] > 0) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity']++;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => 1,
                    'stock' => $product['Stock']
                ];
            }
        }
    }
    $_SESSION['total_bill'] = calculateTotalBill();
    header("Location: users1_display.php");
    exit();
}

// Handle quantity update
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = $_POST['quantity'];
    
    if ($new_quantity > 0 && $new_quantity <= $_SESSION['cart'][$product_id]['stock']) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    }
    $_SESSION['total_bill'] = calculateTotalBill();
    header("Location: users1_display.php");
    exit();
}

// Handle remove from cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    $_SESSION['total_bill'] = calculateTotalBill();
    header("Location: users1_display.php");
    exit();
}

// Handle purchase
if (isset($_POST['purchase_product'])) {
    if (!empty($_SESSION['cart'])) {
        $user_id = $_SESSION['id'];
        $total_amount = $_SESSION['total_bill'];
        $purchase_date = date('Y-m-d H:i:s');
        
        // Insert receipt
        $sql_receipt = "INSERT INTO receipts (user_id, total_amount, purchase_date) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_receipt);
        $stmt->bind_param("ids", $user_id, $total_amount, $purchase_date);
        $stmt->execute();
        $receipt_id = $stmt->insert_id;

        // Insert cart items and update stock
        foreach ($_SESSION['cart'] as $product_id => $cart_item) {
            $quantity = $cart_item['quantity'];
            $price = $cart_item['price'];
            $total_cost = $quantity * $price;

            // Insert into receipt_items
            $sql_items = "INSERT INTO receipt_items (receipt_id, product_id, quantity, price, total_cost) VALUES (?, ?, ?, ?, ?)";
            $stmt_items = $conn->prepare($sql_items);
            $stmt_items->bind_param("iiidd", $receipt_id, $product_id, $quantity, $price, $total_cost);
            $stmt_items->execute();

            // Update product stock
            $sql_update_stock = "UPDATE products SET Stock = Stock - ? WHERE id = ?";
            $stmt_update_stock = $conn->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("ii", $quantity, $product_id);
            $stmt_update_stock->execute();
        }

        // Clear cart and reset total bill
        unset($_SESSION['cart']);
        $_SESSION['total_bill'] = 0;
        
        header("Location: ter.php");
        exit();
    }
}

// Fetch products
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
<?php include_once('header.php'); ?>
</nav>

<h1>Product List</h1>
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['name'] ?></td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td><?= $row['Stock'] ?></td>
                <td>
                    <form action="" method="POST">
                        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="add_to_cart">Add to Cart</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<h2>Your Cart</h2>
<?php if (!empty($_SESSION['cart'])) : ?>
    <table border="1">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Stock</th>
                <th>Quantity</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['cart'] as $product_id => $cart_item) : ?>
                <tr>
                    <td><?= $cart_item['name'] ?></td>
                    <td><?= $cart_item['stock'] ?></td>
                    <td>
                        <form action="" method="POST">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <input type="number" name="quantity" value="<?= $cart_item['quantity'] ?>" min="1" max="<?= $cart_item['stock'] ?>">
                            <button type="submit" name="update_quantity">Update</button>
                        </form>
                    </td>
                    <td>
                        <form action="" method="POST">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <button type="submit" name="remove_from_cart">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="total-bill">Total Bill: ₱<?= number_format($_SESSION['total_bill'], 2) ?></p>

    <!-- Purchase Button -->
    <form action="" method="POST">
        <button type="submit" name="purchase_product">Complete Purchase</button>
    </form>

<?php else : ?>
    <p>Your cart is empty.</p>
<?php endif; ?>


<?php $conn->close(); ?>
</body>
</html>