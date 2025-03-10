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

$stmt_receipts->execute();
$result_receipts = $stmt_receipts->get_result();
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
    <ul>
            <li><a href="users.php">Home</a></li>
            <li><a href="users1_display.php">Cart</a></li>
            <li><a href="ter.php">Receipt</a></li>
            <li><a href="logout.php">Log Out</a></li>
    </ul>
</nav>

<h2>Purchase History</h2>

<?php if ($result_receipts->num_rows > 0): ?>
    <?php while ($receipt = $result_receipts->fetch_assoc()): ?>
        <div class="receipt-box">
            <p><strong>Receipt ID:</strong> <?php echo htmlspecialchars($receipt['id']); ?></p>
            <p><strong>Total Amount:</strong> ₱<?php echo number_format($receipt['total_amount'], 2); ?></p>
            <p><strong>Purchase Date:</strong> <?php echo $receipt['purchase_date']; ?></p>
            <a href="report.php?id=<?php echo $receipt['id']; ?>" target="_blank">View Receipt</a>
        </div>

        <?php
        // Fetch receipt items
        $receipt_id = $receipt['id'];
        $sql_items = "SELECT receipt_items.*, products.name FROM receipt_items 
                      INNER JOIN products ON receipt_items.product_id = products.id 
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
