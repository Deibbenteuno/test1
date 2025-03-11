<?php
session_start();

if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'admin') {
    header("location:index.php");
}

require_once 'db_conn.php';
$sql = "SELECT * FROM products";
$all_products = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="uho.css">
    <title>Gaming Store</title>
</head>
<body>
<h1>Tindahan</h1>
<nav class="navbar">
    <?php include_once('header.php'); ?>
</nav> 

<?php
if (isset($_SESSION['error_message'])) {
    echo "<div class='error-message'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Clear the error message after displaying it
}
?>

<main>
    <?php while($row = mysqli_fetch_assoc($all_products)): ?>
    <div class="card">
        <div class="image">
            <img src="<?php echo $row["image_path"]; ?> ">
        </div>
        <div class="caption">
            <p class="name"><?php echo $row["name"];?></p>
            <p class="description"><?php echo $row["description"];?></p>
            <p class="price">₱ <?php echo number_format($row["price"], 2); ?></p>
        </div>

        <?php
        // Check stock availability
        if ($row['Stock'] > 0): ?>
            <form action="users1_display.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>"> 
                <button type="submit" name="add_to_cart">Add to Cart</button>
            </form>
        <?php else: ?>
            <br><p class="out-of-stock">Out of Stock</p>
        <?php endif; ?>

    </div>
    <?php endwhile; ?>
</main>
</body>
</html>
