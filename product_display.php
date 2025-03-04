<?php
session_start();

if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'user') {
    # code...
    header("location:index.php");
}


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" type="text/css" href="pro.css">
</head>
<body>
<h1>Inventory</h1>
<nav class="navbar">
    <ul>
        <li><a href="ho.php">Home</a></li>
        <li><a href="userinfo.php">User Info</a></li>
        <li><a href="product.php">Product</a></li>
        <li><a href="sales.php">Sales</a></li>
        <li><a href="#">About</a></li>
        <li><a href="logout.php">Log Out</a></li>
    </ul>
</nav>
<h2>Product Inventory Management</h2>
<h3>Add New Product</h3>
<form action="product.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="add_product" value="1">
    <label for="name">Product Name:</label>
    <input type="text" id="name" name="name" required>
    <label for="description">Description:</label>
    <textarea id="description" name="description"></textarea>
    <label for="Stock">Stock:</label>
    <input type="number" id="Stock" name="Stock" required> 
    <label for="price">Price:</label>
    <input type="number" id="price" name="price" step="0.01" required>
    <button type="submit">Add Product</button>
    <input type="file" name ="my_image">
</form>
<h4>Product List</h4>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Stock</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "inventory"; // Adjust this to your actual database name

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        

        // Fetch product data
        $sql = "SELECT id, name, description, Stock, price FROM products";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".$row['id']."</td>";
                echo "<td>".$row['name']."</td>";
                echo "<td>".$row['description']."</td>";
                echo "<td>".$row['Stock']."</td>";
                echo "<td>".$row['price']."</td>";
                echo "<td><a href='product.php?id=".$row['id']."' onclick = 'return confirm(\"Are You Sure?\")'>Delete</a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No products found</td></tr>";
        }

        // Close the connection
        $conn->close();
        ?>
    </tbody>
</table>
</body>
</html>
