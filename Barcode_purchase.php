<?php
session_start(); 

if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'user') {
    header("location:index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function calculateTotalBill() {
    $total = 0;
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product_id => $cart_item) {
            $total += $cart_item['price'] * $cart_item['Stock'];
        }
    }
    return $total;
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
    }

    $_SESSION['total_bill'] = calculateTotalBill();
    header("Location: Barcode_purchase.php");
    exit();
}

if (isset($_POST['remove_from_cart']) && !empty($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    $_SESSION['total_bill'] = calculateTotalBill();
    header("Location: Barcode_purchase.php");
    exit();
}

if (isset($_POST['quantity'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['Stock'] = $quantity;
        }
    }
    $_SESSION['total_bill'] = calculateTotalBill();
    header("Location: Barcode_purchase.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_product'])) {
    if (!empty($_SESSION['cart'])) {
        $conn->begin_transaction();
        try {
            $user_id = $_SESSION['id'];
            $total_amount = calculateTotalBill();
            $purchase_date = date('Y-m-d H:i:s');

            $sql_receipt = "INSERT INTO receipts (user_id, total_amount, purchase_date) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql_receipt);
            $stmt->bind_param("ids", $user_id, $total_amount, $purchase_date);
            $stmt->execute();

            $receipt_id = $stmt->insert_id;

            foreach ($_SESSION['cart'] as $product_id => $cart_item) {
                $quantity = $cart_item['Stock'];
                $price = $cart_item['price'];
                $total_cost = $quantity * $price;

                $sql_check_stock = "SELECT Stock FROM products WHERE id = ?";
                $stmt_check_stock = $conn->prepare($sql_check_stock);
                $stmt_check_stock->bind_param("i", $product_id);
                $stmt_check_stock->execute();
                $result = $stmt_check_stock->get_result();
                $product = $result->fetch_assoc();

                if ($product['Stock'] < $quantity) {
                    throw new Exception("Not enough stock for {$cart_item['name']}.");
                }

                $sql_items = "INSERT INTO receipt_items (receipt_id, product_id, quantity, price, total_cost) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmt_items = $conn->prepare($sql_items);
                $stmt_items->bind_param("iiidd", $receipt_id, $product_id, $quantity, $price, $total_cost);
                $stmt_items->execute();

                $sql_update_stock = "UPDATE products SET Stock = Stock - ? WHERE id = ?";
                $stmt_update_stock = $conn->prepare($sql_update_stock);
                $stmt_update_stock->bind_param("ii", $quantity, $product_id);
                $stmt_update_stock->execute();
            }

            $conn->commit();

            unset($_SESSION['cart']);
            unset($_SESSION['total_bill']);

            if ($_SESSION['usertype'] == 'admin') {
                header("Location: ter.php?single_receipt_id=" . $receipt_id);
            } else {
                header("Location: ho.php");
            }
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Transaction failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pro.css">
    <title>Barcode Purchase</title>
    <script>
        function updateQuantity(product_id, price) {
            let quantity = document.getElementById('quantity_' + product_id).value;
            let total = document.getElementById('total_bill');
            let currentTotal = parseFloat(total.innerText.replace("Total Bill: $", ""));
            let oldQuantity = parseInt(document.getElementById('quantity_' + product_id).dataset.oldvalue || 1);
            
            let newTotal = currentTotal + (quantity - oldQuantity) * price;
            total.innerText = "Total Bill: $" + newTotal.toFixed(2);
            document.getElementById('quantity_' + product_id).dataset.oldvalue = quantity;
            
            let form = document.createElement('form');
            form.method = 'POST';
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'quantity[' + product_id + ']';
            input.value = quantity;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</head>
<body>

<nav class="navbar">
    <?php include_once('header.php') ?>
</nav>

<?php if (isset($_SESSION['error_message'])) { ?>
    <div class='error-message'><?= $_SESSION['error_message']; ?></div>
    <?php unset($_SESSION['error_message']); ?>
<?php } ?>

<form action="" method="POST">
    <label for="barcode">Scan your barcode:</label>
    <input type="text" name="barcode" id="barcode" required>
    <button type="submit">Submit</button> 
</form>

<h2>Your Cart</h2>
<?php if (!empty($_SESSION['cart'])) { ?>
    <table border="1">
        <thead>
            <tr><th>Product Name</th><th>Price</th><th>Quantity</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['cart'] as $product_id => $cart_item) { ?>
                <tr>
                    <td><?= $cart_item['name']; ?></td>
                    <td>$<?= number_format($cart_item['price'], 2); ?></td>
                    <td>
                        <input type="number" id="quantity_<?= $product_id; ?>" 
                               value="<?= $cart_item['Stock']; ?>" min="1"
                               data-oldvalue="<?= $cart_item['Stock']; ?>" 
                               onchange="updateQuantity(<?= $product_id; ?>, <?= $cart_item['price']; ?>)">
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?= $product_id; ?>">
                            <button type="submit" name="remove_from_cart">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <h3 id="total_bill">Total Bill: $<?= number_format(calculateTotalBill(), 2); ?></h3>
<?php } ?>

<form method="POST">
    <button type="submit" name="purchase_product">Complete Purchase</button>
</form>

</body>
</html>
<?php $conn->close(); ?>
