<ul>
    
    <?php
    
    if ($_SESSION['usertype'] == 'admin') {
        echo '<li><a href="ho.php">Home</a></li>';
        echo '<li><a href="userinfo.php">User Info</a></li>';
        echo '<li><a href="product_display.php">Product</a></li>';
        echo '<li><a href="price_checking.php">Price Checking</a></li>';
        echo '<li><a href="Barcode_purchase.php">Barcode Purchase</a></li>';
        echo '<li><a href="ter.php?view_receipts=1"">View User Receipts</a></li>';
        echo '<li><a href="ter.php">Receipt</a></li>';
        echo '<li><a href="sales.php">Sales</a></li>';
        echo '<li><a href="logout.php">Log Out</a></li>';
    } elseif ($_SESSION['usertype'] == 'user') { 
        echo '<li><a href="users.php">Home</a></li>';
        echo '<li><a href="users1_display.php">Cart</a></li>';
        echo '<li><a href="ter.php">Receipt</a></li>';
        echo '<li><a href="logout.php">Log Out</a></li>';
    }
    ?>
    
</ul>