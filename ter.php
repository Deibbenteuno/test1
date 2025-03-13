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

// Ensure user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['usertype'])) {
    header("Location: index.php?error=You must log in first");
    exit();
}

$user_id = $_SESSION['id'];
$user_type = $_SESSION['usertype'];
$filter = "own"; // Default to showing own receipts

if (isset($_GET['filter']) && $_GET['filter'] == "all" && $user_type == 'admin') {
    $filter = "all";
}

$result_receipts = null; // Initialize variable to prevent errors

if (isset($_GET['single_receipt_id'])) {
    // Fetch only the selected receipt
    $receipt_id = $_GET['single_receipt_id'];
    $sql_receipts = "SELECT * FROM receipts WHERE id = ? AND user_id = ?;";
    $stmt_receipts = $conn->prepare($sql_receipts);
    $stmt_receipts->bind_param("ii", $receipt_id, $_SESSION['id']);
} else {
    if ($user_type == 'admin' && isset($_GET['view_receipts'])) {
        // Admin can see all user receipts except their own
        $sql_receipts = "SELECT * FROM receipts WHERE user_id != ? ORDER BY purchase_date DESC";
        $stmt_receipts = $conn->prepare($sql_receipts);
        $stmt_receipts->bind_param("i", $user_id);
    } else {
        // Regular users see only their own receipts
        $sql_receipts = "SELECT * FROM receipts WHERE user_id = ? ORDER BY purchase_date DESC";
        $stmt_receipts = $conn->prepare($sql_receipts);
        $stmt_receipts->bind_param("i", $user_id);
    }
}

if (isset($stmt_receipts)) {
    $stmt_receipts->execute();
    $result_receipts = $stmt_receipts->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pro.css">
    <title>Receipts</title>
</head>
<body>
<nav class="navbar">
<?php include_once('header.php'); ?>
</nav>

<h2>Purchase History</h2>

<?php if ($result_receipts && $result_receipts->num_rows > 0): ?>
    <?php while ($receipt = $result_receipts->fetch_assoc()): ?>
        <div class="receipt-box">
            <p><strong>Receipt ID:</strong> <?php echo htmlspecialchars($receipt['id']); ?></p>
            <p><strong>Total Amount:</strong> ₱<?php echo number_format($receipt['total_amount'], 2); ?></p>
            <p><strong>Purchase Date:</strong> <?php echo $receipt['purchase_date']; ?></p>
            <a href="report.php?id=<?php echo $receipt['id']; ?>" target="_blank">View Receipt</a>
            
            
                
                <?php if ($user_type == 'user' || $receipt['user_id'] == $user_id): ?>
                    <a href="?single_receipt_id=<?php echo $receipt['id']; ?>">View Only This Receipt</a>
                    <a href="?filter=all">View All Receipts</a>
                <?php endif; ?>
           
        </div>

        <?php
        // Fetch receipt items
        $receipt_id = $receipt['id'];
        $sql_items = "SELECT receipt_items.product_id, receipt_items.quantity, 
        receipt_items.price, (receipt_items.quantity * receipt_items.price) AS total_cost, products.name 
                FROM receipt_items 
                JOIN products  ON receipt_items.product_id = products.id 
                WHERE receipt_items.receipt_id = ?";

        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $receipt_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        ?>

        <?php if ($result_items->num_rows > 0): ?>
            <h4>Items Purchased:</h4>
            <table border="1">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $result_items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>₱<?php echo number_format($item['total_cost'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <hr>
    <?php endwhile; ?>
<?php else: ?>
    <p>No purchase history found.</p>
<?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
