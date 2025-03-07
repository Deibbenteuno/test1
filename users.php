<?php
session_start();

if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'admin') {
    # code...
    header("location:index.php");
}
?>



<?php
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
        <ul>
            <li><a href="users.php">Home</a></li>
            <li><a href="users1_display.php">Cart</a></li>
            <li><a href="ter.php">Receipt</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
</nav> 

<main>
    <?php
    while($row = mysqli_fetch_assoc($all_products)){
    ?>
    
    <div class="card">
        <div class="image">
            <img src="<?php echo $row["image_path"]; ?> ">
        </div>
        <div class="caption">
            
            <!-- <p> &#127775; &#127775; &#127775; &#127775;</p> -->
            </p>
            <p class="name"><?php echo $row["name"];?></p>
            <p class="desciption"><?php echo $row["description"];?></p>
            <p class="price">₱ <?php  echo number_format($row["price" ] , 2);?></p>
        </div>

        <!-- Form for "Add to Cart" -->
        <form action="users1.php" method="POST">
    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>"> <!-- Correct product ID -->
    <button type="submit" name="add_to_cart">Add to Cart</button>
</form>

    </div>
    <?php
    }
    ?>
</main>
</body>
</html>