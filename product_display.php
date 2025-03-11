<?php
session_start();

// Check if the user is logged in and has the correct user type
if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'user') {
    // Redirect to home page if the user is logged in as 'user'
    header("location:index.php");
    exit();
}

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

// Check if the product ID is passed via the URL for deletion
if (isset($_GET['id'])) {
    // Sanitize the product ID to prevent SQL injection
    $product_id = intval($_GET['id']);
    
    // Ensure the product ID is valid
    if ($product_id > 0) {
        // Delete the product from the database
        $sql = "DELETE FROM products WHERE id = $product_id";
        
        if ($conn->query($sql) === TRUE) {
            // Redirect back to the product list after successful deletion
            header("Location: product_display.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

if (isset($_POST['Stock'])) {
    $stock = intval($_POST['Stock']);
    
    // Ensure stock is non-negative
    if ($stock < 0) {
        $stock = 0; // Set stock to 0 if negative value is provided
    }
}

if (isset($_POST['price'])) {
    $price = floatval($_POST['price']);
    
    // Ensure price is non-negative
    if ($price < 0) {
        $price = 0; // Set price to 0 if negative value is provided
    }
}

// Insert a new product after validation (this assumes you're adding a product)
if (isset($_POST['add_product'])) {
    // Sanitize and validate inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $stock = intval($_POST['Stock']);
    $price = floatval($_POST['price']);
    
    // Ensure price is non-negative
    if ($price < 0) {
        $price = 0; // Default to 0 if negative value is given
    }
    
    // Ensure stock is non-negative
    if ($stock < 0) {
        $stock = 0; // Default to 0 if negative value is given
    }
    
    // Perform the insert query
    $sql = "INSERT INTO products (name, description, Stock, price) 
            VALUES ('$name', '$description', '$stock', '$price')";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: product_display.php"); // Redirect after successful insert
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Fetch product data
$sql = "SELECT id, name, description, Stock, price FROM products";
$result = $conn->query($sql);
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
<nav class="navbar">
<?php include_once('header.php') ?>
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
    <input type="number" id="Stock" name="Stock" min="0" required>
    <label for="price">Price:</label>
    <input type="number" id="price" name="price" min="0" step="0.01" required>
    <button type="submit">Add Product</button>
    <input type="file" name="my_image">
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
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".$row['id']."</td>";
                echo "<td>".$row['name']."</td>";
                echo "<td>".$row['description']."</td>";
                echo "<td>".$row['Stock']."</td>";
                echo "<td>".$row['price']."</td>";
                // The delete link with confirmation
                echo "<td><a href='product_display.php?id=".$row['id']."' onclick='return confirm(\"Are you sure you want to delete this product?\")'>Delete</a></td>";
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